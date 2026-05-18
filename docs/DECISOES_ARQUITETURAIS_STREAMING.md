# Decisões Arquiteturais - Sistema de Upload Streaming

## 🎯 Decisões Principais a Tomar

### 1. ESCOLHA DA TECNOLOGIA DE WEBSOCKET

#### Opção A: ReactPHP
```php
Vantagens:
✅ Puro PHP (sem extensões)
✅ Compatível com Laravel
✅ Event-driven (não-bloqueante)
✅ Comunidade ativa
✅ Fácil integração

Desvantagens:
❌ Performance menor que Swoole
❌ Mais consumo de memória
❌ Processamento single-threaded

Biblioteca: react/socket, react/http
```

#### Opção B: Swoole
```php
Vantagens:
✅ Performance superior
✅ Multi-threading nativo
✅ Menor uso de memória
✅ Suporte a corrotinas
✅ Melhor para alta concorrência

Desvantagens:
❌ Requer extensão C (compilação)
❌ Curva de aprendizado maior
❌ Pode ter conflitos com Laravel
❌ Requer servidor dedicado

Biblioteca: swoole/swoole
```

#### Opção C: Laravel WebSockets (Beyondcode)
```php
Vantagens:
✅ Integração nativa com Laravel
✅ Fácil de usar
✅ Suporte a broadcasting
✅ Dashboard incluído

Desvantagens:
❌ Baseado em ReactPHP (mesmas limitações)
❌ Focado em broadcasting
❌ Menos controle sobre streams

Biblioteca: beyondcode/laravel-websockets
```

**RECOMENDAÇÃO**: 
- **Desenvolvimento/Teste**: ReactPHP (mais fácil)
- **Produção/Alta Performance**: Swoole (se servidor dedicado)
- **Rápida Implementação**: Laravel WebSockets

---

### 2. ESTRATÉGIA DE MEMÓRIA RAM

#### Opção A: php://memory
```php
Vantagens:
✅ Nativo do PHP
✅ Gerenciamento automático
✅ Fácil de usar
✅ Sem dependências

Desvantagens:
❌ Limitado a processo único
❌ Não compartilhado entre processos
❌ Pode ser mais lento

Uso: Buffer simples, uploads únicos
```

#### Opção B: Shared Memory (shmop)
```php
Vantagens:
✅ Compartilhado entre processos
✅ Mais eficiente
✅ Melhor para múltiplos uploads
✅ Controle fino sobre memória

Desvantagens:
❌ Requer extensão shmop
❌ Requer sincronização manual
❌ Mais complexo de implementar
❌ Limpeza manual necessária

Uso: Múltiplos uploads simultâneos
```

#### Opção C: APCu (Alternative PHP Cache)
```php
Vantagens:
✅ Cache compartilhado
✅ API simples
✅ Integração fácil
✅ TTL automático

Desvantagens:
❌ Limitado a servidor único
❌ Tamanho máximo configurável
❌ Pode expirar dados

Uso: Cache de metadados, não para buffers grandes
```

**RECOMENDAÇÃO**: 
- **Buffer Principal**: php://memory (simples e eficiente)
- **Múltiplos Uploads**: shmop (se necessário compartilhamento)
- **Metadados**: APCu (cache rápido)

---

### 3. ESTRATÉGIA DE ESCRITA NO DISCO

#### Opção A: Append Mode (fopen 'a')
```php
Vantagens:
✅ Escreve enquanto recebe
✅ Não bloqueia recebimento
✅ Simples de implementar
✅ Eficiente

Desvantagens:
❌ Requer gerenciamento de locks
❌ Pode ter fragmentação
❌ Não permite escrita aleatória

Uso: Uploads sequenciais (recomendado)
```

#### Opção B: Write Mode com Seek (fopen 'r+')
```php
Vantagens:
✅ Permite escrita aleatória
✅ Melhor para validação
✅ Permite correção de erros

Desvantagens:
❌ Mais complexo
❌ Pode bloquear
❌ Requer conhecimento do tamanho

Uso: Uploads com validação complexa
```

#### Opção C: Stream Context com Callbacks
```php
Vantagens:
✅ Controle total
✅ Callbacks personalizados
✅ Melhor para processamento

Desvantagens:
❌ Muito complexo
❌ Overhead maior
❌ Difícil debug

Uso: Casos especiais
```

**RECOMENDAÇÃO**: Append Mode ('a') - Simples e eficiente

---

### 4. TAMANHO DE CHUNKS

#### Análise de Tamanhos:

| Tamanho | Vantagens | Desvantagens | Uso Recomendado |
|---------|-----------|--------------|-----------------|
| 1MB | Menor uso de RAM | Mais I/O | Arquivos pequenos |
| 4MB | Balanceado | - | Uso geral |
| 8MB | Menos I/O | Mais RAM | Arquivos grandes |
| 16MB | Muito eficiente | Alto uso RAM | Servidores com muita RAM |

**RECOMENDAÇÃO**: 
- **Buffer RAM**: 64MB-128MB (circular)
- **Chunk de Processamento**: 8MB
- **Chunk de Escrita**: 8MB
- **Flush Threshold**: 80% do buffer

---

### 5. ESTRATÉGIA DE VALIDAÇÃO

#### Opção A: Validação Precoce (Primeiros Bytes)
```php
Vantagens:
✅ Rejeita arquivos inválidos rapidamente
✅ Economiza recursos
✅ Melhor UX

Implementação:
- Ler primeiros bytes (magic bytes)
- Validar MIME type
- Rejeitar imediatamente se inválido
```

#### Opção B: Validação Durante Upload
```php
Vantagens:
✅ Validação contínua
✅ Detecta problemas cedo
✅ Permite correção

Implementação:
- Validar cada chunk recebido
- Verificar integridade
- Processar em paralelo
```

#### Opção C: Validação Pós-Upload
```php
Vantagens:
✅ Validação completa
✅ Mais preciso

Desvantagens:
❌ Desperdiça recursos
❌ Pior UX

Uso: Não recomendado para streaming
```

**RECOMENDAÇÃO**: Validação Precoce + Durante Upload

---

### 6. GERENCIAMENTO DE FALHAS

#### Estratégias:

1. **Retry Automático**
   - Tentar novamente chunks falhos
   - Máximo 3 tentativas
   - Backoff exponencial

2. **Checkpoint System**
   - Salvar progresso periodicamente
   - Permitir retomada
   - Recuperar de falhas

3. **Rollback Automático**
   - Reverter upload incompleto
   - Limpar recursos
   - Notificar usuário

4. **Queue para Retry**
   - Enfileirar uploads falhos
   - Processar depois
   - Notificar quando completo

**RECOMENDAÇÃO**: Checkpoint System + Retry Automático

---

## 📊 COMPARAÇÃO DE PERFORMANCE ESPERADA

### Upload Tradicional (Atual)
```
Arquivo 100MB:
- Tempo: ~30-60s (depende da conexão)
- Memória: ~100MB (arquivo completo em memória)
- I/O: Escrita única no final
- Bloqueio: Sim (página trava)
- Recuperação: Não
```

### Upload Streaming (Proposto)
```
Arquivo 100MB:
- Tempo: ~30-60s (mesma conexão, mas processa enquanto recebe)
- Memória: ~64-128MB (buffer fixo)
- I/O: Escrita contínua (8MB chunks)
- Bloqueio: Não (WebSocket assíncrono)
- Recuperação: Sim (checkpoints)
```

### Ganhos Esperados:
- ✅ **Memória**: 50-80% menos uso
- ✅ **I/O**: Distribuído (melhor uso de disco)
- ✅ **UX**: Página não trava
- ✅ **Recuperação**: Possível retomar uploads
- ✅ **Validação**: Mais rápida (rejeita cedo)

---

## 🏗️ ARQUITETURA RECOMENDADA

### Stack Tecnológico:

```
Frontend:
- WebSocket Client (nativo ou Socket.io)
- Progress Tracker
- Retry Logic

Backend:
- ReactPHP WebSocket Server (ou Swoole)
- Stream Buffer (php://memory)
- Chunk Processor
- Storage Writer (append mode)
- Progress Tracker
- Checkpoint Manager

Infraestrutura:
- PHP 8.1+
- Extensões: sockets, shmop (opcional)
- Memória: 4GB+ recomendado
- Disco: SSD recomendado
```

### Fluxo de Dados:

```
[Browser] 
  → [WebSocket] 
  → [StreamReceiver] 
  → [RAM Buffer 64MB]
  → [Chunk Splitter 8MB]
  → [Parallel Processing]
      ├→ [Validator]
      ├→ [Storage Writer]
      └→ [Metadata Extractor]
  → [Progress Updates]
  → [Checkpoint Save]
  → [Final Commit]
```

---

## 🔧 CONFIGURAÇÕES RECOMENDADAS

### PHP (php.ini)
```ini
; Memória
memory_limit = 4G
max_execution_time = 0

; Upload
upload_max_filesize = 2G
post_max_size = 2G

; Streams
default_socket_timeout = 600
allow_url_fopen = On

; Extensões
extension=sockets
extension=shmop  ; opcional
extension=apcu   ; opcional
```

### Servidor Web (Nginx)
```nginx
# WebSocket proxy
location /ws {
    proxy_pass http://websocket_server;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_read_timeout 600s;
    proxy_send_timeout 600s;
}
```

### Laravel (.env)
```env
STREAM_UPLOAD_ENABLED=true
STREAM_BUFFER_SIZE=67108864  # 64MB
STREAM_CHUNK_SIZE=8388608    # 8MB
STREAM_FLUSH_THRESHOLD=0.8   # 80%
STREAM_MAX_FILE_SIZE=2147483648  # 2GB
STREAM_MAX_CONCURRENT=10
STREAM_CHECKPOINT_INTERVAL=30  # segundos
```

---

## 📈 MÉTRICAS DE SUCESSO

### Performance:
- [ ] Upload de 100MB em < 60s (depende da conexão)
- [ ] Uso de memória < 200MB por upload
- [ ] CPU usage < 50% durante upload
- [ ] I/O distribuído (sem picos)

### Confiabilidade:
- [ ] Taxa de sucesso > 99%
- [ ] Recuperação de falhas > 95%
- [ ] Sem perda de dados
- [ ] Validação precoce funcional

### UX:
- [ ] Página não trava durante upload
- [ ] Progresso em tempo real
- [ ] Feedback imediato de erros
- [ ] Possibilidade de cancelar

---

## ⚠️ RISCOS E MITIGAÇÕES

### Risco 1: Falta de Memória RAM
**Mitigação**: 
- Limitar uploads simultâneos
- Implementar queue system
- Monitorar uso de memória
- Flush automático agressivo

### Risco 2: Falhas de Rede
**Mitigação**:
- Checkpoint system
- Retry automático
- Timeout configurável
- Notificação de falhas

### Risco 3: Concorrência Alta
**Mitigação**:
- Rate limiting por usuário
- Queue system
- Load balancing
- Limite de conexões simultâneas

### Risco 4: I/O de Disco Lento
**Mitigação**:
- Buffer maior
- Chunks maiores
- SSD recomendado
- Distribuir arquivos

---

## 🎯 DECISÕES FINAIS RECOMENDADAS

### Tecnologia Principal:
**ReactPHP** (para começar) → Migrar para **Swoole** se necessário

### Estratégia de Memória:
**php://memory** com buffer circular de 64MB

### Estratégia de Escrita:
**Append mode** com chunks de 8MB

### Validação:
**Precoce** (primeiros bytes) + **Durante upload**

### Recuperação:
**Checkpoint system** a cada 30 segundos

### Monitoramento:
**Dashboard** com métricas em tempo real

---

## 📝 PRÓXIMOS PASSOS

1. **Aprovar Arquitetura** ✅
2. **Escolher Tecnologia** (ReactPHP recomendado)
3. **Configurar Ambiente** (PHP, extensões, memória)
4. **Implementar Protótipo** (upload simples)
5. **Testar Performance** (arquivos grandes)
6. **Expandir Funcionalidades** (múltiplos arquivos)
7. **Otimizar** (ajustar buffers, I/O)
8. **Deploy Produção** (com monitoramento)

---

**Data**: 2025-01-27
**Versão**: 1.0
**Status**: Recomendações Técnicas







