<?php
/**
 * Script de debug para testar conexão WebSocket
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Teste de Configuração WebSocket ===\n\n";

echo "Configurações:\n";
echo "  Host: " . config('streaming.websocket.host') . "\n";
echo "  Port: " . config('streaming.websocket.port') . "\n";
echo "  Path: " . config('streaming.websocket.path') . "\n";
echo "  Client Host: " . config('streaming.websocket.client_host') . "\n\n";

$clientHost = config('streaming.websocket.client_host') ?: 'localhost';
$port = config('streaming.websocket.port', 8080);
$path = config('streaming.websocket.path', '/upload');

$url = "ws://{$clientHost}:{$port}{$path}";
echo "URL de conexão esperada: {$url}\n\n";

// Testa se porta está aberta
echo "Testando porta {$port}...\n";
$connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 2);
if ($connection) {
    echo "✅ Porta {$port} está aberta\n";
    fclose($connection);
} else {
    echo "❌ Porta {$port} não está acessível: {$errstr} ({$errno})\n";
}

echo "\n=== Fim do Teste ===\n";







