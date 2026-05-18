# Como Iniciar os Serviços

## 🚀 Método Rápido (Recomendado)

Use o script automatizado:

```bash
cd /home/bruno/Sistemas_De_Apoio/Sistemas_de_Apoio_10
./scripts/start-all-services.sh
```

Para parar todos os serviços:

```bash
./scripts/stop-all-services.sh
```

## 📋 Método Manual

### 1. Iniciar Workers WebSocket

Você precisa iniciar **4 workers** em portas diferentes:

#### Terminal 1 - Worker 1:
```bash
cd /home/bruno/Sistemas_De_Apoio/Sistemas_de_Apoio_10
php artisan websocket:start --port=20001
```

#### Terminal 2 - Worker 2:
```bash
cd /home/bruno/Sistemas_De_Apoio/Sistemas_de_Apoio_10
php artisan websocket:start --port=20010
```

#### Terminal 3 - Worker 3:
```bash
cd /home/bruno/Sistemas_De_Apoio/Sistemas_de_Apoio_10
php artisan websocket:start --port=20020
```

#### Terminal 4 - Worker 4:
```bash
cd /home/bruno/Sistemas_De_Apoio/Sistemas_de_Apoio_10
php artisan websocket:start --port=20030
```

### 2. Iniciar Gerenciador Central (Opcional mas Recomendado)

#### Terminal 5 - Gerenciador Central:
```bash
cd /home/bruno/Sistemas_De_Apoio/Sistemas_de_Apoio_10
php artisan writer:central
```

**Nota**: O gerenciador central processa as filas compartilhadas. Se não rodar separadamente, cada worker processa suas próprias filas (menos eficiente).

## 🔍 Verificar se os Serviços Estão Rodando

### Verificar portas:
```bash
# Verifica se as portas estão em uso
lsof -i :20001
lsof -i :20010
lsof -i :20020
lsof -i :20030
```

### Verificar processos:
```bash
# Ver processos WebSocket
ps aux | grep "websocket:start"

# Ver processo gerenciador central
ps aux | grep "writer:central"
```

## 🛑 Parar os Serviços

### Parar Workers (por porta):
```bash
# Encontra e mata processo na porta
lsof -ti:20001 | xargs kill -9
lsof -ti:20010 | xargs kill -9
lsof -ti:20020 | xargs kill -9
lsof -ti:20030 | xargs kill -9
```

### Parar Gerenciador Central:
```bash
# Encontra e mata processo
ps aux | grep "writer:central" | grep -v grep | awk '{print $2}' | xargs kill -9
```

### Parar todos de uma vez:
```bash
# Para todos os processos websocket:start
pkill -f "websocket:start"

# Para gerenciador central
pkill -f "writer:central"
```

## 📝 Logs

Os logs são salvos em:
- **Workers**: `storage/logs/websocket-worker-*.log` (se usar script)
- **Gerenciador Central**: `storage/logs/central-writer.log` (se usar script)
- **Laravel**: `storage/logs/laravel.log`

## 🔧 Em Produção (Supervisor)

Para produção, use Supervisor para gerenciar os processos automaticamente:

### Configuração Supervisor (`/etc/supervisor/conf.d/websocket.conf`):

```ini
[program:websocket-worker-1]
command=php /caminho/completo/artisan websocket:start --port=20001
directory=/caminho/completo
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/caminho/completo/storage/logs/websocket-worker-1.log

[program:websocket-worker-2]
command=php /caminho/completo/artisan websocket:start --port=20010
directory=/caminho/completo
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/caminho/completo/storage/logs/websocket-worker-2.log

[program:websocket-worker-3]
command=php /caminho/completo/artisan websocket:start --port=20020
directory=/caminho/completo
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/caminho/completo/storage/logs/websocket-worker-3.log

[program:websocket-worker-4]
command=php /caminho/completo/artisan websocket:start --port=20030
directory=/caminho/completo
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/caminho/completo/storage/logs/websocket-worker-4.log

[program:central-writer]
command=php /caminho/completo/artisan writer:central
directory=/caminho/completo
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/caminho/completo/storage/logs/central-writer.log
```

### Comandos Supervisor:

```bash
# Recarregar configuração
sudo supervisorctl reread
sudo supervisorctl update

# Iniciar todos
sudo supervisorctl start all

# Parar todos
sudo supervisorctl stop all

# Status
sudo supervisorctl status
```

## ✅ Checklist de Inicialização

- [ ] 4 Workers WebSocket rodando (portas 20010-20030)
- [ ] Gerenciador Central rodando (opcional)
- [ ] Portas verificadas e acessíveis
- [ ] Logs sendo gerados
- [ ] Frontend configurado para conectar aos workers

## 🐛 Troubleshooting

### Porta já em uso:
```bash
# Ver qual processo está usando a porta
lsof -i :20010

# Matar processo
kill -9 <PID>
```

### Processo não inicia:
- Verifique se o PHP está instalado: `php -v`
- Verifique se o Laravel está configurado: `php artisan --version`
- Verifique permissões: `chmod +x artisan`

### Workers não recebem conexões:
- Verifique firewall: `sudo ufw status`
- Verifique se o host está correto (127.0.0.1 ou 0.0.0.0)
- Verifique logs em `storage/logs/`

