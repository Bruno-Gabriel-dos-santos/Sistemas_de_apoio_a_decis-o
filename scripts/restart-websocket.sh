#!/bin/bash

# Script para reiniciar o servidor WebSocket

echo "🔄 Reiniciando Servidor WebSocket"
echo "=================================="
echo ""

cd /home/bruno/Sistemas_De_Apoio/Sistemas_de_Apoio_10

# Para o servidor atual
echo "1. Parando servidor atual..."
if lsof -ti:8080 >/dev/null 2>&1; then
    PID=$(lsof -ti:8080)
    echo "   Encontrado processo PID: $PID"
    kill $PID 2>/dev/null
    sleep 2
    
    # Verifica se ainda está rodando
    if lsof -ti:8080 >/dev/null 2>&1; then
        echo "   Forçando encerramento..."
        kill -9 $PID 2>/dev/null
    fi
    
    echo "   ✅ Servidor parado"
else
    echo "   ⚠️  Nenhum servidor encontrado na porta 8080"
fi

echo ""
echo "2. Aguardando liberação da porta..."
sleep 2

echo ""
echo "3. Iniciando servidor WebSocket..."
echo "   (Pressione Ctrl+C para parar)"
echo ""

php artisan websocket:start







