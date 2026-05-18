<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use App\WebSocket\UploadHandlerWorkerman;

class StartWebSocketServerWorkerman extends Command
{
    protected $signature = 'websocket:start-workerman {--host=127.0.0.1 : Host para escutar} {--port=20001 : Porta do servidor}';
    protected $description = 'Inicia servidor WebSocket para uploads usando Workerman';
    
    public function handle()
    {
        $listenHost = $this->option('host') ?: '127.0.0.1';
        
        // Garante que a porta seja sempre um inteiro válido
        $portOption = $this->option('port');
        if (empty($portOption)) {
            $port = 20001; // Porta padrão
        } else {
            $port = (int)$portOption;
        }
        
        // Validação: porta deve estar no range válido (1-65535)
        if ($port < 1 || $port > 65535) {
            $this->error("Porta inválida: {$port}. Use uma porta entre 1 e 65535.");
            return 1;
        }
        
        $this->info("🚀 Iniciando servidor WebSocket com Workerman...");
        $this->info("Host: {$listenHost}");
        $this->info("Porta: {$port}");
        $this->info("URL cliente: ws://{$listenHost}:{$port}");
        
        // Executa o worker usando o arquivo bootstrap
        // Isso funciona melhor com Workerman do que tentar inicializar via Artisan
        $basePath = base_path();
        $command = "php {$basePath}/worker-server.php start --host={$listenHost} --port={$port}";
        
        $this->info("Executando: {$command}");
        $this->info("Pressione Ctrl+C para parar");
        
        // Executa o comando (bloqueia)
        passthru($command);
        
        return 0;
    }
}

