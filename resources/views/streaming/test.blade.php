<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Teste Upload Streaming</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .connected { background: #d4edda; color: #155724; }
        .disconnected { background: #f8d7da; color: #721c24; }
        .connecting { background: #fff3cd; color: #856404; }
        .uploading { background: #d1ecf1; color: #0c5460; }
        .progress {
            width: 100%;
            height: 30px;
            background: #f0f0f0;
            border-radius: 5px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-bar {
            height: 100%;
            background: #007bff;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        button {
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
        }
        .log {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h1>Teste Upload Streaming</h1>
    
    <div id="status" class="status disconnected">Desconectado</div>
    
    <div style="margin: 10px 0;">
        <label>
            <input type="radio" name="uploadMode" value="files" checked> Arquivos
        </label>
        <label style="margin-left: 20px;">
            <input type="radio" name="uploadMode" value="folder"> Pasta
        </label>
    </div>
    <input type="file" id="fileInput" multiple>
    <button id="startBtn" disabled>Iniciar Upload</button>
    <button id="cancelBtn" disabled>Cancelar</button>
    
    <div class="progress">
        <div id="progressBar" class="progress-bar" style="width: 0%">0%</div>
    </div>
    <div id="progressText" style="margin: 5px 0; font-size: 12px; color: #666;"></div>
    
    <div id="info"></div>
    <div id="filesList" style="margin-top: 20px; max-height: 200px; overflow-y: auto;"></div>
    
    <div id="log" class="log"></div>
    
    <script>
        const WS_URLS = @json($websocket_urls); // Array com 4 URLs dos workers
        const UPLOAD_TOKEN = @json($upload_token);
        const CHUNK_SIZE = 32 * 1024 * 1024; // 32MB
        const MAX_CONCURRENT_CHUNKS_PER_WORKER = 8; // Cada worker envia até 8 chunks simultaneamente
        const NUM_WORKERS = WS_URLS.length; // Total de workers
        const SMALL_FILE_THRESHOLD = 256 * 1024 * 1024; // 256MB - Arquivos menores são processados 1 por worker
        
        let workers = []; // Array de conexões WebSocket [ws1, ws2, ws3, ws4]
        let workerStatus = []; // Status de cada worker [{connected: bool, authenticated: bool, paused: bool, chunksInFlight: int}]
        let currentWorkerIndex = 0; // Índice do worker atual para distribuição round-robin
        
        let filesToUpload = []; // Lista de arquivos para upload [{file, relativePath, fileId}]
        let smallFilesQueue = []; // Fila de arquivos pequenos (≤256MB) aguardando processamento
        let largeFilesQueue = []; // Fila de arquivos grandes (>256MB) aguardando processamento
        let activeSmallFiles = new Set(); // Set de fileIds de arquivos pequenos atualmente sendo enviados
        let activeLargeFileId = null; // ID do arquivo grande atualmente sendo enviado (apenas 1 por vez)
        let fileSessions = {}; // {fileId: {sessionId, currentChunk, chunksInFlight, totalSize, sentSize, assignedWorker, isLargeFile, assignedWorkers}}
        let isUploading = false;
        let globalPaused = false; // Pausa global quando qualquer worker sinalizar
        let totalFiles = 0;
        let completedFiles = 0;
        let totalBytes = 0;
        let totalBytesSent = 0;
        let currentLargeFileIndex = 0; // Para distribuir chunks de arquivo grande entre workers
        let authenticatedCount = 0;
        
        function log(msg) {
            const logEl = document.getElementById('log');
            logEl.innerHTML += `[${new Date().toLocaleTimeString()}] ${msg}<br>`;
            logEl.scrollTop = logEl.scrollHeight;
        }

        function updateStatus(status, text) {
            const statusEl = document.getElementById('status');
            statusEl.className = `status ${status}`;
            statusEl.textContent = text;
        }
        
        function formatSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        function updateProgress() {
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            
            if (totalBytes === 0) {
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                if (progressText) progressText.textContent = 'Aguardando...';
                return;
            }
            
            const percent = (totalBytesSent / totalBytes) * 100;
            progressBar.style.width = percent + '%';
            progressBar.textContent = percent.toFixed(1) + '%';
            
            if (progressText) {
                progressText.textContent = `Arquivos: ${completedFiles}/${totalFiles} | Enviado: ${formatSize(totalBytesSent)} / Total: ${formatSize(totalBytes)} (${percent.toFixed(1)}%)`;
            }
        }

        function areWorkersReady() {
            if (workerStatus.length < NUM_WORKERS) return false;
            return workerStatus.every(status => status.authenticated);
        }

        function updateStartButtonState() {
            const startBtn = document.getElementById('startBtn');
            if (!startBtn) {
                return;
            }
            startBtn.disabled = !(areWorkersReady() && filesToUpload.length > 0 && !isUploading);
        }
        
        function connect() {
            if (!UPLOAD_TOKEN) {
                log('❌ Token de upload indisponível. Recarregue a página.');
                updateStatus('disconnected', 'Token de upload ausente');
                return;
            }
            updateStatus('connecting', 'Conectando aos workers...');
            log(`Conectando a ${NUM_WORKERS} workers...`);
            
            // Fecha conexões anteriores se existirem
            workers.forEach((ws) => {
                if (ws) {
                    try {
                        if (ws.readyState === WebSocket.OPEN || ws.readyState === WebSocket.CONNECTING) {
                            ws.close();
                        }
                    } catch (e) {
                        // Ignora
                    }
                }
            });
            
            workers = [];
            workerStatus = [];
            authenticatedCount = 0;
            updateStartButtonState();
            
            // Cria conexão para cada worker
            for (let i = 0; i < NUM_WORKERS; i++) {
                const wsUrl = WS_URLS[i];
                log(`Conectando ao Worker ${i + 1} (${wsUrl})...`);
                
                try {
                    const ws = new WebSocket(wsUrl);
                    
                    // Inicializa status do worker
                    workerStatus[i] = {
                        connected: false,
                        authenticated: false,
                        paused: false,
                        chunksInFlight: 0
                    };
                    
                    ws.onopen = () => {
                        console.log(`✅ Worker ${i + 1} conectado! Autenticando...`);
                        updateStatus('connecting', `Autenticando workers...`);
                        ws.send(JSON.stringify({
                            type: 'auth',
                            token: UPLOAD_TOKEN
                        }));
                    };
                    
                    ws.onmessage = (event) => {
                        handleWorkerMessage(i, event);
                    };
                    
                    ws.onerror = (error) => {
                        console.error(`❌ Erro no Worker ${i + 1}:`, error);
                        log(`❌ Erro no Worker ${i + 1}`);
                        if (workerStatus[i].authenticated) {
                            authenticatedCount = Math.max(authenticatedCount - 1, 0);
                        }
                        workerStatus[i].connected = false;
                        workerStatus[i].authenticated = false;
                        updateStartButtonState();
                    };
                    
                    ws.onclose = (event) => {
                        console.log(`⚠️ Worker ${i + 1} desconectado (code: ${event.code})`);
                        log(`⚠️ Worker ${i + 1} desconectado`);
                        if (workerStatus[i].authenticated) {
                            authenticatedCount = Math.max(authenticatedCount - 1, 0);
                        }
                        workerStatus[i].connected = false;
                        workerStatus[i].authenticated = false;
                        updateStartButtonState();
                        
                        // Tenta reconectar após 3 segundos
                        if (!isUploading && event.code !== 1000) {
                            setTimeout(() => {
                                if (!isUploading) {
                                    try {
                                        connect();
                                    } catch (err) {
                                        log(`❌ Erro ao reconectar: ${err.message}`);
                                    }
                                }
                            }, 3000);
                        }
                    };
                    
                    workers[i] = ws;
                } catch (error) {
                    log(`❌ Erro ao criar conexão Worker ${i + 1}: ${error.message}`);
                    workerStatus[i] = { connected: false, authenticated: false, paused: false, chunksInFlight: 0 };
                    updateStartButtonState();
                }
            }
        }
        
        function handleWorkerMessage(workerIndex, event) {
            // Verifica se é binário ou texto
            if (event.data instanceof ArrayBuffer || event.data instanceof Blob) {
                log(`📨 Mensagem binária recebida do Worker ${workerIndex + 1} (não esperado do servidor)`);
                return;
            }
            
            console.log(`📨 Mensagem do Worker ${workerIndex + 1}:`, event.data);
            try {
                const data = JSON.parse(event.data);
                log(`📨 Worker ${workerIndex + 1} - ${data.type}`);
                
                switch(data.type) {
                    case 'auth_ok':
                        if (!workerStatus[workerIndex].authenticated) {
                            authenticatedCount++;
                        }
                        workerStatus[workerIndex].authenticated = true;
                        workerStatus[workerIndex].connected = true;
                        if (areWorkersReady()) {
                            updateStatus('connected', `Conectado e autenticado a ${NUM_WORKERS} workers`);
                            log(`✅ Todos os ${NUM_WORKERS} workers autenticados!`);
                        } else {
                            log(`🔐 Worker ${workerIndex + 1} autenticado`);
                        }
                        updateStartButtonState();
                        break;
                    case 'auth_error':
                        if (workerStatus[workerIndex].authenticated) {
                            authenticatedCount = Math.max(authenticatedCount - 1, 0);
                        }
                        workerStatus[workerIndex].authenticated = false;
                        workerStatus[workerIndex].connected = false;
                        log(`❌ Autenticação falhou no Worker ${workerIndex + 1}: ${data.message || 'Token inválido'}`);
                        updateStatus('disconnected', 'Falha de autenticação');
                        try {
                            workers[workerIndex]?.close();
                        } catch (e) {
                            console.error(e);
                        }
                        updateStartButtonState();
                        break;
                    case 'connected':
                        log(`Worker ${workerIndex + 1} confirmou conexão`);
                        break;
                    case 'upload_started':
                        const fileId = data.file_id;
                        if (fileSessions[fileId]) {
                            const session = fileSessions[fileId];
                            session.sessionId = data.session_id;
                            
                            if (session.isLargeFile) {
                                // Arquivos grandes: não atribui worker específico (usa os 4)
                                log(`📤 Upload iniciado (arquivo GRANDE): ${data.relative_path || data.file_name} - usando todos os 4 workers`);
                            } else {
                                // Arquivos pequenos: atribui worker específico
                                session.assignedWorker = workerIndex;
                                log(`📤 Upload iniciado (arquivo PEQUENO) no Worker ${workerIndex + 1}: ${data.relative_path || data.file_name}`);
                            }
                            
                            // Inicia envio de chunks
                            sendNextChunk(fileId);
                        }
                        break;
                    case 'chunk_metadata_received':
                        // Metadados confirmados
                        break;
                    case 'chunk_received':
                        const chunkFileId = data.file_id;
                        if (fileSessions[chunkFileId]) {
                            // Decrementa chunks em voo do worker
                            workerStatus[workerIndex].chunksInFlight = Math.max(0, workerStatus[workerIndex].chunksInFlight - 1);
                            fileSessions[chunkFileId].chunksInFlight = Math.max(0, fileSessions[chunkFileId].chunksInFlight - 1);
                            
                            // Verifica backpressure - se fila cheia, pausa TODOS os workers
                            if (data.can_resume_sending === false || data.queue_size >= data.max_queue_size) {
                                log(`⏸️ Fila cheia no Worker ${workerIndex + 1} (${data.queue_size}/${data.max_queue_size}). Pausando todos os workers...`);
                                globalPaused = true;
                                workerStatus.forEach((status, idx) => {
                                    workerStatus[idx].paused = true;
                                });
                            } else {
                                // Fila liberada - pode retomar
                                if (globalPaused && data.can_resume_sending === true) {
                                    log(`▶️ Fila liberada no Worker ${workerIndex + 1}. Retomando todos os workers...`);
                                    globalPaused = false;
                                    workerStatus.forEach((status, idx) => {
                                        workerStatus[idx].paused = false;
                                    });
                                }
                            }
                            
                            // Continua enviando se não estiver pausado
                            if (!globalPaused && !workerStatus[workerIndex].paused) {
                                sendNextChunk(chunkFileId);
                            }
                        }
                        break;
                    case 'chunk_rejected':
                        const rejectedFileId = data.file_id;
                        log(`⚠️ Worker ${workerIndex + 1} - Chunk #${data.sequence} rejeitado: ${data.message || data.reason}`);
                        if (fileSessions[rejectedFileId]) {
                            workerStatus[workerIndex].chunksInFlight = Math.max(0, workerStatus[workerIndex].chunksInFlight - 1);
                            fileSessions[rejectedFileId].chunksInFlight = Math.max(0, fileSessions[rejectedFileId].chunksInFlight - 1);
                            
                            // Tenta reenviar após um delay
                            setTimeout(() => {
                                if (fileSessions[rejectedFileId] && isUploading && !globalPaused) {
                                    const fileInfo = filesToUpload.find(f => f.fileId === rejectedFileId);
                                    if (fileInfo) {
                                        const chunkIndex = data.sequence;
                                        const offset = chunkIndex * CHUNK_SIZE;
                                        const chunk = fileInfo.file.slice(offset, offset + CHUNK_SIZE);
                                        if (chunk.size > 0) {
                                            prepareAndSendChunk(rejectedFileId, chunk, chunkIndex, workerIndex);
                                        }
                                    }
                                }
                            }, 200);
                        }
                        break;
                    case 'file_completed':
                        const completedFileId = data.file_id;
                        completedFiles++;
                        log(`✅ Worker ${workerIndex + 1} - Arquivo concluído: ${data.relative_path || data.file_name}`);
                        
                        if (fileSessions[completedFileId]) {
                            const session = fileSessions[completedFileId];
                            session.sentSize = session.totalSize;
                            updateFileProgress(completedFileId);
                            updateFileStatus(completedFileId, 'completed');
                            
                            // Se era arquivo pequeno, remove da lista de ativos e libera worker
                            if (!session.isLargeFile) {
                                activeSmallFiles.delete(completedFileId);
                                
                                // Inicia próximo arquivo pequeno na fila
                                startNextSmallFiles();
                                
                                // Verifica se pode iniciar arquivos grandes (quando não há pequenos)
                                checkAndStartLargeFiles();
                            } else {
                                // Arquivo grande completou - libera e verifica se pode iniciar próximo
                                if (activeLargeFileId === completedFileId) {
                                    activeLargeFileId = null;
                                }
                                // Verifica se pode iniciar próximo arquivo grande
                                checkAndStartLargeFiles();
                            }
                        }
                        
                        updateProgress();
                        
                        // Verifica se todos os arquivos foram concluídos
                        if (completedFiles >= totalFiles) {
                            updateStatus('connected', 'Todos os arquivos foram enviados!');
                            isUploading = false;
                            document.getElementById('cancelBtn').disabled = true;
                            updateStartButtonState();
                            log(`✅ Upload completo! ${completedFiles} arquivo(s) enviado(s)`);
                        }
                        break;
                    case 'error':
                        log(`❌ Worker ${workerIndex + 1} - Erro: ${data.message}`);
                        if (data.file_id && fileSessions[data.file_id]) {
                            workerStatus[workerIndex].chunksInFlight = Math.max(0, workerStatus[workerIndex].chunksInFlight - 1);
                            fileSessions[data.file_id].chunksInFlight = Math.max(0, fileSessions[data.file_id].chunksInFlight - 1);
                        }
                        break;
                }
            } catch (e) {
                log(`❌ Erro ao processar mensagem do Worker ${workerIndex + 1}: ${e.message}`);
                console.error('Erro ao processar:', e);
            }
        }
        
        async function calculateHash(arrayBuffer) {
            const hashBuffer = await crypto.subtle.digest('SHA-256', arrayBuffer);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
        }

        function getFirstConnectedWorker() {
            for (let i = 0; i < NUM_WORKERS; i++) {
                if (workerStatus[i]?.connected) {
                    return i;
                }
            }
            return 0;
        }
        
        // Seleciona próximo worker disponível (round-robin)
        function getNextAvailableWorker() {
            // Encontra worker com menos chunks em voo e não pausado
            let bestWorker = -1;
            let minChunks = Infinity;
            
            for (let i = 0; i < NUM_WORKERS; i++) {
                if (workerStatus[i].connected && !workerStatus[i].paused && !globalPaused) {
                    if (workerStatus[i].chunksInFlight < MAX_CONCURRENT_CHUNKS_PER_WORKER) {
                        if (workerStatus[i].chunksInFlight < minChunks) {
                            minChunks = workerStatus[i].chunksInFlight;
                            bestWorker = i;
                        }
                    }
                }
            }
            
            // Se não encontrou, tenta round-robin
            if (bestWorker === -1) {
                for (let i = 0; i < NUM_WORKERS; i++) {
                    currentWorkerIndex = (currentWorkerIndex + 1) % NUM_WORKERS;
                    if (workerStatus[currentWorkerIndex].connected && !workerStatus[currentWorkerIndex].paused && !globalPaused) {
                        if (workerStatus[currentWorkerIndex].chunksInFlight < MAX_CONCURRENT_CHUNKS_PER_WORKER) {
                            return currentWorkerIndex;
                        }
                    }
                }
            }
            
            return bestWorker;
        }
        
        // Envia próximo chunk do arquivo atual
        async function sendNextChunk(fileId) {
            if (!isUploading || globalPaused) return;
            
            const fileSession = fileSessions[fileId];
            if (!fileSession) return;
            
            const fileInfo = filesToUpload.find(f => f.fileId === fileId);
            if (!fileInfo) return;
            
            let workerIndex;
            
            if (fileSession.isLargeFile) {
                // Arquivos grandes: distribui chunks entre os 4 workers (round-robin)
                // Encontra worker disponível entre os 4 workers atribuídos
                let foundWorker = false;
                for (let attempt = 0; attempt < 4; attempt++) {
                    const candidateIndex = currentLargeFileIndex % NUM_WORKERS;
                    currentLargeFileIndex++;
                    
                    if (workerStatus[candidateIndex]?.connected && 
                        !workerStatus[candidateIndex]?.paused &&
                        workerStatus[candidateIndex].chunksInFlight < MAX_CONCURRENT_CHUNKS_PER_WORKER) {
                        workerIndex = candidateIndex;
                        foundWorker = true;
                        break;
                    }
                }
                
                // Se nenhum worker disponível, tenta novamente depois
                if (!foundWorker) {
                    setTimeout(() => sendNextChunk(fileId), 100);
                    return;
                }
            } else {
                // Arquivos pequenos: usa worker fixo atribuído
                workerIndex = fileSession.assignedWorker;
                
                if (workerIndex === undefined || workerIndex === null || 
                    !workerStatus[workerIndex]?.connected || 
                    workerStatus[workerIndex]?.paused ||
                    workerStatus[workerIndex]?.chunksInFlight >= MAX_CONCURRENT_CHUNKS_PER_WORKER) {
                    // Worker não disponível, tenta novamente depois
                    setTimeout(() => sendNextChunk(fileId), 100);
                    return;
                }
            }
            
            const offset = fileSession.currentChunk * CHUNK_SIZE;
            const chunk = fileInfo.file.slice(offset, offset + CHUNK_SIZE);
            
            if (chunk.size === 0) {
                // Arquivo completo - verifica se todos os chunks foram confirmados
                if (fileSession.chunksInFlight === 0) {
                    log(`📤 Finalizando arquivo: ${fileInfo.relativePath}`);
                    // Para arquivos grandes, pode usar qualquer worker para finalizar
                    // Para arquivos pequenos, usa o worker atribuído
                    const preferredWorker = fileSession.isLargeFile
                        ? (fileSession.lastWorker ?? getFirstConnectedWorker())
                        : fileSession.assignedWorker;
                    const finalizeWorker = (preferredWorker !== undefined && preferredWorker !== null)
                        ? preferredWorker
                        : getFirstConnectedWorker();
                    const ws = workers[finalizeWorker];
                    if (ws && ws.readyState === WebSocket.OPEN) {
                        ws.send(JSON.stringify({ 
                            type: 'finalize',
                            file_id: fileId,
                            session_id: fileSession.sessionId
                        }));
                    }
                }
                return;
            }
            
            const chunkIndex = fileSession.currentChunk;
            fileSession.currentChunk++;
            fileSession.chunksInFlight++;
            workerStatus[workerIndex].chunksInFlight++;
            
            const fileType = fileSession.isLargeFile ? 'GRANDE' : 'PEQUENO';
            log(`📤 Enviando chunk #${chunkIndex} de arquivo ${fileType}: ${fileInfo.relativePath} via Worker ${workerIndex + 1} (${(chunk.size / 1024 / 1024).toFixed(2)}MB)...`);
            
            prepareAndSendChunk(fileId, chunk, chunkIndex, workerIndex).then(() => {
                // Continua enviando se não estiver pausado
                if (!globalPaused && !workerStatus[workerIndex].paused) {
                    sendNextChunk(fileId);
                }
            }).catch((error) => {
                fileSession.chunksInFlight = Math.max(0, fileSession.chunksInFlight - 1);
                workerStatus[workerIndex].chunksInFlight = Math.max(0, workerStatus[workerIndex].chunksInFlight - 1);
                log(`❌ Erro ao enviar chunk #${chunkIndex} de ${fileInfo.relativePath}: ${error.message}`);
            });
        }
        
        async function prepareAndSendChunk(fileId, chunk, chunkIndex, workerIndex) {
            const fileSession = fileSessions[fileId];
            const fileInfo = filesToUpload.find(f => f.fileId === fileId);
            const ws = workers[workerIndex];
            
            if (!ws || ws.readyState !== WebSocket.OPEN) {
                throw new Error(`Worker ${workerIndex + 1} não está conectado`);
            }
            
            if (fileSession) {
                fileSession.lastWorker = workerIndex;
            }
            
            const arrayBuffer = await chunk.arrayBuffer();
            const hash = await calculateHash(arrayBuffer);
            
            // Envia metadados do chunk
            ws.send(JSON.stringify({
                type: 'chunk_metadata',
                file_id: fileId,
                session_id: fileSession.sessionId,
                sequence: chunkIndex,
                hash: hash
            }));
            
            // Envia dados binários
            const blob = new Blob([arrayBuffer], { type: 'application/octet-stream' });
            ws.send(blob);
            
            fileSession.sentSize += arrayBuffer.byteLength;
            totalBytesSent += arrayBuffer.byteLength;
            updateProgress();
            updateFileProgress(fileId);
        }
        
        function startUpload() {
            if (filesToUpload.length === 0) {
                log('❌ Nenhum arquivo selecionado');
                return;
            }
            
            if (!areWorkersReady()) {
                log('❌ Nem todos os workers estão autenticados. Aguarde...');
                return;
            }
            
            isUploading = true;
            globalPaused = false;
            completedFiles = 0;
            totalFiles = filesToUpload.length;
            totalBytes = filesToUpload.reduce((sum, f) => sum + f.file.size, 0);
            totalBytesSent = 0;
            activeSmallFiles.clear();
            activeLargeFileId = null;
            currentLargeFileIndex = 0;
            
            // Reseta status dos workers
            workerStatus.forEach((status, idx) => {
                workerStatus[idx].paused = false;
                workerStatus[idx].chunksInFlight = 0;
            });
            
            // Separa arquivos pequenos (≤256MB) e grandes (>256MB)
            const smallFiles = filesToUpload.filter(f => f.file.size <= SMALL_FILE_THRESHOLD);
            const largeFiles = filesToUpload.filter(f => f.file.size > SMALL_FILE_THRESHOLD);
            
            log(`📊 Arquivos pequenos (≤256MB): ${smallFiles.length} | Arquivos grandes (>256MB): ${largeFiles.length}`);
            
            // Inicializa filas
            smallFilesQueue = [...smallFiles];
            largeFilesQueue = [...largeFiles];
            
            // Inicializa fileSessions para todos os arquivos
            filesToUpload.forEach((fileInfo) => {
                const fileId = fileInfo.fileId;
                const isLargeFile = fileInfo.file.size > SMALL_FILE_THRESHOLD;
                
                fileSessions[fileId] = {
                    sessionId: null,
                    currentChunk: 0,
                    chunksInFlight: 0,
                    totalSize: fileInfo.file.size,
                    sentSize: 0,
                    assignedWorker: null,
                    isLargeFile: isLargeFile,
                    assignedWorkers: isLargeFile ? [0, 1, 2, 3] : null, // Arquivos grandes usam todos os workers
                    lastWorker: null
                };
            });
            
            updateStatus('uploading', `Enviando ${totalFiles} arquivo(s) via ${NUM_WORKERS} workers...`);
            document.getElementById('cancelBtn').disabled = false;
            updateStartButtonState();
            
            // Inicia apenas os 4 primeiros arquivos pequenos (1 por worker)
            startNextSmallFiles();
            
            // Verifica se pode iniciar arquivos grandes (se não há pequenos)
            checkAndStartLargeFiles();
        }
        
        // Inicia os próximos arquivos pequenos (máximo 4, 1 por worker)
        function startNextSmallFiles() {
            // Encontra workers disponíveis (sem arquivo pequeno ativo)
            const availableWorkers = [];
            for (let i = 0; i < NUM_WORKERS; i++) {
                // Verifica se worker está conectado e não tem arquivo pequeno ativo
                if (workerStatus[i].connected) {
                    let hasActiveSmallFile = false;
                    activeSmallFiles.forEach(fileId => {
                        const session = fileSessions[fileId];
                        if (session && session.assignedWorker === i && !session.isLargeFile) {
                            hasActiveSmallFile = true;
                        }
                    });
                    if (!hasActiveSmallFile) {
                        availableWorkers.push(i);
                    }
                }
            }
            
            // Inicia novos arquivos pequenos nos workers disponíveis
            let started = 0;
            while (smallFilesQueue.length > 0 && availableWorkers.length > 0 && started < availableWorkers.length) {
                const fileInfo = smallFilesQueue.shift();
                const fileId = fileInfo.fileId;
                const workerIndex = availableWorkers.shift();
                
                activeSmallFiles.add(fileId);
                fileSessions[fileId].assignedWorker = workerIndex;
                
                const ws = workers[workerIndex];
                if (ws && ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({
                        type: 'start_upload',
                        file_id: fileId,
                        file_name: fileInfo.file.name,
                        relative_path: fileInfo.relativePath,
                        total_size: fileInfo.file.size,
                        use_shared_queue: false // Arquivos pequenos não usam fila compartilhada
                    }));
                    log(`📤 Iniciando arquivo PEQUENO: ${fileInfo.relativePath} no Worker ${workerIndex + 1}`);
                    started++;
                } else {
                    log(`❌ Worker ${workerIndex + 1} não está conectado para arquivo ${fileInfo.relativePath}`);
                    smallFilesQueue.unshift(fileInfo); // Coloca de volta na fila
                    activeSmallFiles.delete(fileId);
                }
            }
        }
        
        // Verifica se pode iniciar arquivos grandes (apenas quando não há pequenos)
        function checkAndStartLargeFiles() {
            // Só inicia arquivos grandes se:
            // 1. Não há arquivos pequenos na fila
            // 2. Não há arquivos pequenos sendo enviados
            // 3. Não há arquivo grande sendo enviado atualmente
            if (smallFilesQueue.length === 0 && 
                activeSmallFiles.size === 0 && 
                activeLargeFileId === null &&
                largeFilesQueue.length > 0) {
                const fileInfo = largeFilesQueue.shift();
                const fileId = fileInfo.fileId;
                activeLargeFileId = fileId; // Marca como ativo
                
                log(`📤 Iniciando arquivo GRANDE: ${fileInfo.relativePath} (usando todos os 4 workers)`);
                
                // Arquivos grandes iniciam em todos os workers (mas envia start_upload em apenas 1)
                // O backend criará a fila compartilhada automaticamente se total_size > 1GB
                const ws = workers[0];
                if (ws && ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({
                        type: 'start_upload',
                        file_id: fileId,
                        file_name: fileInfo.file.name,
                        relative_path: fileInfo.relativePath,
                        total_size: fileInfo.file.size,
                        use_shared_queue: true // Força uso de fila compartilhada para arquivos grandes
                    }));
                }
            }
        }
        
        function updateFileProgress(fileId) {
            const fileSession = fileSessions[fileId];
            const fileInfo = filesToUpload.find(f => f.fileId === fileId);
            if (!fileSession || !fileInfo) return;
            
            const percent = fileSession.totalSize > 0 
                ? (fileSession.sentSize / fileSession.totalSize) * 100 
                : 0;
            
            const fileElement = document.getElementById(`file-${fileId}`);
            if (fileElement) {
                const progressBar = fileElement.querySelector('.file-progress');
                if (progressBar) {
                    progressBar.style.width = percent + '%';
                    progressBar.textContent = percent.toFixed(1) + '%';
                }
                const statusEl = fileElement.querySelector('.file-status');
                if (statusEl) {
                    statusEl.textContent = `Enviado: ${formatSize(fileSession.sentSize)} / ${formatSize(fileSession.totalSize)}`;
                }
            }
        }
        
        function updateFileStatus(fileId, status) {
            const fileElement = document.getElementById(`file-${fileId}`);
            if (fileElement) {
                const statusEl = fileElement.querySelector('.file-status');
                if (statusEl) {
                    if (status === 'completed') {
                        statusEl.textContent = '✅ Concluído';
                        statusEl.style.color = '#28a745';
                    }
                }
            }
        }
        
        function displayFilesList() {
            const filesListEl = document.getElementById('filesList');
            if (!filesListEl) return;
            
            if (filesToUpload.length === 0) {
                filesListEl.innerHTML = '';
                return;
            }
            
            let html = '<strong>Arquivos selecionados:</strong><br>';
            filesToUpload.forEach(fileInfo => {
                html += `
                    <div id="file-${fileInfo.fileId}" style="margin: 5px 0; padding: 5px; border: 1px solid #ddd; border-radius: 3px;">
                        <div style="font-weight: bold; font-size: 12px;">${fileInfo.relativePath}</div>
                        <div style="font-size: 11px; color: #666;">${formatSize(fileInfo.file.size)}</div>
                        <div class="file-progress" style="width: 0%; height: 4px; background: #007bff; margin-top: 3px; border-radius: 2px; transition: width 0.3s;">0%</div>
                        <div class="file-status" style="font-size: 10px; color: #666; margin-top: 2px;">Aguardando...</div>
                    </div>
                `;
            });
            filesListEl.innerHTML = html;
        }
        
        // Atualiza input quando modo muda
        document.querySelectorAll('input[name="uploadMode"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                const fileInput = document.getElementById('fileInput');
                if (e.target.value === 'folder') {
                    fileInput.setAttribute('webkitdirectory', '');
                    fileInput.setAttribute('directory', '');
                    fileInput.setAttribute('multiple', '');
                } else {
                    fileInput.removeAttribute('webkitdirectory');
                    fileInput.removeAttribute('directory');
                    fileInput.setAttribute('multiple', '');
                }
                // Limpa seleção anterior
                fileInput.value = '';
                filesToUpload = [];
                document.getElementById('info').innerHTML = '';
                document.getElementById('filesList').innerHTML = '';
                updateStartButtonState();
            });
        });
        
        document.getElementById('fileInput').addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            if (files.length === 0) {
                filesToUpload = [];
                document.getElementById('info').innerHTML = '';
                document.getElementById('filesList').innerHTML = '';
                updateStartButtonState();
                return;
            }
            
            const uploadMode = document.querySelector('input[name="uploadMode"]:checked').value;
            
            filesToUpload = files.map((file, index) => {
                let relativePath;
                
                if (uploadMode === 'folder') {
                    // Modo pasta: usa webkitRelativePath (preserva estrutura de pastas)
                    relativePath = file.webkitRelativePath || file.name;
                } else {
                    // Modo arquivos: usa apenas o nome do arquivo (sem estrutura de pastas)
                    relativePath = file.name;
                }
                
                return {
                    fileId: `file_${Date.now()}_${index}`,
                    file: file,
                    relativePath: relativePath,
                    fileName: file.name
                };
            });
            
            const totalSize = filesToUpload.reduce((sum, f) => sum + f.file.size, 0);
            const modeText = uploadMode === 'folder' ? 'Pasta' : 'Arquivos';
            document.getElementById('info').innerHTML = `
                <strong>Modo:</strong> ${modeText}<br>
                <strong>Arquivos:</strong> ${files.length}<br>
                <strong>Tamanho total:</strong> ${formatSize(totalSize)}<br>
                <strong>Chunks totais:</strong> ${filesToUpload.reduce((sum, f) => sum + Math.ceil(f.file.size / CHUNK_SIZE), 0)}
            `;
            
            displayFilesList();
            updateStartButtonState();
        });
        
        document.getElementById('startBtn').addEventListener('click', () => {
            if (!areWorkersReady()) {
                log('❌ Nem todos os workers estão autenticados');
                return;
            }

            if (filesToUpload.length === 0) {
                log('❌ Nenhum arquivo selecionado');
                return;
            }

            startUpload();
        });
        
        document.getElementById('cancelBtn').addEventListener('click', () => {
            if (isUploading) {
                // Cancela todos os uploads em andamento em todos os workers
                Object.keys(fileSessions).forEach(fileId => {
                    const fileSession = fileSessions[fileId];
                    if (fileSession.sessionId !== null) {
                        // Para arquivos grandes, pode usar qualquer worker para cancelar
                        // Para arquivos pequenos, usa o worker atribuído
                        const preferredWorker = fileSession.isLargeFile
                            ? (fileSession.lastWorker ?? getFirstConnectedWorker())
                            : fileSession.assignedWorker;
                        const cancelWorker = (preferredWorker !== undefined && preferredWorker !== null)
                            ? preferredWorker
                            : getFirstConnectedWorker();
                        const ws = workers[cancelWorker];
                        if (ws && ws.readyState === WebSocket.OPEN) {
                            ws.send(JSON.stringify({ 
                                type: 'cancel',
                                file_id: fileId,
                                session_id: fileSession.sessionId
                            }));
                        }
                    }
                });
                
                isUploading = false;
                globalPaused = false;
                smallFilesQueue = [];
                largeFilesQueue = [];
                activeSmallFiles.clear();
                activeLargeFileId = null;
                document.getElementById('cancelBtn').disabled = true;
                updateStartButtonState();
                updateStatus('connected', 'Upload cancelado');
                log('❌ Upload cancelado');
            }
        });
        
        function initializeStreaming() {
            try {
                connect();
            } catch (error) {
                log(`❌ Falha ao conectar: ${error.message}`);
                updateStatus('disconnected', 'Falha ao conectar aos workers');
            }
        }

        // Conecta ao carregar
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeStreaming);
        } else {
            initializeStreaming();
        }
    </script>
</body>
</html>
