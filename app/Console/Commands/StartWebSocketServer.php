<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Ratchet\App;
use App\WebSocket\UploadHandler;

class StartWebSocketServer extends Command
{
    protected $signature = 'websocket:start {--host=127.0.0.1 : Host para escutar} {--port=20001 : Porta do servidor}';
    protected $description = 'Inicia servidor WebSocket para uploads';
    
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
        
        // NOTA: Não validamos a porta aqui porque o Ratchet gerencia isso.
        // O Ratchet pode criar múltiplos sockets (incluindo um secundário na porta 8843).
        // Se houver conflito, o Ratchet lançará uma exceção que será capturada no try/catch abaixo.
        
        // IMPORTANTE: Para Ratchet App, o primeiro parâmetro (httpHost) deve corresponder
        // exatamente ao hostname usado na URL do cliente
        // Se o cliente usa ws://127.0.0.1:40400, httpHost deve ser 127.0.0.1
        $httpHost = '127.0.0.1'; // Sempre usa 127.0.0.1 para corresponder ao cliente
        $bindAddress = $listenHost; // Onde fazer bind (pode ser 0.0.0.0 ou 127.0.0.1)
        
        $this->info("Iniciando servidor WebSocket...");
        $this->info("HTTP Host: {$httpHost} (deve corresponder à URL do cliente)");
        $this->info("Bind Address: {$bindAddress}");
        $this->info("Porta: {$port}");
        $this->info("URL cliente: ws://{$httpHost}:{$port}/upload");
        
        // Validação final antes de criar o App
        if ($port < 1 || $port > 65535) {
            $this->error("ERRO CRÍTICO: Porta inválida detectada: {$port}");
            return 1;
        }
        
        try {
            $handler = new UploadHandler();
            
            // Garante que a porta é um inteiro puro
            $portForRatchet = (int)$port;
            
            // Ratchet App(httpHost, port, address)
            // httpHost = hostname na URL (127.0.0.1)
            // port = porta (deve ser o valor passado)
            // address = onde fazer bind (127.0.0.1 ou 0.0.0.0)
            $app = new App($httpHost, $portForRatchet, $bindAddress);
            $app->route('/upload', $handler, ['*']);
            
            $this->info("✅ Servidor iniciado na porta {$portForRatchet}!");
            $this->info("Pressione Ctrl+C para parar");
            
            $app->run();
        } catch (\Exception $e) {
            $this->error("Erro ao iniciar servidor: " . $e->getMessage());
            $this->error("Porta tentada: {$port}");
            return 1;
        }
        
        return 0;
    }
}
