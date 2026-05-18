#!/bin/bash

# Script para iniciar todos os serviços do sistema de upload usando Workerman
# Uso: ./scripts/start-all-services-workerman.sh

cd "$(dirname "$0")/.." || exit 1

echo "🚀 Iniciando todos os serviços com Workerman..."
echo ""

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Verifica se já existem processos rodando
check_port() {
    if lsof -Pi :$1 -sTCP:LISTEN -t >/dev/null 2>&1 ; then
        echo -e "${YELLOW}⚠️  Porta $1 já está em uso${NC}"
        return 1
    fi
    return 0
}

# Aguarda uma porta ficar disponível
wait_for_port() {
    local port=$1
    local max_attempts=10
    local attempt=0
    while [ $attempt -lt $max_attempts ]; do
        if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null 2>&1; then
            return 0
        fi
        sleep 0.5
        attempt=$((attempt + 1))
    done
    return 1
}

# Mata processos existentes nas portas
kill_existing() {
    echo "🔍 Verificando processos existentes..."
    for port in 20001 20010 20020 20040; do
        if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null 2>&1 ; then
            echo "🛑 Parando processo na porta $port..."
            lsof -ti:$port | xargs kill -9 2>/dev/null
            sleep 1
        fi
    done
}

# Pergunta se quer parar processos existentes
for port in 20001 20010 20020 20040; do
    if ! check_port $port; then
        read -p "Deseja parar os processos existentes? (s/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Ss]$ ]]; then
            kill_existing
            break
        else
            echo "❌ Cancelado. Pare os processos manualmente primeiro."
            exit 1
        fi
    fi
done

echo ""
echo "📦 Iniciando Workers WebSocket (Workerman)..."

# Workerman não tem o problema da porta 8843, então podemos iniciar mais rapidamente
# Mas ainda vamos aguardar um pouco entre cada worker para garantir estabilidade

# Worker 1
echo -e "${GREEN}▶️  Iniciando Worker 1 (porta 20001)...${NC}"
php worker-server.php start --host=127.0.0.1 --port=20001 > storage/logs/websocket-worker-1.log 2>&1 &
WORKER1_PID=$!
echo "   PID: $WORKER1_PID"
sleep 2
if wait_for_port 20001; then
    echo -e "${GREEN}   ✅ Worker 1 iniciado${NC}"
else
    echo -e "${YELLOW}   ⚠️  Worker 1 pode não ter iniciado corretamente${NC}"
fi

# Worker 2
echo -e "${GREEN}▶️  Iniciando Worker 2 (porta 20010)...${NC}"
php worker-server.php start --host=127.0.0.1 --port=20010 > storage/logs/websocket-worker-2.log 2>&1 &
WORKER2_PID=$!
echo "   PID: $WORKER2_PID"
sleep 2
if wait_for_port 20010; then
    echo -e "${GREEN}   ✅ Worker 2 iniciado${NC}"
else
    echo -e "${YELLOW}   ⚠️  Worker 2 pode não ter iniciado corretamente${NC}"
fi

# Worker 3
echo -e "${GREEN}▶️  Iniciando Worker 3 (porta 20020)...${NC}"
php worker-server.php start --host=127.0.0.1 --port=20020 > storage/logs/websocket-worker-3.log 2>&1 &
WORKER3_PID=$!
echo "   PID: $WORKER3_PID"
sleep 2
if wait_for_port 20020; then
    echo -e "${GREEN}   ✅ Worker 3 iniciado${NC}"
else
    echo -e "${YELLOW}   ⚠️  Worker 3 pode não ter iniciado corretamente${NC}"
fi

# Worker 4
echo -e "${GREEN}▶️  Iniciando Worker 4 (porta 20040)...${NC}"
php worker-server.php start --host=127.0.0.1 --port=20040 > storage/logs/websocket-worker-4.log 2>&1 &
WORKER4_PID=$!
echo "   PID: $WORKER4_PID"
sleep 2
if wait_for_port 20040; then
    echo -e "${GREEN}   ✅ Worker 4 iniciado${NC}"
else
    echo -e "${YELLOW}   ⚠️  Worker 4 pode não ter iniciado corretamente${NC}"
fi

# Verifica se os workers iniciaram
echo ""
echo "🔍 Verificando workers..."
for port in 20001 20010 20020 20040; do
    if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null 2>&1; then
        echo -e "${GREEN}✅ Worker na porta $port está rodando${NC}"
    else
        echo -e "${YELLOW}⚠️  Worker na porta $port não iniciou${NC}"
    fi
done

echo ""
echo "📝 Iniciando Gerenciador Central de Escrita..."
php artisan writer:central > storage/logs/central-writer.log 2>&1 &
CENTRAL_PID=$!
echo -e "${GREEN}▶️  Gerenciador Central iniciado${NC}"
echo "   PID: $CENTRAL_PID"

echo ""
echo "✅ Todos os serviços foram iniciados!"
echo ""
echo "📊 PIDs dos processos:"
echo "   Worker 1: $WORKER1_PID"
echo "   Worker 2: $WORKER2_PID"
echo "   Worker 3: $WORKER3_PID"
echo "   Worker 4: $WORKER4_PID"
echo "   Central Writer: $CENTRAL_PID"
echo ""
echo "📝 Logs:"
echo "   Workers: storage/logs/websocket-worker-*.log"
echo "   Central: storage/logs/central-writer.log"
echo ""
echo "🛑 Para parar todos os serviços, execute:"
echo "   ./scripts/stop-all-services.sh"
echo ""

