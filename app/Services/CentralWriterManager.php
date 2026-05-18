<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Gerenciador central de escrita
 * - Processa filas compartilhadas de todos os arquivos
 * - Escreve sequencialmente para reduzir concorrência de disco
 * - Pode ser executado em processo separado ou no mesmo processo
 */
class CentralWriterManager
{
    private $writers = []; // [fileId => AsyncWriter]
    private $sharedQueues = []; // [fileId => SharedChunkQueue|SharedChunkQueueMemory]
    private $isRunning = false;
    private $processInterval = 0.1; // 100ms entre processamentos
    private $lastProcessTime = 0;
    
    /**
     * Registra um arquivo para processamento central
     * Aceita SharedChunkQueue (disco) ou SharedChunkQueueMemory (memória)
     */
    public function registerFile(string $fileId, string $filePath, $sharedQueue): void
    {
        // Cria escritor
        $writer = new AsyncWriter();
        $dummyQueue = new ChunkQueue(); // Fila dummy (não usada, apenas para inicializar)
        $writer->initialize($filePath, $dummyQueue);
        
        $this->writers[$fileId] = $writer;
        $this->sharedQueues[$fileId] = $sharedQueue;
        
        if (!$this->isRunning) {
            $this->start();
        }
        
        Log::info("Arquivo registrado no gerenciador central", [
            'file_id' => $fileId,
            'file_path' => $filePath
        ]);
    }
    
    /**
     * Remove arquivo do processamento
     */
    public function unregisterFile(string $fileId): void
    {
        if (isset($this->writers[$fileId])) {
            $this->writers[$fileId]->finalize();
            unset($this->writers[$fileId]);
        }
        
        if (isset($this->sharedQueues[$fileId])) {
            $this->sharedQueues[$fileId]->clear();
            unset($this->sharedQueues[$fileId]);
        }
        
        if (empty($this->writers) && $this->isRunning) {
            $this->stop();
        }
    }
    
    /**
     * Inicia processamento
     */
    private function start(): void
    {
        $this->isRunning = true;
        Log::info("Gerenciador central de escrita iniciado");
    }
    
    /**
     * Para processamento
     */
    private function stop(): void
    {
        $this->isRunning = false;
        Log::info("Gerenciador central de escrita parado");
    }
    
    /**
     * Processa todas as filas compartilhadas
     * Deve ser chamado periodicamente
     */
    public function processAllQueues(): void
    {
        if (!$this->isRunning) {
            return;
        }
        
        // Limita frequência de processamento
        $now = microtime(true);
        if (($now - $this->lastProcessTime) < $this->processInterval) {
            return;
        }
        $this->lastProcessTime = $now;
        
        foreach ($this->writers as $fileId => $writer) {
            if (!isset($this->sharedQueues[$fileId])) {
                continue;
            }
            
            $sharedQueue = $this->sharedQueues[$fileId];
            
            try {
                $this->processFileQueue($fileId, $writer, $sharedQueue);
            } catch (\Exception $e) {
                Log::error("Erro ao processar fila compartilhada", [
                    'file_id' => $fileId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Processa fila de um arquivo específico
     * Aceita SharedChunkQueue (disco) ou SharedChunkQueueMemory (memória)
     */
    private function processFileQueue(string $fileId, AsyncWriter $writer, $sharedQueue): int
    {
        $writtenCount = 0;
        $maxChunksPerCycle = 32;
        
        // Pega handle do arquivo do writer (via reflexão)
        $fileHandle = $this->getFileHandle($writer);
        if (!$fileHandle) {
            return 0;
        }
        
        while ($writtenCount < $maxChunksPerCycle && ($chunk = $sharedQueue->getNextChunk())) {
            $sequence = $chunk['sequence'];
            $chunkData = $chunk['data'];
            $chunkSize = $chunk['size'];
            
            // Escreve no disco
            $written = fwrite($fileHandle, $chunkData);
            
            if ($written === false) {
                Log::error("Erro ao escrever chunk #{$sequence} do arquivo {$fileId}");
                break;
            }
            
            // Remove chunk da fila compartilhada
            $sharedQueue->removeChunk($sequence);
            
            // Limpa referências
            unset($chunkData, $chunk);
            
            $writtenCount++;
            
            // Flush periódico
            if ($writtenCount % 10 === 0) {
                fflush($fileHandle);
            }
        }
        
        // Flush final
        if ($writtenCount > 0) {
            fflush($fileHandle);
        }
        
        return $writtenCount;
    }
    
    /**
     * Obtém file handle do writer (via reflexão)
     */
    private function getFileHandle(AsyncWriter $writer)
    {
        try {
            $reflection = new \ReflectionClass($writer);
            $property = $reflection->getProperty('fileHandle');
            $property->setAccessible(true);
            $handle = $property->getValue($writer);
            return is_resource($handle) ? $handle : null;
        } catch (\Exception $e) {
            Log::error("Erro ao obter file handle", ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Retorna estatísticas
     */
    public function getStats(): array
    {
        $stats = [];
        foreach ($this->sharedQueues as $fileId => $queue) {
            $stats[$fileId] = $queue->getStats();
        }
        
        return [
            'is_running' => $this->isRunning,
            'active_files' => count($this->writers),
            'file_stats' => $stats
        ];
    }
}

