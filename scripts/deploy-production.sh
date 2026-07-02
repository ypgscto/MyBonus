#!/usr/bin/env bash
#
# Deploy BONUSKU ke server production.
# Usage:
#   chmod +x scripts/deploy-production.sh
#   ./scripts/deploy-production.sh
#
# Variabel opsional:
#   APP_DIR=/var/www/bonusku   # path root project Laravel
#   BRANCH=main                # branch yang di-pull
#   RUN_SEED=0                 # set 1 hanya untuk instalasi pertama (php artisan db:seed)

set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
BRANCH="${BRANCH:-main}"
RUN_SEED="${RUN_SEED:-0}"

cd "$APP_DIR"

echo "==> [BONUSKU] Deploy dari $APP_DIR (branch: $BRANCH)"

if [ ! -f artisan ]; then
    echo "Error: file artisan tidak ditemukan. Pastikan APP_DIR mengarah ke root Laravel."
    exit 1
fi

echo "==> Git pull origin $BRANCH"
git fetch origin "$BRANCH"
git pull origin "$BRANCH"

echo "==> Composer install (production)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Migrate database"
php artisan migrate --force

if [ "$RUN_SEED" = "1" ]; then
    echo "==> Seed database (instalasi pertama)"
    php artisan db:seed --force
fi

if [ -f package.json ]; then
    echo "==> Build assets (npm)"
    npm ci
    npm run build
fi

echo "==> Storage link"
php artisan storage:link 2>/dev/null || true

echo "==> Cache optimize"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Deploy selesai."
echo "    Login super admin (jika baru seed): bashar.ypgs@gmail.com"
echo "    Ganti password segera setelah login pertama."
