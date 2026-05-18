#!/bin/bash

# Configurações
TTYD_PORT=7681
TTYD_USER="www-data"
TTYD_HOME="/home/$TTYD_USER"
TTYD_INTERFACE="lo"
TTYD_FONT_SIZE=14
TTYD_THEME='{"background":"#1a1b26","foreground":"#c0caf5","selection":"#33467c"}'

# Cores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Função para mensagens
log() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verifica se está rodando como root
if [ "$EUID" -ne 0 ]; then
    error "Este script precisa ser executado como root (sudo)"
    exit 1
fi

# Cria o diretório home para o usuário se não existir
if [ ! -d "$TTYD_HOME" ]; then
    log "Criando diretório home $TTYD_HOME..."
    mkdir -p "$TTYD_HOME"
    chown $TTYD_USER:$TTYD_USER "$TTYD_HOME"
fi

# Configura o serviço
log "Configurando serviço ttyd na porta $TTYD_PORT..."
cat > /etc/systemd/system/ttyd.service << EOL
[Unit]
Description=TTY Web Service
After=network.target

[Service]
Type=simple
User=$TTYD_USER
Environment=HOME=$TTYD_HOME
WorkingDirectory=$TTYD_HOME
ExecStart=/usr/bin/ttyd \
    --writable \
    -p $TTYD_PORT \
    -i $TTYD_INTERFACE \
    -t fontSize=$TTYD_FONT_SIZE \
    -t theme=$TTYD_THEME \
    bash
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOL

# Recarrega as configurações do systemd
log "Recarregando configurações do systemd..."
systemctl daemon-reload

# Habilita o serviço para iniciar com o sistema
log "Habilitando serviço ttyd..."
systemctl enable ttyd

# Inicia o serviço
log "Iniciando serviço ttyd..."
systemctl start ttyd

# Espera um momento para o serviço iniciar
sleep 2

# Mostra o status
log "Verificando status do serviço..."
systemctl status ttyd

# Verifica se a porta está aberta
if netstat -tuln | grep ":$TTYD_PORT " > /dev/null; then
    log "Terminal web disponível em: http://localhost:$TTYD_PORT"
else
    error "Porta $TTYD_PORT não está aberta. Verifique os logs com: journalctl -u ttyd -f"
fi 