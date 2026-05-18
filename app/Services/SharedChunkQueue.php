<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Fila compartilhada de chunks usando arquivos temporários
 * - Permite múltiplos workers adicionarem chunks à mesma fila
 * - Usa arquivos temporários para compartilhamento entre processos
 * - Gerenciador central processa e escreve
 */
class SharedChunkQueue
{
    private $fileId;
    private $queueDir;
    private $chunksDir;
    private $lockFile;
    private $queueFile;
    private $maxQueueSize = 128;
    private $resumeQueueSize = 64;
    private $nextExpectedSequence = 0; // Próxima sequence esperada para escrita (garante ordem sequencial)
    
    public function __construct(string $fileId)
    {
        $this->fileId = $fileId;
        $this->queueDir = storage_path('app/streaming/queues');
        
        // Cria diretório se não existir
        if (!is_dir($this->queueDir)) {
            mkdir($this->queueDir, 0755, true);
        }
        
        $hash = md5($fileId);
        $this->queueFile = $this->queueDir . '/' . $hash . '.queue';
        $this->lockFile = $this->queueFile . '.lock';
        $this->chunksDir = $this->queueDir . '/' . $hash . '_chunks';

        if (!is_dir($this->chunksDir)) {
            mkdir($this->chunksDir, 0755, true);
        }
    }
    
    /**
     * Adiciona chunk à fila compartilhada (thread-safe)
     */
    public function enqueue(int $sequence, string $data, string $hash): array
    {
        // Valida hash
        $calculatedHash = hash('sha256', $data);
        if (!hash_equals($calculatedHash, $hash)) {
            throw new \InvalidArgumentException("Hash inválido para chunk #{$sequence}");
        }
        
        // Lock para escrita thread-safe
        $lock = $this->acquireLock();
        try {
            // Lê fila atual
            $queue = $this->readQueue();
            
            // Remove metadado interno da contagem
            $queueSize = count($queue);
            if (isset($queue['_next_expected'])) {
                $queueSize--;
            }
            
            // Verifica backpressure
            if ($queueSize >= $this->maxQueueSize) {
                throw new \RuntimeException(
                    "Fila compartilhada cheia. Tamanho: {$queueSize} / {$this->maxQueueSize}"
                );
            }
            
            $chunkPath = $this->getChunkPath($sequence);
            if (file_put_contents($chunkPath, $data, LOCK_EX) === false) {
                throw new \RuntimeException("Falha ao escrever chunk #{$sequence} em {$chunkPath}");
            }
            
            // Adiciona chunk
            $queue[$sequence] = [
                'path' => $chunkPath,
                'hash' => $hash,
                'size' => strlen($data),
                'received_at' => time(),
                'worker_id' => getmypid() // Identifica qual worker adicionou
            ];
            
            // Salva fila
            $this->writeQueue($queue);
            
            // Remove metadado interno da contagem
            $queueSize = count($queue);
            if (isset($queue['_next_expected'])) {
                $queueSize--;
            }
            
            return [
                'success' => true,
                'sequence' => $sequence,
                'queue_size' => $queueSize,
                'total_memory_gb' => $this->calculateTotalMemory($queue)
            ];
        } finally {
            $this->releaseLock($lock);
        }
    }
    
    /**
     * Pega próximo chunk para escrita (em ordem sequencial)
     * Retorna apenas o chunk com a sequence esperada (garante ordem sequencial)
     */
    public function getNextChunk(): ?array
    {
        $lock = $this->acquireLock();
        try {
            $queue = $this->readQueue();
            
            // Carrega nextExpectedSequence da fila (compartilhado entre processos)
            $this->nextExpectedSequence = $queue['_next_expected'] ?? 0;
            
            // Verifica se o chunk esperado está disponível
            if (empty($queue) || !isset($queue[$this->nextExpectedSequence])) {
                return null;
            }
            
            $chunk = $queue[$this->nextExpectedSequence];
            $data = null;
            if (!empty($chunk['path']) && file_exists($chunk['path'])) {
                $data = file_get_contents($chunk['path']);
            }

            if ($data === false || $data === null) {
                Log::error("Erro ao ler chunk #{$this->nextExpectedSequence} de {$chunk['path']}");
                unset($queue[$this->nextExpectedSequence]);
                $this->writeQueue($queue);
                return null;
            }
            
            return [
                'sequence' => $this->nextExpectedSequence,
                'data' => $data,
                'hash' => $chunk['hash'],
                'size' => $chunk['size']
            ];
        } finally {
            $this->releaseLock($lock);
        }
    }
    
    /**
     * Remove chunk da fila após escrita
     */
    public function removeChunk(int $sequence): void
    {
        $lock = $this->acquireLock();
        try {
            $queue = $this->readQueue();
            
            // Carrega nextExpectedSequence da fila
            $this->nextExpectedSequence = $queue['_next_expected'] ?? 0;
            
            if (isset($queue[$sequence])) {
                $chunkPath = $queue[$sequence]['path'] ?? null;
                unset($queue[$sequence]);
                
                // Atualiza próxima sequence esperada (incrementa sequencialmente)
                if ($sequence === $this->nextExpectedSequence) {
                    $this->nextExpectedSequence++;
                    $queue['_next_expected'] = $this->nextExpectedSequence;
                }
                
                $this->writeQueue($queue);

                if ($chunkPath && file_exists($chunkPath)) {
                    @unlink($chunkPath);
                }
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
            $queue = $this->readQueue();
            
            // Remove metadado interno da contagem
            $queueSize = count($queue);
            if (isset($queue['_next_expected'])) {
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
            $queue = $this->readQueue();
            
            // Remove metadado interno da contagem
            $queueSize = count($queue);
            if (isset($queue['_next_expected'])) {
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
            $queue = $this->readQueue();
            $this->nextExpectedSequence = $queue['_next_expected'] ?? 0;
            
            // Remove metadado interno da contagem
            $queueSize = count($queue);
            if (isset($queue['_next_expected'])) {
                $queueSize--;
            }
            
            $totalMemory = $this->calculateTotalMemory($queue);
            
            return [
                'queue_size' => $queueSize,
                'max_queue_size' => $this->maxQueueSize,
                'resume_queue_size' => $this->resumeQueueSize,
                'can_accept_chunks' => $queueSize < $this->maxQueueSize,
                'can_resume_sending' => $queueSize < $this->resumeQueueSize,
                'total_memory_gb' => round($totalMemory / 1024 / 1024 / 1024, 2),
                'next_expected_sequence' => $this->nextExpectedSequence,
                'file_id' => $this->fileId
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
            $queue = $this->readQueue();
            
            // Remove metadado interno da verificação
            $chunks = $queue;
            unset($chunks['_next_expected']);
            
            return !empty($chunks);
        } finally {
            $this->releaseLock($lock);
        }
    }
    
    /**
     * Limpa a fila
     */
    public function clear(): void
    {
        $lock = $this->acquireLock();
        try {
            if (file_exists($this->queueFile)) {
                unlink($this->queueFile);
            }
            if (is_dir($this->chunksDir)) {
                foreach (glob($this->chunksDir . '/*') as $file) {
                    @unlink($file);
                }
                @rmdir($this->chunksDir);
            }
        } finally {
            $this->releaseLock($lock);
        }
    }
    
    /**
     * Lê fila do arquivo
     */
    private function readQueue(): array
    {
        if (!file_exists($this->queueFile)) {
            return [];
        }
        
        $content = file_get_contents($this->queueFile);
        if (empty($content)) {
            return [];
        }
        
        $data = json_decode($content, true);
        return $data ?: [];
    }
    
    /**
     * Escreve fila no arquivo
     */
    private function writeQueue(array $queue): void
    {
        file_put_contents($this->queueFile, json_encode($queue), LOCK_EX);
    }

    private function getChunkPath(int $sequence): string
    {
        return $this->chunksDir . '/' . $sequence . '.chunk';
    }
    
    /**
     * Calcula memória total
     */
    private function calculateTotalMemory(array $queue): int
    {
        $total = 0;
        foreach ($queue as $key => $chunk) {
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
    
    /**
     * Retorna caminho do arquivo de fila
     */
    public function getQueueFilePath(): string
    {
        return $this->queueFile;
    }
}

