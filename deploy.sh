#!/usr/bin/env bash
#
# Zero-drama deploy for the prop firm platform.
# Run on the droplet from the project root: ./deploy.sh
#
# Prerequisites (one-time): git remote configured, .env present (never in git),
# Supervisor worker installed (see ops/propfirm-worker.conf), scheduler cron set.

set -euo pipefail

# New files should stay group-writable so both the deploy user and www-data
# (php-fpm) can touch them. Ownership/setgid is set once — see
# ops/deploy-user-setup.md.
umask 002

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

# Deploys should run as the non-root `deploy` user (ops/deploy-user-setup.md).
# Running as root leaves root-owned files php-fpm can't write and disables
# Composer plugins — warn, but don't hard-fail in case of an emergency deploy.
if [ "$(id -u)" = "0" ]; then
    echo "   WARNING: running as root. Prefer 'su - deploy' then ./deploy.sh"
    echo "            (see ops/deploy-user-setup.md). Continuing anyway…"
fi

echo "==> Pulling latest code"
git pull --ff-only

echo "==> Installing PHP dependencies (production)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Running database migrations"
php artisan migrate --force

echo "==> Ensuring public storage symlink (CMS image uploads)"
# Serves uploaded logos/hero/testimonial images at /storage/*. No-op if present.
php artisan storage:link 2>/dev/null || true

echo "==> Rebuilding caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Ensuring storage/cache stay group-writable"
# Both the deploy user and php-fpm (www-data) write here. Ownership is
# <deploy>:www-data with setgid (set once in ops/deploy-user-setup.md), so we
# only need to guarantee group-write — no root required.
chmod -R ug+rwX storage bootstrap/cache
# If this deploy happened to run as root, normalise ownership too.
if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
fi

echo "==> Restarting queue workers"
# Requires the Supervisor group from ops/propfirm-worker.conf.
if command -v supervisorctl >/dev/null 2>&1; then
    sudo supervisorctl restart propfirm-worker:* || echo "   (worker group not installed yet — skipping)"
fi

echo "==> Done."
