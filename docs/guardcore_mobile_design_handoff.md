# GuardCore Pro Field App: mobile design handoff

**Version:** 1.0 design concept, 24 November 2026  
**Scope:** Field guard experience, not the supervisor/administrator web console.  
**Status:** Complete UX specification and clickable demonstration; not a production application.

## 1. Product principles

- **Action first:** each page identifies the next permitted action, not merely its current data.
- **Safety first:** SOS always accessible, but emergency dispatch actions are governed by validated operational states.
- **No false confidence:** distinguish GPS permission, connectivity, GPS freshness, device sync, deployment verification and site protection.
- **Clear ownership:** Patriot supplies monitored activation details; GuardCore Pro owns dispatch and guard response state. Patriot write-back is server-side.
- **Works under poor connectivity:** capture permitted field work in an offline queue with explicit pending/synced/failed states. Emergency signaling requires confirmed delivery and a documented fallback.
- **Small-screen ergonomics:** one main action per screen; critical information visible first; minimum ~44px tap targets; persistent five-tab navigation.

## 2. App map

| Primary navigation | Main view | Supporting views |
|---|---|---|
| Home | Duty status, current assignment, next dispatch/patrol action | Shift details, current assignment, quick actions |
| Dispatch | Assigned/active/completed jobs | Job details, accept/decline, en route, arrive, field notes, evidence, complete, outcome |
| Patrol | Assigned route and progress | Checkpoint list, route map, history, start/finish |
| Scan | QR, NFC, manual fallback | Verified result, duplicate, wrong route, pending sync, camera permission error |
| More | Operations Hub | My shift, open shifts, swaps, passdown, incident report, notifications, messages, GPS/sync, settings, profile, support |

**Global:** SOS, critical alerts, online/offline indicator, location health, notification count, active user identity.

## 3. Design tokens

| Token | Example |
|---|---|
| Background | `#090E17` |
| Primary surfaces | `#121C2B`, `#172336` |
| Dividers | `#2B3B50` |
| Primary text | `#F3F6FC` |
| Secondary text | `#9BADC2` |
| Urgent/action | `#EE333B` |
| Verified/complete | `#2CCC83` |
| Caution/needs attention | `#F0B94C` |
| Informational | `#62A7FF` |
| Card radius | 17px |
| Minimum tap target | 44 x 44 CSS px |
| Main horizontal gutter | 17 to 20px |

Colors must always be accompanied by clear text and iconography. Respect font scaling, touch accessibility and safe areas.

## 4. Workflow contracts

### 4.1 Guard deployment and attendance

`Scheduled → Assigned → Arrived → On duty → Relieved/withdrawn → Completed`

- An assigned shift is not confirmed presence. Check-in must be validated by the server, against schedule, authorization and location or approved exception.
- Site protection is computed centrally from required coverage and eligible active deployments, not controlled by the guard's visual app status.
- Guard duty, patrol status and dispatch status are independent dimensions.
- Early withdrawal, shift swap and relief require authorization and audit history.

### 4.2 Dispatch

`Assigned → Accepted → En route → On site → Completion submitted → Supervisor reviewed → Closed`

Additional: Declined, cancelled, reassigned, response unsafe, support requested.

- **Assigned:** show customer/site, priority, safety instructions, accept/decline; decline requires reason.
- **Accepted:** show directions and communicate with Control; allow mark en route.
- **En route:** keep route, site and Control contact visible; allow arrival with permitted verification.
- **On site:** show incident note, findings, photos/evidence, safety check and request support.
- **Completion:** outcome and report required; create a separate supervisor review state.
- **Patriot:** separate `sync_pending`, `sync_succeeded` and `sync_failed` from guard state. Never display integration failure as if it were a guard action. Do not auto-complete Patriot alarm solely because GCS field work finished.

### 4.3 Patrol and checkpoint

`Assigned → In progress → All required checkpoints verified → Submitted → Reviewed/Completed`

- Only the assigned route and valid checkpoint identifier are accepted.
- Show `verified`, `duplicate`, `wrong_route`, `out_of_order` when order matters, `permission_denied`, and `pending_sync` explicitly.
- QR/NFC/manual validation happens server-side; offline captures must use secure event identifiers and upload timestamps to protect against replay or tampering.
- Do not make a scanned code count as validated until the verification rules are satisfied.

### 4.4 SOS

`Initiating → Sending → Server received → Control acknowledged → Responding → Resolved`

- SOS must be present on every primary screen.
- Show delivery state and explicit server acknowledgment, including timestamp and receiving desk when available.
- If no delivery confirmation, show urgent alternative contact instructions. Avoid presenting a failed transmission as successful.
- Support duplicate suppression, anti-accidental activation appropriate to the risk, audit logging and background delivery constraints.
- Acknowledgment is separate from incident resolution.

### 4.5 Notifications

- Routine assignments/reminders: in-app list with read/unread.
- Critical guard actions: persistent in-app alert, sound/vibration where permitted, acknowledgment and escalation.
- Never equate read, acknowledged and resolved.
- Recover alerts after reconnect, and display a banner for stale/offline status.

## 5. Screen-level requirements

### Home

Display identity, current assignment, duty, GPS freshness, SOS, active dispatch priority, patrol progress, and no more than four immediate quick actions. For no shift, show a purposeful empty state with a path to open shifts or the supervisor, not a disabled clock-in button without explanation.

### Dispatch list/detail

Separate Assigned, Active, Completed. Compact cards include activation reference, priority, site, relevant deadline, and real operational status. Detail uses state-dependent primary action; important location and Control contact stay readily accessible. Guard-facing UI does not display raw Patriot API JSON or infrastructure errors.

### Patrol

Show route, estimated checkpoints, completed/remaining counts, next checkpoint and ability to resume interrupted session. Map and history may be secondary tabs. Show offline/sync health without confusing it with patrol completion.

### Scan

Prefer automatic selection of the single active patrol; ask only if ambiguous. Primary scan action uses camera or NFC according to device support, with manual code fallback. Provide distinct confirmation/error states and clear next action.

### Operations Hub

Group: Shift and attendance; Field reports and passdown; Communications; Device and permissions; Support and account. Avoid nesting frequently used duty tasks more than two taps away.

### SOS and alerts

Always accessible, high contrast, visually distinct from routine actions, with clear progress states and alternative contact steps if failed.

## 6. Suggested backend contracts (conceptual, not existing API declarations)

- `GET /api/field/me/overview`
- `GET /api/field/shifts/current`
- `POST /api/field/shifts/{id}/check-in`
- `POST /api/field/shifts/{id}/check-out`
- `GET /api/field/dispatches?status=...`
- `POST /api/field/dispatches/{id}/accept`
- `POST /api/field/dispatches/{id}/decline`
- `POST /api/field/dispatches/{id}/en-route`
- `POST /api/field/dispatches/{id}/arrive`
- `POST /api/field/dispatches/{id}/complete`
- `GET /api/field/patrols/active`
- `POST /api/field/patrols/{id}/start`
- `POST /api/field/patrols/{id}/checkpoints/scan`
- `POST /api/field/patrols/{id}/submit`
- `POST /api/field/sos`
- `GET /api/field/alerts`
- `POST /api/field/alerts/{id}/acknowledge`

Use existing route naming and controllers if present; endpoints above are a design aid, not a request to duplicate them. All state mutations must validate server-side identity, assignment, expected state, timestamps, idempotency key and user permissions.

## 7. Connectivity and platform requirements

- If PWA: verify platform-specific background location, Web Push, NFC availability, camera lifecycle, battery behavior and offline storage. Do not promise capabilities that mobile browsers do not reliably support.
- If native/Flutter: implement OS permissions and foreground/background policies in the native client with battery and privacy considerations.
- Offline queue: typed records, local durable storage, retry with backoff, per-item sync status, and non-destructive conflict resolution.
- Critical SOS must never be represented as successfully delivered until an authenticated server acknowledgment is received.

## 8. Acceptance checks

1. Every primary tab renders meaningful data and empty/error states.
2. Dispatcher assignment remains separate from shift status and Patriot sync.
3. Guard can accept, go en route, arrive and submit findings using validated transitions.
4. A completion does not automatically close the original Patriot alarm.
5. One active patrol can be resumed and valid scans counted exactly once.
6. Offline scans are visibly queued and later synchronized/validated.
7. Critical notifications remain accessible when switching pages or reconnecting.
8. SOS displays sent/confirmed/acknowledged/failed states independently.
9. Site Protected/Unprotected derives from backend verified coverage, not GPS connectivity alone.
10. Permission denied, GPS stale, no shift, no dispatch, invalid checkpoint, API error and offline are designed and tested.
11. Narrow screen, large text, low contrast, touch targets, screen reader and safe-area tests pass.
12. No sample names/coordinates or fabricated server state appear as real customer data in production.

## 9. Prototype scope and limitations

`guardcore_mobile_prototype.html` is a **clickable local design demonstrator**. It deliberately uses sample data. It simulates dispatch transitions, scanning, shift status and emergency dialogs; it does **not** connect to Patriot, a real GPS provider, a server, an SOS endpoint, a QR scanner or an NFC device. No dispatch or emergency call is transmitted by the demo.

Before production, inspect the existing GuardCore Pro mobile project and implement this UX using actual APIs, authenticated data, server-controlled state machines, robust offline behavior, real mobile capabilities and automated tests.
