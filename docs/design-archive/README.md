# Design Archive

Historical UI mockups and reference materials for GuardOps visual design decisions.

## Files

| File | Description |
|------|-------------|
| [`securguard-dashboard-mockup.html`](securguard-dashboard-mockup.html) | Original SecurGuard dashboard concept (Inter, KPI strip, Chart.js, sidebar with icons) |

## Adopted from SecurGuard mockup

- Inter typography and 260px sidebar width
- Card radius (~14px) and soft shadows
- Icon + label navigation with left accent active state
- Dense KPI strip with secondary hint metrics
- Donut and bar chart patterns for incident analytics
- Subtle card entry animations (with `prefers-reduced-motion` respect)

## Rejected from SecurGuard mockup

- Flat 14-item nav list with duplicate Reports / Invoicer / Scheduler entries
- Full orange/red rebrand (GuardOps keeps zinc neutrals + sky accent)
- Font Awesome CDN (Heroicons inline SVG instead)
- Hardcoded mock data (wired to Livewire services)

## Active roadmap

See the UI/UX Refresh plan in the project for phased implementation status.
