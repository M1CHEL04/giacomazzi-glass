#!/bin/sh
# Deploy de TEST. Se corre desde ~/giacomazzi-glass-test (worktree rama test).
# Test es descartable → migra automático.
set -e

cd "$(dirname "$0")/.."

echo "▶ git pull origin test..."
git pull origin test

echo "▶ Rebuild + up de todo el stack de test..."
docker compose -f compose.test.yml up -d --build

echo "▶ Migraciones de test..."
docker compose -f compose.test.yml exec -T app-test php artisan migrate --force

echo "✔ Deploy de test completo."
