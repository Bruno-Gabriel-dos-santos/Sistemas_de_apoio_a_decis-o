#!/bin/bash

# Definição de cores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Configurações do ttyd
PORT=7681
INTERFACE="lo"
MAX_CLIENTS=10
FONT_SIZE=14
THEME='{"background":"#1a1b26","foreground":"#c0caf5","selection":"#33467c"}'

# Função para exibir mensagens
log() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

# Função para exibir erros
error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verifica se está rodando como root
if [ "$EUID" -ne 0 ]; then
    error "Este script precisa ser executado como root (sudo)"
    exit 1
fi

# Verifica se o ttyd está instalado
if ! command -v ttyd &> /dev/null; then
    error "ttyd não está instalado"
    exit 1
fi

# Mata qualquer instância anterior do ttyd
log "Parando instâncias anteriores do ttyd..."
pkill ttyd 2>/dev/null

# Espera um momento para garantir que o processo anterior foi finalizado
sleep 2

# Inicia o ttyd com todas as configurações
log "Iniciando ttyd na porta $PORT..."
ttyd \
    --writable \
    -p $PORT \
    -i $INTERFACE \
    -P $MAX_CLIENTS \
    -t fontSize=$FONT_SIZE \
    -t theme=$THEME \
    -t rendererType=webgl \
    -t disableLeaveAlert=true \
    bash

# Se o ttyd parar, exibe mensagem de erro
error "O serviço ttyd parou inesperadamente"

# Mantém o script rodando
tail -f /dev/null 