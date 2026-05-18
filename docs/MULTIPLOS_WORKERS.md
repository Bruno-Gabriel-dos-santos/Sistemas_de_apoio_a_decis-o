# Sistema de Múltiplos Workers com Gerenciador Central

## Visão Geral

Este sistema permite usar múltiplos workers PHP para uploads, com duas modalidades:

1. **Arquivos Grandes (>1GB)**: Usa fila compartilhada - múltiplos workers colocam chunks na memória, um gerenciador central escreve (reduz concorrência de disco)
2. **Múltiplos Arquivos**: Cada worker gerencia seus próprios arquivos independentemente

## Arquitetura

```
Frontend
    ↓
    ├─→ Worker 1 (porta 20001) → Fila Compartilhada → Gerenciador Central → Disco
    ├─→ Worker 2 (porta 20010) → Fila Compartilhada → Gerenciador Central → Disco
    ├─→ Worker 3 (porta 20020) → Fila Compartilhada → Gerenciador Central → Disco
    └─→ Worker 4 (porta 20030) → Fila Compartilhada → Gerenciador Central → Disco
```

## Componentes

### 1. SharedChunkQueue
- Fila compartilhada usando arquivos temporários
- Thread-safe com locks
- Permite múltiplos workers adicionarem chunks à mesma fila

### 2. CentralWriterManager
- Gerenciador central que processa todas as filas compartilhadas
- Escreve sequencialmente para reduzir concorrência de disco
- Pode rodar em processo separado ou no mesmo processo

### 3. UploadHandler (modificado)
- Detecta automaticamente arquivos grandes (>1GB) e usa fila compartilhada
- Suporta parâmetro `use_shared_queue` para forçar uso de fila compartilhada

## Como Usar

### 1. Iniciar Múltiplos Workers

```bash
# Terminal 1
php artisan websocket:start --port=20001

# Terminal 2
php artisan websocket:start --port=20010

# Terminal 3
php artisan websocket:start --port=20020

# Terminal 4
php artisan websocket:start --port=20030
```

### 2. Iniciar Gerenciador Central (Opcional)

O gerenciador central pode rodar em processo separado para melhor performance:

```bash
php artisan writer:central
```

**Nota**: Se não rodar o gerenciador central separadamente, cada worker processa suas próprias filas compartilhadas (menos eficiente, mas funciona).

### 3. Configurar Frontend

O frontend deve conectar a múltiplos workers e distribuir arquivos:

```javascript
// Para arquivos grandes, todos os workers podem contribuir
// Para múltiplos arquivos, distribui entre workers
const workers = [
    new WebSocket('ws://127.0.0.1:20001/upload'),
    new WebSocket('ws://127.0.0.1:20010/upload'),
    new WebSocket('ws://127.0.0.1:20020/upload'),
    new WebSocket('ws://127.0.0.1:20030/upload')
];
```

### 4. Forçar Uso de Fila Compartilhada

No frontend, ao iniciar upload, envie:

```javascript
ws.send(JSON.stringify({
    type: 'start_upload',
    file_id: 'arquivo-grande-123',
    file_name: 'arquivo-grande.zip',
    total_size: 5368709120, // 5GB
    use_shared_queue: true  // Força uso de fila compartilhada
}));
```

## Detecção Automática

O sistema detecta automaticamente arquivos grandes (>1GB) e usa fila compartilhada automaticamente, mesmo sem `use_shared_queue: true`.

## Vantagens

1. **Reduz Concorrência de Disco**: Um único processo escreve, mesmo com múltiplos workers recebendo
2. **Escalável**: Adicione mais workers conforme necessário
3. **Simples**: Usa arquivos temporários, sem necessidade de Redis
4. **Flexível**: Funciona com ou sem gerenciador central separado

## Limitações

1. **Arquivos Temporários**: Usa disco para compartilhamento (mais lento que Redis, mas mais simples)
2. **Lock Files**: Pode haver contenção em alta concorrência
3. **Gerenciador Central**: Se não rodar separadamente, cada worker processa suas próprias filas

## Produção

Para produção, use Supervisor ou systemd para gerenciar os processos:

### Supervisor

```ini
[program:websocket-worker-1]
command=php /caminho/artisan websocket:start --port=20001
autostart=true
autorestart=true

[program:websocket-worker-2]
command=php /caminho/artisan websocket:start --port=20010
autostart=true
autorestart=true

[program:websocket-worker-3]
command=php /caminho/artisan websocket:start --port=20020
autostart=true
autorestart=true

[program:websocket-worker-4]
command=php /caminho/artisan websocket:start --port=20030
autostart=true
autorestart=true

[program:central-writer]
command=php /caminho/artisan writer:central
autostart=true
autorestart=true
```

## Monitoramento

As filas compartilhadas são armazenadas em:
```
storage/app/streaming/queues/
```

Cada arquivo tem uma fila com nome: `{md5(file_id)}.queue`

## Troubleshooting

1. **Fila não processa**: Verifique se o gerenciador central está rodando
2. **Locks travados**: Delete arquivos `.lock` em `storage/app/streaming/queues/`
3. **Memória alta**: Verifique se chunks estão sendo removidos após escrita

