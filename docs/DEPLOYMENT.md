# Deployment Notes

Production deployment guide for GuardOps.

## Ubuntu VPS (recommended)

See **[VPS-DEPLOYMENT.md](./VPS-DEPLOYMENT.md)** for the full walkthrough:

- Docker Compose stack with host nginx + Certbot TLS
- Native nginx + PHP-FPM alternative
- DNS wildcard setup for multi-tenant subdomains
- Cron, updates, and troubleshooting

Helper scripts live in `deploy/`:

| Script | Purpose |
|--------|---------|
| `deploy/bootstrap-server.sh --docker` | One-time Ubuntu server prep |
| `deploy/deploy.sh` | Pull, build, migrate, cache |

## Stack requirements

- PHP 8.2+
- Nginx
- MySQL 8 or PostgreSQL 15+
- Redis (cache, sessions, queues)
- Laravel Reverb for realtime dispatch (optional; set `BROADCAST_CONNECTION=log` to disable)
- Object storage for documents/photos at scale (S3-compatible)
- Supervisor or Docker for queue workers + Reverb

## Useful commands

```bash
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:work
```

## Docker production

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

Schedule runner (host cron): `* * * * * php artisan schedule:run`
