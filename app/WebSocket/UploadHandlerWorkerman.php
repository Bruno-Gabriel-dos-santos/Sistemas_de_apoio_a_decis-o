<?php

namespace App\WebSocket;

use App\Services\ChunkQueue;
use App\Services\AsyncWriter;
use App\Services\ChunkWriterManager;
use App\Services\SharedChunkQueue;
use App\Services\SharedChunkQueueMemory;
use App\Services\CentralWriterManager;
use App\Services\SharedFileRegistry;
use App\Services\SharedWriterProxy;
use App\Services\UploadTokenService;
use App\Services\Streaming\ArquivoStreamingService;
use App\Services\Streaming\CodigoStreamingService;
use App\Services\Streaming\SistemaStreamingService;
use App\Services\Streaming\PaginaStreamingService;
use App\Services\Streaming\LivroStreamingService;
use App\Services\Streaming\PublicDiskStreamingService;
use App\Services\Streaming\FinanceiroStreamingService;
use App\Services\Streaming\Contracts\StreamingContextHandlerInterface;
use Workerman\Connection\TcpConnection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handler WebSocket para upload de arquivos usando Workerman
 */
class UploadHandlerWorkerman
{
    protected $clients = [];
    protected $sessions = []; // [sessionId => ['queue' => ChunkQueue, 'writer' => AsyncWriter, 'metadata' => array]]
    protected $fileSessions = []; // [fileId => [...]]
    protected $pendingChunks = []; // [connectionId => ['sequence' => int, 'hash' => string, 'file_id' => string, 'session_id' => string]]
    protected $writerManager; // Gerenciador de escrita assíncrona
    protected $centralWriterManager; // Gerenciador central (para filas compartilhadas)
    protected $sharedFileRegistry;
    protected $connectionFiles = []; // [connectionId => [fileId => true]]
    protected $fileOwners = []; // [fileId => connectionId]
    protected $uploadBasePath;
    protected $tokenService;
    protected $connectionLimits = []; // [connectionId => ['max_active_uploads' => int, ...]]
    protected $userActiveUploads = []; // [userId => [fileId => true]]
    protected $defaultConnectionLimits = [];
    protected $contextHandlers = [];
    
    public function __construct()
    {
        $this->writerManager = new ChunkWriterManager();
        $this->centralWriterManager = new CentralWriterManager();
        $this->sharedFileRegistry = new SharedFileRegistry();
        $this->tokenService = new UploadTokenService();
        $this->uploadBasePath = storage_path('app/streaming/upload');
        if (!is_dir($this->uploadBasePath)) {
            mkdir($this->uploadBasePath, 0755, true);
        }
        $this->defaultConnectionLimits = [
            'max_active_uploads' => (int) config('upload.max_active_files', 4),
            'max_bytes_per_user' => (int) config('upload.max_bytes_per_user', 0),
        ];
    }
    
    /**
     * Quando uma nova conexão WebSocket é estabelecida
     */
    public function onConnect(TcpConnection $connection)
    {
        $connectionId = $connection->id;
        $this->clients[$connectionId] = $connection;
        $connection->sessionId = null;
        $connection->authenticated = false;
        $connection->userId = null;
        $connection->userLimits = $this->defaultConnectionLimits;
        $this->connectionLimits[$connectionId] = $connection->userLimits;
        
        Log::info('Nova conexão WebSocket (Workerman)', [
            'connection_id' => $connectionId,
            'remote_address' => $connection->getRemoteAddress() ?? 'unknown'
        ]);
        
        try {
            $connection->send(json_encode([
                'type' => 'hello',
                'message' => 'Autenticação requerida',
                'auth_required' => true,
                'resource_id' => $connectionId
            ]));
        } catch (\Exception $e) {
            Log::error('Erro ao enviar mensagem de conexão', [
                'error' => $e->getMessage(),
                'connection_id' => $connectionId
            ]);
        }
    }
    
    /**
     * Quando uma mensagem é recebida
     */
    public function onMessage(TcpConnection $connection, $message)
    {
        try {
            $connectionId = $connection->id;
            $msgLength = strlen($message);
            $msgLengthMB = round($msgLength / 1024 / 1024, 2);
            
            // Verifica se é mensagem binária (chunk de dados)
            // Se há um chunk pendente esperando dados binários
            if (isset($this->pendingChunks[$connectionId])) {
                if (!$this->isConnectionAuthenticated($connection)) {
                    throw new \RuntimeException('Conexão não autenticada');
                }
                // Esta é uma mensagem binária (chunk de dados)
                $this->handleBinaryChunk($connection, $message);
                return;
            }
            
            // Tenta decodificar como JSON (comandos)
            $data = json_decode($message, true);
            
            if (!$data || !isset($data['type'])) {
                Log::error("Erro ao decodificar JSON", [
                    'json_error' => json_last_error_msg(),
                    'msg_length' => $msgLength,
                    'msg_preview' => substr($message, 0, 200)
                ]);
                throw new \InvalidArgumentException('Mensagem inválida');
            }
            
            Log::debug("Comando recebido", [
                'type' => $data['type'],
                'msg_length_mb' => $msgLengthMB
            ]);
            
            $messageType = $data['type'];

            if ($messageType !== 'auth' && !$this->isConnectionAuthenticated($connection)) {
                throw new \RuntimeException('Conexão não autenticada');
            }
            
            switch ($messageType) {
                case 'auth':
                    $this->handleAuth($connection, $data);
                    break;
                case 'start_upload':
                    $this->handleStartUpload($connection, $data);
                    break;
                    
                case 'chunk_metadata':
                    // Cliente enviou metadados do chunk, aguarda dados binários
                    $this->handleChunkMetadata($connection, $data);
                    break;
                    
                case 'finalize':
                    $this->handleFinalize($connection, $data);
                    break;
                    
                case 'cancel':
                    $this->handleCancel($connection, $data);
                    break;
                    
                default:
                    throw new \InvalidArgumentException("Tipo desconhecido: {$data['type']}");
            }
        } catch (\Exception $e) {
            Log::error('Erro ao processar mensagem', [
                'error' => $e->getMessage(),
                'connection_id' => $connection->id
            ]);
            
            $connection->send(json_encode([
                'type' => 'error',
                'message' => $e->getMessage()
            ]));
        }
    }
    
    protected function handleStartUpload(TcpConnection $connection, array $data)
    {
        $this->ensureAuthenticated($connection);

        $fileId = $data['file_id'] ?? null;
        if (!$fileId) {
            throw new \InvalidArgumentException('file_id é obrigatório');
        }
        
        $sessionId = Str::uuid()->toString();
        
        $fileName = $data['file_name'] ?? 'upload_' . time();
        $context = $data['context'] ?? 'user_storage';
        $contextPayload = $data['context_payload'] ?? [];
        if (!is_array($contextPayload)) {
            $contextPayload = [];
        }

        $requestedPath = $contextPayload['path'] ?? ($data['relative_path'] ?? $fileName);
        $relativePath = $this->sanitizeRelativePath($requestedPath);
        if ($relativePath === '') {
            $relativePath = $fileName;
        }
        
        // Verifica se deve usar fila compartilhada (para arquivos grandes com múltiplos workers)
        $useSharedQueue = $data['use_shared_queue'] ?? false;
        $totalSize = $data['total_size'] ?? 0;

        $this->assertCanStartUpload($connection, (int) $totalSize);
        $userId = $connection->userId;
        
        // Auto-detecta: arquivos > 1GB usam fila compartilhada
        if (!$useSharedQueue && $totalSize > 1073741824) { // 1GB
            $useSharedQueue = true;
            Log::info("Arquivo grande detectado, usando fila compartilhada", [
                'file_id' => $fileId,
                'size_gb' => round($totalSize / 1024 / 1024 / 1024, 2)
            ]);
        }
        
        // Cria caminho completo preservando estrutura de pastas
        $storageRelativePath = $this->resolveStorageRelativePath($connection, $relativePath, $context, $contextPayload);
        $fullPath = rtrim($this->uploadBasePath, '/\\') . '/' . $storageRelativePath;

        if ($handler = $this->getContextHandler($context)) {
            try {
                $handler->onUploadStarted($contextPayload);
            } catch (\Throwable $e) {
                throw new \RuntimeException($e->getMessage(), 0, $e);
            }
        }
        
        // Cria diretórios necessários
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        if ($useSharedQueue) {
            // Usa fila compartilhada (múltiplos workers podem adicionar chunks)
            // Tenta usar memória compartilhada (mais rápido), fallback para disco
            $queueDriver = 'disk';
            if (function_exists('shmop_open')) {
                try {
                    $queue = new SharedChunkQueueMemory($fileId);
                    $queueDriver = 'memory';
                    Log::info('Upload iniciado com fila compartilhada (MEMÓRIA)', [
                        'file_id' => $fileId,
                        'session_id' => $sessionId,
                        'file_name' => $fileName,
                        'relative_path' => $relativePath
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Falha ao usar memória compartilhada, usando disco', [
                        'error' => $e->getMessage()
                    ]);
                    $queue = new SharedChunkQueue($fileId);
                    $queueDriver = 'disk';
                    Log::info('Upload iniciado com fila compartilhada (DISCO)', [
                        'file_id' => $fileId,
                        'session_id' => $sessionId,
                        'file_name' => $fileName,
                        'relative_path' => $relativePath
                    ]);
                }
            } else {
                $queue = new SharedChunkQueue($fileId);
                $queueDriver = 'disk';
                Log::info('Upload iniciado com fila compartilhada (DISCO - shmop não disponível)', [
                    'file_id' => $fileId,
                    'session_id' => $sessionId,
                    'file_name' => $fileName,
                    'relative_path' => $relativePath
                ]);
            }
            
            $writer = new SharedWriterProxy($fullPath);
            
            // Registra no gerenciador central (se ainda não estiver registrado)
            // O gerenciador central processa todas as filas compartilhadas
            $this->centralWriterManager->registerFile($fileId, $fullPath, $queue);

            $this->sharedFileRegistry->put($fileId, [
                'file_id' => $fileId,
                'session_id' => $sessionId,
                'file_name' => $fileName,
                'relative_path' => $relativePath,
                'storage_relative_path' => $storageRelativePath,
                'full_path' => $fullPath,
                'total_size' => $totalSize,
                'use_shared_queue' => true,
                'queue_driver' => $queueDriver,
                'user_id' => $userId,
                'context' => $context,
                'context_payload' => $contextPayload,
                'created_at' => time()
            ]);
        } else {
            // Usa fila local (worker individual)
            $queue = new ChunkQueue();
            $writer = new AsyncWriter();
            $writer->initialize($fullPath, $queue);
            
            // Registra escritor no gerenciador local
            $this->writerManager->registerWriter($sessionId, $writer);
            
            Log::info('Upload iniciado com fila local', [
                'file_id' => $fileId,
                'session_id' => $sessionId,
                'file_name' => $fileName,
                'relative_path' => $relativePath
            ]);
        }
        
        // Registra sessão do arquivo
        $this->fileSessions[$fileId] = [
            'sessionId' => $sessionId,
            'queue' => $queue,
            'writer' => $writer,
            'use_shared' => $useSharedQueue,
            'metadata' => [
                'file_id' => $fileId,
                'file_name' => $fileName,
                'relative_path' => $relativePath,
                'storage_relative_path' => $storageRelativePath,
                'file_path' => $fullPath,
                'total_size' => $totalSize,
                'started_at' => time(),
                'user_id' => $userId,
                'context' => $context,
                'context_payload' => $contextPayload
            ],
            'user_id' => $userId,
            'context' => $context,
            'context_payload' => $contextPayload
        ];

        $this->registerFileOwnership($connection, $fileId, (int) $totalSize);
        
        $connection->send(json_encode([
            'type' => 'upload_started',
            'file_id' => $fileId,
            'session_id' => $sessionId,
            'file_name' => $fileName,
            'relative_path' => $relativePath,
            'use_shared_queue' => $useSharedQueue
        ]));
    }
    
    /**
     * Recebe metadados do chunk (antes dos dados binários)
     */
    protected function handleChunkMetadata(TcpConnection $connection, array $data)
    {
        $sequence = (int)($data['sequence'] ?? 0);
        $hash = $data['hash'] ?? '';
        $fileId = $data['file_id'] ?? null;
        $sessionId = $data['session_id'] ?? '';

        if (empty($hash) || empty($fileId)) {
            throw new \InvalidArgumentException('Hash ou file_id vazio');
        }

        $fileSession = $this->getFileSession($fileId);
        $this->assertFileOwnership($connection, $fileSession);

        // Busca sessionId do arquivo se não foi fornecido
        if (empty($sessionId)) {
            $sessionId = $fileSession['sessionId'];
        }

        if (empty($sessionId)) {
            throw new \InvalidArgumentException('Sessão não encontrada para file_id: ' . $fileId);
        }

        // Armazena metadados, aguardando dados binários
        $this->pendingChunks[$connection->id] = [
            'sequence' => $sequence,
            'hash' => $hash,
            'file_id' => $fileId,
            'session_id' => $sessionId
        ];

        Log::debug("Metadados do chunk #{$sequence} recebidos para arquivo {$fileId}, aguardando dados binários", [
            'hash' => substr($hash, 0, 16) . '...'
        ]);

        // Confirma recebimento dos metadados
        $connection->send(json_encode([
            'type' => 'chunk_metadata_received',
            'file_id' => $fileId,
            'sequence' => $sequence
        ]));
    }

    /**
     * Recebe dados binários do chunk
     */
    protected function handleBinaryChunk(TcpConnection $connection, string $binaryData)
    {
        $connectionId = $connection->id;
        
        if (!isset($this->pendingChunks[$connectionId])) {
            throw new \RuntimeException('Chunk binário recebido sem metadados');
        }

        $metadata = $this->pendingChunks[$connectionId];
        unset($this->pendingChunks[$connectionId]);

        $sequence = $metadata['sequence'];
        $hash = $metadata['hash'];
        $fileId = $metadata['file_id'];
        $sessionId = $metadata['session_id'];

        $dataSize = strlen($binaryData);
        $dataSizeMB = round($dataSize / 1024 / 1024, 2);

        Log::info("Chunk binário recebido", [
            'file_id' => $fileId,
            'sequence' => $sequence,
            'size_mb' => $dataSizeMB,
            'expected_mb' => 32
        ]);

        $fileSession = $this->getFileSession($fileId);
        $this->assertFileOwnership($connection, $fileSession);
        $queue = $fileSession['queue'];
        $writer = $fileSession['writer'];

        // Valida hash
        $calculatedHash = hash('sha256', $binaryData);
        if (!hash_equals($calculatedHash, $hash)) {
            Log::error("Hash mismatch para chunk #{$sequence} do arquivo {$fileId}", [
                'received' => substr($hash, 0, 16),
                'calculated' => substr($calculatedHash, 0, 16)
            ]);
            throw new \InvalidArgumentException("Hash inválido para chunk #{$sequence}");
        }

        // Verifica backpressure antes de adicionar à fila
        if (!$queue->canAcceptChunk()) {
            Log::warning("Fila cheia, rejeitando chunk", [
                'file_id' => $fileId,
                'sequence' => $sequence,
                'queue_size' => $queue->getQueueSize()
            ]);
            
            $connection->send(json_encode([
                'type' => 'chunk_rejected',
                'file_id' => $fileId,
                'sequence' => $sequence,
                'reason' => 'queue_full',
                'queue_size' => $queue->getQueueSize(),
                'message' => 'Fila cheia. Aguarde processamento.'
            ]));
            return;
        }
        
        // Adiciona à fila
        $result = $queue->enqueue($sequence, $binaryData, $hash);

        // Processa filas
        if ($fileSession['use_shared'] ?? false) {
            // Fila compartilhada: gerenciador central processa
            $this->centralWriterManager->processAllQueues();
        } else {
            // Fila local: gerenciador local processa
            for ($i = 0; $i < 3; $i++) {
                $this->writerManager->processAllQueues();
            }
        }

        // Responde ao cliente com status da fila
        $stats = $queue->getStats();
        $writerStats = $writer->getStats();

        $connection->send(json_encode([
            'type' => 'chunk_received',
            'file_id' => $fileId,
            'sequence' => $sequence,
            'queue_size' => $stats['queue_size'],
            'max_queue_size' => $stats['max_queue_size'],
            'can_resume_sending' => $stats['can_resume_sending'],
            'memory_gb' => $stats['total_memory_gb'],
            'file_size_gb' => $writerStats['file_size_gb']
        ]));
    }
    
    protected function handleFinalize(TcpConnection $connection, array $data)
    {
        $fileId = $data['file_id'] ?? null;
        $sessionId = $data['session_id'] ?? null;

        if (!$fileId) {
            throw new \RuntimeException('file_id é obrigatório para finalizar');
        }

        $fileSession = $this->getFileSession($fileId);
        $this->assertFileOwnership($connection, $fileSession);
        $fileUserId = $fileSession['user_id'] ?? null;
        $writer = $fileSession['writer'];
        $queue = $fileSession['queue'];
        $metadata = $fileSession['metadata'];
        $useShared = $fileSession['use_shared'] ?? false;

        // Processa chunks restantes
        $maxAttempts = 60;
        $attempts = 0;

        while ($queue->hasPendingChunks() && $attempts < $maxAttempts) {
            if ($useShared) {
                // Fila compartilhada: gerenciador central processa
                $this->centralWriterManager->processAllQueues();
            } else {
                // Fila local: processa localmente
                $writer->processQueue();
            }
            
            if ($queue->hasPendingChunks()) {
                usleep(100000); // 100ms
            }
            $attempts++;
        }

        // Finaliza escrita
        $writer->finalize();

        // Limpa fila explicitamente para liberar memória
        $queue->clear();

        $filePath = $writer->getFilePath();
        $fileSize = file_exists($filePath) ? filesize($filePath) : 0;

        try {
            $contextResult = $this->handleContextFinalize($connection, $fileSession, $filePath, $fileSize);

            Log::info('Upload finalizado', [
                'file_id' => $fileId,
                'session_id' => $sessionId,
                'file_path' => $filePath,
                'relative_path' => $metadata['relative_path'],
                'file_size' => round($fileSize / 1024 / 1024 / 1024, 2) . 'GB',
                'use_shared_queue' => $useShared,
                'context_result' => $contextResult
            ]);

            $response = [
                'type' => 'file_completed',
                'file_id' => $fileId,
                'file_path' => $filePath,
                'relative_path' => $metadata['relative_path'],
                'file_size' => $fileSize
            ];

            if (!empty($contextResult)) {
                $response['context'] = $contextResult;
            }

            $connection->send(json_encode($response));
        } catch (\Exception $e) {
            Log::error('Erro ao finalizar upload para contexto específico', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);

            if ($filePath && file_exists($filePath)) {
                @unlink($filePath);
            }

            $connection->send(json_encode([
                'type' => 'error',
                'file_id' => $fileId,
                'message' => $e->getMessage()
            ]));
        }

        // Remove sessão e desregistra escritor
        if ($useShared) {
            $this->centralWriterManager->unregisterFile($fileId);
            $this->sharedFileRegistry->forget($fileId);
        } else {
            $this->writerManager->unregisterWriter($sessionId);
        }
        
        // Limpa referências explicitamente
        $writer = null;
        $queue = null;
        unset($this->fileSessions[$fileId]);
        $this->unregisterFileOwnership($fileId, $fileUserId);
    }
    
    protected function handleCancel(TcpConnection $connection, array $data)
    {
        $fileId = $data['file_id'] ?? null;
        $sessionId = $data['session_id'] ?? null;
        $connectionId = $connection->id;
        $suppressNotification = (bool)($data['suppress_notification'] ?? false);

        $fileSession = null;
        if ($fileId) {
            try {
                $fileSession = $this->getFileSession($fileId);
            } catch (\RuntimeException $e) {
                $fileSession = null;
            }
        }

        if ($fileSession) {
            if (!$sessionId) {
                $sessionId = $fileSession['sessionId'] ?? null;
            }
            $this->assertFileOwnership($connection, $fileSession);
            $fileUserId = $fileSession['user_id'] ?? null;
            // Limpa fila (libera memória)
            $fileSession['queue']->clear();
            
            // Remove arquivo se existir
            $filePath = $fileSession['writer']->getFilePath();
            if ($filePath && file_exists($filePath)) {
                @unlink($filePath);
            }
            
            if ($fileSession['use_shared'] ?? false) {
                $this->centralWriterManager->unregisterFile($fileId);
                $this->sharedFileRegistry->forget($fileId);
            } elseif ($sessionId) {
                $this->writerManager->unregisterWriter($sessionId);
            }
            
            unset($this->fileSessions[$fileId]);
            $this->unregisterFileOwnership($fileId, $fileUserId);
        } elseif ($connection->sessionId && isset($this->sessions[$connection->sessionId])) {
            // Fallback para compatibilidade
            $session = $this->sessions[$connection->sessionId];
            $session['queue']->clear();
            
            $filePath = $session['writer']->getFilePath();
            if ($filePath && file_exists($filePath)) {
                @unlink($filePath);
            }
            
            $this->writerManager->unregisterWriter($connection->sessionId);
            unset($this->sessions[$connection->sessionId]);
        }

        // Limpa buffers de chunks pendentes
        if (isset($this->pendingChunks[$connectionId])) {
            unset($this->pendingChunks[$connectionId]);
        }

        if (!$suppressNotification) {
            try {
                $connection->send(json_encode([
                    'type' => 'upload_cancelled',
                    'file_id' => $fileId
                ]));
            } catch (\Exception $e) {
                // Conexão pode já estar fechada; ignora.
            }
        }
    }
    
    /**
     * Quando a conexão é fechada
     */
    public function onClose(TcpConnection $connection)
    {
        $connectionId = $connection->id;
        
        Log::info('Conexão fechada (Workerman)', [
            'connection_id' => $connectionId,
            'session_id' => $connection->sessionId ?? null
        ]);

        // Cancela apenas os arquivos associados quando não restarem outros donos
        if (isset($this->connectionFiles[$connectionId])) {
            $fileIds = array_keys($this->connectionFiles[$connectionId]);
            foreach ($fileIds as $fileId) {
                $hasOtherOwners = $this->detachConnectionFromFile($fileId, $connectionId);

                if (!$hasOtherOwners && isset($this->fileSessions[$fileId])) {
                    try {
                        $this->handleCancel($connection, [
                            'file_id' => $fileId,
                            'session_id' => $this->fileSessions[$fileId]['sessionId'] ?? null,
                            'suppress_notification' => true
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Erro ao cancelar upload no onClose', [
                            'file_id' => $fileId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            unset($this->connectionFiles[$connectionId]);
        }

        // Fallback legado
        if (isset($connection->sessionId) && isset($this->sessions[$connection->sessionId])) {
            $this->handleCancel($connection, ['suppress_notification' => true]);
        }

        // Limpa buffers de chunks pendentes
        if (isset($this->pendingChunks[$connectionId])) {
            unset($this->pendingChunks[$connectionId]);
        }

        // Remove da lista de clientes
        if (isset($this->clients[$connectionId])) {
            unset($this->clients[$connectionId]);
        }

        unset($this->connectionLimits[$connectionId]);
    }
    
    /**
     * Quando ocorre um erro na conexão
     */
    public function onError(TcpConnection $connection, $code, $msg)
    {
        Log::error('Erro WebSocket (Workerman)', [
            'error' => $msg,
            'code' => $code,
            'connection_id' => $connection->id
        ]);
        
        try {
            $connection->send(json_encode([
                'type' => 'error',
                'message' => $msg
            ]));
        } catch (\Exception $sendError) {
            // Ignora erro ao enviar
        }
        
        $connection->close();
    }

    protected function handleAuth(TcpConnection $connection, array $data): void
    {
        $token = $data['token'] ?? null;
        try {
            $record = $this->tokenService->validateAndConsume($token);
        } catch (\Throwable $e) {
            $connection->send(json_encode([
                'type' => 'auth_error',
                'message' => $e->getMessage()
            ]));
            $connection->close();
            return;
        }

        $connection->authenticated = true;
        $connection->userId = $record->user_id;
        $connection->userInfo = [
            'token_id' => $record->id,
            'token' => $record->token,
        ];
        $connection->userLimits = $this->defaultConnectionLimits;
        $this->connectionLimits[$connection->id] = $connection->userLimits;
        $this->userActiveUploads[$connection->userId] = $this->userActiveUploads[$connection->userId] ?? [];

        Log::info('Conexão autenticada', [
            'connection_id' => $connection->id,
            'user_id' => $connection->userId
        ]);

        $connection->send(json_encode([
            'type' => 'auth_ok',
            'user_id' => $connection->userId,
            'limits' => $connection->userLimits
        ]));

        $connection->send(json_encode([
            'type' => 'connected',
            'message' => 'Conexão autenticada',
            'resource_id' => $connection->id
        ]));
    }

    protected function isConnectionAuthenticated(TcpConnection $connection): bool
    {
        return isset($connection->authenticated, $connection->userId) && $connection->authenticated === true;
    }

    protected function ensureAuthenticated(TcpConnection $connection): void
    {
        if (!$this->isConnectionAuthenticated($connection)) {
            throw new \RuntimeException('Conexão não autenticada');
        }
    }

    protected function assertCanStartUpload(TcpConnection $connection, int $fileSize): void
    {
        $limits = $this->connectionLimits[$connection->id] ?? $this->defaultConnectionLimits;
        $maxActive = $limits['max_active_uploads'] ?? 4;
        $maxBytes = $limits['max_bytes_per_user'] ?? 0;
        $userId = $connection->userId;
        $activeUploads = $this->userActiveUploads[$userId] ?? [];
        $activeCount = count($activeUploads);

        if ($activeCount >= $maxActive) {
            $this->cleanupUserActiveUploads($userId);
            $activeUploads = $this->userActiveUploads[$userId] ?? [];
            if (count($activeUploads) >= $maxActive) {
                throw new \RuntimeException('Limite de uploads simultâneos atingido');
            }
        }

        if ($maxBytes > 0) {
            $activeBytes = array_sum(array_map(function ($info) {
                return is_array($info) ? ($info['size'] ?? 0) : 0;
            }, $activeUploads));

            if (($activeBytes + $fileSize) > $maxBytes) {
                throw new \RuntimeException(
                    'Limite de banda simultânea atingido. Libere espaço antes de iniciar outro upload.'
                );
            }
        }
    }

    protected function releaseUserUpload(?int $userId, string $fileId): void
    {
        if ($userId === null) {
            return;
        }

        if (!isset($this->userActiveUploads[$userId][$fileId])) {
            return;
        }

        unset($this->userActiveUploads[$userId][$fileId]);

        if (empty($this->userActiveUploads[$userId])) {
            unset($this->userActiveUploads[$userId]);
        }
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . $units[$i];
    }

    protected function cleanupUserActiveUploads(?int $userId): void
    {
        if ($userId === null || empty($this->userActiveUploads[$userId])) {
            return;
        }

        foreach (array_keys($this->userActiveUploads[$userId]) as $fileId) {
            $hasSession = isset($this->fileSessions[$fileId]);
            $hasSharedSession = false;

            if (!$hasSession) {
                $sharedMetadata = $this->sharedFileRegistry->get($fileId);
                $hasSharedSession = is_array($sharedMetadata);
            }

            if (!$hasSession && !$hasSharedSession) {
                unset($this->userActiveUploads[$userId][$fileId]);
            }
        }

        if (empty($this->userActiveUploads[$userId])) {
            unset($this->userActiveUploads[$userId]);
        }
    }

    protected function registerFileOwnership(TcpConnection $connection, string $fileId, int $fileSize): void
    {
        $connectionId = $connection->id;
        if (!isset($this->fileOwners[$fileId])) {
            $this->fileOwners[$fileId] = [];
        }
        $this->fileOwners[$fileId][$connectionId] = true;

        if (!isset($this->connectionFiles[$connectionId])) {
            $this->connectionFiles[$connectionId] = [];
        }

        $this->connectionFiles[$connectionId][$fileId] = true;

        if ($connection->userId) {
            if (!isset($this->userActiveUploads[$connection->userId])) {
                $this->userActiveUploads[$connection->userId] = [];
            }
            $this->userActiveUploads[$connection->userId][$fileId] = [
                'size' => $fileSize,
                'started_at' => time(),
            ];
        }
    }

    protected function unregisterFileOwnership(string $fileId, ?int $userId = null): void
    {
        if (!isset($this->fileOwners[$fileId])) {
            return;
        }

        foreach (array_keys($this->fileOwners[$fileId]) as $ownerId) {
            if (isset($this->connectionFiles[$ownerId][$fileId])) {
                unset($this->connectionFiles[$ownerId][$fileId]);
                if (empty($this->connectionFiles[$ownerId])) {
                    unset($this->connectionFiles[$ownerId]);
                }
            }
        }

        unset($this->fileOwners[$fileId]);

        if ($userId !== null) {
            $this->releaseUserUpload($userId, $fileId);
        }
    }

    protected function detachConnectionFromFile(string $fileId, int $connectionId): bool
    {
        if (!isset($this->fileOwners[$fileId][$connectionId])) {
            return isset($this->fileOwners[$fileId]) && !empty($this->fileOwners[$fileId]);
        }

        unset($this->fileOwners[$fileId][$connectionId]);

        if (isset($this->connectionFiles[$connectionId][$fileId])) {
            unset($this->connectionFiles[$connectionId][$fileId]);
            if (empty($this->connectionFiles[$connectionId])) {
                unset($this->connectionFiles[$connectionId]);
            }
        }

        if (empty($this->fileOwners[$fileId])) {
            unset($this->fileOwners[$fileId]);
            return false;
        }

        return true;
    }

    protected function assertFileOwnership(TcpConnection $connection, array $fileSession): void
    {
        $fileUserId = $fileSession['user_id'] ?? ($fileSession['metadata']['user_id'] ?? null);
        if ($fileUserId === null || $fileUserId !== $connection->userId) {
            throw new \RuntimeException('Sem permissão para acessar este upload');
        }
    }

    protected function resolveStorageRelativePath(TcpConnection $connection, string $relativePath, string $context, array $payload): string
    {
        $relativePath = trim($relativePath, '/');
        if ($relativePath === '') {
            $relativePath = 'upload_' . time();
        }

        if ($handler = $this->getContextHandler($context)) {
            return $handler->resolveStoragePath($connection->userId ?? 0, $relativePath, $payload);
        }

        return 'user_' . $connection->userId . '/' . $relativePath;
    }

    protected function handleContextFinalize(TcpConnection $connection, array $fileSession, string $filePath, int $fileSize): array
    {
        $metadata = $fileSession['metadata'] ?? [];
        $context = $fileSession['context'] ?? ($metadata['context'] ?? 'user_storage');

        $handler = $this->getContextHandler($context);

        if (!$handler) {
            return [];
        }

        try {
            return $handler->finalize($metadata, $filePath, $fileSize);
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage(), 0, $e);
        }
    }

    protected function getContextHandler(string $context): ?StreamingContextHandlerInterface
    {
        if (!isset($this->contextHandlers[$context])) {
            $this->contextHandlers[$context] = match ($context) {
                'arquivos' => new ArquivoStreamingService(),
                'codigos' => new CodigoStreamingService(),
                'sistemas' => new SistemaStreamingService(),
                'pagina_sistemas' => new PaginaStreamingService(),
                'livros' => new LivroStreamingService(),
                'public' => new PublicDiskStreamingService(),
                'financeiro' => new FinanceiroStreamingService(),
                default => null,
            };
        }

        return $this->contextHandlers[$context];
    }

    protected function sanitizeRelativePath(string $path): string
    {
        $path = str_replace(["\0"], '', $path);
        $path = str_replace('\\', '/', $path);

        $segments = [];
        foreach (explode('/', $path) as $segment) {
            $segment = trim($segment);
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                continue;
            }
            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    protected function getFileSession(string $fileId): array
    {
        if (!isset($this->fileSessions[$fileId])) {
            $this->recoverSharedFileSession($fileId);
        }

        if (!isset($this->fileSessions[$fileId])) {
            throw new \RuntimeException('Sessão do arquivo não encontrada: ' . $fileId);
        }

        return $this->fileSessions[$fileId];
    }

    protected function recoverSharedFileSession(string $fileId): void
    {
        $metadata = $this->sharedFileRegistry->get($fileId);

        if (!$metadata || empty($metadata['use_shared_queue'])) {
            return;
        }

        $queueDriver = $metadata['queue_driver'] ?? 'disk';
        if ($queueDriver === 'memory' && !function_exists('shmop_open')) {
            $queueDriver = 'disk';
        }
        $queue = $queueDriver === 'memory'
            ? new SharedChunkQueueMemory($fileId)
            : new SharedChunkQueue($fileId);

        $writer = new SharedWriterProxy($metadata['full_path'] ?? '');

        $context = $metadata['context'] ?? 'user_storage';
        $contextPayload = $metadata['context_payload'] ?? [];

        $this->fileSessions[$fileId] = [
            'sessionId' => $metadata['session_id'] ?? null,
            'queue' => $queue,
            'writer' => $writer,
            'use_shared' => true,
            'user_id' => $metadata['user_id'] ?? null,
            'context' => $context,
            'context_payload' => $contextPayload,
            'metadata' => [
                'file_id' => $metadata['file_id'] ?? $fileId,
                'file_name' => $metadata['file_name'] ?? $fileId,
                'relative_path' => $metadata['relative_path'] ?? ($metadata['file_name'] ?? $fileId),
                'storage_relative_path' => $metadata['storage_relative_path'] ?? null,
                'file_path' => $metadata['full_path'] ?? null,
                'total_size' => $metadata['total_size'] ?? 0,
                'started_at' => $metadata['created_at'] ?? time(),
                'user_id' => $metadata['user_id'] ?? null,
                'context' => $context,
                'context_payload' => $contextPayload,
            ]
        ];
    }
}


