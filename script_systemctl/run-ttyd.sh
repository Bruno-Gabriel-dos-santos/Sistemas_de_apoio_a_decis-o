#!/usr/bin/env bash
set -euo pipefail

PORT="${TTYD_PORT:-7681}"
CREDENTIAL="${TTYD_CREDENTIAL:-admin:senha123}"
SHELL_CMD="${TTYD_SHELL:-/bin/bash}"
SSL_CERT="${TTYD_SSL_CERT:-/etc/ssl/certs/ssl-cert-snakeoil.pem}"
SSL_KEY="${TTYD_SSL_KEY:-/etc/ssl/private/ssl-cert-snakeoil.key}"

CMD=(/usr/bin/env ttyd -p "${PORT}" -c "${CREDENTIAL}")

if [[ -n "${SSL_CERT}" && -n "${SSL_KEY}" ]]; then
    CMD+=(-S -C "${SSL_CERT}" -K "${SSL_KEY}")
fi

CMD+=("${SHELL_CMD}")

exec "${CMD[@]}"
