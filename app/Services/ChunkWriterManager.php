<?php

namespace App\Services;

use React\EventLoop\Loop;
use Illuminate\Support\Facades\Log;

/**
 * Gerenciador de escrita assíncrona de chunks
 * - Processa fila de chunks continuamente em background
 * - Não bloqueia o recebimento de novos chunks
 * - Escreve chunks em ordem sequencial
 */
class ChunkWriterManager
{
    private $writers = []; // [sessionId => AsyncWriter]
    private $isRunning = false;
    private $loop = null;
    private $timer = null;
    
    /**
     * Registra um escritor para processamento contínuo
     */
    public function registerWriter(string $sessionId, AsyncWriter $writer): void
    {
        $this->writers[$sessionId] = $writer;
        
        if (!$this->isRunning) {
            $this->start();
        }
    }
    
    /**
     * Remove um escritor do processamento
     */
    public function unregisterWriter(string $sessionId): void
    {
        if (isset($this->writers[$sessionId])) {
            unset($this->writers[$sessionId]);
        }
        
        if (empty($this->writers) && $this->isRunning) {
            $this->stop();
        }
    }
    
    /**
     * Inicia o processamento contínuo da fila
     */
    private function start(): void
    {
        if ($this->isRunning) {
            return;
        }
        
        $this->isRunning = true;
        Log::debug("ChunkWriterManager iniciado");
    }
    
    /**
     * Para o processamento
     */
    private function stop(): void
    {
        if (!$this->isRunning) {
            return;
        }
        
        $this->isRunning = false;
        Log::debug("ChunkWriterManager parado");
    }
    
    /**
     * Processa todas as filas de escrita
     * Deve ser chamado periodicamente (a cada chunk recebido ou em intervalos)
     */
    public function processAllQueues(): void
    {
        if (!$this->isRunning) {
            return;
        }
        
        foreach ($this->writers as $sessionId => $writer) {
            try {
                $writer->processQueue();
            } catch (\Exception $e) {
                Log::error("Erro ao processar fila de escrita", [
                    'session_id' => $sessionId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Força processamento imediato de uma fila específica
     */
    public function processQueue(string $sessionId): void
    {
        if (isset($this->writers[$sessionId])) {
            $this->writers[$sessionId]->processQueue();
        }
    }
    
    /**
     * Retorna estatísticas
     */
    public function getStats(): array
    {
        return [
            'is_running' => $this->isRunning,
            'active_writers' => count($this->writers),
            'sessions' => array_keys($this->writers)
        ];
    }
}

