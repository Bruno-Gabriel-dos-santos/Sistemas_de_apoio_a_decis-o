#!/bin/bash

# Configurações
PORT=7681
CREDENTIAL="admin:senha123"  # Altere para suas credenciais
SSL_CERT="/etc/ssl/certs/ssl-cert-snakeoil.pem"
SSL_KEY="/etc/ssl/private/ssl-cert-snakeoil.key"

# Função para iniciar o ttyd
start_ttyd() {
    echo "Iniciando ttyd..."
    ttyd -p $PORT -c $CREDENTIAL -S -C $SSL_CERT -K $SSL_KEY bash &
    echo $! > /tmp/ttyd.pid
    echo "ttyd iniciado na porta $PORT"
}

# Função para parar o ttyd
stop_ttyd() {
    if [ -f /tmp/ttyd.pid ]; then
        echo "Parando ttyd..."
        kill $(cat /tmp/ttyd.pid)
        rm /tmp/ttyd.pid
        echo "ttyd parado"
    else
        echo "ttyd não está rodando"
    fi
}

# Função para verificar status
status_ttyd() {
    if [ -f /tmp/ttyd.pid ] && ps -p $(cat /tmp/ttyd.pid) > /dev/null; then
        echo "ttyd está rodando na porta $PORT"
    else
        echo "ttyd não está rodando"
    fi
}

# Processamento dos argumentos
case "$1" in
    start)
        start_ttyd
        ;;
    stop)
        stop_ttyd
        ;;
    restart)
        stop_ttyd
        sleep 1
        start_ttyd
        ;;
    status)
        status_ttyd
        ;;
    *)
        echo "Uso: $0 {start|stop|restart|status}"
        exit 1
        ;;
esac

exit 0 