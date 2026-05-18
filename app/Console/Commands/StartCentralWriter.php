<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CentralWriterManager;
use App\Services\SharedChunkQueue;
use App\Services\AsyncWriter;
use Illuminate\Support\Facades\Log;

/**
 * Comando para iniciar gerenciador central de escrita
 * 
 * Este comando processa filas compartilhadas de todos os arquivos
 * e escreve sequencialmente para reduzir concorrência de disco.
 * 
 * Uso:
 * php artisan writer:central
 */
class StartCentralWriter extends Command
{
    protected $signature = 'writer:central';
    protected $description = 'Inicia gerenciador central de escrita (processa filas compartilhadas)';
    
    private $manager;
    
    public function handle()
    {
        $this->info("🚀 Iniciando Gerenciador Central de Escrita...");
        $this->info("Este processo processa filas compartilhadas de todos os arquivos");
        $this->info("Pressione Ctrl+C para parar");
        $this->newLine();
        
        $this->manager = new CentralWriterManager();
        
        // Loop principal
        while (true) {
            try {
                // Processa todas as filas
                $this->manager->processAllQueues();
                
                // Pequeno delay para não sobrecarregar CPU
                usleep(50000); // 50ms
                
            } catch (\Exception $e) {
                Log::error("Erro no gerenciador central", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $this->error("Erro: " . $e->getMessage());
                sleep(1); // Aguarda antes de continuar
            }
        }
        
        return 0;
    }
}






