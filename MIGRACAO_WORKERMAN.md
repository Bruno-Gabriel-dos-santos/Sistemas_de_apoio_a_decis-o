# 🔄 Migração para Workerman

## ✅ Migração Concluída!

O sistema foi migrado do Ratchet para Workerman, resolvendo o problema de conflitos de porta.

## 🎯 Vantagens do Workerman

- ✅ **Sem conflitos de porta** - Cada worker usa apenas sua porta principal
- ✅ **Múltiplos workers nativos** - Suporta múltiplos workers sem problemas
- ✅ **Performance excelente** - PHP puro, muito rápido
- ✅ **Estável** - Usado em produção por milhares de aplicações

## 📁 Arquivos Criados

### 1. **UploadHandlerWorkerman.php**
- Novo handler WebSocket usando Workerman
- Mantém toda a lógica do handler original
- Localização: `app/WebSocket/UploadHandlerWorkerman.php`

### 2. **StartWebSocketServerWorkerman.php**
- Novo comando Artisan para iniciar servidor Workerman
- Localização: `app/Console/Commands/StartWebSocketServerWorkerman.php`
- Comando: `php artisan websocket:start-workerman --port=XXXXX`

### 3. **start-all-services-workerman.sh**
- Script para iniciar todos os 4 workers + central writer
- Localização: `scripts/start-all-services-workerman.sh`

## 🚀 Como Usar

### Iniciar um Worker Individual

```bash
php artisan websocket:start-workerman --port=20001
```

### Iniciar Todos os Workers

```bash
./scripts/start-all-services-workerman.sh
```

### Parar os Serviços

```bash
./scripts/stop-all-services.sh
```

## 🔧 Diferenças do Ratchet

| Aspecto | Ratchet | Workerman |
|---------|---------|-----------|
| Porta secundária | Sempre cria porta 8843 | Não cria portas extras |
| Múltiplos workers | Conflitos de porta | Funciona perfeitamente |
| API | ConnectionInterface | TcpConnection |
| Inicialização | App::run() | Worker::runAll() |

## 📝 Frontend

O frontend **não precisa ser alterado**! As URLs e protocolo WebSocket são os mesmos:
- `ws://127.0.0.1:20001/upload`
- `ws://127.0.0.1:20010/upload`
- `ws://127.0.0.1:20020/upload`
- `ws://127.0.0.1:20040/upload`

## ⚠️ Nota

Os arquivos antigos do Ratchet ainda estão no projeto:
- `app/WebSocket/UploadHandler.php` (Ratchet)
- `app/Console/Commands/StartWebSocketServer.php` (Ratchet)

Você pode manter ambos ou remover os arquivos do Ratchet se não precisar mais deles.

## 🧪 Testando

1. Inicie os workers:
   ```bash
   ./scripts/start-all-services-workerman.sh
   ```

2. Verifique se as portas estão abertas:
   ```bash
   lsof -i :20001 -i :20010 -i :20020 -i :20040
   ```

3. Acesse o frontend e teste o upload!

## 🐛 Troubleshooting

### Worker não inicia
- Verifique os logs: `storage/logs/websocket-worker-*.log`
- Verifique se a porta está livre: `lsof -i :XXXXX`

### Conexão não estabelecida
- Verifique se o worker está rodando
- Verifique se a URL do frontend está correta
- Verifique os logs do worker

### Erro ao instalar Workerman
- Execute: `composer require workerman/workerman`
- Verifique se PHP >= 7.4

## 📚 Documentação

- [Workerman Docs](https://www.workerman.net/doc)
- [Workerman GitHub](https://github.com/walkor/workerman)






