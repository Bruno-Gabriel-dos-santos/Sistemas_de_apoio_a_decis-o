# 🚀 Como Usar o Workerman

## ✅ Migração Completa!

O sistema foi migrado do Ratchet para Workerman. Agora você pode usar múltiplos workers sem conflitos de porta!

## 📋 Pré-requisitos

✅ Workerman já está instalado via Composer  
✅ Todos os arquivos necessários foram criados  
✅ Scripts de inicialização foram atualizados

## 🎯 Comandos Rápidos

### Iniciar Todos os Workers

```bash
./scripts/start-all-services-workerman.sh
```

Isso iniciará:
- Worker 1 na porta 20001
- Worker 2 na porta 20010
- Worker 3 na porta 20020
- Worker 4 na porta 20040
- Central Writer (gerenciador de escrita)

### Iniciar um Worker Individual

```bash
php artisan websocket:start-workerman --port=20001
```

### Parar Todos os Serviços

```bash
./scripts/stop-all-services.sh
```

## 🔍 Verificar se Está Funcionando

### Verificar Portas

```bash
lsof -i :20001 -i :20010 -i :20020 -i :20040
```

### Verificar Logs

```bash
# Ver logs dos workers
tail -f storage/logs/websocket-worker-1.log
tail -f storage/logs/websocket-worker-2.log
tail -f storage/logs/websocket-worker-3.log
tail -f storage/logs/websocket-worker-4.log

# Ver logs do central writer
tail -f storage/logs/central-writer.log
```

## 🔄 Diferenças do Ratchet

### ❌ Problemas Resolvidos

- ✅ **Sem porta 8843** - Workerman não cria portas secundárias
- ✅ **Múltiplos workers funcionam** - Sem conflitos
- ✅ **Inicialização mais rápida** - Não precisa esperar entre workers

### 📝 Frontend

**O frontend NÃO precisa ser alterado!** As URLs são as mesmas:
- `ws://127.0.0.1:20001/upload`
- `ws://127.0.0.1:20010/upload`
- `ws://127.0.0.1:20020/upload`
- `ws://127.0.0.1:20040/upload`

## 🐛 Problemas Comuns

### Worker não inicia

**Sintoma:** Porta não fica em escuta

**Solução:**
1. Verifique se a porta está livre: `lsof -i :XXXXX`
2. Verifique os logs: `tail storage/logs/websocket-worker-X.log`
3. Verifique se há erros no PHP: `php artisan websocket:start-workerman --port=XXXXX`

### Conexão não estabelecida

**Sintoma:** Frontend não consegue conectar

**Solução:**
1. Verifique se o worker está rodando: `lsof -i :XXXXX`
2. Verifique a URL no frontend (deve ser `ws://127.0.0.1:XXXXX`)
3. Verifique os logs do worker

### Erro "Class not found"

**Sintoma:** `Class 'Workerman\Worker' not found`

**Solução:**
```bash
composer require workerman/workerman
composer dump-autoload
```

## 📚 Arquivos Importantes

### Handlers
- `app/WebSocket/UploadHandlerWorkerman.php` - Handler principal (Workerman)
- `app/WebSocket/UploadHandler.php` - Handler antigo (Ratchet) - pode ser removido

### Comandos
- `app/Console/Commands/StartWebSocketServerWorkerman.php` - Comando Workerman
- `app/Console/Commands/StartWebSocketServer.php` - Comando antigo (Ratchet) - pode ser removido

### Scripts
- `scripts/start-all-services-workerman.sh` - Iniciar todos (Workerman)
- `scripts/start-all-services.sh` - Script antigo (Ratchet)

## 🎉 Vantagens do Workerman

1. ✅ **Sem conflitos de porta** - Cada worker usa apenas sua porta
2. ✅ **Múltiplos workers nativos** - Funciona perfeitamente
3. ✅ **Performance excelente** - PHP puro, muito rápido
4. ✅ **Estável** - Usado em produção por milhares de aplicações
5. ✅ **Fácil de debugar** - Logs claros e estrutura simples

## 🧪 Testando

1. Inicie os workers:
   ```bash
   ./scripts/start-all-services-workerman.sh
   ```

2. Verifique se estão rodando:
   ```bash
   lsof -i :20001 -i :20010 -i :20020 -i :20040
   ```

3. Acesse o frontend e teste um upload!

## 📖 Documentação

- [Workerman Docs](https://www.workerman.net/doc)
- [Workerman GitHub](https://github.com/walkor/workerman)






