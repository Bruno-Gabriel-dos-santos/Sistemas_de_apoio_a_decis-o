<?php

namespace App\WebSocket;

use App\Services\ChunkQueue;
use App\Services\AsyncWriter;
use App\Services\ChunkWriterManager;
use App\Services\SharedChunkQueue;
use App\Services\SharedChunkQueueMemory;
use App\Services\CentralWriterManager;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handler WebSocket para upload de arquivos
 */
class UploadHandler implements MessageComponentInterface
{
    protected $clients;
    protected $sessions = []; // [sessionId => ['queue' => ChunkQueue, 'writer' => AsyncWriter, 'metadata' => array]]
    protected $fileSessions = []; // [fileId => ['sessionId' => string, 'queue' => ChunkQueue|SharedChunkQueue, 'writer' => AsyncWriter, 'metadata' => array, 'use_shared' => bool]]
    protected $pendingChunks = []; // [resourceId => ['sequence' => int, 'hash' => string, 'file_id' => string, 'session_id' => string]]
    protected $writerManager; // Gerenciador de escrita assíncrona
    protected $centralWriterManager; // Gerenciador central (para filas compartilhadas)
    
    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->writerManager = new ChunkWriterManager();
        $this->centralWriterManager = new CentralWriterManager();
    }
    
    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        $conn->sessionId = null;
        
        Log::info('Nova conexão WebSocket', [
            'resource_id' => $conn->resourceId,
            'remote_address' => $conn->remoteAddress ?? 'unknown'
        ]);
        
        try {
            $conn->send(json_encode([
                'type' => 'connected',
                'message' => 'Conexão estabelecida',
                'resource_id' => $conn->resourceId
            ]));
        } catch (\Exception $e) {
            Log::error('Erro ao enviar mensagem de conexão', [
                'error' => $e->getMessage(),
                'resource_id' => $conn->resourceId
            ]);
        }
    }
    
    public function onMessage(ConnectionInterface $from, $msg)
    {
        try {
            $msgLength = strlen($msg);
            $msgLengthMB = round($msgLength / 1024 / 1024, 2);
            
            // Verifica se é mensagem binária (chunk de dados)
            // Se há um chunk pendente esperando dados binários
            if (isset($this->pendingChunks[$from->resourceId])) {
                // Esta é uma mensagem binária (chunk de dados)
                $this->handleBinaryChunk($from, $msg);
                return;
            }
            
            // Tenta decodificar como JSON (comandos)
            $data = json_decode($msg, true);
            
            if (!$data || !isset($data['type'])) {
                Log::error("Erro ao decodificar JSON", [
                    'json_error' => json_last_error_msg(),
                    'msg_length' => $msgLength,
                    'msg_preview' => substr($msg, 0, 200)
                ]);
                throw new \InvalidArgumentException('Mensagem inválida');
            }
            
            Log::debug("Comando recebido", [
                'type' => $data['type'],
                'msg_length_mb' => $msgLengthMB
            ]);
            
            switch ($data['type']) {
                case 'start_upload':
                    $this->handleStartUpload($from, $data);
                    break;
                    
                case 'chunk_metadata':
                    // Cliente enviou metadados do chunk, aguarda dados binários
                    $this->handleChunkMetadata($from, $data);
                    break;
                    
                case 'finalize':
                    $this->handleFinalize($from, $data);
                    break;
                    
                case 'cancel':
                    $this->handleCancel($from, $data);
                    break;
                    
                default:
                    throw new \InvalidArgumentException("Tipo desconhecido: {$data['type']}");
            }
        } catch (\Exception $e) {
            Log::error('Erro ao processar mensagem', [
                'error' => $e->getMessage(),
                'resource_id' => $from->resourceId
            ]);
            
            $from->send(json_encode([
                'type' => 'error',
                'message' => $e->getMessage()
            ]));
        }
    }
    
    protected function handleStartUpload(ConnectionInterface $conn, array $data)
    {
        $fileId = $data['file_id'] ?? null;
        if (!$fileId) {
            throw new \InvalidArgumentException('file_id é obrigatório');
        }
        
        $sessionId = Str::uuid()->toString();
        
        $fileName = $data['file_name'] ?? 'upload_' . time();
        $relativePath = $data['relative_path'] ?? $fileName;
        
        // Verifica se deve usar fila compartilhada (para arquivos grandes com múltiplos workers)
        $useSharedQueue = $data['use_shared_queue'] ?? false;
        $totalSize = $data['total_size'] ?? 0;
        
        // Auto-detecta: arquivos > 1GB usam fila compartilhada
        if (!$useSharedQueue && $totalSize > 1073741824) { // 1GB
            $useSharedQueue = true;
            Log::info("Arquivo grande detectado, usando fila compartilhada", [
                'file_id' => $fileId,
                'size_gb' => round($totalSize / 1024 / 1024 / 1024, 2)
            ]);
        }
        
        // Cria caminho completo preservando estrutura de pastas
        $fullPath = storage_path('app/streaming/upload/' . $relativePath);
        
        // Cria diretórios necessários
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        if ($useSharedQueue) {
            // Usa fila compartilhada (múltiplos workers podem adicionar chunks)
            // Tenta usar memória compartilhada (mais rápido), fallback para disco
            if (function_exists('shmop_open')) {
                try {
                    $queue = new SharedChunkQueueMemory($fileId);
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
                    Log::info('Upload iniciado com fila compartilhada (DISCO)', [
                        'file_id' => $fileId,
                        'session_id' => $sessionId,
                        'file_name' => $fileName,
                        'relative_path' => $relativePath
                    ]);
                }
            } else {
                $queue = new SharedChunkQueue($fileId);
                Log::info('Upload iniciado com fila compartilhada (DISCO - shmop não disponível)', [
                    'file_id' => $fileId,
                    'session_id' => $sessionId,
                    'file_name' => $fileName,
                    'relative_path' => $relativePath
                ]);
            }
            
            $writer = new AsyncWriter();
            $dummyQueue = new ChunkQueue(); // Fila dummy apenas para inicializar
            $writer->initialize($fullPath, $dummyQueue);
            
            // Registra no gerenciador central (se ainda não estiver registrado)
            // O gerenciador central processa todas as filas compartilhadas
            $this->centralWriterManager->registerFile($fileId, $fullPath, $queue);
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
                'file_path' => $fullPath,
                'total_size' => $totalSize,
                'started_at' => time()
            ]
        ];
        
        $conn->send(json_encode([
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
            protected function handleChunkMetadata(ConnectionInterface $conn, array $data)
            {
                $sequence = (int)($data['sequence'] ?? 0);
                $hash = $data['hash'] ?? '';
                $fileId = $data['file_id'] ?? null;
                $sessionId = $data['session_id'] ?? '';

                if (empty($hash) || empty($fileId)) {
                    throw new \InvalidArgumentException('Hash ou file_id vazio');
                }

                // Busca sessionId do arquivo se não foi fornecido
                if (empty($sessionId) && isset($this->fileSessions[$fileId])) {
                    $sessionId = $this->fileSessions[$fileId]['sessionId'];
                }

                if (empty($sessionId)) {
                    throw new \InvalidArgumentException('Sessão não encontrada para file_id: ' . $fileId);
                }

                // Armazena metadados, aguardando dados binários
                $this->pendingChunks[$conn->resourceId] = [
                    'sequence' => $sequence,
                    'hash' => $hash,
                    'file_id' => $fileId,
                    'session_id' => $sessionId
                ];

                Log::debug("Metadados do chunk #{$sequence} recebidos para arquivo {$fileId}, aguardando dados binários", [
                    'hash' => substr($hash, 0, 16) . '...'
                ]);

                // Confirma recebimento dos metadados
                $conn->send(json_encode([
                    'type' => 'chunk_metadata_received',
                    'file_id' => $fileId,
                    'sequence' => $sequence
                ]));
            }
    
            /**
             * Recebe dados binários do chunk
             */
            protected function handleBinaryChunk(ConnectionInterface $conn, string $binaryData)
            {
                if (!isset($this->pendingChunks[$conn->resourceId])) {
                    throw new \RuntimeException('Chunk binário recebido sem metadados');
                }

                $metadata = $this->pendingChunks[$conn->resourceId];
                unset($this->pendingChunks[$conn->resourceId]);

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

                if (!isset($this->fileSessions[$fileId])) {
                    throw new \RuntimeException('Sessão do arquivo não encontrada: ' . $fileId);
                }

                $fileSession = $this->fileSessions[$fileId];
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
                    
                    $conn->send(json_encode([
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

                $conn->send(json_encode([
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
    
    protected function handleChunk(ConnectionInterface $conn, array $data)
    {
        if (!isset($conn->sessionId) || !isset($this->sessions[$conn->sessionId])) {
            throw new \RuntimeException('Sessão não encontrada');
        }
        
        $session = $this->sessions[$conn->sessionId];
        $queue = $session['queue'];
        $writer = $session['writer'];
        
        $sequence = (int)($data['sequence'] ?? 0);
        $hash = $data['hash'] ?? '';
        $base64Chunk = $data['chunk'] ?? '';
        
        // Logs detalhados do chunk recebido
        $base64Length = strlen($base64Chunk);
        $base64LengthMB = round($base64Length / 1024 / 1024, 2);
        $expectedBase64Size = (32 * 1024 * 1024) * 4 / 3; // 32MB em base64 ≈ 42.67MB
        $expectedBase64SizeMB = round($expectedBase64Size / 1024 / 1024, 2);
        
        Log::info("Chunk recebido no servidor", [
            'sequence' => $sequence,
            'hash_length' => strlen($hash),
            'hash_preview' => substr($hash, 0, 16) . '...',
            'base64_length' => $base64Length,
            'base64_length_mb' => $base64LengthMB,
            'expected_base64_size_mb' => $expectedBase64SizeMB,
            'difference_mb' => round(($base64Length - $expectedBase64Size) / 1024 / 1024, 2),
            'percent_complete' => round(($base64Length / $expectedBase64Size) * 100, 2),
            'base64_start' => substr($base64Chunk, 0, 50),
            'base64_end' => '...' . substr($base64Chunk, -50)
        ]);
        
        if (empty($hash) || empty($base64Chunk)) {
            Log::error("Hash ou chunk vazio", [
                'has_hash' => !empty($hash),
                'has_chunk' => !empty($base64Chunk),
                'hash_length' => strlen($hash),
                'chunk_length' => strlen($base64Chunk)
            ]);
            throw new \InvalidArgumentException('Hash ou chunk vazio');
        }
        
        // Verifica se o base64 parece estar completo
        if ($base64Length < ($expectedBase64Size * 0.9)) {
            Log::error("Base64 parece estar truncado", [
                'actual_size_mb' => $base64LengthMB,
                'expected_size_mb' => $expectedBase64SizeMB,
                'missing_mb' => round(($expectedBase64Size - $base64Length) / 1024 / 1024, 2),
                'percent_received' => round(($base64Length / $expectedBase64Size) * 100, 2)
            ]);
        }
        
        // Decodifica chunk
        $chunkData = base64_decode($base64Chunk, true);
        if ($chunkData === false) {
            $decodedLength = strlen(base64_decode($base64Chunk, false)); // Tenta sem strict para ver o que decodifica
            Log::error("Falha ao decodificar base64", [
                'sequence' => $sequence,
                'base64_length' => $base64Length,
                'base64_length_mb' => $base64LengthMB,
                'base64_valid_chars' => preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $base64Chunk) ? 'sim' : 'não',
                'base64_start' => substr($base64Chunk, 0, 100),
                'base64_end' => '...' . substr($base64Chunk, -100),
                'decoded_length_without_strict' => $decodedLength
            ]);
            throw new \InvalidArgumentException('Chunk inválido (base64)');
        }
        
        $decodedSize = strlen($chunkData);
        $decodedSizeMB = round($decodedSize / 1024 / 1024, 2);
        
        Log::info("Chunk decodificado com sucesso", [
            'sequence' => $sequence,
            'decoded_size' => $decodedSize,
            'decoded_size_mb' => $decodedSizeMB,
            'expected_size_mb' => 32,
            'size_match' => abs($decodedSize - (32 * 1024 * 1024)) < (1024 * 1024) // Tolerância de 1MB
        ]);
        
        // Adiciona à fila (a validação do hash é feita dentro de enqueue)
        // O hash será calculado dos dados binários $chunkData e comparado com $hash recebido
        $result = $queue->enqueue($sequence, $chunkData, $hash);
        
        // Processa escrita assíncrona
        $writer->processQueue();
        
        // Retorna progresso
        $stats = $queue->getStats();
        $writerStats = $writer->getStats();
        
        $conn->send(json_encode([
            'type' => 'chunk_received',
            'sequence' => $sequence,
            'queue_size' => $stats['queue_size'],
            'memory_gb' => $stats['total_memory_gb'],
            'file_size_gb' => $writerStats['file_size_gb']
        ]));
    }
    
    protected function handleFinalize(ConnectionInterface $conn, array $data)
    {
        $fileId = $data['file_id'] ?? null;
        $sessionId = $data['session_id'] ?? null;

        if (!$fileId || !isset($this->fileSessions[$fileId])) {
            throw new \RuntimeException('Sessão do arquivo não encontrada: ' . $fileId);
        }

        $fileSession = $this->fileSessions[$fileId];
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

        Log::info('Upload finalizado', [
            'file_id' => $fileId,
            'session_id' => $sessionId,
            'file_path' => $filePath,
            'relative_path' => $metadata['relative_path'],
            'file_size' => round($fileSize / 1024 / 1024 / 1024, 2) . 'GB',
            'use_shared_queue' => $useShared
        ]);

        $conn->send(json_encode([
            'type' => 'file_completed',
            'file_id' => $fileId,
            'file_path' => $filePath,
            'relative_path' => $metadata['relative_path'],
            'file_size' => $fileSize
        ]));

        // Remove sessão e desregistra escritor
        if ($useShared) {
            $this->centralWriterManager->unregisterFile($fileId);
        } else {
            $this->writerManager->unregisterWriter($sessionId);
        }
        
        // Limpa referências explicitamente
        $writer = null;
        $queue = null;
        unset($this->fileSessions[$fileId]);
    }
    
    protected function handleCancel(ConnectionInterface $conn, array $data)
    {
        $fileId = $data['file_id'] ?? null;
        $sessionId = $data['session_id'] ?? null;

        if ($fileId && isset($this->fileSessions[$fileId])) {
            $fileSession = $this->fileSessions[$fileId];
            
            // Limpa fila (libera memória)
            $fileSession['queue']->clear();
            
            // Remove arquivo se existir
            $filePath = $fileSession['writer']->getFilePath();
            if ($filePath && file_exists($filePath)) {
                @unlink($filePath);
            }
            
            // Desregistra escritor
            if ($sessionId) {
                $this->writerManager->unregisterWriter($sessionId);
            }
            
            // Remove sessão e limpa referências
            unset($this->fileSessions[$fileId]);
        } elseif ($conn->sessionId && isset($this->sessions[$conn->sessionId])) {
            // Fallback para compatibilidade
            $session = $this->sessions[$conn->sessionId];
            $session['queue']->clear();
            
            $filePath = $session['writer']->getFilePath();
            if ($filePath && file_exists($filePath)) {
                @unlink($filePath);
            }
            
            $this->writerManager->unregisterWriter($conn->sessionId);
            unset($this->sessions[$conn->sessionId]);
        }

        // Limpa buffers de chunks pendentes
        if (isset($this->pendingChunks[$conn->resourceId])) {
            unset($this->pendingChunks[$conn->resourceId]);
        }

        $conn->send(json_encode([
            'type' => 'upload_cancelled',
            'file_id' => $fileId
        ]));
    }
    
    public function onClose(ConnectionInterface $conn)
    {
        Log::info('Conexão fechada', [
            'resource_id' => $conn->resourceId,
            'session_id' => $conn->sessionId ?? null
        ]);

        // Cancela todos os arquivos em upload desta conexão
        // Isso garante que memória seja liberada ao fechar conexão
        // Copia array para evitar modificação durante iteração
        $fileSessionsToCancel = [];
        foreach ($this->fileSessions as $fileId => $fileSession) {
            $fileSessionsToCancel[$fileId] = $fileSession;
        }
        
        foreach ($fileSessionsToCancel as $fileId => $fileSession) {
            $this->handleCancel($conn, [
                'file_id' => $fileId,
                'session_id' => $fileSession['sessionId']
            ]);
        }

        // Fallback para compatibilidade
        if (isset($conn->sessionId) && isset($this->sessions[$conn->sessionId])) {
            $this->handleCancel($conn, []);
        }

        // Limpa buffers de chunks pendentes
        if (isset($this->pendingChunks[$conn->resourceId])) {
            unset($this->pendingChunks[$conn->resourceId]);
        }

        $this->clients->detach($conn);
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        Log::error('Erro WebSocket', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'resource_id' => $conn->resourceId
        ]);
        
        try {
            $conn->send(json_encode([
                'type' => 'error',
                'message' => $e->getMessage()
            ]));
        } catch (\Exception $sendError) {
            // Ignora erro ao enviar
        }
        
        $conn->close();
    }
}

