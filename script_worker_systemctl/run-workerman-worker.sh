#!/usr/bin/env bash
set -euo pipefail

DEFAULT_ROOT="/var/www/Sitemas"
FALLBACK_ROOT="/home/bruno/Sistemas_De_Apoio/Sistemas_de_Apoio_10"
PROJECT_ROOT="${APP_ROOT:-}"
PORT="${1:-${WORKER_PORT:-}}"
HOST="${WORKER_HOST:-127.0.0.1}"

if [[ -z "${PROJECT_ROOT}" ]]; then
    if [[ -d "${DEFAULT_ROOT}" ]]; then
        PROJECT_ROOT="${DEFAULT_ROOT}"
    elif [[ -d "${FALLBACK_ROOT}" ]]; then
        PROJECT_ROOT="${FALLBACK_ROOT}"
    else
        echo "Erro: defina APP_ROOT apontando para o projeto."
        exit 1
    fi
fi

if [[ -z "${PORT}" ]]; then
    echo "Uso: WORKER_PORT=20001 $0 ou informe a porta como argumento."
    exit 1
fi

cd "${PROJECT_ROOT}"

exec /usr/bin/env php worker-server.php start --host="${HOST}" --port="${PORT}"






