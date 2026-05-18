# Guia de Teste - Upload Streaming

## 🚀 Como Testar

### 1. Iniciar o Servidor WebSocket

Abra um terminal e execute:

```bash
cd /home/bruno/Sistemas_De_Apoio/Sistemas_de_Apoio_10
php artisan websocket:start
```

Você verá uma mensagem como:
```
Iniciando servidor WebSocket em 0.0.0.0:8080
Servidor WebSocket iniciado!
Conecte-se em: ws://0.0.0.0:8080/upload
Pressione Ctrl+C para parar o servidor
```

**Mantenha este terminal aberto!** O servidor precisa estar rodando.

### 2. Iniciar o Servidor Laravel (se ainda não estiver rodando)

Em outro terminal:

```bash
cd /home/bruno/Sistemas_De_Apoio/Sistemas_de_Apoio_10
php artisan serve
```

### 3. Acessar a Página de Teste

Abra o navegador e acesse:
```
http://localhost:8000/streaming/test
```

### 4. Testar Upload

1. **Clique na área de upload** ou **arraste um arquivo**
2. Selecione um arquivo (qualquer tamanho, até 2GB)
3. Clique em **"Iniciar Upload"**
4. Observe o progresso em tempo real

### 5. Verificar Resultado

O arquivo será salvo em:
```
storage/app/streaming/uploads/
```

## ✅ Checklist de Teste

### Teste Básico
- [ ] Servidor WebSocket inicia sem erros
- [ ] Página de teste carrega corretamente
- [ ] Conexão WebSocket é estabelecida (status verde)
- [ ] Upload de arquivo pequeno (< 1MB) funciona
- [ ] Progresso é exibido em tempo real
- [ ] Arquivo é salvo corretamente

### Teste de Arquivos Grandes
- [ ] Upload de arquivo médio (10-50MB) funciona
- [ ] Upload de arquivo grande (100MB+) funciona
- [ ] Progresso atualiza durante todo o upload
- [ ] Memória não excede limites

### Teste de Múltiplos Uploads
- [ ] Múltiplos uploads simultâneos funcionam
- [ ] Cada upload tem seu próprio progresso
- [ ] Limite de uploads simultâneos é respeitado

### Teste de Recuperação
- [ ] Cancelar upload funciona
- [ ] Conexão perdida é detectada
- [ ] Reconexão automática funciona

## 🐛 Troubleshooting

### Erro: "Porta já em uso"
```bash
# Verifique se a porta 8080 está em uso
netstat -tulpn | grep 8080

# Ou use outra porta
php artisan websocket:start --port=8081
```

### Erro: "Conexão recusada"
- Verifique se o servidor WebSocket está rodando
- Verifique o firewall
- Verifique se a URL do WebSocket está correta

### Erro: "Memória insuficiente"
- Aumente `memory_limit` no php.ini
- Reduza `STREAM_BUFFER_SIZE` no .env
- Reduza `STREAM_MAX_CONCURRENT` no .env

### Upload não inicia
- Verifique os logs em `storage/logs/laravel.log`
- Verifique se o diretório `storage/app/streaming/uploads` existe
- Verifique permissões de escrita

## 📊 Monitoramento

### Verificar Uso de Memória

Durante o teste, monitore o uso de memória:

```bash
# Em outro terminal
watch -n 1 'free -h'
```

### Verificar Logs

```bash
tail -f storage/logs/laravel.log
```

### Verificar Arquivos Enviados

```bash
ls -lh storage/app/streaming/uploads/
```

## 🎯 Próximos Passos Após Teste Bem-Sucedido

1. ✅ Teste básico funcionando
2. ⏭️ Integrar com ArquivoController
3. ⏭️ Integrar com CodigosController
4. ⏭️ Integrar com outras áreas

---

**Data**: 2025-01-27
**Status**: Pronto para Teste







