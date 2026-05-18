#!/bin/bash

# Script para testar conexão WebSocket

echo "🔍 Testando Conexão WebSocket"
echo "=============================="
echo ""

# Verifica se o servidor está rodando
echo "1. Verificando se o servidor está rodando..."
if lsof -Pi :8080 -sTCP:LISTEN -t >/dev/null 2>&1 || ss -tulpn | grep -q ":8080" 2>/dev/null; then
    echo "   ✅ Servidor WebSocket está rodando na porta 8080"
    PID=$(lsof -ti:8080 2>/dev/null || ss -tulpn | grep ":8080" | awk '{print $NF}' | cut -d',' -f2 | cut -d'=' -f2 | head -1)
    echo "   PID: $PID"
else
    echo "   ❌ Servidor WebSocket NÃO está rodando"
    echo "   Execute: php artisan websocket:start"
    exit 1
fi

echo ""
echo "2. Testando conexão TCP na porta 8080..."
if timeout 2 bash -c "echo > /dev/tcp/localhost/8080" 2>/dev/null; then
    echo "   ✅ Porta 8080 está acessível via TCP"
else
    echo "   ❌ Porta 8080 não está acessível"
    echo "   Verifique firewall ou se o servidor está escutando corretamente"
fi

echo ""
echo "3. Verificando extensões PHP necessárias..."
php -m | grep -q sockets && echo "   ✅ Extensão sockets instalada" || echo "   ❌ Extensão sockets NÃO instalada"
php -m | grep -q pcntl && echo "   ✅ Extensão pcntl instalada" || echo "   ❌ Extensão pcntl NÃO instalada"
php -m | grep -q posix && echo "   ✅ Extensão posix instalada" || echo "   ❌ Extensão posix NÃO instalada"

echo ""
echo "4. Testando conexão WebSocket com wscat (se instalado)..."
if command -v wscat &> /dev/null; then
    echo '{"type":"get_progress"}' | timeout 3 wscat -c ws://localhost:8080/upload 2>&1 | head -5
    if [ $? -eq 0 ]; then
        echo "   ✅ Conexão WebSocket funcionando"
    else
        echo "   ⚠️  Não foi possível conectar via wscat"
    fi
else
    echo "   ⚠️  wscat não instalado. Instale com: npm install -g wscat"
fi

echo ""
echo "5. Verificando configuração..."
cd /home/bruno/Sistemas_De_Apoio/Sistemas_de_Apoio_10 2>/dev/null || cd "$(dirname "$0")/.."
if [ -f .env ]; then
    if grep -q "WEBSOCKET_HOST" .env; then
        echo "   ✅ Configuração WEBSOCKET encontrada no .env"
        grep "WEBSOCKET" .env | head -3
    else
        echo "   ⚠️  Configuração WEBSOCKET não encontrada no .env (usando padrões)"
    fi
else
    echo "   ⚠️  Arquivo .env não encontrado"
fi

echo ""
echo "=============================="
echo "✅ Verificação concluída!"
echo ""
echo "Para testar manualmente:"
echo "1. Acesse: http://localhost:8000/streaming/test"
echo "2. Abra o Console do Navegador (F12)"
echo "3. Verifique se há erros de conexão"
echo ""







