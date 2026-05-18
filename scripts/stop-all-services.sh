#!/bin/bash

# Script para parar todos os serviços do sistema de upload
# Uso: ./scripts/stop-all-services.sh

cd "$(dirname "$0")/.." || exit 1

echo "🛑 Parando todos os serviços..."
echo ""

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

# Para workers WebSocket
echo "📦 Parando Workers WebSocket..."
for port in 20001 20010 20020 20040; do
    if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null 2>&1 ; then
        PID=$(lsof -ti:$port)
        echo -e "${RED}🛑 Parando processo na porta $port (PID: $PID)...${NC}"
        kill -9 $PID 2>/dev/null
        sleep 1
    else
        echo -e "${GREEN}✅ Porta $port já está livre${NC}"
    fi
done

# Para gerenciador central (procura por processo writer:central)
echo ""
echo "📝 Parando Gerenciador Central..."
PIDS=$(ps aux | grep "writer:central" | grep -v grep | awk '{print $2}')
if [ -z "$PIDS" ]; then
    echo -e "${GREEN}✅ Gerenciador Central não está rodando${NC}"
else
    for PID in $PIDS; do
        echo -e "${RED}🛑 Parando Gerenciador Central (PID: $PID)...${NC}"
        kill -9 $PID 2>/dev/null
    done
fi

# Para qualquer processo websocket:start restante
echo ""
echo "🔍 Limpando processos restantes..."
PIDS=$(ps aux | grep "websocket:start" | grep -v grep | awk '{print $2}')
if [ -z "$PIDS" ]; then
    echo -e "${GREEN}✅ Nenhum processo restante${NC}"
else
    for PID in $PIDS; do
        echo -e "${RED}🛑 Parando processo (PID: $PID)...${NC}"
        kill -9 $PID 2>/dev/null
    done
fi

echo ""
echo "✅ Todos os serviços foram parados!"
echo ""

