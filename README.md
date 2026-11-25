# GuardCore Pro — Laravel + Livewire Security Guard Management System

This is a full source starter for an enterprise security guard management SaaS. It is designed for Laravel, Livewire, Tailwind, RBAC, multi-tenant data isolation, guard scheduling, GPS attendance, QR/NFC patrol checkpoints, incident reporting, client portal, dispatch, billing, payroll foundation, and SaaS subscriptions.

## Included Modules

- SaaS tenant/company management foundation
- Subscription plan and tenant subscription tables
- User/role/permission management using Spatie Permission
- Client management
- Site/location management with geofence fields
- Site posts and post orders foundation
- Guard/officer management with documents/certifications foundation
- Shift scheduling and guard assignment with conflict detection service
- Attendance clock-in/clock-out service with geofence validation
- Patrol routes, QR/NFC checkpoints, patrol sessions, checkpoint scans
- Incident reporting, approval, closing workflow
- Daily activity reports and approval
- Dispatch/control-room dashboard, live guards, SOS alerts
- Client portal dashboard for proof of service
- Billing and invoice generation foundation
- Payroll timesheet generation foundation
- API routes for future mobile app/PWA guard interface
- Demo data seeder
- Feature tests for tenant isolation, RBAC, and mobile API authorization

## Install

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
npm install
npm run build
php artisan migrate:fresh --seed
php vendor/bin/phpunit
php artisan serve
```

For real-time dispatch, set `BROADCAST_CONNECTION=reverb` in `.env` and run `php artisan reverb:start` in a second terminal.

Subdomain tenant access: set `TENANCY_BASE_DOMAIN=guardops.test` and access `http://demo-security.guardops.test` (after DNS/hosts setup).

## Authentication

All web and API routes (except login) require authentication. Multi-tenant isolation is enforced via the `tenant` middleware and `BelongsToTenant` model scope.

| Role | Email | Password |
|------|-------|----------|
| Company admin | admin@demo.test | password |
| Guard (mobile API / web app) | john.guard@test | password |

## Roadmap implementation (Phases 0–5)

See `docs/PHASED-ROADMAP.md` for full status. Highlights:

- **CI/CD:** GitHub Actions workflow
- **Docker:** `docker-compose up` for nginx, PHP, MySQL, Redis, queue worker, Reverb
- **Notifications:** Email alerts for incidents, SOS, compliance, missed patrols
- **Scheduled jobs:** Analytics snapshots, compliance expiry, missed patrol detection
- **Guard field app:** `/guard` mobile PWA — installable, offline queue, camera QR scan
- **Maps:** Leaflet maps in dispatch and patrol playback
- **Billing:** Paystack checkout at `/billing/subscription` (cards, bank, USSD)
- **UX:** Onboarding checklist, dashboard trend charts, design system components
- **Client portal:** Isolated layout with client-scoped data
- **Enterprise:** Audit logs, 2FA setup, webhook subscriptions, OpenAPI stub

## Production deployment (Ubuntu VPS)

See **`docs/VPS-DEPLOYMENT.md`** for the full guide. Quick start:

```bash
sudo bash deploy/bootstrap-server.sh --docker
cp .env.production.example .env   # edit DB, domain, Reverb secrets
npm ci && npm run build
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=RolePermissionSeeder --force
```

Updates: `./deploy/deploy.sh`

Schedule runner (host cron): `* * * * * cd /var/www/guardops && docker compose exec -T app php artisan schedule:run`

## Security hardening included

- Session login with protected Livewire routes
- Tenant middleware and global tenant scoping on all tenant-owned models
- Spatie permission checks on sensitive Livewire actions
- Policy-based authorization for core models
- Mobile API IDOR protection and rate limiting
- Secured enterprise API controller (replaces open closure routes)
- Schema reconciliation migration aligning models and database
- Offline sync queue job processor

Demo admin:

```text
Email: admin@demo.test
Password: password
```

## Guard deployment

- Choose a client, site, date, and optional post, then an existing shift needing
  coverage or a new shift. Existing shifts must match the selected context.
- Only active, verified guards can be assigned. Leave, availability, overlapping
  shifts, duplicate assignments, and capacity are checked before continuing and
  again when saving. No-shows do not occupy a staffing position.
- Optional kit is issued immediately when the assignment is saved, including for
  future shifts and assignments awaiting confirmation. Skip kit if handover will
  happen later.
- Review the actual post, full shift dates/times, and coverage before deploying.
  Leave "Confirm assignment now" unchecked to create a pending assignment; use
  the deployment sheet to confirm it later. Success shows confirmation and any
  remaining staffing gaps separately.
- The deployment sheet uses labeled roster cards on smaller screens and a table
  on wide screens and in print. Overnight entries show their end date explicitly;
  date navigation, site filtering, and printing do not change assignments.
- The reassignment drawer keeps keyboard focus inside it; Escape closes it
  without changing the assignment and restores focus to its opener.
- Unassigning, cancelling, or replacing a guard does **not** record kit as
  physically returned or transfer it to a replacement. It remains issued to the
  original guard until its return is recorded in Assets.
- Refreshing the wizard restarts at Site with the URL's site/date context.
  Unsaved shift, guard, and kit selections are not restored as a draft.
- Equipment catalog setup creates categories only by default. Sample equipment
  belongs in explicitly requested demo setup, not inventory reads.

## Important Note

Run `composer install` on your development machine or server if `vendor/` is not present.

## Documentation

- `docs/VPS-DEPLOYMENT.md` — Ubuntu VPS deploy guide
- `docs/PHASED-ROADMAP.md` — phased delivery status
- `docs/ENTERPRISE-GAP-REVIEW.md` — gap analysis
- `docs/FULL-ENTERPRISE-COMPLETION.md` — module inventory
- `docs/TENANCY.md` — multi-tenant architecture
- `docs/openapi.yaml` — API v1 reference stub
