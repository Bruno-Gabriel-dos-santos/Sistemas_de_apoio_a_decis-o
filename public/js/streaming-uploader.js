(function (global) {
    'use strict';

    const GLOBAL_SLOT_STORAGE_KEY = '__streaming_upload_active_count';
    const GlobalSlotManager = (() => {
        let memoryCount = 0;

        function readStorage() {
            if (typeof global === 'undefined') {
                return memoryCount;
            }
            try {
                if (global.localStorage) {
                    const raw = global.localStorage.getItem(GLOBAL_SLOT_STORAGE_KEY);
                    const parsed = parseInt(raw, 10);
                    if (!Number.isNaN(parsed)) {
                        memoryCount = parsed;
                        return parsed;
                    }
                }
            } catch (err) {
                // Ignore storage errors (private mode, quota exceeded, etc.)
            }
            return memoryCount;
        }

        function writeStorage(value) {
            memoryCount = Math.max(0, value);
            if (typeof global === 'undefined') {
                return;
            }
            try {
                if (global.localStorage) {
                    global.localStorage.setItem(GLOBAL_SLOT_STORAGE_KEY, String(memoryCount));
                }
            } catch (err) {
                // Ignore storage errors
            }
        }

        return {
            getCount() {
                return readStorage();
            },
            increment() {
                const next = readStorage() + 1;
                writeStorage(next);
                return next;
            },
            decrement() {
                const next = Math.max(0, readStorage() - 1);
                writeStorage(next);
                return next;
            }
        };
    })();

    const DEFAULTS = {
        chunkSize: 32 * 1024 * 1024,
        largeFileThreshold: 256 * 1024 * 1024,
        maxConcurrentChunksPerWorker: 8,
        reconnectDelayMs: 3000,
        maxGlobalActiveUploads: 2,
        globalLimitMessage: 'Limite de uploads simultâneos atingido. Aguarde os envios em andamento terminarem antes de iniciar um novo.',
    };

    function generateFileId(index) {
        return `file_${Date.now()}_${index}_${Math.random().toString(16).slice(2)}`;
    }

    function sanitizeRelativePath(value) {
        if (!value) {
            return '';
        }
        const normalized = String(value).trim().replace(/\\/g, '/');
        return normalized.replace(/^\/+/g, '');
    }

    function ensurePlainObject(value) {
        if (!value || typeof value !== 'object') {
            return {};
        }
        return value;
    }

    async function sha256(buffer) {
        if (!global.crypto || !global.crypto.subtle) {
            throw new Error('API de criptografia indisponível para calcular hash.');
        }
        const hashBuffer = await global.crypto.subtle.digest('SHA-256', buffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map((b) => b.toString(16).padStart(2, '0')).join('');
    }

    class StreamingUploadClient {
        constructor(options) {
            if (!options || !Array.isArray(options.websocketUrls) || options.websocketUrls.length === 0) {
                throw new Error('É necessário informar pelo menos uma URL de WebSocket.');
            }
            if (!options.token) {
                throw new Error('Token de upload ausente.');
            }

            this.options = Object.assign({}, DEFAULTS, options);
            if (typeof global.STREAM_UPLOAD_MAX_ACTIVE === 'number') {
                const parsedLimit = Number(global.STREAM_UPLOAD_MAX_ACTIVE);
                if (!Number.isNaN(parsedLimit) && parsedLimit >= 0) {
                    this.options.maxGlobalActiveUploads = parsedLimit;
                }
            }
            if (typeof global.STREAM_UPLOAD_LIMIT_MESSAGE === 'string') {
                const message = global.STREAM_UPLOAD_LIMIT_MESSAGE.trim();
                if (message.length > 0) {
                    this.options.globalLimitMessage = message;
                }
            }
            this.websocketUrls = options.websocketUrls;
            this.token = options.token;
            this.workers = [];
            this.workerStatus = [];
            this.workerAssignments = [];
            this.listeners = {};
            this.fileSessions = new Map();
            this.connectedPromise = null;
            this.isUploading = false;
            this.globalPaused = false;
            this.batchOptions = null;
            this.hasGlobalSlot = false;
            this.beforeUnloadHandler = null;
            this.resetBatchState();
        }

        resetBatchState() {
            this.fileSessions.clear();
            this.filesToUpload = [];
            this.smallFilesQueue = [];
            this.largeFilesQueue = [];
            this.activeSmallFiles = new Set();
            this.activeLargeFileId = null;
            this.workerAssignments = new Array(this.websocketUrls.length).fill(null);
            this.totalFiles = 0;
            this.completedFiles = 0;
            this.totalBytes = 0;
            this.totalBytesSent = 0;
            this.currentLargeFileWorkerIndex = 0;
        }

        acquireGlobalSlot(limit) {
            if (this.hasGlobalSlot || !limit || limit <= 0) {
                return;
            }
            const active = GlobalSlotManager.getCount();
            if (active >= limit) {
                throw new Error(this.options.globalLimitMessage || 'Limite global de uploads atingido. Aguarde os envios em andamento terminarem.');
            }
            GlobalSlotManager.increment();
            this.hasGlobalSlot = true;
            if (typeof global !== 'undefined' && typeof global.addEventListener === 'function') {
                this.beforeUnloadHandler = () => {
                    this.releaseGlobalSlot();
                };
                global.addEventListener('beforeunload', this.beforeUnloadHandler);
            }
        }

        releaseGlobalSlot() {
            if (!this.hasGlobalSlot) {
                return;
            }
            GlobalSlotManager.decrement();
            this.hasGlobalSlot = false;
            if (this.beforeUnloadHandler && typeof global !== 'undefined' && typeof global.removeEventListener === 'function') {
                global.removeEventListener('beforeunload', this.beforeUnloadHandler);
            }
            this.beforeUnloadHandler = null;
        }

        on(event, callback) {
            if (typeof callback !== 'function') {
                return;
            }
            if (!this.listeners[event]) {
                this.listeners[event] = [];
            }
            this.listeners[event].push(callback);
        }

        emit(event, payload) {
            if (!this.listeners[event]) {
                return;
            }
            this.listeners[event].forEach((callback) => {
                try {
                    callback(payload);
                } catch (err) {
                    console.error(err);
                }
            });
        }

        ensureConnected() {
            if (this.connectedPromise) {
                return this.connectedPromise;
            }
            const promises = this.websocketUrls.map((url, index) => this.setupWorker(index, url));
            this.connectedPromise = Promise.all(promises);
            return this.connectedPromise;
        }

        areWorkersReady() {
            if (!this.workerStatus.length) {
                return false;
            }
            return this.workerStatus.every((status) => status && status.authenticated);
        }

        setupWorker(index, url) {
            return new Promise((resolve, reject) => {
                const ws = new WebSocket(url);
                ws.binaryType = 'arraybuffer';
                ws._pendingAuth = { resolve, reject };
                this.workers[index] = ws;
                this.workerStatus[index] = {
                    connected: false,
                    authenticated: false,
                    paused: false,
                    chunksInFlight: 0,
                };

                ws.onopen = () => {
                    this.workerStatus[index].connected = true;
                    try {
                        ws.send(JSON.stringify({
                            type: 'auth',
                            token: this.token,
                        }));
                    } catch (error) {
                        if (ws._pendingAuth && ws._pendingAuth.reject) {
                            ws._pendingAuth.reject(error);
                            ws._pendingAuth = null;
                        }
                    }
                };

                ws.onmessage = (event) => this.handleWorkerMessage(index, event);

                ws.onerror = (error) => {
                    if (ws._pendingAuth && ws._pendingAuth.reject) {
                        ws._pendingAuth.reject(error);
                        ws._pendingAuth = null;
                    } else {
                        this.handleWorkerRuntimeError(index, error);
                    }
                };

                ws.onclose = (event) => {
                    const assignedFileId = this.workerAssignments[index];
                    this.workerAssignments[index] = null;
                    const status = this.workerStatus[index];
                    if (status) {
                        status.connected = false;
                        status.authenticated = false;
                        status.paused = false;
                        status.chunksInFlight = 0;
                    }

                    if (ws._pendingAuth && ws._pendingAuth.reject) {
                        ws._pendingAuth.reject(new Error(`Worker ${index + 1} desconectado antes da autenticação.`));
                        ws._pendingAuth = null;
                        return;
                    }

                    if (assignedFileId && this.fileSessions.has(assignedFileId)) {
                        this.rejectFile(assignedFileId, new Error(`Worker ${index + 1} desconectado durante o upload.`));
                        this.startNextSmallFiles();
                    }

                    if (this.isUploading && (!event || event.code !== 1000)) {
                        this.failActiveUploads(new Error(`Worker ${index + 1} desconectado.`));
                    } else {
                        this.connectedPromise = null;
                    }
                };
            });
        }

        handleWorkerRuntimeError(workerIndex, error) {
            if (this.isUploading) {
                const normalizedError = error instanceof Error ? error : new Error('Erro no worker.');
                this.failActiveUploads(normalizedError);
            } else {
                this.connectedPromise = null;
            }
        }

        handleWorkerMessage(workerIndex, event) {
            const raw = event && typeof event === 'object' && 'data' in event ? event.data : event;
            if (raw instanceof ArrayBuffer || raw instanceof Blob) {
                return;
            }
            if (typeof raw !== 'string') {
                return;
            }

            let data;
            try {
                data = JSON.parse(raw);
            } catch (err) {
                console.error('Mensagem inválida do worker', err);
                return;
            }

            const status = this.workerStatus[workerIndex];
            if (!status) {
                return;
            }

            switch (data.type) {
                case 'hello':
                case 'connected':
                    break;
                case 'auth_ok':
                    status.connected = true;
                    status.authenticated = true;
                    if (this.workers[workerIndex] && this.workers[workerIndex]._pendingAuth) {
                        this.workers[workerIndex]._pendingAuth.resolve();
                        this.workers[workerIndex]._pendingAuth = null;
                    }
                    this.emit('worker_ready', { workerIndex });
                    break;
                case 'auth_error': {
                    status.authenticated = false;
                    status.connected = false;
                    const error = new Error(data.message || 'Falha de autenticação.');
                    if (this.workers[workerIndex] && this.workers[workerIndex]._pendingAuth) {
                        this.workers[workerIndex]._pendingAuth.reject(error);
                        this.workers[workerIndex]._pendingAuth = null;
                    } else {
                        this.failActiveUploads(error);
                    }
                    break;
                }
                case 'upload_started':
                    this.handleUploadStarted(workerIndex, data);
                    break;
                case 'chunk_metadata_received':
                    break;
                case 'chunk_received':
                    this.handleChunkReceived(workerIndex, data);
                    break;
                case 'chunk_rejected':
                    this.handleChunkRejected(workerIndex, data);
                    break;
                case 'file_completed':
                    this.handleFileCompleted(workerIndex, data);
                    break;
                case 'upload_cancelled':
                    this.handleUploadCancelled(workerIndex, data);
                    break;
                case 'error':
                    this.handleWorkerError(workerIndex, data);
                    break;
                default:
                    break;
            }
        }

        handleUploadStarted(workerIndex, data) {
            const session = this.fileSessions.get(data.file_id);
            if (!session) {
                return;
            }
            session.sessionId = data.session_id;
            this.primeFileChunks(session.fileId);
        }

        primeFileChunks(fileId) {
            const session = this.fileSessions.get(fileId);
            if (!session || !session.sessionId) {
                return;
            }
            const multiplier = session.isLargeFile ? this.workers.length : 1;
            const attempts = Math.max(1, multiplier * this.options.maxConcurrentChunksPerWorker);
            for (let i = 0; i < attempts; i++) {
                this.sendNextChunk(fileId);
            }
        }

        sendNextChunk(fileId) {
            if (!this.isUploading || this.globalPaused) {
                return;
            }
            const session = this.fileSessions.get(fileId);
            if (!session || !session.sessionId) {
                return;
            }

            if (session.currentChunk >= session.totalChunks) {
                if (session.chunksInFlight === 0) {
                    this.finalizeFile(fileId);
                }
                return;
            }

            let workerIndex;
            if (session.isLargeFile) {
                workerIndex = this.getWorkerForLargeFile();
            } else {
                workerIndex = session.assignedWorker;
            }

            if (workerIndex === undefined || workerIndex === null || workerIndex === -1) {
                setTimeout(() => this.sendNextChunk(fileId), 100);
                return;
            }

            const workerState = this.workerStatus[workerIndex];
            if (!this.canUseWorker(workerIndex) || workerState.chunksInFlight >= this.options.maxConcurrentChunksPerWorker) {
                setTimeout(() => this.sendNextChunk(fileId), 50);
                return;
            }

            const start = session.currentChunk * this.options.chunkSize;
            const end = Math.min(start + this.options.chunkSize, session.totalSize);
            const chunk = session.file.slice(start, end);
            if (!chunk || chunk.size === 0) {
                if (session.chunksInFlight === 0) {
                    this.finalizeFile(fileId);
                }
                return;
            }

            const chunkIndex = session.currentChunk;
            session.currentChunk += 1;
            session.chunksInFlight += 1;
            workerState.chunksInFlight += 1;

            this.prepareAndSendChunk(workerIndex, session, chunk, chunkIndex)
                .then(() => {
                    if (!this.globalPaused && !this.workerStatus[workerIndex].paused) {
                        this.sendNextChunk(fileId);
                    }
                })
                .catch((error) => {
                    session.chunksInFlight = Math.max(0, session.chunksInFlight - 1);
                    workerState.chunksInFlight = Math.max(0, workerState.chunksInFlight - 1);
                    this.rejectFile(session, error);
                });
        }

        getWorkerForLargeFile() {
            const count = this.workers.length;
            for (let i = 0; i < count; i++) {
                const workerIndex = (this.currentLargeFileWorkerIndex + i) % count;
                const state = this.workerStatus[workerIndex];
                if (this.canUseWorker(workerIndex) && state.chunksInFlight < this.options.maxConcurrentChunksPerWorker) {
                    this.currentLargeFileWorkerIndex = (workerIndex + 1) % count;
                    return workerIndex;
                }
            }
            return -1;
        }

        canUseWorker(workerIndex) {
            const worker = this.workers[workerIndex];
            const state = this.workerStatus[workerIndex];
            return Boolean(
                worker &&
                worker.readyState === WebSocket.OPEN &&
                state &&
                state.authenticated &&
                !state.paused
            );
        }

        getFirstConnectedWorker() {
            for (let i = 0; i < this.workers.length; i++) {
                if (this.canUseWorker(i)) {
                    return i;
                }
            }
            return -1;
        }

        async prepareAndSendChunk(workerIndex, session, chunk, chunkIndex) {
            const worker = this.workers[workerIndex];
            if (!worker || worker.readyState !== WebSocket.OPEN) {
                throw new Error(`Worker ${workerIndex + 1} indisponível.`);
            }

            session.lastWorker = workerIndex;
            const buffer = await chunk.arrayBuffer();
            const hash = await sha256(buffer);

            worker.send(JSON.stringify({
                type: 'chunk_metadata',
                file_id: session.fileId,
                session_id: session.sessionId,
                sequence: chunkIndex,
                hash,
            }));
            worker.send(new Blob([buffer], { type: 'application/octet-stream' }));

            session.sentSize += buffer.byteLength;
            this.totalBytesSent += buffer.byteLength;
            this.updateFileProgress(session.fileId);
            this.emit('total_progress', {
                sent: this.totalBytesSent,
                total: this.totalBytes,
            });
        }

        finalizeFile(fileId) {
            const session = this.fileSessions.get(fileId);
            if (!session || session.finalizeRequested) {
                return;
            }
            session.finalizeRequested = true;

            let workerIndex = session.isLargeFile
                ? session.lastWorker ?? this.getFirstConnectedWorker()
                : session.assignedWorker;
            if ((workerIndex === undefined || workerIndex === null || workerIndex === -1) || !this.canUseWorker(workerIndex)) {
                workerIndex = this.getFirstConnectedWorker();
            }

            if (workerIndex === -1) {
                this.rejectFile(session, new Error('Nenhum worker disponível para finalizar upload.'));
                return;
            }

            try {
                this.workers[workerIndex].send(JSON.stringify({
                    type: 'finalize',
                    file_id: session.fileId,
                    session_id: session.sessionId,
                }));
            } catch (error) {
                this.rejectFile(session, error);
            }
        }

        handleChunkReceived(workerIndex, data) {
            const session = this.fileSessions.get(data.file_id);
            if (!session) {
                return;
            }
            const state = this.workerStatus[workerIndex];
            state.chunksInFlight = Math.max(0, state.chunksInFlight - 1);
            session.chunksInFlight = Math.max(0, session.chunksInFlight - 1);

            const queueFull = data.can_resume_sending === false || (
                typeof data.queue_size === 'number' &&
                typeof data.max_queue_size === 'number' &&
                data.queue_size >= data.max_queue_size
            );

            if (queueFull && !this.globalPaused) {
                this.globalPaused = true;
                this.workerStatus.forEach((workerState) => {
                    if (workerState) {
                        workerState.paused = true;
                    }
                });
                this.emit('paused', { workerIndex, reason: 'queue_full' });
            } else if (this.globalPaused && data.can_resume_sending !== false) {
                this.globalPaused = false;
                this.workerStatus.forEach((workerState) => {
                    if (workerState) {
                        workerState.paused = false;
                    }
                });
                this.emit('resumed', { workerIndex });
                this.resumePendingChunks();
            }

            if (!this.globalPaused && !state.paused) {
                this.sendNextChunk(data.file_id);
            }

            if (session.currentChunk >= session.totalChunks && session.chunksInFlight === 0) {
                this.finalizeFile(data.file_id);
            }
        }

        resumePendingChunks() {
            this.activeSmallFiles.forEach((fileId) => this.primeFileChunks(fileId));
            if (this.activeLargeFileId) {
                this.primeFileChunks(this.activeLargeFileId);
            }
        }

        handleChunkRejected(workerIndex, data) {
            const session = this.fileSessions.get(data.file_id);
            if (!session) {
                return;
            }
            const state = this.workerStatus[workerIndex];
            state.chunksInFlight = Math.max(0, state.chunksInFlight - 1);
            session.chunksInFlight = Math.max(0, session.chunksInFlight - 1);

            setTimeout(() => {
                if (!this.isUploading || !this.fileSessions.has(data.file_id)) {
                    return;
                }
                const start = data.sequence * this.options.chunkSize;
                const end = Math.min(start + this.options.chunkSize, session.totalSize);
                const chunk = session.file.slice(start, end);
                this.prepareAndSendChunk(workerIndex, session, chunk, data.sequence)
                    .catch((error) => this.rejectFile(session, error));
            }, 200);
        }

        handleFileCompleted(workerIndex, data) {
            const session = this.fileSessions.get(data.file_id);
            if (!session) {
                return;
            }
            this.completedFiles += 1;

            if (!session.isLargeFile && session.assignedWorker !== null) {
                this.workerAssignments[session.assignedWorker] = null;
                this.activeSmallFiles.delete(session.fileId);
            }

            if (session.isLargeFile && this.activeLargeFileId === session.fileId) {
                this.activeLargeFileId = null;
            }

            this.resolveFile(session, {
                fileId: session.fileId,
                filePath: data.file_path || null,
                relativePath: data.relative_path || session.relativePath,
                context: data.context || null,
            });

            this.fileSessions.delete(session.fileId);

            if (!session.isLargeFile) {
                this.startNextSmallFiles();
            } else {
                this.checkAndStartLargeFiles();
            }

            if (this.completedFiles >= this.totalFiles) {
                this.emit('batch_completed', { total: this.completedFiles });
            }
        }

        handleUploadCancelled(workerIndex, data) {
            const session = this.fileSessions.get(data.file_id);
            if (!session) {
                return;
            }
            this.rejectFile(session, new Error(data.message || 'Upload cancelado pelo servidor.'));
        }

        handleWorkerError(workerIndex, data) {
            const session = data.file_id ? this.fileSessions.get(data.file_id) : null;
            const error = new Error(data.message || `Erro no worker ${workerIndex + 1}.`);
            if (session) {
                this.rejectFile(session, error);
            } else {
                this.failActiveUploads(error);
            }
        }

        updateFileProgress(fileId) {
            const session = this.fileSessions.get(fileId);
            if (!session || !session.descriptor) {
                return;
            }
            const percent = session.totalSize > 0
                ? Math.min(100, (session.sentSize / session.totalSize) * 100)
                : 0;

            if (typeof this.batchOptions?.onFileProgress === 'function') {
                try {
                    this.batchOptions.onFileProgress({
                        descriptor: session.descriptor,
                        percent: Number(percent.toFixed(4)),
                        fileId,
                    });
                } catch (err) {
                    console.error(err);
                }
            }

            this.emit('file_progress', {
                descriptor: session.descriptor,
                percent,
                fileId,
            });
        }

        resolveFile(session, result) {
            if (session.resolve) {
                try {
                    session.resolve(result);
                } catch (err) {
                    console.error(err);
                }
            }

            if (typeof this.batchOptions?.onFileComplete === 'function') {
                try {
                    this.batchOptions.onFileComplete({
                        descriptor: session.descriptor,
                        result,
                    });
                } catch (err) {
                    console.error(err);
                }
            }

            this.emit('file_completed', {
                descriptor: session.descriptor,
                result,
            });
        }

        rejectFile(sessionOrId, error) {
            const session = typeof sessionOrId === 'string'
                ? this.fileSessions.get(sessionOrId)
                : sessionOrId;
            if (!session) {
                return;
            }

            if (session.reject) {
                try {
                    session.reject(error);
                } catch (err) {
                    console.error(err);
                }
            }

            this.fileSessions.delete(session.fileId);
            this.emit('error', error);

            if (!session.isLargeFile && session.assignedWorker !== null) {
                this.workerAssignments[session.assignedWorker] = null;
                this.activeSmallFiles.delete(session.fileId);
                this.startNextSmallFiles();
            }

            if (session.isLargeFile && this.activeLargeFileId === session.fileId) {
                this.activeLargeFileId = null;
                this.checkAndStartLargeFiles();
            }
        }

        failActiveUploads(error) {
            const normalizedError = error instanceof Error ? error : new Error(String(error || 'Erro no upload.'));
            this.fileSessions.forEach((session) => {
                if (session.reject) {
                    try {
                        session.reject(normalizedError);
                    } catch (err) {
                        console.error(err);
                    }
                }
            });
            this.fileSessions.clear();
            this.smallFilesQueue = [];
            this.largeFilesQueue = [];
            this.activeSmallFiles.clear();
            this.activeLargeFileId = null;
            this.workerAssignments = new Array(this.websocketUrls.length).fill(null);
            this.isUploading = false;
            this.globalPaused = false;
            this.emit('error', normalizedError);
        }

        startNextSmallFiles() {
            if (!this.isUploading) {
                return;
            }
            for (let workerIndex = 0; workerIndex < this.workerAssignments.length; workerIndex++) {
                if (!this.smallFilesQueue.length) {
                    break;
                }
                if (this.workerAssignments[workerIndex]) {
                    continue;
                }
                if (!this.canUseWorker(workerIndex)) {
                    continue;
                }
                const session = this.smallFilesQueue.shift();
                const started = this.startUploadOnWorker(workerIndex, session, true);
                if (!started) {
                    this.smallFilesQueue.unshift(session);
                    continue;
                }
                this.activeSmallFiles.add(session.fileId);
            }

            if (!this.smallFilesQueue.length && this.activeSmallFiles.size === 0) {
                this.checkAndStartLargeFiles();
            }
        }

        checkAndStartLargeFiles() {
            if (!this.largeFilesQueue.length || this.activeLargeFileId) {
                return;
            }
            if (this.smallFilesQueue.length > 0 || this.activeSmallFiles.size > 0) {
                return;
            }
            const session = this.largeFilesQueue.shift();
            const workerIndex = this.getFirstConnectedWorker();
            if (workerIndex === -1) {
                this.largeFilesQueue.unshift(session);
                return;
            }
            const started = this.startUploadOnWorker(workerIndex, session, false);
            if (!started) {
                this.largeFilesQueue.unshift(session);
                return;
            }
            this.activeLargeFileId = session.fileId;
        }

        startUploadOnWorker(workerIndex, session, lockWorker) {
            const worker = this.workers[workerIndex];
            if (!worker || worker.readyState !== WebSocket.OPEN) {
                return false;
            }

            const payload = {
                type: 'start_upload',
                file_id: session.fileId,
                file_name: session.descriptor.fileName || session.file.name,
                relative_path: session.relativePath,
                total_size: session.totalSize,
                use_shared_queue: !!session.useSharedQueue,
                context: session.descriptor.context || 'user_storage',
                context_payload: ensurePlainObject(session.descriptor.contextPayload),
            };

            try {
                worker.send(JSON.stringify(payload));
            } catch (error) {
                return false;
            }

            session.startWorker = workerIndex;
            if (lockWorker) {
                session.assignedWorker = workerIndex;
                this.workerAssignments[workerIndex] = session.fileId;
            }
            return true;
        }

        buildDescriptor(file, index, total, buildRequest) {
            const base = typeof buildRequest === 'function' ? (buildRequest(file, index) || {}) : {};
            const descriptor = Object.assign({}, base);
            descriptor.fileIndex = descriptor.fileIndex || index + 1;
            descriptor.totalFiles = descriptor.totalFiles || total;
            descriptor.relativePath = sanitizeRelativePath(descriptor.relativePath || file.webkitRelativePath || file.name);
            if (!descriptor.relativePath) {
                descriptor.relativePath = file.name;
            }
            descriptor.fileName = descriptor.fileName || file.name;
            descriptor.context = descriptor.context || 'user_storage';
            descriptor.contextPayload = ensurePlainObject(descriptor.contextPayload);
            descriptor.fileId = descriptor.fileId || generateFileId(index);
            return descriptor;
        }

        upload(fileList, options = {}) {
            const files = Array.from(fileList || []);
            if (!files.length) {
                return Promise.resolve([]);
            }

            return this.ensureConnected().then(() => {
                if (!this.areWorkersReady()) {
                    throw new Error('Conexões de streaming ainda não estão prontas.');
                }
                if (this.isUploading) {
                    throw new Error('Já existe um upload em andamento.');
                }
                return this.startBatch(files, options);
            });
        }

        startBatch(files, options) {
            this.resetBatchState();
            const limit = Number(this.options.maxGlobalActiveUploads || 0);
            if (limit > 0) {
                this.acquireGlobalSlot(limit);
            }
            try {
                this.isUploading = true;
                this.batchOptions = options || {};
                const buildRequest = this.batchOptions.buildRequest;
                const totalFiles = files.length;
                this.totalFiles = totalFiles;

                const filePromises = files.map((file, index) => {
                    const descriptor = this.buildDescriptor(file, index, totalFiles, buildRequest);
                    const isLargeFile = descriptor.forceLarge === true || file.size > this.options.largeFileThreshold;
                    const useSharedQueue = descriptor.useSharedQueue !== undefined ? !!descriptor.useSharedQueue : isLargeFile;

                    const session = {
                        file,
                        fileId: descriptor.fileId,
                        descriptor,
                        relativePath: descriptor.relativePath,
                        isLargeFile,
                        useSharedQueue,
                        sessionId: null,
                        totalSize: file.size,
                        totalChunks: Math.max(1, Math.ceil(file.size / this.options.chunkSize)),
                        currentChunk: 0,
                        chunksInFlight: 0,
                        sentSize: 0,
                        assignedWorker: null,
                        startWorker: null,
                        lastWorker: null,
                        finalizeRequested: false,
                        resolve: null,
                        reject: null,
                    };

                    this.fileSessions.set(session.fileId, session);
                    if (isLargeFile) {
                        this.largeFilesQueue.push(session);
                    } else {
                        this.smallFilesQueue.push(session);
                    }
                    this.totalBytes += session.totalSize;

                    return new Promise((resolve, reject) => {
                        session.resolve = resolve;
                        session.reject = reject;
                    });
                });

                this.emit('batch_started', { totalFiles, totalBytes: this.totalBytes });

                if (this.smallFilesQueue.length) {
                    this.startNextSmallFiles();
                } else {
                    this.checkAndStartLargeFiles();
                }

                const aggregatePromise = Promise.all(filePromises);
                return aggregatePromise.finally(() => {
                    this.isUploading = false;
                    this.batchOptions = null;
                    this.globalPaused = false;
                    this.releaseGlobalSlot();
                    this.resetBatchState();
                });
            } catch (error) {
                this.releaseGlobalSlot();
                this.isUploading = false;
                this.batchOptions = null;
                this.globalPaused = false;
                this.resetBatchState();
                throw error;
            }
        }
    }

    global.StreamingUploadClient = StreamingUploadClient;

    let sharedClient = null;

    function getSharedClient() {
        if (sharedClient) {
            return sharedClient;
        }
        if (!Array.isArray(global.WS_URLS) || !global.WS_URLS.length) {
            throw new Error('WebSocket URLs não configuradas.');
        }
        if (!global.STREAM_UPLOAD_TOKEN) {
            throw new Error('Token de upload não disponível.');
        }

        sharedClient = new StreamingUploadClient({
            websocketUrls: global.WS_URLS,
            token: global.STREAM_UPLOAD_TOKEN,
        });
        return sharedClient;
    }

    global.StreamingUpload = {
        getDefaultClient: getSharedClient,
        resetDefaultClient() {
            sharedClient = null;
        },
        createClient: (options) => new StreamingUploadClient(options),
    };
})(window);
