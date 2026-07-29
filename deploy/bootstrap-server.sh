#!/usr/bin/env bash
# One-time Ubuntu VPS bootstrap for GuardOps.
#
# Usage:
#   curl -fsSL ... | bash -s -- --docker     # recommended
#   curl -fsSL ... | bash -s -- --native
#
# Or from a cloned repo:
#   sudo bash deploy/bootstrap-server.sh --docker

set -euo pipefail

MODE="docker"
APP_DIR="/var/www/guardops"
DEPLOY_USER="${SUDO_USER:-$USER}"

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

if [[ "${EUID}" -ne 0 ]]; then
    echo "Run as root: sudo bash $0"
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive

echo "==> Updating packages"
apt-get update
apt-get upgrade -y

echo "==> Installing base tools"
apt-get install -y curl git unzip ufw fail2ban

configure_firewall() {
    echo "==> Configuring firewall"
    ufw allow OpenSSH
    ufw allow 80/tcp
    ufw allow 443/tcp
    ufw --force enable
}

if [[ "$MODE" == "docker" ]]; then
    echo "==> Installing Docker"
    if ! command -v docker >/dev/null 2>&1; then
        curl -fsSL https://get.docker.com | sh
    fi
    usermod -aG docker "$DEPLOY_USER" || true
    systemctl enable --now docker

    echo "==> Installing host nginx + Certbot (TLS termination)"
    apt-get install -y nginx certbot python3-certbot-nginx
    systemctl enable nginx

    configure_firewall

    echo ""
    echo "Docker bootstrap complete."
    echo "Next:"
    echo "  1. Clone the app to ${APP_DIR}"
    echo "  2. cp .env.production.example .env && edit secrets"
    echo "  3. docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build"
    echo "  4. docker compose exec app php artisan key:generate"
    echo "  5. docker compose exec app php artisan migrate --force"
    echo "  6. docker compose exec app php artisan db:seed --class=RolePermissionSeeder --force"
    echo "  7. cp deploy/nginx/guardops.conf /etc/nginx/sites-available/guardops"
    echo "  8. certbot --nginx -d app.yourdomain.com -d '*.yourdomain.com'"
    echo ""
    echo "See docs/VPS-DEPLOYMENT.md for the full walkthrough."
    exit 0
fi

echo "==> Installing native stack (nginx, PHP 8.3, MySQL, Redis, Node, Supervisor)"
apt-get install -y \
    nginx certbot python3-certbot-nginx \
    mysql-server redis-server supervisor \
    php8.3-fpm php8.3-cli php8.3-mysql php8.3-redis php8.3-xml \
    php8.3-mbstring php8.3-curl php8.3-zip php8.3-bcmath php8.3-gd php8.3-intl \
    chromium fonts-liberation \
    nodejs npm

if ! command -v composer >/dev/null 2>&1; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

systemctl enable nginx php8.3-fpm mysql redis-server supervisor

configure_firewall

mkdir -p "$APP_DIR"
chown -R "$DEPLOY_USER:www-data" "$APP_DIR"

echo ""
echo "Native bootstrap complete."
echo "Next:"
echo "  1. Clone the app to ${APP_DIR}"
echo "  2. Follow docs/VPS-DEPLOYMENT.md (native path)"
echo ""
