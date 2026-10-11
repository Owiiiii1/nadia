#!/usr/bin/env bash
set -euo pipefail

if [[ "$(id -un)" != "deploy" ]]; then
    echo "deploy.sh must run as deploy" >&2
    exit 1
fi

cd /var/www/nadia

mkdir -p storage/logs
log="storage/logs/deploy-$(date -u +%Y%m%d-%H%M%S).log"
exec > >(tee -a "$log") 2>&1

echo "deploy start $(date -u +%Y-%m-%dT%H:%M:%SZ)"
echo "log $log"

maintenance=0

bring_up() {
    if [[ "$maintenance" == "1" ]]; then
        maintenance=0
        php artisan up || true
    fi
}

on_error() {
    local code=$?
    echo "deploy failed (${code})"
    bring_up
    exit 1
}
trap on_error ERR

branch="$(git rev-parse --abbrev-ref HEAD)"
if [[ "$branch" != "main" ]]; then
    echo "refusing to deploy from ${branch}"
    exit 1
fi

if [[ -n "$(git status --porcelain)" ]]; then
    echo "working tree is not clean"
    exit 1
fi

git pull --ff-only

composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

if php artisan migrate:status --no-ansi | grep -q 'Pending'; then
    echo "pending migrations, enabling maintenance mode"
    php artisan down --retry=15
    maintenance=1
fi

php artisan migrate --force
bring_up

php artisan optimize:clear
php artisan optimize
sudo systemctl reload php8.5-fpm
php artisan nadia:smoke

trap - ERR
echo "deploy finished $(date -u +%Y-%m-%dT%H:%M:%SZ)"
