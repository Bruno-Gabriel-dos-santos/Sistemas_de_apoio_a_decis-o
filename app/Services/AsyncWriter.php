<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Escritor assíncrono de chunks
 * - Processa fila de chunks em ordem
 * - Escreve no disco
 * - Remove chunks da fila após escrita
 */
class AsyncWriter
{
    private $fileHandle = null;
    private $filePath = null;
    private $chunkQueue = null;
    private $isWriting = false;
    private $totalWritten = 0;
    
    /**
     * Inicializa o escritor
     */
    public function initialize(string $filePath, ChunkQueue $chunkQueue): void
    {
        $this->filePath = $filePath;
        $this->chunkQueue = $chunkQueue;
        
        // Cria diretório se não existir
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Abre arquivo para escrita (append binary)
        $this->fileHandle = fopen($filePath, 'ab');
        if (!$this->fileHandle) {
            throw new \RuntimeException("Não foi possível abrir arquivo: {$filePath}");
        }
        
    }
    
    /**
     * Processa fila de chunks e escreve no disco
     * Processa chunks em lote para otimizar performance e liberar memória rapidamente
     */
    public function processQueue(): int
    {
        if ($this->isWriting || !$this->fileHandle || !$this->chunkQueue) {
            return 0;
        }
        
        $this->isWriting = true;
        $writtenCount = 0;
        $bytesWritten = 0;
        $maxChunksPerCycle = 32; // Reduzido para processar mais frequentemente
        $flushInterval = 10;
        
        try {
            while ($writtenCount < $maxChunksPerCycle && ($chunk = $this->chunkQueue->getNextChunk())) {
                $sequence = $chunk['sequence'];
                $chunkData = $chunk['data'];
                $chunkSize = $chunk['size'];
                
                // Escreve no disco
                $written = fwrite($this->fileHandle, $chunkData);
                
                if ($written === false) {
                    Log::error("Erro ao escrever chunk #{$sequence}");
                    break;
                }
                
                // Remove chunk da fila IMEDIATAMENTE após escrita (libera memória)
                $this->chunkQueue->removeChunk($sequence);
                
                // Limpa referências imediatamente para ajudar garbage collector
                unset($chunkData, $chunk);
                
                $writtenCount++;
                $bytesWritten += $written;
                $this->totalWritten += $written;
                
                // Flush periódico para otimizar I/O
                if ($writtenCount % $flushInterval === 0) {
                    fflush($this->fileHandle);
                }
            }
            
            // Flush final se escreveu algo
            if ($writtenCount > 0) {
                fflush($this->fileHandle);
            }
        } finally {
            $this->isWriting = false;
        }
        
        return $bytesWritten;
    }
    
    /**
     * Finaliza escrita e fecha arquivo
     */
    public function finalize(): void
    {
        // Processa chunks restantes
        $this->processQueue();
        
        if ($this->fileHandle) {
            fflush($this->fileHandle);
            fclose($this->fileHandle);
            $this->fileHandle = null;
        }
        
    }
    
    /**
     * Retorna estatísticas
     */
    public function getStats(): array
    {
        $fileSize = $this->filePath && file_exists($this->filePath) ? filesize($this->filePath) : 0;
        
        return [
            'file_path' => $this->filePath,
            'file_size' => $fileSize,
            'file_size_gb' => round($fileSize / 1024 / 1024 / 1024, 2),
            'total_written' => $this->totalWritten,
            'is_writing' => $this->isWriting
        ];
    }
    
    /**
     * Retorna caminho do arquivo
     */
    public function getFilePath(): ?string
    {
        return $this->filePath;
    }
}

