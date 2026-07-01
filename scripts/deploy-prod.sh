#!/bin/sh
# Deploy de PRODUCCIÓN. Se corre desde ~/giacomazzi-glass (rama main).
# Nombra el servicio app-prod explícito → Compose NO reinicia el proxy.
# NO corre migraciones: las de prod se manejan por fuera, manualmente.
set -e

cd "$(dirname "$0")/.."

echo "▶ git pull origin main..."
git pull origin main

echo "▶ Rebuild + up app-prod (el proxy no se toca)..."
docker compose -f compose.prod.yml up -d --build app-prod

echo "✔ Deploy de prod completo. (Migraciones: manual, aparte.)"
