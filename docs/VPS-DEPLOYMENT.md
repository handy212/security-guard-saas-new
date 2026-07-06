# Ubuntu VPS Deployment

Deploy GuardOps on a raw Ubuntu VPS with SSH. Two supported paths:

| Path | Best for |
|------|----------|
| **Docker Compose** (recommended) | Fastest setup, matches local dev, includes queue + Reverb |
| **Native stack** | No Docker, traditional nginx + PHP-FPM |

**Minimum server:** Ubuntu 22.04 or 24.04, 2 GB RAM, 2 vCPU, 20 GB disk.

---

## 1. DNS

Point these records at your VPS public IP:

| Record | Type | Purpose |
|--------|------|---------|
| `app.yourdomain.com` | A | Central app / login |
| `*.yourdomain.com` | A | Tenant subdomains (`demo-security.yourdomain.com`) |

Set `TENANCY_BASE_DOMAIN=yourdomain.com` in `.env`.

**Using Nginx Proxy Manager on a separate server?** See **[NPM-PROXY.md](./NPM-PROXY.md)** — DNS points at NPM, not this VPS.

---

## 2. One-time server bootstrap

SSH in as a user with sudo:

```bash
ssh deploy@YOUR_SERVER_IP
```

Clone the repo (or upload it), then run:

```bash
cd /var/www
sudo git clone https://github.com/YOUR_ORG/security-guard-saas-new.git guardops
cd guardops
sudo bash deploy/bootstrap-server.sh --docker
```

Log out and back in so the `docker` group applies.

---

## 3. Docker path (recommended)

### 3.1 Configure environment

```bash
cd /var/www/guardops
cp .env.production.example .env
nano .env
```

Important values:

```env
APP_URL=https://app.yourdomain.com
APP_DEBUG=false

DB_DATABASE=guard_saas
DB_USERNAME=guardops
DB_PASSWORD=CHANGE_ME_STRONG

TENANCY_BASE_DOMAIN=yourdomain.com

# Reverb — public host/port (browser connects via nginx TLS)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=guardops
REVERB_APP_KEY=CHANGE_ME
REVERB_APP_SECRET=CHANGE_ME
REVERB_HOST=app.yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"

# Optional: S3 for uploads in production
FILESYSTEM_DISK=public
TENANT_FILES_DISK=public
```

Generate secrets:

```bash
openssl rand -hex 16   # REVERB_APP_KEY
openssl rand -hex 32   # REVERB_APP_SECRET
```

### 3.2 Build assets and start stack

Build frontend on the server (or commit `public/build/` from CI):

```bash
npm ci && npm run build
```

Start production stack (ports bound to localhost only):

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

First-run Laravel setup:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan storage:link
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=RolePermissionSeeder --force
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

Health check: `curl -s http://127.0.0.1:8080/up`

### 3.3 TLS with host nginx

```bash
sudo cp deploy/nginx/guardops.conf /etc/nginx/sites-available/guardops
sudo nano /etc/nginx/sites-available/guardops   # replace guardops.com with your domain
sudo ln -sf /etc/nginx/sites-available/guardops /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d app.yourdomain.com -d '*.yourdomain.com'
```

Visit `https://app.yourdomain.com`.

### 3.4 What runs in Docker

| Service | Role |
|---------|------|
| `nginx` | Serves `public/` (proxied by host nginx) |
| `app` | PHP 8.3-FPM |
| `mysql` | Database |
| `redis` | Cache, sessions, queues |
| `horizon` | Queue worker (`queue:work`) |
| `reverb` | WebSockets for dispatch control room |

### 3.5 Scheduler (cron)

Laravel scheduled jobs (analytics, compliance, missed patrols) need cron on the **host**:

```bash
crontab -e
```

Add:

```cron
* * * * * cd /var/www/guardops && docker compose exec -T app php artisan schedule:run >> /dev/null 2>&1
```

---

## 4. Native path (no Docker)

Bootstrap:

```bash
sudo bash deploy/bootstrap-server.sh --native
```

### 4.1 MySQL

```bash
sudo mysql
```

```sql
CREATE DATABASE guard_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'guardops'@'localhost' IDENTIFIED BY 'CHANGE_ME_STRONG';
GRANT ALL ON guard_saas.* TO 'guardops'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4.2 App setup

```bash
cd /var/www/guardops
cp .env.production.example .env
nano .env
```

Native overrides:

```env
DB_HOST=127.0.0.1
REDIS_HOST=127.0.0.1
FILESYSTEM_DISK=public
TENANT_FILES_DISK=public
```

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan key:generate
php artisan storage:link
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 4.3 nginx + Supervisor

Edit `deploy/nginx/guardops.conf`: comment out the Docker `proxy_pass` blocks and uncomment the native `root` / `php-fpm` block. Then:

```bash
sudo cp deploy/nginx/guardops.conf /etc/nginx/sites-available/guardops
sudo ln -sf /etc/nginx/sites-available/guardops /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d app.yourdomain.com -d '*.yourdomain.com'
```

```bash
sudo cp deploy/supervisor/guardops.conf /etc/supervisor/conf.d/guardops.conf
sudo supervisorctl reread && sudo supervisorctl update
```

Cron:

```cron
* * * * * cd /var/www/guardops && php artisan schedule:run >> /dev/null 2>&1
```

---

## 5. Updates after deployment

From the app directory:

```bash
./deploy/deploy.sh              # Docker
./deploy/deploy.sh --native     # Native
```

This pulls latest code, rebuilds containers or assets, runs migrations, and refreshes caches.

**Before each release:**

1. Back up the database: `docker compose exec mysql mysqldump -u guardops -p guard_saas > backup.sql`
2. Back up `storage/`
3. Optionally enable maintenance mode: `php artisan down` (or via docker exec)

Never run `migrate:fresh` in production.

---

## 6. Optional production hardening

- **Object storage:** set `FILESYSTEM_DISK=s3` and `TENANT_FILES_DISK=s3` with AWS credentials
- **Mail:** configure SMTP in `.env` for incident/SOS notifications
- **Paystack:** set `PAYSTACK_*` keys for billing
- **Fail2ban:** installed by bootstrap script; tune jails for nginx
- **Backups:** automate daily `mysqldump` + `storage/` sync to S3
- **Monitoring:** hit `/up` from an external uptime checker

---

## 7. Troubleshooting

| Symptom | Fix |
|---------|-----|
| 502 Bad Gateway | `docker compose ps` — ensure `app` and `nginx` are up |
| 500 after deploy | `docker compose exec app php artisan config:clear` then re-cache |
| Permission errors | `docker compose exec app chown -R www-data:www-data storage bootstrap/cache` |
| WebSockets not connecting | Check `REVERB_HOST` / `REVERB_PORT` / `REVERB_SCHEME` match public URL; verify nginx `/app` proxy |
| Tenant subdomain 404 | Wildcard DNS `*.yourdomain.com` must point to this server |
| Queue jobs stuck | `docker compose logs horizon` or `supervisorctl status` |
| MySQL/Redis exposed on `0.0.0.0` | Recreate with prod override: `docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --force-recreate` |
| nginx on public `8080` instead of `127.0.0.1` | Same — prod file uses `!reset` so dev ports are not merged in |
| `horizon` / `reverb` missing | `docker compose ps -a` then `docker compose logs horizon reverb` |

Logs:

```bash
docker compose logs -f app horizon reverb
tail -f storage/logs/laravel.log
```

---

## Quick reference

```bash
# Start
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Stop
docker compose down

# Update
./deploy/deploy.sh

# Shell into app
docker compose exec app bash
```
