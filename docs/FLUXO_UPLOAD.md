# Fluxo de Upload - Sistema de Streaming TCP/IP

## 🎯 Visão Geral

O sistema implementa upload de arquivos via **WebSocket (TCP/IP)** com:
- **Chunks de 64MB** sequenciais
- **Hash SHA-256** obrigatório para cada chunk
- **Fila em memória** (pilha) com limite de **4GB**
- **Escrita assíncrona** no disco
- **Chunks removidos** da memória após escrita

---

## 📋 Etapas do Processo

### **ETAPA 1: Inicialização da Conexão**

```
Cliente (Browser) → WebSocket → Servidor Ratchet → UploadHandler
```

1. Cliente cria conexão WebSocket: `ws://127.0.0.1:8081/upload`
2. Servidor aceita conexão e chama `onOpen()`
3. Servidor envia mensagem `{"type": "connected"}`
4. Cliente recebe confirmação e habilita botão "Iniciar Upload"

**Arquivos envolvidos:**
- `resources/views/streaming/test.blade.php` (função `connect()`)
- `app/WebSocket/UploadHandler.php` (método `onOpen()`)

---

### **ETAPA 2: Início do Upload**

```
Cliente → start_upload → Servidor cria sessão → Cliente recebe session_id
```

1. Usuário seleciona arquivo e clica "Iniciar Upload"
2. Cliente envia:
   ```json
   {
     "type": "start_upload",
     "file_name": "arquivo.zip",
     "total_size": 1073741824
   }
   ```
3. Servidor (`handleStartUpload()`):
   - Gera `sessionId` único (UUID)
   - Cria `ChunkQueue` (fila em memória)
   - Cria `AsyncWriter` (escritor assíncrono)
   - Inicializa arquivo em `storage/app/uploads/`
   - Retorna `session_id` para o cliente

**Arquivos envolvidos:**
- `app/WebSocket/UploadHandler.php` (método `handleStartUpload()`)
- `app/Services/ChunkQueue.php` (nova instância)
- `app/Services/AsyncWriter.php` (nova instância)

---

### **ETAPA 3: Envio de Chunks (Loop Principal)**

```
Cliente → chunk (64MB + hash) → Servidor → Fila → Escrita Assíncrona → Resposta
```

#### **3.1. Cliente Envia Chunk**

1. Cliente lê arquivo em chunks de **64MB**
2. Para cada chunk:
   - Calcula **hash SHA-256** usando Web Crypto API
   - Converte para **base64**
   - Envia via WebSocket:
     ```json
     {
       "type": "chunk",
       "sequence": 0,
       "chunk": "base64_encoded_data...",
       "hash": "sha256_hash..."
     }
     ```

#### **3.2. Servidor Recebe Chunk**

1. Servidor (`handleChunk()`):
   - Decodifica base64 → dados binários
   - Valida hash SHA-256 (calcula e compara)
   - Adiciona à **ChunkQueue** (fila em memória)
   - Chama `AsyncWriter::processQueue()` (escrita assíncrona)
   - Retorna progresso:
     ```json
     {
       "type": "chunk_received",
       "sequence": 0,
       "queue_size": 1,
       "memory_gb": 0.06,
       "file_size_gb": 0.06
     }
     ```

#### **3.3. ChunkQueue (Fila em Memória)**

- **Armazena chunks** em array: `[sequence => ['data', 'hash', 'size']]`
- **Valida hash** antes de adicionar
- **Verifica limite de 4GB** de memória
- **Ordena por sequence** para escrita sequencial
- **Remove chunks** após escrita

**Estrutura da fila:**
```php
[
  0 => ['data' => '...', 'hash' => '...', 'size' => 67108864],
  1 => ['data' => '...', 'hash' => '...', 'size' => 67108864],
  ...
]
```

#### **3.4. AsyncWriter (Escrita Assíncrona)**

- **Processa fila** em ordem sequencial
- **Escreve no disco** usando `fwrite()`
- **Força flush** imediato (`fflush()`) para garantir persistência
- **Remove chunk da fila** após escrita bem-sucedida
- **Libera memória** automaticamente

**Fluxo de escrita:**
```
ChunkQueue → getNextChunk() → fwrite() → fflush() → removeChunk()
```

#### **3.5. Cliente Recebe Confirmação**

1. Cliente recebe `chunk_received`
2. Envia próximo chunk automaticamente
3. Atualiza progress bar
4. Repete até enviar todos os chunks

---

### **ETAPA 4: Finalização**

```
Cliente → finalize → Servidor processa fila → Fecha arquivo → Confirma
```

1. Cliente envia último chunk (ou chunk vazio)
2. Cliente envia:
   ```json
   {
     "type": "finalize"
   }
   ```
3. Servidor (`handleFinalize()`):
   - Processa chunks restantes na fila (até 60 tentativas)
   - Finaliza escrita (`AsyncWriter::finalize()`)
   - Fecha arquivo
   - Verifica tamanho final
   - Retorna:
     ```json
     {
       "type": "upload_completed",
       "file_path": "/path/to/file",
       "file_size": 1073741824
     }
     ```
4. Cliente recebe confirmação e exibe sucesso

---

## 🔄 Fluxo Completo (Diagrama)

```
┌─────────────┐
│   Cliente   │
│  (Browser)  │
└──────┬──────┘
       │ 1. connect()
       ▼
┌─────────────────┐
│  WebSocket      │
│  ws://:8081     │
└──────┬──────────┘
       │ 2. start_upload
       ▼
┌─────────────────┐
│ UploadHandler   │
│ (onMessage)     │
└──────┬──────────┘
       │ 3. Cria ChunkQueue + AsyncWriter
       ▼
┌─────────────────┐
│  ChunkQueue     │
│  (Memória)      │
│  Limite: 4GB    │
└──────┬──────────┘
       │ 4. enqueue(chunk)
       │    - Valida hash
       │    - Adiciona à fila
       ▼
┌─────────────────┐
│  AsyncWriter    │
│  (processQueue) │
└──────┬──────────┘
       │ 5. getNextChunk()
       │    - Escreve no disco
       │    - Remove da fila
       │    - Libera memória
       ▼
┌─────────────────┐
│  storage/app/   │
│  uploads/       │
└─────────────────┘
```

---

## 📊 Características Técnicas

### **Chunks**
- **Tamanho**: 64MB (67.108.864 bytes)
- **Hash**: SHA-256 obrigatório
- **Sequência**: Numeração sequencial (0, 1, 2, ...)
- **Encoding**: Base64 para transmissão WebSocket

### **Memória**
- **Limite total**: 4GB (4.294.967.296 bytes)
- **Fila**: Array associativo em memória RAM
- **Liberação**: Automática após escrita no disco
- **Monitoramento**: Logs de uso de memória

### **Escrita**
- **Modo**: Append binary (`ab`)
- **Flush**: Imediato após cada chunk (64MB)
- **Ordem**: Sequencial (garantida pela fila)
- **Assíncrona**: Processa enquanto recebe novos chunks

### **Validação**
- **Hash**: SHA-256 calculado no cliente e servidor
- **Comparação**: `hash_equals()` (timing-safe)
- **Rejeição**: Chunks com hash inválido são rejeitados

---

## 🔍 Logs e Monitoramento

### **Logs do Servidor** (`storage/logs/laravel.log`)

```
[INFO] Nova conexão WebSocket
[INFO] Upload iniciado (session_id: xxx, file_name: yyy)
[DEBUG] Chunk #0 adicionado à fila (size: 64MB, total: 0.06GB)
[DEBUG] Chunk #0 escrito (size: 64MB, total: 0.06GB)
[DEBUG] Chunk #0 removido da fila (remaining: 0GB)
[INFO] Upload finalizado (file_size: 1GB)
```

### **Console do Navegador**

```
[INFO] Conectando ao servidor: ws://127.0.0.1:8081/upload
[INFO] ✅ Conectado!
[INFO] 📨 connected: {...}
[INFO] Upload iniciado. Sessão: xxx
[INFO] Chunk #0 recebido. Fila: 1, Memória: 0.06GB
[INFO] Chunk #1 recebido. Fila: 1, Memória: 0.06GB
...
[INFO] ✅ Upload concluído! Arquivo: /path/to/file
```

---

## ⚡ Vantagens do Sistema

1. **Não trava a página**: Upload assíncrono via WebSocket
2. **Uso eficiente de memória**: Chunks são liberados após escrita
3. **Integridade garantida**: Hash SHA-256 em cada chunk
4. **Escrita em tempo real**: Não espera upload completo
5. **Limite de memória**: Proteção contra estouro (4GB máximo)

---

## 🛠️ Arquivos do Sistema

### **Backend**
- `app/Services/ChunkQueue.php` - Fila de chunks em memória
- `app/Services/AsyncWriter.php` - Escritor assíncrono
- `app/WebSocket/UploadHandler.php` - Handler WebSocket
- `app/Console/Commands/StartWebSocketServer.php` - Comando servidor

### **Frontend**
- `resources/views/streaming/test.blade.php` - Interface e JavaScript

### **Rotas**
- `routes/web.php` - Rotas de teste

---

**Data**: 2025-01-27
**Status**: Sistema funcional e testado







