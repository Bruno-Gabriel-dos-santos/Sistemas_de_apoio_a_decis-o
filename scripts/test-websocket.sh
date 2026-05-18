#!/bin/bash

# Script para testar conexão WebSocket

echo "=== Teste de Conexão WebSocket ==="
echo ""

# Verifica se servidor está rodando
echo "1. Verificando se servidor está rodando..."
if lsof -ti:8080 > /dev/null 2>&1; then
    echo "✅ Servidor está rodando na porta 8080"
    PID=$(lsof -ti:8080)
    echo "   PID: $PID"
else
    echo "❌ Servidor NÃO está rodando na porta 8080"
    echo "   Execute: php artisan websocket:start"
    exit 1
fi

echo ""

# Verifica porta
echo "2. Verificando porta 8080..."
if ss -tuln | grep -q ":8080"; then
    echo "✅ Porta 8080 está aberta"
    ss -tuln | grep ":8080"
else
    echo "❌ Porta 8080 não está aberta"
fi

echo ""

# Testa conexão HTTP (deve retornar 404 para WebSocket)
echo "3. Testando conexão HTTP..."
HTTP_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8080)
if [ "$HTTP_RESPONSE" = "404" ]; then
    echo "✅ Servidor responde (404 é normal para WebSocket sem handshake)"
elif [ "$HTTP_RESPONSE" = "000" ]; then
    echo "❌ Servidor não responde"
else
    echo "⚠️  Resposta HTTP: $HTTP_RESPONSE"
fi

echo ""

# Mostra configuração
echo "4. Configuração atual:"
cd /home/bruno/Sistemas_De_Apoio/Sistemas_de_Apoio_10
php artisan tinker --execute="
echo 'Host: ' . config('streaming.websocket.host') . PHP_EOL;
echo 'Port: ' . config('streaming.websocket.port') . PHP_EOL;
echo 'Path: ' . config('streaming.websocket.path') . PHP_EOL;
echo 'Client Host: ' . config('streaming.websocket.client_host') . PHP_EOL;
"

echo ""

# URL de conexão esperada
echo "5. URL de conexão esperada:"
CLIENT_HOST=$(php artisan tinker --execute="echo config('streaming.websocket.client_host');" 2>/dev/null | tail -1)
PORT=$(php artisan tinker --execute="echo config('streaming.websocket.port');" 2>/dev/null | tail -1)
PATH=$(php artisan tinker --execute="echo config('streaming.websocket.path');" 2>/dev/null | tail -1)
echo "   ws://${CLIENT_HOST}:${PORT}${PATH}"

echo ""
echo "=== Fim do Teste ==="
