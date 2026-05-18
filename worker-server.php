<?php

/**
 * Servidor WebSocket usando Workerman
 * Este arquivo deve ser executado diretamente: php worker-server.php start --port=XXXXX
 */

require __DIR__.'/vendor/autoload.php';

use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use App\WebSocket\UploadHandlerWorkerman;

// Carrega o Laravel ANTES de criar os workers
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Pega porta e host dos argumentos
$port = 20001;
$host = '127.0.0.1';

if (isset($argv)) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--port=') === 0) {
            $port = (int) substr($arg, 7);
        }
        if (strpos($arg, '--host=') === 0) {
            $host = substr($arg, 7);
        }
    }
}

$workermanPath = __DIR__.'/storage/workerman';
if (!is_dir($workermanPath)) {
    mkdir($workermanPath, 0775, true);
}

Worker::$pidFile = $workermanPath."/websocket-worker-{$port}.pid";

// Permite receber pacotes grandes (chunks de até 64MB por padrão)
$maxPackageSizeMb = (int) env('WORKERMAN_MAX_PACKAGE_MB', 64);
TcpConnection::$defaultMaxPackageSize = max($maxPackageSizeMb, 8) * 1024 * 1024;

// Cria instância do handler (depois do Laravel estar bootstrapado)
$handler = new UploadHandlerWorkerman();

// Cria worker WebSocket
$worker = new Worker("websocket://{$host}:{$port}");

// Número de processos (workers). 1 = único processo
$worker->count = 1;

// Nome do processo
$worker->name = "WebSocket-Upload-{$port}";

// Callbacks do worker
$worker->onConnect = function(TcpConnection $connection) use ($handler) {
    $handler->onConnect($connection);
};

$worker->onMessage = function(TcpConnection $connection, $message) use ($handler) {
    $handler->onMessage($connection, $message);
};

$worker->onClose = function(TcpConnection $connection) use ($handler) {
    $handler->onClose($connection);
};

$worker->onError = function(TcpConnection $connection, $code, $msg) use ($handler) {
    $handler->onError($connection, $code, $msg);
};

// Handler para quando o worker iniciar
$worker->onWorkerStart = function($worker) use ($port, $host) {
    echo "✅ Worker iniciado na porta {$port}!\n";
    echo "URL: ws://{$host}:{$port}\n";
    echo "Pressione Ctrl+C para parar\n\n";
};

// Inicia o worker (isso deve ser chamado no final do script)
Worker::runAll();

