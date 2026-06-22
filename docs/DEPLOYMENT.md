# Deployment Notes

Recommended production services:

- PHP 8.2+
- Nginx
- MySQL 8 or PostgreSQL 15+
- Redis
- Laravel Horizon for queues
- Laravel Reverb for realtime control-room events
- Object storage for documents/photos/videos
- Supervisor for queue workers

Useful commands:

```bash
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:work
```
