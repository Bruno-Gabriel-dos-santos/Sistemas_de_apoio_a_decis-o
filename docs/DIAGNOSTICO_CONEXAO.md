# Diagnóstico de Problemas de Conexão WebSocket

## 🔍 Verificações

### 1. Servidor está rodando?
```bash
# Verifica processo
ps aux | grep "websocket:start" | grep -v grep

# Verifica porta
ss -tuln | grep 8080
# ou
lsof -ti:8080
```

### 2. URL de Conexão

**Configuração atual**:
- Servidor escuta em: `0.0.0.0:8080`
- Cliente conecta em: `127.0.0.1:8080/upload`
- httpHost do Ratchet: `127.0.0.1`

**Problema comum**: Se httpHost não corresponder ao hostname na URL do cliente, conexão falha.

### 3. Teste Manual

```bash
# Testa se servidor responde
curl -I http://127.0.0.1:8080

# Deve retornar 404 (normal para WebSocket sem handshake)
```

### 4. Logs do Servidor

```bash
# Monitora logs em tempo real
tail -f storage/logs/laravel.log | grep -i "websocket\|conexão\|error"
```

### 5. Console do Navegador

Abra DevTools (F12) e verifique:
- Erros no Console
- Network tab → WS (WebSocket)
- Status da conexão

## 🐛 Problemas Comuns

### Problema 1: "Conectando..." mas nunca conecta

**Causas possíveis**:
1. Servidor não está rodando
2. URL incorreta
3. httpHost não corresponde ao hostname na URL
4. Firewall bloqueando porta 8080
5. CORS ou política de segurança

**Solução**:
1. Verifique se servidor está rodando
2. Verifique URL no console do navegador
3. Verifique httpHost no comando websocket:start
4. Teste conexão manualmente

### Problema 2: Conexão fecha imediatamente

**Causas possíveis**:
1. Erro no handler onOpen
2. Exceção não tratada
3. Problema de autenticação

**Solução**:
1. Verifique logs do servidor
2. Verifique código de fechamento (onclose event.code)
3. Adicione try-catch em onOpen

### Problema 3: Erro 1006 (Abnormal closure)

**Causas possíveis**:
1. Servidor não está rodando
2. URL incorreta
3. Problema de rede
4. Timeout

**Solução**:
1. Verifique se servidor está rodando
2. Verifique URL
3. Verifique logs do servidor

## ✅ Checklist de Diagnóstico

- [ ] Servidor WebSocket está rodando?
- [ ] Porta 8080 está aberta?
- [ ] URL de conexão está correta?
- [ ] httpHost corresponde ao hostname na URL?
- [ ] Não há erros no console do navegador?
- [ ] Não há erros nos logs do servidor?
- [ ] Firewall não está bloqueando?
- [ ] Configuração está correta no .env?

## 🔧 Comandos Úteis

```bash
# Reinicia servidor
lsof -ti:8080 | xargs kill
php artisan websocket:start

# Limpa cache
php artisan config:clear
php artisan cache:clear

# Verifica configuração
php artisan tinker --execute="echo config('streaming.websocket.host');"

# Monitora logs
tail -f storage/logs/laravel.log
```

---

**Data**: 2025-01-27







