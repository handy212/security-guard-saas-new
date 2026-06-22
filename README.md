# GuardOps SaaS — Laravel + Livewire Security Guard Management System

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
| Guard (mobile API) | john.guard@test | password |

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

## Important Note

Run `composer install` on your development machine or server if `vendor/` is not present.

## Suggested Next Steps

1. Add production tenant resolution by domain/subdomain.
2. Connect Reverb/Echo for live control-room updates.
3. Add file upload storage for incident media, guard documents, and certifications.
4. Add PDF report exports using DomPDF.
5. Add mobile app or PWA scanner UI for guards.


## Enterprise gap review added
See `docs/ENTERPRISE-GAP-REVIEW.md` for the review of included modules, added enterprise modules, and remaining production work.


## Full Enterprise Completion

The package now includes expanded source modules for SaaS tenant management, branches, tenant settings, billing limits, HR records, shift marketplace, calendar, deployment sheets, break tracking, patrol playback, vehicle patrols, complaints, SLA/compliance policies, accounting exports and analytics snapshots. See `docs/FULL-ENTERPRISE-COMPLETION.md`.
