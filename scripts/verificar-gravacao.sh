#!/bin/bash

# Script para verificar se arquivos estão sendo gravados

echo "🔍 Verificando Gravação de Arquivos"
echo "===================================="
echo ""

cd /home/bruno/Sistemas_De_Apoio/Sistemas_de_Apoio_10

echo "1. Verificando diretório de uploads..."
if [ -d "storage/app/streaming/uploads" ]; then
    echo "   ✅ Diretório existe"
    
    file_count=$(ls -1 storage/app/streaming/uploads 2>/dev/null | wc -l)
    echo "   Arquivos encontrados: $file_count"
    
    if [ $file_count -gt 0 ]; then
        echo ""
        echo "2. Listando arquivos:"
        ls -lh storage/app/streaming/uploads/ | tail -n +2 | while read line; do
            echo "   $line"
        done
        
        echo ""
        echo "3. Verificando permissões:"
        ls -ld storage/app/streaming/uploads/
        
        echo ""
        echo "4. Verificando espaço em disco:"
        df -h storage/app/streaming/uploads/ | tail -1
        
        echo ""
        echo "5. Verificando logs recentes de gravação:"
        if [ -f "storage/logs/laravel.log" ]; then
            echo "   Últimas gravações:"
            grep -i "chunk.*gravado\|arquivo.*inicializado\|finalizado" storage/logs/laravel.log | tail -5
        else
            echo "   ⚠️  Arquivo de log não encontrado"
        fi
    else
        echo "   ⚠️  Nenhum arquivo encontrado"
        echo "   Teste fazer upload de um arquivo"
    fi
else
    echo "   ❌ Diretório não existe"
    echo "   Criando diretório..."
    mkdir -p storage/app/streaming/uploads
    chmod 755 storage/app/streaming/uploads
    echo "   ✅ Diretório criado"
fi

echo ""
echo "6. Verificando permissões do diretório storage:"
ls -ld storage/app/streaming/ 2>/dev/null || echo "   ⚠️  Diretório não existe"

echo ""
echo "===================================="
echo "✅ Verificação concluída!"



