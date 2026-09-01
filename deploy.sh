#!/usr/bin/env bash
#
# Zero-drama deploy for the prop firm platform.
# Run on the droplet from the project root: ./deploy.sh
#
# Prerequisites (one-time): git remote configured, .env present (never in git),
# Supervisor worker installed (see ops/propfirm-worker.conf), scheduler cron set.

set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

echo "==> Pulling latest code"
git pull --ff-only

echo "==> Installing PHP dependencies (production)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Rebuilding caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Fixing storage/cache ownership + permissions"
# Artisan run as root can leave root-owned files that php-fpm (www-data)
# cannot write — restore ownership so logging and uploads work.
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "==> Restarting queue workers"
# Requires the Supervisor group from ops/propfirm-worker.conf.
if command -v supervisorctl >/dev/null 2>&1; then
    sudo supervisorctl restart propfirm-worker:* || echo "   (worker group not installed yet — skipping)"
fi

echo "==> Done."
