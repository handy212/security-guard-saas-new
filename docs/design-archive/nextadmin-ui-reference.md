# NextAdmin UI Reference Archive

Offline catalog of NextAdmin demo surfaces archived for GuardCore Pro admin web UX. Use as **pattern reference only** — do not copy Next.js/React markup or abandon the live Blade/Tailwind command-room system.

- Source product: [NextAdmin](https://nextadmin.co/) (Next.js + Tailwind admin kit)
- Live demo root: https://demo.nextadmin.co/
- Archived: 2026-07-22
- Scope: admin web console only (not field PWA)

## Reference index

| Area | Demo URL | Patterns to study |
|------|----------|-------------------|
| Settings | https://demo.nextadmin.co/pages/settings | Sectioned settings cards, photo upload dropzone, Cancel/Save footer pairs, personal info form density |
| Manage team | https://demo.nextadmin.co/manage-team | Staff table, per-page selector, verified/role chips, row action menus, result count + page nav |
| Pro form layout | https://demo.nextadmin.co/forms/pro-form-layout | Multi-column contact form, survey radios/checkboxes, consistent label→control spacing |
| Tables | https://demo.nextadmin.co/tables | Channel/product tables, status cells, invoice action links, tabular money |
| Pro tables | https://demo.nextadmin.co/tables/pro-tables | Dense people grids, role badges, Edit/Action column alignment |
| Advanced chart | https://demo.nextadmin.co/charts/advanced-chart | KPI + sparkline pairing, visitors analytics, payments received/due, weekly profit |
| Alerts | https://demo.nextadmin.co/ui-elements/alerts | Attention / success / error banner variants with title + body |
| Tabs | https://demo.nextadmin.co/ui-elements/tabs | Underline tabs, pill tabs, vertical settings-style tabs |
| Tooltips | https://demo.nextadmin.co/ui-elements/tooltips | Four-direction tooltips, light/dark tip styles |
| Popovers | https://demo.nextadmin.co/ui-elements/popovers | Four-direction popovers, button-triggered content |
| Progress | https://demo.nextadmin.co/ui-elements/progress | Linear bars, labeled %, stacked/toned variants |
| Pagination | https://demo.nextadmin.co/ui-elements/pagination | Numbered pages, compact prev/next, bordered page chips |

## Pattern inventory (cross-app)

### Forms & settings
- Group fields into titled panels (Personal Information, Your Photo), not one flat scroll
- Primary actions sit as Cancel + Save pairs at the foot of each panel
- Upload: drag-drop zone + clear file constraints (type/size)
- Pro layouts: 2-column name/contact grids; surveys use radio groups + multi-check lists

### Tables & team
- Sticky identity column (name/avatar) + muted secondary (email)
- Status as soft chips (Paid / Unpaid / Pending / Verified), not rainbow pills
- Row actions in a trailing column (View / Edit / Delete / Download) or overflow menu
- Footer: per-page select + “Showing N of M” + page numbers

### Charts
- Pair a headline metric with a small trend delta (+2.5%)
- Split money overview into Received vs Due
- Keep chart chrome quiet so the series reads first

### Feedback & chrome
- Alerts: title + short body; tone via border/background (attention / success / error)
- Tabs: one active underline or left accent; avoid equal-weight card tabs
- Tooltips/popovers: placement API (top/right/bottom/left); short copy only
- Progress: determinate bar + optional % label; prefer brand/tenant accent for fill
- Pagination: clear current page; disable edges when at bounds

## GuardCore mapping

| NextAdmin surface | GuardCore targets |
|-------------------|-------------------|
| Settings | `settings-hub`, KYG page settings, branding, staff users |
| Manage team | Staff user index, roles/permissions boards |
| Pro form layout | Client/guard create-edit, invoice/estimate forms, deploy wizard |
| Tables / Pro tables | Guards, clients, sites, invoices, payments, expenses indexes |
| Advanced chart | Dashboard overview, incident analytics, payroll summaries |
| Alerts | `.alert-banner`, flash messages, SOS/attention strips |
| Tabs | `page-toolbar` tabs, settings nav sections |
| Tooltips / popovers | Header help, row overflow menus, dispatch quick actions |
| Progress | Onboarding/deploy wizard, payroll runs, upload jobs |
| Pagination | Shared `<x-pagination>` / Livewire list footers |

## Adopt vs reject

### Adopt (fit into existing system)
- Sectioned settings with per-panel Cancel/Save
- Table density: identity + muted meta + trailing actions
- Soft status chips (aligns with `.status-chip`)
- Alert title + body structure (aligns with `.alert-banner`)
- Chart metric + delta pairing beside series
- Pagination “Showing N of M” clarity

### Reject / do not port
- Next.js/React component tree or NextAdmin CSS wholesale
- Generic SaaS purple/indigo accents → keep `--tenant-brand` / teal fallback
- Soft multi-layer card shadows → keep border-led `.panel-surface`
- Inter / default stacks → keep **IBM Plex Sans**
- Light white sidebar → keep **zinc-950** command sidebar
- Rounded-full rainbow pills and glow effects

## How to use this archive

1. Open the live demo URL when implementing or polishing a matching GuardCore surface.
2. Translate structure (grouping, hierarchy, density) into Blade + existing utilities (`.card-header`, `.list-row`, `.board-item`, `.status-chip`, `.alert-banner`, `.meta-tile`).
3. Prefer enhancing shared components under `resources/views/components/` over one-off page styling.
4. Re-check this file when adding new admin CRUD hubs so tables/forms/alerts stay consistent.
