# Enterprise Gap Review

This review compares the source package against enterprise security workforce and guard-tour platforms.

## Strong coverage already included
- Multi-tenant SaaS foundation
- Tenant-scoped core models
- Auth/RBAC package dependencies
- Client, site, guard, shift, attendance, patrol, incident, dispatch, billing, payroll and analytics foundations
- Guard mobile API foundation
- Client portal foundation

## Added in this revision
- Visitor management / visitor log
- Checkpoint task checklists and task submissions
- Guard availability and leave requests
- Equipment/uniform issue and return tracking
- Client report approval/signature records
- Notification templates
- Offline mobile sync batch queue
- Compliance service for expiring documents/certifications
- Additional Livewire module entry screens
- Additional API routes for offline sync and visitor check-in/out

## Implemented and awaiting deployment verification

- Authentication UI, authorization policies, and two-factor authentication
- Leaflet/OpenStreetMap tracking and browser QR scanning
- Paystack subscription billing
- Guard PWA offline synchronization, including scan and attendance events
- Daily Activity Report review and approved PDF downloads
- Scheduled portal reminders, web push, and provider-backed messaging
- Docker production configuration and automated feature tests

## Open production acceptance

- Verify tenant isolation and permission coverage for sensitive workflows in the target deployment.
- Exercise GPS, QR/NFC, intermittent connectivity, alarm delivery, and notification recovery on real devices.
- Validate provider credentials, scheduler and queue health, storage access, backup restore, and file scanning.
- Confirm analytics definitions and any contracted reports or exports with the operating team.

## Enterprise feature sources reviewed
Enterprise guard-management platforms commonly include scheduling/timekeeping, guard tour tracking with GPS/NFC/QR, incident and activity reporting, client portals, compliance/license tracking, payroll/billing integrations, real-time monitoring, missed-checkpoint alerts, visitor workflows, post orders, and offline sync.
