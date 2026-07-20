#!/usr/bin/env bash
# Deploy or update GuardOps on an Ubuntu VPS.
#
# Docker (default):
#   ./deploy/deploy.sh
#   ./deploy/deploy.sh --docker
#
# Native stack:
#   ./deploy/deploy.sh --native
#
# Environment:
#   APP_DIR   — app root (default: repo root or /var/www/guardops)
#   BRANCH    — git branch to deploy (default: main)

set -euo pipefail

MODE="docker"
APP_DIR="${APP_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
BRANCH="${BRANCH:-main}"

usage() {
    echo "Usage: $0 [--docker|--native]"
    exit 1
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --docker) MODE="docker"; shift ;;
        --native) MODE="native"; shift ;;
        -h|--help) usage ;;
        *) echo "Unknown option: $1"; usage ;;
    esac
done

cd "$APP_DIR"

if [[ -d .git ]]; then
    echo "==> Pulling ${BRANCH}"
    git fetch origin
    git checkout "$BRANCH"
    git pull origin "$BRANCH"
fi

if [[ "$MODE" == "docker" ]]; then
    echo "==> Building and starting containers"
    docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

    echo "==> Running migrations"
    docker compose exec -T app php artisan migrate --force

    echo "==> Caching config/routes/views"
    docker compose exec -T app php artisan config:cache
    docker compose exec -T app php artisan route:cache
    docker compose exec -T app php artisan view:cache

    echo "==> Done (docker)"
    exit 0
fi

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

if [[ -f package.json ]]; then
    echo "==> Building frontend assets"
    npm ci
    npm run build
fi

echo "==> Running migrations"
php artisan migrate --force

echo "==> Caching config/routes/views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Reloading workers"
sudo supervisorctl restart guardops-queue guardops-queue-heavy guardops-reverb 2>/dev/null || true

echo "==> Done (native)"
