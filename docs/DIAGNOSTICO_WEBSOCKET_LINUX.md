# Diagnóstico e Correção de Conexão WebSocket no Linux

## ✅ Verificações Realizadas

### Status Atual:
- ✅ PHP 8.3.6 instalado
- ✅ Extensões necessárias instaladas (sockets, pcntl, posix)
- ✅ Servidor rodando na porta 8080
- ✅ Porta acessível via TCP
- ✅ Conexão WebSocket funciona (testado com wscat)

## 🔧 Correções Aplicadas

### 1. Construtor do Ratchet\App Corrigido

**Problema**: Parâmetros do construtor estavam incorretos

**Solução**: 
- `$httpHost` = hostname que o cliente usa (localhost)
- `$port` = porta (8080)
- `$address` = IP para bind (0.0.0.0 ou 127.0.0.1)

### 2. Configuração de Host

O servidor agora diferencia:
- **Host de escuta** (bind): Onde o servidor escuta
- **Host HTTP** (httpHost): Hostname que o cliente usa (deve corresponder ao JS)

## 🚀 Como Reiniciar o Servidor Corretamente

### Passo 1: Parar o Servidor Atual

```bash
# Encontrar o PID
lsof -ti:8080
# ou
ps aux | grep "websocket:start"

# Matar o processo
kill <PID>
# ou
pkill -f "websocket:start"
```

### Passo 2: Verificar Configuração no .env

```env
WEBSOCKET_HOST=0.0.0.0          # Para escutar em todas interfaces
WEBSOCKET_CLIENT_HOST=127.0.0.1 # Para o navegador conectar
WEBSOCKET_PORT=8080
WEBSOCKET_PATH=/upload
```

### Passo 3: Reiniciar o Servidor

```bash
cd /home/bruno/Sistemas_De_Apoio/Sistemas_de_Apoio_10
php artisan websocket:start
```

Você deve ver:
```
Iniciando servidor WebSocket...
Escutando em: 0.0.0.0:8080
HTTP Host: 127.0.0.1
Conecte-se em: ws://127.0.0.1:8080/upload
✅ Servidor WebSocket iniciado!
```

## 🧪 Teste de Conexão

### 1. Teste Manual no Console do Navegador

Abra o Console (F12) e execute:

```javascript
const ws = new WebSocket('ws://127.0.0.1:8080/upload');
ws.onopen = () => console.log('✅ Conectado!');
ws.onerror = (e) => console.error('❌ Erro:', e);
ws.onclose = (e) => console.log('⚠️ Fechado:', e.code, e.reason);
ws.onmessage = (e) => console.log('📨 Mensagem:', e.data);

// Enviar mensagem de teste após conectar
setTimeout(() => {
    if (ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({type: 'get_progress'}));
    }
}, 1000);
```

### 2. Teste com wscat (se instalado)

```bash
echo '{"type":"get_progress"}' | wscat -c ws://127.0.0.1:8080/upload
```

### 3. Teste na Página

1. Acesse: `http://localhost:8000/streaming/test`
2. Abra o Console (F12)
3. Verifique se mostra "✅ Conectado"
4. Tente fazer upload de um arquivo

## 🐛 Problemas Comuns e Soluções

### Erro: "Failed to construct 'WebSocket'"
**Causa**: URL inválida ou servidor não está rodando
**Solução**: 
- Verifique se o servidor está rodando
- Use `ws://127.0.0.1:8080/upload` ou `ws://localhost:8080/upload`

### Erro: "Connection refused"
**Causa**: Servidor não está escutando ou firewall bloqueando
**Solução**:
```bash
# Verificar se está escutando
netstat -tulpn | grep 8080
# ou
ss -tulpn | grep 8080

# Verificar firewall
sudo ufw status
# Se necessário, permitir porta
sudo ufw allow 8080/tcp
```

### Erro: "WebSocket connection failed"
**Causa**: Host HTTP não corresponde
**Solução**: 
- Certifique-se que `WEBSOCKET_CLIENT_HOST` no .env corresponde à URL usada no JS
- Se usar `localhost` no JS, use `localhost` no .env
- Se usar `127.0.0.1` no JS, use `127.0.0.1` no .env

### Erro: "Invalid frame header"
**Causa**: Servidor não está processando WebSocket corretamente
**Solução**:
- Reinicie o servidor com o código corrigido
- Verifique logs em `storage/logs/laravel.log`

## 📊 Verificação Rápida

Execute o script de teste:

```bash
bash scripts/test-websocket-connection.sh
```

## 🔍 Logs

### Ver logs do Laravel:
```bash
tail -f storage/logs/laravel.log
```

### Ver logs do servidor WebSocket:
O servidor mostra logs no terminal onde está rodando.

## ✅ Checklist Final

- [ ] Servidor WebSocket está rodando
- [ ] Porta 8080 está acessível
- [ ] Configuração no .env está correta
- [ ] URL no JavaScript corresponde ao WEBSOCKET_CLIENT_HOST
- [ ] Console do navegador não mostra erros
- [ ] Teste manual no console funciona
- [ ] Upload na página funciona

---

**Data**: 2025-01-27
**Status**: ✅ Código Corrigido - Aguardando Reinicialização







