#!/bin/sh
# Baja TEST on-demand. Se corre desde ~/giacomazzi-glass-test.
# Frena app-test y mysql-test, pero DEJA fileserver-test arriba: el dev local
# (Laragon) depende de él por SFTP (127.0.0.1:2222). Conserva volúmenes/datos.
set -e

cd "$(dirname "$0")/.."

echo "▶ Frenando app-test y mysql-test (fileserver-test queda arriba)..."
docker compose -f compose.test.yml stop app-test mysql-test

echo "✔ Test abajo. fileserver-test sigue disponible para el dev local."
