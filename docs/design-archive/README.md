# Design Archive

Historical UI mockups and reference materials for GuardCore Pro visual design decisions.

## Files

| File | Description |
|------|-------------|
| [`securguard-dashboard-mockup.html`](securguard-dashboard-mockup.html) | Original SecurGuard dashboard concept (Inter, KPI strip, Chart.js, sidebar with icons) |
| [`nextadmin-ui-reference.md`](nextadmin-ui-reference.md) | NextAdmin demo catalog (settings, tables, charts, forms, alerts, tabs, tooltips, popovers, progress, pagination) — cross-app pattern reference |

## Adopted from SecurGuard mockup

- Dense KPI strip with secondary hint metrics
- Donut and bar chart patterns for incident analytics
- Icon + label navigation with left accent active state
- Subtle card entry animations (with `prefers-reduced-motion` respect)

## Adopted from NextAdmin (patterns only)

- Sectioned settings panels with Cancel/Save footers
- Dense tables: identity column, soft status chips, trailing row actions, “Showing N of M”
- Alert title + body banners; metric + delta chart pairing
- Form density: multi-column contact grids, clear label→control rhythm
- Shared components (2026-07-22 pass): `<x-alert>`, `<x-progress>`, pagination page chips + optional per-page, tabs `underline`/`pills`, form/section card `footer` slot
- See [`nextadmin-ui-reference.md`](nextadmin-ui-reference.md) for full URL index and GuardCore mapping

## Rejected / superseded

- Inter typography → **IBM Plex Sans**
- Light white sidebar → **zinc-950 command sidebar**
- Fixed sky accent → **per-tenant `--tenant-brand`** (fallback teal `#0f766e`)
- Soft card shadows → **border-led surfaces**
- Orange/red and cream/terracotta rebrands
- Font Awesome CDN (Heroicons inline SVG instead)
- Full visual redesigns that abandon the command-room system (keep enhancing the live Blade/Tailwind UI instead)
- NextAdmin React/Next.js markup or CSS wholesale (translate patterns into Blade components)

## Active system (2026 refresh)

- Product name: **GuardCore Pro**
- Accent ownership: **per-tenant** via CSS `--tenant-brand`
- Shell: dark sidebar, light canvas, open page headers
- Field PWA and client portal share the same token system
- Shared utilities: `.card-header`, `.list-row`, `.alert-banner`, `.quick-action`, tabular nums on KPIs
