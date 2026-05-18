# Sistema Antifalha - Upload Streaming

## ✅ Funcionalidades Implementadas

### 1. Sistema de Checkpoint
- **Salva progresso periodicamente** (a cada 30 segundos por padrão)
- **Armazena em arquivo JSON** e cache
- **Permite retomada** após queda de conexão
- **Limpa checkpoints antigos** automaticamente

### 2. Hash de Validação
- **MD5 ou SHA256** para cada chunk
- **Validação automática** ao receber chunk
- **Detecção de chunks corrompidos**
- **Armazenamento de hashes** para validação posterior

### 3. Retomada de Upload
- **Identifica chunks faltantes**
- **Valida arquivo parcial**
- **Permite reenvio** de chunks específicos
- **Continua de onde parou**

### 4. Validação de Integridade
- **Valida cada chunk** com hash
- **Verifica arquivo completo** ao finalizar
- **Detecta chunks corrompidos**
- **Solicita reenvio** de chunks inválidos

## 🔧 Componentes

### CheckpointService
- Salva estado do upload periodicamente
- Carrega checkpoint para retomada
- Gerencia limpeza de checkpoints antigos

### ChunkValidatorService
- Calcula hash de chunks (MD5/SHA256)
- Valida integridade de chunks
- Detecta chunks corrompidos

### ResumeService
- Verifica se pode retomar upload
- Identifica chunks faltantes
- Valida arquivo parcial

## 📊 Fluxo de Funcionamento

### Upload Normal:
```
1. Cliente envia chunk #0 com hash
2. Servidor valida hash
3. Adiciona à fila
4. Grava no disco
5. Salva checkpoint (a cada 30s)
6. Confirma recebimento
```

### Após Queda de Conexão:
```
1. Cliente reconecta
2. Solicita retomada (resume_upload)
3. Servidor verifica checkpoint
4. Retorna chunks faltantes
5. Cliente reenvia apenas chunks faltantes
6. Servidor valida e continua
```

### Validação de Chunk:
```
1. Cliente calcula hash do chunk
2. Envia chunk + hash
3. Servidor recebe e calcula hash
4. Compara hashes
5. Se diferente → solicita reenvio
6. Se igual → adiciona à fila
```

## 🚨 Tratamento de Falhas

### 1. Queda de Conexão
- **Checkpoint salvo** periodicamente
- **Arquivo parcial preservado**
- **Chunks faltantes identificados**
- **Retomada automática**

### 2. Chunk Corrompido
- **Hash inválido detectado**
- **Chunk rejeitado**
- **Cliente solicita reenvio**
- **Servidor valida novamente**

### 3. Arquivo Parcial Corrompido
- **Validação de integridade**
- **Chunks corrompidos identificados**
- **Reenvio solicitado**
- **Arquivo reconstruído**

## 📝 Protocolo WebSocket

### Mensagens do Cliente:

#### 1. Iniciar Upload:
```json
{
  "type": "start_upload",
  "metadata": {
    "name": "arquivo.zip",
    "total_size": 104857600,
    "destination_path": "streaming/uploads"
  }
}
```

#### 2. Enviar Chunk:
```json
{
  "type": "chunk",
  "chunk": "base64...",
  "hash": "md5_hash_do_chunk",
  "sequence": 0
}
```

#### 3. Retomar Upload:
```json
{
  "type": "resume_upload",
  "session_id": "uuid-da-sessao"
}
```

#### 4. Reenviar Chunk:
```json
{
  "type": "resend_chunk",
  "session_id": "uuid-da-sessao",
  "sequence": 5
}
```

### Mensagens do Servidor:

#### 1. Chunk Recebido:
```json
{
  "type": "chunk_received",
  "sequence": 0,
  "hash_valid": true,
  "progress": 12.5
}
```

#### 2. Hash Inválido:
```json
{
  "type": "chunk_invalid",
  "sequence": 5,
  "error": "Hash mismatch",
  "request_resend": true
}
```

#### 3. Retomada Disponível:
```json
{
  "type": "resume_available",
  "session_id": "uuid",
  "last_written_sequence": 10,
  "missing_chunks": [11, 12, 13],
  "file_size": 83886080
}
```

## 🔍 Validação de Integridade

### Durante Upload:
- Cada chunk validado com hash
- Checkpoint salvo periodicamente
- Chunks corrompidos rejeitados

### Após Finalização:
- Hash total do arquivo calculado
- Comparado com hash esperado
- Arquivo validado completamente

### Em Retomada:
- Arquivo parcial validado
- Chunks corrompidos identificados
- Reenvio solicitado

## 🧪 Como Testar

### 1. Teste de Queda de Conexão:
```bash
# Inicie upload
# Desconecte WebSocket no meio
# Reconecte e solicite retomada
```

### 2. Teste de Chunk Corrompido:
```bash
# Envie chunk com hash incorreto
# Verifique se servidor rejeita
# Reenvie com hash correto
```

### 3. Teste de Retomada:
```bash
# Inicie upload grande
# Pare no meio
# Solicite retomada
# Verifique chunks faltantes
```

## 📊 Estrutura de Checkpoint

```json
{
  "session_id": "uuid",
  "saved_at": 1234567890,
  "metadata": {
    "name": "arquivo.zip",
    "total_size": 104857600
  },
  "chunks_received": [0, 1, 2, 3],
  "chunks_written": [0, 1, 2],
  "total_received": 33554432,
  "total_written": 25165824,
  "file_path": "/path/to/file",
  "file_size": 25165824,
  "last_chunk_sequence": 3,
  "last_written_sequence": 2,
  "chunk_hashes": {
    "0": "md5_hash_0",
    "1": "md5_hash_1",
    "2": "md5_hash_2",
    "3": "md5_hash_3"
  }
}
```

## ✅ Benefícios

1. **Resiliência**: Upload continua após falhas
2. **Integridade**: Validação de cada chunk
3. **Eficiência**: Reenvia apenas chunks faltantes
4. **Confiabilidade**: Checkpoints periódicos
5. **Rastreabilidade**: Hash de cada chunk

---

**Data**: 2025-01-27
**Status**: ✅ Implementado







