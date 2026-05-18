#!/bin/bash
echo "🔍 DIAGNÓSTICO DE CONEXÃO WEBSOCKET"
echo "===================================="
echo ""
echo "1. Verificando Workers Rodando:"
echo "--------------------------------"
for port in 20001 20010 20020 20040; do
    if lsof -i :$port 2>/dev/null | grep -q LISTEN; then
        echo "✅ Worker na porta $port: RODANDO"
        ps aux | grep "websocket:start.*--port=$port" | grep -v grep | head -1 | awk '{print "   PID: " $2}'
    else
        echo "❌ Worker na porta $port: NÃO ESTÁ RODANDO"
    fi
done
echo ""
echo "2. Verificando URLs no Controller:"
echo "----------------------------------"
grep -A 4 "websocketUrls" app/Http/Controllers/StreamUploadController.php | grep "ws://"
echo ""
echo "3. Verificando Logs de Erro:"
echo "----------------------------"
for i in 1 2 3 4; do
    if [ -f storage/logs/websocket-worker-$i.log ]; then
        echo "Worker $i - Últimas linhas:"
        tail -3 storage/logs/websocket-worker-$i.log 2>/dev/null || echo "  (vazio)"
        echo ""
    fi
done
echo "4. Para iniciar os workers manualmente:"
echo "---------------------------------------"
echo "Terminal 1: php artisan websocket:start --port=20001"
echo "Terminal 2: php artisan websocket:start --port=20010"
echo "Terminal 3: php artisan websocket:start --port=20020"
echo "Terminal 4: php artisan websocket:start --port=20040"
