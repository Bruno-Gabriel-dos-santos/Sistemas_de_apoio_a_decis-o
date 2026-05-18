# Configuração do WebSocket Server

## 🔧 Configuração de Host

### Diferença entre Host de Escuta e Host de Conexão

- **Host de Escuta (0.0.0.0)**: O servidor escuta em todas as interfaces de rede
  - Permite conexões de qualquer IP
  - Usado em produção/servidores
  - Não pode ser usado diretamente no navegador

- **Host de Conexão (127.0.0.1 ou localhost)**: IP usado pelo navegador para conectar
  - 127.0.0.1 = localhost (mesma máquina)
  - Deve ser usado na URL do WebSocket no navegador

## 📝 Configuração Recomendada

### Para Desenvolvimento Local

No arquivo `.env`:
```env
WEBSOCKET_HOST=127.0.0.1
WEBSOCKET_PORT=8080
WEBSOCKET_PATH=/upload
WEBSOCKET_CLIENT_HOST=localhost
```

Ou simplesmente use os valores padrão (já configurados para desenvolvimento).

### Para Produção

No arquivo `.env`:
```env
WEBSOCKET_HOST=0.0.0.0
WEBSOCKET_PORT=8080
WEBSOCKET_PATH=/upload
WEBSOCKET_CLIENT_HOST=seu-dominio.com
# ou
WEBSOCKET_CLIENT_HOST=192.168.1.100  # IP do servidor
```

## 🚀 Como Iniciar

### Desenvolvimento (padrão)
```bash
php artisan websocket:start
```
- Escuta em: `127.0.0.1:8080`
- Conecte-se em: `ws://localhost:8080/upload`

### Produção (todas interfaces)
```bash
php artisan websocket:start --host=0.0.0.0
```
- Escuta em: `0.0.0.0:8080` (todas interfaces)
- Conecte-se em: `ws://seu-dominio.com:8080/upload` (ou IP do servidor)

### Porta Customizada
```bash
php artisan websocket:start --port=9000
```

## 🔍 Verificar Configuração

O comando mostra automaticamente:
- Host de escuta
- URL de conexão para o cliente

Exemplo de saída:
```
Iniciando servidor WebSocket...
Escutando em: 127.0.0.1:8080
Conecte-se em: ws://localhost:8080/upload
✅ Servidor WebSocket iniciado!
```

## ⚠️ Problemas Comuns

### Erro: "Connection refused"
- **Causa**: Host incorreto na URL
- **Solução**: Use `localhost` ou `127.0.0.1` no navegador, não `0.0.0.0`

### Erro: "Cannot connect"
- **Causa**: Servidor não está rodando ou porta bloqueada
- **Solução**: 
  1. Verifique se o servidor está rodando
  2. Verifique firewall
  3. Verifique se a porta está disponível

### Funciona localmente mas não em produção
- **Causa**: Firewall bloqueando porta ou host incorreto
- **Solução**: 
  1. Configure `WEBSOCKET_CLIENT_HOST` com o IP/domínio correto
  2. Abra a porta no firewall
  3. Configure proxy reverso (Nginx) se necessário

## 🔐 Segurança

### Desenvolvimento
- ✅ Usar `127.0.0.1` (apenas localhost)
- ✅ Não expor para rede externa

### Produção
- ✅ Usar autenticação (implementar no handler)
- ✅ Usar WSS (WebSocket Secure) via proxy reverso
- ✅ Limitar IPs permitidos
- ✅ Rate limiting

---

**Data**: 2025-01-27
**Status**: ✅ Configurado







