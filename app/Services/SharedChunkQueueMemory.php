<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Fila compartilhada de chunks usando MEMÓRIA COMPARTILHADA (shmop)
 * - Permite múltiplos workers adicionarem chunks à mesma fila
 * - Usa memória compartilhada do sistema (muito mais rápido que disco)
 * - Gerenciador central processa e escreve
 * 
 * IMPORTANTE: Requer extensão shmop do PHP
 */
class SharedChunkQueueMemory
{
    private $fileId;
    private $queueDir;
    private $lockFile;
    private $metadataFile; // Apenas metadados (sequence, hash, size, shm_id)
    private $maxQueueSize = 128;
    private $resumeQueueSize = 64;
    private $nextExpectedSequence = 0; // Próxima sequence esperada para escrita (garante ordem sequencial)
    private $shmSegmentSize = 4294967296; // 4GB por segmento
    
    public function __construct(string $fileId)
    {
        $this->fileId = $fileId;
        $this->queueDir = storage_path('app/streaming/queues');
        
        // Cria diretório se não existir
        if (!is_dir($this->queueDir)) {
            mkdir($this->queueDir, 0755, true);
        }
        
        $queueHash = md5($fileId);
        $this->metadataFile = $this->queueDir . '/' . $queueHash . '.meta';
        $this->lockFile = $this->queueDir . '/' . $queueHash . '.lock';
        
        // Verifica se shmop está disponível
        if (!function_exists('shmop_open')) {
            throw new \RuntimeException('Extensão shmop não está disponível. Use SharedChunkQueue para versão com disco.');
        }
    }
    
    /**
     * Adiciona chunk à fila compartilhada (thread-safe, em memória)
     */
    public function enqueue(int $sequence, string $data, string $hash): array
    {
        // Valida hash
        $calculatedHash = hash('sha256', $data);
        if (!hash_equals($calculatedHash, $hash)) {
            throw new \InvalidArgumentException("Hash inválido para chunk #{$sequence}");
        }
        
        $dataSize = strlen($data);
        
        // Lock para escrita thread-safe
        $lock = $this->acquireLock();
        try {
            // Lê metadados atuais
            $metadata = $this->readMetadata();
            
            // Remove metadado interno da contagem
            $queueSize = count($metadata);
            if (isset($metadata['_next_expected'])) {
                $queueSize--;
            }
            
            // Verifica backpressure
            if ($queueSize >= $this->maxQueueSize) {
                throw new \RuntimeException(
                    "Fila compartilhada cheia. Tamanho: {$queueSize} / {$this->maxQueueSize}"
                );
            }
            
            // Cria segmento de memória compartilhada para este chunk
            $shmKey = $this->createShmSegment($sequence, $data);
            
            // Adiciona metadados (sem os dados, que ficam na memória)
            $metadata[$sequence] = [
                'hash' => $hash,
                'size' => $dataSize,
                'shm_key' => $shmKey, // Armazena chave, não resource
                'received_at' => time(),
                'worker_id' => getmypid()
            ];
            
            // Salva apenas metadados (pequeno)
            $this->writeMetadata($metadata);
            
            // Remove metadado interno da contagem
            $queueSize = count($metadata);
            if (isset($metadata['_next_expected'])) {
                $queueSize--;
            }
            
            return [
                'success' => true,
                'sequence' => $sequence,
                'queue_size' => $queueSize,
                'total_memory_gb' => $this->calculateTotalMemory($metadata)
            ];
        } finally {
            $this->releaseLock($lock);
        }
    }
    
    /**
     * Pega próximo chunk para escrita (em ordem sequencial, da memória)
     * Retorna apenas o chunk com a sequence esperada (garante ordem sequencial)
     */
    public function getNextChunk(): ?array
    {
        $lock = $this->acquireLock();
        try {
            $metadata = $this->readMetadata();
            
            // Carrega nextExpectedSequence dos metadados (compartilhado entre processos)
            $this->nextExpectedSequence = $metadata['_next_expected'] ?? 0;
            
            // Verifica se o chunk esperado está disponível
            if (empty($metadata) || !isset($metadata[$this->nextExpectedSequence])) {
                return null;
            }
            
            $chunkMeta = $metadata[$this->nextExpectedSequence];
            
            // Lê dados da memória compartilhada
            $data = $this->readShmSegment($chunkMeta['shm_key'], $chunkMeta['size']);
            
            if ($data === false) {
                Log::error("Erro ao ler chunk #{$this->nextExpectedSequence} da memória compartilhada");
                unset($metadata[$this->nextExpectedSequence]);
                $this->writeMetadata($metadata);
                return null;
            }
            
            return [
                'sequence' => $this->nextExpectedSequence,
                'data' => $data,
                'hash' => $chunkMeta['hash'],
                'size' => $chunkMeta['size'],
                'shm_key' => $chunkMeta['shm_key'] // Para limpar depois
            ];
        } finally {
            $this->releaseLock($lock);
        }
    }
    
    /**
     * Remove chunk da fila após escrita (libera memória compartilhada)
     */
    public function removeChunk(int $sequence): void
    {
        $lock = $this->acquireLock();
        try {
            $metadata = $this->readMetadata();
            
            // Carrega nextExpectedSequence dos metadados
            $this->nextExpectedSequence = $metadata['_next_expected'] ?? 0;
            
            if (isset($metadata[$sequence])) {
                // Libera memória compartilhada
                $shmKey = $metadata[$sequence]['shm_key'];
                $this->deleteShmSegment($shmKey);
                
                // Remove metadados
                unset($metadata[$sequence]);
                
                // Atualiza próxima sequence esperada (incrementa sequencialmente)
                if ($sequence === $this->nextExpectedSequence) {
                    $this->nextExpectedSequence++;
                    $metadata['_next_expected'] = $this->nextExpectedSequence;
                }
                
                $this->writeMetadata($metadata);
            }
        } finally {
            $this->releaseLock($lock);
        }
    }
    
    /**
     * Verifica se pode receber mais chunks
     */
    public function canAcceptChunk(): bool
    {
        $lock = $this->acquireLock();
        try {
            $metadata = $this->readMetadata();
            
            // Remove metadado interno da contagem
            $queueSize = count($metadata);
            if (isset($metadata['_next_expected'])) {
                $queueSize--;
            }
            
            return $queueSize < $this->maxQueueSize;
        } finally {
            $this->releaseLock($lock);
        }
    }
    
    /**
     * Verifica se pode liberar envio
     */
    public function canResumeSending(): bool
    {
        $lock = $this->acquireLock();
        try {
            $metadata = $this->readMetadata();
            
            // Remove metadado interno da contagem
            $queueSize = count($metadata);
            if (isset($metadata['_next_expected'])) {
                $queueSize--;
            }
            
            return $queueSize < $this->resumeQueueSize;
        } finally {
            $this->releaseLock($lock);
        }
    }
    
    /**
     * Retorna estatísticas
     */
    public function getStats(): array
    {
        $lock = $this->acquireLock();
        try {
            $metadata = $this->readMetadata();
            $this->nextExpectedSequence = $metadata['_next_expected'] ?? 0;
            
            // Remove metadado interno da contagem
            $queueSize = count($metadata);
            if (isset($metadata['_next_expected'])) {
                $queueSize--;
            }
            
            $totalMemory = $this->calculateTotalMemory($metadata);
            
            return [
                'queue_size' => $queueSize,
                'max_queue_size' => $this->maxQueueSize,
                'resume_queue_size' => $this->resumeQueueSize,
                'can_accept_chunks' => $queueSize < $this->maxQueueSize,
                'can_resume_sending' => $queueSize < $this->resumeQueueSize,
                'total_memory_gb' => round($totalMemory / 1024 / 1024 / 1024, 2),
                'next_expected_sequence' => $this->nextExpectedSequence,
                'file_id' => $this->fileId,
                'storage_type' => 'memory_shared'
            ];
        } finally {
            $this->releaseLock($lock);
        }
    }
    
    /**
     * Verifica se há chunks pendentes
     */
    public function hasPendingChunks(): bool
    {
        $lock = $this->acquireLock();
        try {
            $metadata = $this->readMetadata();
            $this->nextExpectedSequence = $metadata['_next_expected'] ?? 0;
            
            // Remove metadado interno da verificação
            $chunks = $metadata;
            unset($chunks['_next_expected']);
            
            return !empty($chunks);
        } finally {
            $this->releaseLock($lock);
        }
    }
    
    /**
     * Limpa a fila e libera toda memória compartilhada
     */
    public function clear(): void
    {
        $lock = $this->acquireLock();
        try {
            $metadata = $this->readMetadata();
            
            // Libera todos os segmentos de memória
            foreach ($metadata as $key => $chunkMeta) {
                // Ignora metadado interno
                if ($key === '_next_expected') {
                    continue;
                }
                if (isset($chunkMeta['shm_key'])) {
                    $this->deleteShmSegment($chunkMeta['shm_key']);
                }
            }
            
            // Remove arquivo de metadados
            if (file_exists($this->metadataFile)) {
                unlink($this->metadataFile);
            }
            
            // Reseta nextExpectedSequence
            $this->nextExpectedSequence = 0;
        } finally {
            $this->releaseLock($lock);
        }
    }
    
    /**
     * Cria segmento de memória compartilhada para um chunk
     * Retorna a chave (key) do segmento, não o resource
     */
    private function createShmSegment(int $sequence, string $data): int
    {
        $dataSize = strlen($data);
        
        // Gera ID único baseado no fileId e sequence
        $shmKey = crc32($this->fileId . '_' . $sequence);
        
        // Tenta criar segmento (se já existir, tenta abrir)
        $shmId = @shmop_open($shmKey, "c", 0644, $dataSize);
        
        // Se falhou, tenta abrir existente e deletar
        if (!$shmId) {
            $shmId = @shmop_open($shmKey, "w", 0, 0);
            if ($shmId) {
                // Deleta existente e cria novo
                shmop_delete($shmId);
                shmop_close($shmId);
                $shmId = shmop_open($shmKey, "c", 0644, $dataSize);
            }
        }
        
        if (!$shmId) {
            throw new \RuntimeException("Não foi possível criar segmento de memória compartilhada para chunk #{$sequence} (key: {$shmKey})");
        }
        
        // Escreve dados na memória compartilhada
        $written = shmop_write($shmId, $data, 0);
        
        if ($written !== $dataSize) {
            shmop_delete($shmId);
            shmop_close($shmId);
            throw new \RuntimeException("Erro ao escrever chunk #{$sequence} na memória compartilhada");
        }
        
        // Fecha resource (dados já estão na memória compartilhada)
        shmop_close($shmId);
        
        // Retorna chave (não resource) para poder reabrir depois
        return $shmKey;
    }
    
    /**
     * Lê dados de um segmento de memória compartilhada
     */
    private function readShmSegment(int $shmKey, int $size): string|false
    {
        $shmId = @shmop_open($shmKey, "a", 0, 0);
        if (!$shmId) {
            return false;
        }
        
        $data = shmop_read($shmId, 0, $size);
        shmop_close($shmId);
        
        return $data !== false ? $data : false;
    }
    
    /**
     * Deleta segmento de memória compartilhada
     */
    private function deleteShmSegment(int $shmKey): void
    {
        if ($shmKey) {
            try {
                $shmId = @shmop_open($shmKey, "w", 0, 0);
                if ($shmId) {
                    shmop_delete($shmId);
                    shmop_close($shmId);
                }
            } catch (\Exception $e) {
                Log::warning("Erro ao deletar segmento de memória compartilhada", [
                    'shm_key' => $shmKey,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Lê metadados do arquivo (apenas referências, não os dados)
     */
    private function readMetadata(): array
    {
        if (!file_exists($this->metadataFile)) {
            return [];
        }
        
        $content = file_get_contents($this->metadataFile);
        if (empty($content)) {
            return [];
        }
        
        $data = json_decode($content, true);
        return $data ?: [];
    }
    
    /**
     * Escreve metadados no arquivo (apenas referências, não os dados)
     */
    private function writeMetadata(array $metadata): void
    {
        file_put_contents($this->metadataFile, json_encode($metadata), LOCK_EX);
    }
    
    /**
     * Calcula memória total
     */
    private function calculateTotalMemory(array $metadata): int
    {
        $total = 0;
        foreach ($metadata as $key => $chunk) {
            // Ignora metadado interno
            if ($key === '_next_expected') {
                continue;
            }
            $total += $chunk['size'];
        }
        return $total;
    }
    
    /**
     * Adquire lock para operações thread-safe
     */
    private function acquireLock()
    {
        $lock = fopen($this->lockFile, 'c');
        $attempts = 0;
        $maxAttempts = 100; // 10 segundos
        
        while (!flock($lock, LOCK_EX | LOCK_NB)) {
            $attempts++;
            if ($attempts >= $maxAttempts) {
                fclose($lock);
                throw new \RuntimeException("Não foi possível adquirir lock após {$maxAttempts} tentativas");
            }
            usleep(100000); // 100ms
        }
        
        return $lock;
    }
    
    /**
     * Libera lock
     */
    private function releaseLock($lock): void
    {
        if ($lock) {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}

