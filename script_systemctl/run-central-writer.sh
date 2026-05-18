#!/usr/bin/env bash
set -euo pipefail

DEFAULT_ROOT="/var/www/Sitemas"
FALLBACK_ROOT="/home/bruno/Sistemas_De_Apoio/Sistemas_de_Apoio_10"
PROJECT_ROOT="${APP_ROOT:-}"

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

cd "${PROJECT_ROOT}"

exec /usr/bin/env php artisan writer:central
