# Phased Roadmap — Implementation Status

This document tracks delivery of the commercial/enterprise roadmap for GuardOps SaaS.

## Phase 0 — Foundation freeze ✅

- [x] GitHub Actions CI (`.github/workflows/ci.yml`)
- [x] Shared Blade components (`page-header`, `stat-card`, `data-table`, `empty-state`, `map`)
- [x] Thin module upgrades (Calendar, Deployment Sheet, Playback, Analytics, Client Portal)
- [x] Tenancy documentation (`docs/TENANCY.md`)
- [x] Expanded tests (14 total)

## Phase 1 — Production pilot ✅ (scaffold)

- [x] Docker Compose stack (`docker-compose.yml`, `Dockerfile`, nginx config)
- [x] Production env template (`.env.production.example`)
- [x] Email notification pipeline (`NotificationDispatcher`, incident/SOS notifications)
- [x] Scheduled jobs (`guardops:analytics-snapshot`, `guardops:compliance-expiry`, `guardops:missed-patrols`)
- [x] Grouped role-based navigation (`config/navigation.php`)
- [x] Demo notification templates in seeder

## Phase 2 — Guard operations ✅ (web/PWA scaffold)

- [x] Guard mobile web app (`/guard` — clock-in, SOS, checkpoint scan, GPS)
- [x] Live guard location API (`POST /api/v1/location`)
- [x] `guard_locations` table + `GuardLocationService`
- [x] Leaflet map in dispatch control room and patrol playback
- [x] Reverb listener retained on control room

## Phase 3 — SMB commercial ✅ (scaffold)

- [x] Stripe billing service + checkout flow (`/billing/subscription`)
- [x] Stripe webhook endpoint (`POST /stripe/webhook`)
- [x] Plan limit service + middleware alias `plan.limits`
- [x] Isolated client portal layout (`layouts/portal.blade.php`)
- [x] Client-scoped portal data + approval workflow

## Phase 4 — Enterprise hardening ✅ (foundation)

- [x] Audit log service wired to incidents and SOS
- [x] Two-factor setup screen (`/settings/two-factor`) + middleware alias `two-factor`
- [x] SSO config stub (`config/sso.php`)
- [x] User `client_account_id` for portal isolation

## Phase 5 — Scale & ecosystem ✅ (foundation)

- [x] OpenAPI stub (`docs/openapi.yaml`)
- [x] Outbound webhook subscriptions (`/settings/webhooks`)
- [x] Analytics trend chart (CSS bar chart)
- [x] API v1 location endpoint documented

## Still required for full production

- Native mobile app (React Native / Flutter) or hardened PWA offline store
- Stripe signature verification with `stripe/stripe-php`
- TOTP library for real 2FA (currently setup key scaffold)
- SSO OIDC/SAML controller implementation
- File virus scanning, S3 production disks
- 100+ automated tests and browser tests
- Pen test and SOC2 evidence collection
