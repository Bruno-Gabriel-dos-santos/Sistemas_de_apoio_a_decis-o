#!/bin/bash

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configurações
TTYD_PORT=7681
TTYD_USER="www-data"
TTYD_HOME="/home/$TTYD_USER"
LOG_FILE="/var/log/ttyd.log"

# Função para exibir mensagens
log() {
    local level=$1
    local message=$2
    local color=$NC
    
    case $level in
        "INFO") color=$GREEN ;;
        "WARN") color=$YELLOW ;;
        "ERROR") color=$RED ;;
    esac
    
    echo -e "${color}[$level] $message${NC}"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [$level] $message" >> $LOG_FILE
}

# Verifica se o script está sendo executado como root
check_root() {
    if [ "$EUID" -ne 0 ]; then
        log "ERROR" "Este script precisa ser executado como root"
        exit 1
    fi
}

# Verifica se o ttyd está instalado
check_ttyd() {
    if ! command -v ttyd &> /dev/null; then
        log "ERROR" "ttyd não está instalado"
        log "INFO" "Instalando ttyd..."
        apt-get update && apt-get install -y ttyd
    fi
}

# Configura o ambiente
setup_environment() {
    # Cria diretório home se não existir
    if [ ! -d "$TTYD_HOME" ]; then
        mkdir -p "$TTYD_HOME"
        chown $TTYD_USER:$TTYD_USER "$TTYD_HOME"
    fi
    
    # Configura shell para o usuário
    usermod -s /bin/bash $TTYD_USER
    
    # Cria arquivo de log se não existir
    touch $LOG_FILE
    chown $TTYD_USER:$TTYD_USER $LOG_FILE
}

# Inicia o serviço ttyd
start_ttyd() {
    log "INFO" "Iniciando TTY Web Service..."
    
    # Verifica se já está rodando
    if systemctl is-active --quiet ttyd; then
        log "WARN" "TTY Web Service já está rodando"
        return
    fi
    
    # Inicia o serviço
    systemctl start ttyd
    
    # Verifica se iniciou corretamente
    if systemctl is-active --quiet ttyd; then
        log "INFO" "TTY Web Service iniciado com sucesso na porta $TTYD_PORT"
    else
        log "ERROR" "Falha ao iniciar TTY Web Service"
    fi
}

# Para o serviço ttyd
stop_ttyd() {
    log "INFO" "Parando TTY Web Service..."
    
    # Verifica se está rodando
    if ! systemctl is-active --quiet ttyd; then
        log "WARN" "TTY Web Service não está rodando"
        return
    }
    
    # Para o serviço
    systemctl stop ttyd
    
    # Verifica se parou corretamente
    if ! systemctl is-active --quiet ttyd; then
        log "INFO" "TTY Web Service parado com sucesso"
    else
        log "ERROR" "Falha ao parar TTY Web Service"
    fi
}

# Reinicia o serviço ttyd
restart_ttyd() {
    log "INFO" "Reiniciando TTY Web Service..."
    stop_ttyd
    sleep 2
    start_ttyd
}

# Mostra o status do serviço
status_ttyd() {
    log "INFO" "Verificando status do TTY Web Service..."
    
    if systemctl is-active --quiet ttyd; then
        log "INFO" "TTY Web Service está rodando"
        systemctl status ttyd
        
        # Mostra informações da porta
        netstat -tulpn | grep $TTYD_PORT
    else
        log "WARN" "TTY Web Service não está rodando"
    fi
}

# Instala o serviço ttyd
install_ttyd() {
    log "INFO" "Instalando TTY Web Service..."
    
    # Cria arquivo de serviço
    cat > /etc/systemd/system/ttyd.service << EOL
[Unit]
Description=TTY Web Service
After=network.target

[Service]
Type=simple
User=$TTYD_USER
Environment=HOME=$TTYD_HOME
WorkingDirectory=$TTYD_HOME
ExecStart=/usr/bin/ttyd --writable -p $TTYD_PORT -i lo bash
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
EOL
    
    # Recarrega configurações do systemd
    systemctl daemon-reload
    
    # Habilita o serviço para iniciar com o sistema
    systemctl enable ttyd
    
    log "INFO" "TTY Web Service instalado com sucesso"
    log "INFO" "Use 'sudo ./ttyd-manager.sh start' para iniciar o serviço"
}

# Desinstala o serviço ttyd
uninstall_ttyd() {
    log "INFO" "Desinstalando TTY Web Service..."
    
    # Para o serviço se estiver rodando
    if systemctl is-active --quiet ttyd; then
        stop_ttyd
    fi
    
    # Remove arquivo de serviço
    rm -f /etc/systemd/system/ttyd.service
    
    # Recarrega configurações do systemd
    systemctl daemon-reload
    
    log "INFO" "TTY Web Service desinstalado com sucesso"
}

# Menu de ajuda
show_help() {
    echo "Uso: $0 {start|stop|restart|status|install|uninstall}"
    echo
    echo "Comandos:"
    echo "  start      Inicia o TTY Web Service"
    echo "  stop       Para o TTY Web Service"
    echo "  restart    Reinicia o TTY Web Service"
    echo "  status     Mostra o status do TTY Web Service"
    echo "  install    Instala o TTY Web Service como serviço do sistema"
    echo "  uninstall  Remove o TTY Web Service"
    echo
    echo "Exemplo: $0 start"
}

# Função principal
main() {
    check_root
    check_ttyd
    setup_environment
    
    case "$1" in
        start)     start_ttyd ;;
        stop)      stop_ttyd ;;
        restart)   restart_ttyd ;;
        status)    status_ttyd ;;
        install)   install_ttyd ;;
        uninstall) uninstall_ttyd ;;
        *)         show_help ;;
    esac
}

# Executa função principal
main "$@" 