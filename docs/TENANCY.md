# Tenancy Architecture

GuardOps uses a **custom single-database multi-tenant** model, not `stancl/tenancy` database-per-tenant isolation.

## How it works

1. Every tenant-owned model uses `App\Models\Concerns\BelongsToTenant`.
2. `ResolveTenant` middleware binds `currentTenant` from subdomain, custom domain, or the authenticated user's `tenant_id`.
3. `TenantContext` helper reads the bound tenant for services and Livewire components.
4. Policies and API controllers enforce `tenant_id` on every query.

## Why not stancl/tenancy?

The package is listed historically but **not integrated**. Removing it avoids conflicting tenancy strategies. If you need database-per-tenant at scale, evaluate stancl as a dedicated migration project.

## Production DNS

- Wildcard: `*.yourdomain.com` → app server
- Custom domain per tenant: set `tenants.domain`
- Subdomain per tenant: set `tenants.subdomain` + `TENANCY_BASE_DOMAIN`
