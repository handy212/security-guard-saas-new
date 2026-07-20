# Design Archive

Historical UI mockups and reference materials for GuardCore Pro visual design decisions.

## Files

| File | Description |
|------|-------------|
| [`securguard-dashboard-mockup.html`](securguard-dashboard-mockup.html) | Original SecurGuard dashboard concept (Inter, KPI strip, Chart.js, sidebar with icons) |

## Adopted from SecurGuard mockup

- Dense KPI strip with secondary hint metrics
- Donut and bar chart patterns for incident analytics
- Icon + label navigation with left accent active state
- Subtle card entry animations (with `prefers-reduced-motion` respect)

## Rejected / superseded

- Inter typography → **IBM Plex Sans**
- Light white sidebar → **zinc-950 command sidebar**
- Fixed sky accent → **per-tenant `--tenant-brand`** (fallback teal `#0f766e`)
- Soft card shadows → **border-led surfaces**
- Orange/red and cream/terracotta rebrands
- Font Awesome CDN (Heroicons inline SVG instead)
- Full visual redesigns that abandon the command-room system (keep enhancing the live Blade/Tailwind UI instead)

## Active system (2026 refresh)

- Product name: **GuardCore Pro**
- Accent ownership: **per-tenant** via CSS `--tenant-brand`
- Shell: dark sidebar, light canvas, open page headers
- Field PWA and client portal share the same token system
- Shared utilities: `.card-header`, `.list-row`, `.alert-banner`, `.quick-action`, tabular nums on KPIs
