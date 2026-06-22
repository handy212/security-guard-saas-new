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
- Feature test placeholders for tenant isolation and RBAC

## Install

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
php artisan migrate --seed
php artisan serve
```

Demo admin after seeding:

```text
Email: admin@demo.test
Password: password
```

## Important Note

The sandbox used to build this source package does not have Composer installed, so `vendor/` is not included. Run `composer install` on your development machine or server.

## Suggested Next Steps

1. Install a Laravel auth starter such as Breeze, Jetstream, or your preferred custom auth.
2. Add production tenant middleware rules by domain/subdomain.
3. Connect Reverb/Echo for live control-room updates.
4. Add file upload storage for incident media, guard documents, and certifications.
5. Add PDF report exports using DomPDF.
6. Add mobile app or PWA scanner UI for guards.


## Enterprise gap review added
See `docs/ENTERPRISE-GAP-REVIEW.md` for the review of included modules, added enterprise modules, and remaining production work.


## Full Enterprise Completion

The package now includes expanded source modules for SaaS tenant management, branches, tenant settings, billing limits, HR records, shift marketplace, calendar, deployment sheets, break tracking, patrol playback, vehicle patrols, complaints, SLA/compliance policies, accounting exports and analytics snapshots. See `docs/FULL-ENTERPRISE-COMPLETION.md`.
