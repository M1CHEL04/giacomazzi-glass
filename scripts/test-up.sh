#!/bin/sh
# Levanta TEST con lo ya construido (sin git pull ni rebuild). Se corre desde
# ~/giacomazzi-glass-test. Para traer cambios nuevos usar deploy-test.sh.
set -e

cd "$(dirname "$0")/.."

echo "▶ Levantando stack de test..."
docker compose -f compose.test.yml up -d

echo "✔ Test arriba."
