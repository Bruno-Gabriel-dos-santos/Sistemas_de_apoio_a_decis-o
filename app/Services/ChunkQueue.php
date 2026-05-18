<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Fila de chunks em memória
 * - Chunks de 32MB
 * - Hash SHA-256 para verificação
 * - Limite de 4GB de memória
 * - Chunks são removidos após escrita
 */
class ChunkQueue
{
    private $queue = []; // [sequence => ['data' => string, 'hash' => string, 'size' => int]]
    private $totalMemory = 0; // Total de memória usada em bytes
    private $maxMemory = 4294967296; // 4GB
    private $chunkSize = 33554432; // 32MB
    private $nextExpectedSequence = 0; // Próxima sequence esperada para escrita (garante ordem)
    private $maxQueueSize = 128; // Limite máximo de chunks na fila (backpressure)
    private $resumeQueueSize = 64; // Tamanho da fila para liberar envio novamente
    
    /**
     * Verifica se pode receber mais chunks (backpressure)
     */
    public function canAcceptChunk(): bool
    {
        return count($this->queue) < $this->maxQueueSize;
    }
    
    /**
     * Verifica se a fila está abaixo do limite para liberar envio
     */
    public function canResumeSending(): bool
    {
        return count($this->queue) < $this->resumeQueueSize;
    }
    
    /**
     * Adiciona chunk à fila
     */
    public function enqueue(int $sequence, string $data, string $hash): array
    {
        $dataSize = strlen($data);
        $currentQueueSize = count($this->queue);
        
        // Backpressure: verifica se fila está cheia
        if ($currentQueueSize >= $this->maxQueueSize) {
            throw new \RuntimeException(
                "Fila de chunks cheia. Tamanho: {$currentQueueSize} / {$this->maxQueueSize}. " .
                "Aguarde até que chunks sejam escritos (limite de liberação: {$this->resumeQueueSize})"
            );
        }
        
        // Verifica se excede limite de memória
        if (($this->totalMemory + $dataSize) > $this->maxMemory) {
            throw new \RuntimeException(
                "Limite de memória atingido. Usado: " . 
                round($this->totalMemory / 1024 / 1024 / 1024, 2) . "GB / " . 
                round($this->maxMemory / 1024 / 1024 / 1024, 2) . "GB"
            );
        }
        
        // Valida hash
        $calculatedHash = hash('sha256', $data);
        if (!hash_equals($calculatedHash, $hash)) {
            throw new \InvalidArgumentException("Hash inválido para chunk #{$sequence}");
        }
        
        $this->queue[$sequence] = [
            'data' => $data,
            'hash' => $hash,
            'size' => $dataSize,
            'received_at' => time()
        ];
        
        $this->totalMemory += $dataSize;
        
        return [
            'success' => true,
            'sequence' => $sequence,
            'queue_size' => count($this->queue),
            'total_memory_gb' => round($this->totalMemory / 1024 / 1024 / 1024, 2)
        ];
    }
    
    /**
     * Pega próximo chunk para escrita (em ordem sequencial)
     * Retorna apenas o chunk com a sequence esperada (garante ordem)
     * IMPORTANTE: Retorna cópia dos dados para evitar múltiplas referências na memória
     */
    public function getNextChunk(): ?array
    {
        if (empty($this->queue) || !isset($this->queue[$this->nextExpectedSequence])) {
            return null;
        }
        
        $chunk = $this->queue[$this->nextExpectedSequence];
        
        // Retorna cópia dos dados para evitar múltiplas referências
        // Isso ajuda o garbage collector a liberar memória mais rapidamente
        return [
            'sequence' => $this->nextExpectedSequence,
            'data' => $chunk['data'], // String é copiada por valor em PHP
            'hash' => $chunk['hash'],
            'size' => $chunk['size']
        ];
    }
    
    /**
     * Remove chunk da fila após escrita
     * Libera memória imediatamente e força garbage collection
     */
    public function removeChunk(int $sequence): void
    {
        if (!isset($this->queue[$sequence])) {
            return;
        }
        
        $chunkSize = $this->queue[$sequence]['size'];
        
        // Remove dados explicitamente antes de unset
        $this->queue[$sequence]['data'] = null;
        $this->queue[$sequence]['hash'] = null;
        unset($this->queue[$sequence]);
        
        // Decrementa memória total
        $this->totalMemory -= $chunkSize;
        
        // Atualiza próxima sequence esperada
        if ($sequence === $this->nextExpectedSequence) {
            $this->nextExpectedSequence++;
        }
        
        // Força garbage collection para chunks grandes (32MB)
        // Isso ajuda a liberar memória imediatamente após escrita
        if ($chunkSize > 16 * 1024 * 1024 && function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
    
    /**
     * Retorna estatísticas da fila incluindo memória real do PHP
     */
    public function getStats(): array
    {
        // Calcula memória real usada na fila
        $realMemoryUsed = 0;
        foreach ($this->queue as $chunk) {
            $realMemoryUsed += $chunk['size'];
        }
        
        // Obtém memória real do PHP (se disponível)
        $phpMemoryUsed = function_exists('memory_get_usage') ? memory_get_usage(true) : 0;
        $phpMemoryPeak = function_exists('memory_get_peak_usage') ? memory_get_peak_usage(true) : 0;
        
        return [
            'queue_size' => count($this->queue),
            'max_queue_size' => $this->maxQueueSize,
            'resume_queue_size' => $this->resumeQueueSize,
            'can_accept_chunks' => $this->canAcceptChunk(),
            'can_resume_sending' => $this->canResumeSending(),
            'total_memory_bytes' => $this->totalMemory,
            'total_memory_gb' => round($this->totalMemory / 1024 / 1024 / 1024, 2),
            'real_memory_used_gb' => round($realMemoryUsed / 1024 / 1024 / 1024, 2),
            'php_memory_used_mb' => round($phpMemoryUsed / 1024 / 1024, 2),
            'php_memory_peak_mb' => round($phpMemoryPeak / 1024 / 1024, 2),
            'max_memory_gb' => round($this->maxMemory / 1024 / 1024 / 1024, 2),
            'memory_usage_percent' => round(($this->totalMemory / $this->maxMemory) * 100, 2),
            'memory_freed' => abs($this->totalMemory - $realMemoryUsed) < 1024 ? 'sim' : 'não'
        ];
    }
    
    /**
     * Verifica se há chunks pendentes
     */
    public function hasPendingChunks(): bool
    {
        return !empty($this->queue);
    }
    
    /**
     * Retorna tamanho da fila
     */
    public function getQueueSize(): int
    {
        return count($this->queue);
    }
    
    /**
     * Limpa a fila e força liberação de memória
     */
    public function clear(): void
    {
        // Limpa dados explicitamente antes de limpar array
        foreach ($this->queue as $sequence => $chunk) {
            $this->queue[$sequence]['data'] = null;
            $this->queue[$sequence]['hash'] = null;
            unset($this->queue[$sequence]);
        }
        
        $this->queue = [];
        $this->totalMemory = 0;
        $this->nextExpectedSequence = 0;
        
        // Força garbage collection após limpeza completa
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
}

