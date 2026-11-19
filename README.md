# GuardCore Pro — Laravel + Livewire Security Guard Management System

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
| Guard (mobile API / web app) | john.guard@test | password |

## Roadmap implementation (Phases 0–5)

See `docs/PHASED-ROADMAP.md` for full status. Highlights:

- **CI/CD:** GitHub Actions workflow
- **Docker:** `docker-compose up` for nginx, PHP, MySQL, Redis, queue worker, Reverb
- **Notifications:** Email alerts for incidents, SOS, compliance, missed patrols
- **Scheduled jobs:** Analytics snapshots, compliance expiry, missed patrol detection
- **Guard field app:** `/guard` mobile PWA — installable, offline queue, camera QR scan
- **Maps:** Leaflet maps in dispatch and patrol playback
- **Billing:** Paystack checkout at `/billing/subscription` (cards, bank, USSD)
- **UX:** Onboarding checklist, dashboard trend charts, design system components
- **Client portal:** Isolated layout with client-scoped data
- **Enterprise:** Audit logs, 2FA setup, webhook subscriptions, OpenAPI stub

## Production deployment (Ubuntu VPS)

See **`docs/VPS-DEPLOYMENT.md`** for the full guide. Quick start:

```bash
sudo bash deploy/bootstrap-server.sh --docker
cp .env.production.example .env   # edit DB, domain, Reverb secrets
npm ci && npm run build
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=RolePermissionSeeder --force
```

Updates: `./deploy/deploy.sh`

Schedule runner (host cron): `* * * * * cd /var/www/guardops && docker compose exec -T app php artisan schedule:run`

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

## Guard deployment

- Choose a client, site, date, and optional post, then an existing shift needing
  coverage or a new shift. Existing shifts must match the selected context.
- Only active, verified guards can be assigned. Leave, availability, overlapping
  shifts, duplicate assignments, and capacity are checked before continuing and
  again when saving. No-shows do not occupy a staffing position.
- Optional kit is issued immediately when the assignment is saved, including for
  future shifts and assignments awaiting confirmation. Skip kit if handover will
  happen later.
- Review the actual post, full shift dates/times, and coverage before deploying.
  Leave "Confirm assignment now" unchecked to create a pending assignment; use
  the deployment sheet to confirm it later. Success shows confirmation and any
  remaining staffing gaps separately.
- The deployment sheet uses labeled roster cards on smaller screens and a table
  on wide screens and in print. Overnight entries show their end date explicitly;
  date navigation, site filtering, and printing do not change assignments.
- The reassignment drawer keeps keyboard focus inside it; Escape closes it
  without changing the assignment and restores focus to its opener.
- Unassigning, cancelling, or replacing a guard does **not** record kit as
  physically returned or transfer it to a replacement. It remains issued to the
  original guard until its return is recorded in Assets.
- Refreshing the wizard restarts at Site with the URL's site/date context.
  Unsaved shift, guard, and kit selections are not restored as a draft.
- Equipment catalog setup creates categories only by default. Sample equipment
  belongs in explicitly requested demo setup, not inventory reads.

## JWM5000ZG ingestion foundation

The backend accepts **normalized events**, not JWM binary packets. Both a future
Windows TCP/GPRS bridge can use this contract. A read-only USB capture utility,
an opt-in USB tag-scan uploader and an in-app configuration screen are available
below. A TCP listener, background USB synchronization and in-app fingerprint
enrollment are not included yet.
The additive JWM migrations require the normal reviewed migration rollout;
do not use `migrate:fresh` on an existing installation.

### Managing readers in the application

Open **Patrols > Patrol readers** (`/patrols/devices`), or use the Patrol readers
button on the patrol board. The screen requires `patrols.manage` and the
tenant's patrol-plan entitlement, including on subsequent Livewire actions.
It provides reader registration, enable/disable and strict-identification
settings, immutable guard handovers and exact external-ID mappings.
Handovers are entered with an explicit timezone and displayed in UTC.
Searchable target selectors show up to 50 tenant-owned matches; refine the
search to find additional guards/checkpoints. Reader and history lists paginate.

The UI and management API share the same validation, device locking, overlap,
token-revocation and immutable-mapping rules. The screen deliberately cannot
issue credentials, enroll fingerprints, command readers or upload events.
Connection status is **not monitored**, not an inferred online/offline state.
Missing migrations show an explicit setup notice; nothing migrates from the UI.
Disabling a reader revokes its bridge token, so re-enabling requires a new token
through the existing management API.

All paths below are relative to `/api/v1`. Management requires a tenant account
with `patrols.manage` and its normal Sanctum bearer token:

| Method | Path | Purpose |
|---|---|---|
| POST / GET | `/admin/jwm-devices` | Register / paginate devices |
| PATCH | `/admin/jwm-devices/{device}` | Set `is_active` and/or `require_device_identity` |
| POST / GET | `/admin/jwm-devices/{device}/assignments` | Record / paginate guard-use intervals |
| POST / GET | `/admin/jwm-devices/{device}/guard-mappings` | Bind / paginate external guard IDs |
| POST / GET | `/admin/jwm-devices/{device}/checkpoint-mappings` | Bind / paginate external checkpoint IDs |
| POST | `/admin/jwm-devices/{device}/bridge-token` | Issue or rotate one device's bridge credential |
| POST | `/jwm/devices/{device}/events` | Ingest one event using that device's bridge token |

Example request bodies, in order (replace IDs and dates with actual app values):

```json
{"name":"Patrol reader 1","reader_number":"READER-001"}
```

```json
{"guard_id":12,"starts_at":"2026-11-12T08:00:00Z","ends_at":"2026-11-12T16:00:00Z"}
```

POST the bridge-token endpoint with no body. Its `data.token` is shown only once;
store it securely on the bridge, never in source control or packet logs. It
expires after 30 days and is bound to this device and its issuing administrator.
Rotation revokes the previous credential. Disabling a device also revokes it;
re-enabling requires minting a new token. Bridge tokens cannot access other API
routes; ordinary admin tokens cannot ingest device events. Ingestion also
requires the issuer and tenant to remain active and authorized.

Submit GPS with `Authorization: Bearer <BRIDGE_TOKEN>`:

```json
{"event_id":"reader-001-gps-000042","type":"gps","occurred_at":"2026-11-12T09:30:00Z","latitude":5.6037,"longitude":-0.187}
```

Or a checkpoint scan, after starting the guard's patrol in the application:

```json
{"event_id":"reader-001-scan-000043","type":"checkpoint","occurred_at":"2026-11-12T09:35:00Z","patrol_session_id":45,"checkpoint_code":"CP-01"}
```

- Times require whole-second RFC3339 with `Z` or an explicit offset; events must
  not be in the future or before the Unix timestamp range used by tracking.
  Storage normalizes to UTC. Do not guess the device's timezone.
- Guard-use intervals are immutable in this slice, non-overlapping,
  start-inclusive and end-exclusive. Record a new adjacent interval for handover.
  The original occurrence time, not upload time or current holder, selects the
  guard. Bodies cannot supply a tenant or guard ID.
- GPS requires both valid coordinates. Accuracy remains unknown, and backfilled
  points do not trigger current-shift geofence alerts. Points feed guard location
  tracking/history, not the separate patrol-playback-point table.
- Checkpoints require an in-progress patrol for the mapped guard, a checkpoint
  on its route, and a time at/after patrol start and the last scan. Existing
  sequence/completion rules apply. Omit both coordinates or provide both; missing
  GPS is stored as null, never `0,0`. Closed/out-of-order offline reconciliation
  is deferred, not silently reopened.
- A new event returns `201` with `data` (journal record/result IDs) and
  `replayed:false`. An equivalent retry returns `200`, `replayed:true`, including
  after patrol completion. Same ID with different normalized content returns
  `409`. IDs are lowercase ASCII letters/digits plus `._:-`, max 100 characters,
  starting with a letter/digit. IDs must remain stable across retries; different
  IDs are not deduplicated. Cross-transport TCP/USB identity still needs device
  verification.
- Authentication/authorization failures return `401`/`403`, inaccessible devices
  `404`, validation/attribution failures `422`, and throttling `429` (60 requests
  per minute under the existing API policy). Retain failed events bridge-side
  for retries/operator review; do not generate a fresh ID on every retry.
- The replay journal and result are committed atomically. Retention may delete a
  location/scan and null its result reference, but keeps its replay key so retries
  do not recreate deleted data. Do not purge journal/device records while their
  operational records remain; device deletion is deliberately not exposed.

### External identifiers and strict checkpoint identification

These are application-side mappings, not enrollment commands or proof of a
fingerprint match. Do not enable automatic reader uploads until a real export
establishes what its guard/checkpoint IDs mean. No fingerprint images or
templates are accepted or stored.

Using an administrator token, POST these bodies to the respective mapping
endpoints for a registered device (the target records must already exist in
the same tenant):

```json
{"external_id":"0007","guard_id":12}
```

```json
{"external_id":"00A1","patrol_checkpoint_id":34}
```

IDs are opaque strings of 1-100 printable ASCII characters without whitespace.
Case and leading zeroes are significant: `0007` is not `7`, and `00A1` is not
`00a1`. They are not trimmed, case-folded or parsed as numbers. Uniqueness and
lookup use an exact-ID SHA-256 digest so database collation cannot change this
rule. IDs are scoped to a reader and mapping kind, not shared across readers.
Do not substitute a fingerprint feature-slot number for a personnel ID without
verified evidence that the exported record uses that identifier.

Mappings are immutable in this slice: there is no update/delete/rebind endpoint.
Deleting a target leaves its external ID reserved with a null target; new
uploads using it fail instead of silently pointing historical IDs at somebody
else. Mapping configuration is retained for the registered device's lifetime
and cascades on device/tenant deletion. Journaled external IDs follow the
existing event-journal retention policy. For corrections or vendor ID reuse,
stop uploads and review a historical mapping migration rather than editing
these rows directly.

Explicitly opt a reader into strict checkpoint identification with PATCH:

```json
{"require_device_identity":true}
```

This defaults to `false` for existing and newly registered readers, preserving
the original interval-based behavior. In strict mode, a new checkpoint event
must carry **both** external IDs instead of an internal checkpoint code:

```json
{"event_id":"reader-001-scan-000044","type":"checkpoint","occurred_at":"2026-11-12T09:40:00Z","patrol_session_id":45,"guard_external_id":"0007","checkpoint_external_id":"00A1"}
```

The mapped guard must agree with the guard-use interval at the original event
time, and the mapped checkpoint must still belong to that tenant and patrol
route. Missing, unknown, deleted, other-reader or conflicting mappings return
`422` without journal/scan/location writes. There is no fallback to the current
holder or internal code. Supply exactly one of `checkpoint_code` and
`checkpoint_external_id`; supplying both is invalid, even if they agree.

For GPS, `guard_external_id` is optional: without it, attribution is still
interval-based, **not device-identified**. Whenever a guard ID is supplied, even
on a reader with strict mode off, it must resolve and agree with the interval.
External IDs are included in replay comparison and retained in the event
journal. Replays are checked before current mapping/mode/patrol state, so
previously accepted legacy requests remain replayable after strict mode is
enabled. Changing an external ID on an accepted event returns `409`.

The additional migration is
`2026_11_16_000001_create_jwm_identity_mappings.php`; apply it only through the
normal reviewed rollout, after the initial JWM migration. Its rollback removes
the mappings, mode and external-ID journal columns, not existing guard,
checkpoint or operational event records.

Implementation references: Laravel 12
[transactional row locking](https://laravel.com/docs/12.x/queries#pessimistic-locking),
[foreign-key deletion actions](https://laravel.com/docs/12.x/migrations#foreign-key-constraints),
and [input-normalization configuration](https://laravel.com/docs/12.x/requests#input-trimming-and-normalization).

See [OpenAPI](docs/openapi.yaml) for field schemas. Run the isolated integration
tests with:

```bash
docker compose exec -T app ./vendor/bin/phpunit tests/Feature/JwmDeviceIngestionTest.php --no-progress
```

### Windows USB capture proof

[The USB utility](tools/jwm-usb/Read-JwmUsb.ps1) reads device identity and exports
the SDK's original bytes locally. It does **not** parse or upload events, request
bridge credentials, clear records, or change device settings. Its only device
operations are `OpenDevice`, `GetDeviceId`, `Verify(value)`, `GetRecords(path, 0)`,
and `CloseDevice`. The vendor workbooks disagree on `Verify`: the English
workbook's later instruction and C# demo use `1`, but the USB workbook uses `0`.
The default remains `1`; an operator may explicitly select `-VerificationValue 0`
for a vendor-approved comparison. The utility never tries alternative values
automatically or treats either mode as fingerprint identification.
Confirm the SDK's behavior with the vendor before using an operational reader.

On the Windows PC:

1. Use 64-bit Windows 10/11 and **64-bit Windows PowerShell 5.1**. Copy all four
   capture files (`Read-JwmUsb.ps1`, `JwmUsb.psm1`, `NativeDevice.cs` and
   `Test-JwmUsb.ps1`) in [tools/jwm-usb](tools/jwm-usb) to one folder, or copy that
   entire folder to include the uploader. No .NET SDK, Pester module, Laravel
   server or token is needed for capture.
2. Obtain the trusted vendor SDK locally and use its **x64** `libJComm.dll`.
   Vendor binaries are not bundled or tracked here. Install the vendor's USB
   driver if required; connect **one** reader and close other reader software.
3. Run the synthetic tests first, from the repository root. They compile the
   interop declarations but do not load a vendor DLL or communicate with USB:

   ```powershell
   powershell.exe -NoProfile -File .\tools\jwm-usb\Test-JwmUsb.ps1
   ```

4. Run the capture, replacing the path with your actual SDK location:

   ```powershell
   powershell.exe -NoProfile -File .\tools\jwm-usb\Read-JwmUsb.ps1 -SdkDllPath "C:\JWM\x64\libJComm.dll"
   ```

Follow your organization's PowerShell execution policy; do not disable security
controls to run the utility. The capture command exits `0` only after a nonempty
export, successful device close and final size/SHA-256 recording; failures exit
`1`. No patrol rows are printed. Loading the DLL uses its absolute local path,
with dependency searches limited to its directory and the Windows system
directory, not the current working directory.

Each run gets a new directory under
`%LOCALAPPDATA%\GuardOps\JwmUsbExports`, outside the repository:

- `records.txt`: unmodified vendor bytes, including any unknown encoding.
- `manifest.json`: capture time, numeric device identity, selected verification
  value, SDK DLL SHA-256, native return codes, export byte count/SHA-256, errors
  and status (`captured`, `failed`, or `in_progress`
  if interrupted). `captured` means bytes were exported, **not** that their
  record identities, timestamps or GPS meaning have been verified.

Existing exports are never reused or overwritten. Failed/partial exports remain
local for investigation and are never automatically retried or uploaded. Treat
both files as sensitive operational data: keep them in a private folder, do not
commit them or place them in shared/cloud folders, and remove them according to
your organization's retention policy after the proof is reviewed.

The legacy export API accepts ANSI paths. To avoid silently writing to a wrong
path, this proof requires ASCII-only output paths shorter than 260 characters
(including generated filenames). If your Windows profile path contains other
characters, specify a short **private directory you own** using
`-OutputRoot "C:\JwmUsbExports"`; ensure only your account can access that folder.
The synthetic test command also accepts `-OutputRoot` if your temporary directory
is non-ASCII or too long; its synthetic files are removed after the tests.

Troubleshooting:

- Open failure: check the USB driver/cable, single-device connection and other
  reader software. A 32-bit process is refused before device access.
- Load failure: verify the x64 DLL and vendor dependencies; the native Windows
  error code is included. No fallback DLL or alternate verification value is
  tried automatically.
- Empty/missing export: this is not reported as a populated capture. The device
  may contain no records, or exporting may have failed. The workbook's Functions
  sheet, row 54, defines nonnegative return values as successful communication;
  `GetRecords: 0` is not documented as a count of zero records. Validate an
  existing record using the device's normal identification/checkpoint workflow,
  not a tag-only test on a reader requiring fingerprint identification.
- Export/close failure: inspect local `manifest.json`, preserve partial bytes,
  and reconnect before retrying. Do not treat an interrupted run as complete.

The first controlled device run must verify: the expected reader ID; whether
records remain on the device; repeat-export identity stability; actual column
structure; device clock/timezone; GPS units/datum; and checkpoint tag mapping.
Do not assume `lineid` is stable or matches TCP IDs, guess timezone/coordinate
conversions, or map device guard-card IDs directly to app guards. Only after
these checks should normalization and authenticated API uploads be added.

For fingerprint-identifying readers, identity verification is an additional
release gate. The USB workbook lists a `guardid` export column, but does not
establish that it is the fingerprint-identified person's ID. The TCP guide calls
`GuardID` a guard card number; these meanings must not be assumed interchangeable.
Confirm the exact model/firmware and compare a known existing patrol record in
the vendor application with its USB export locally. Check whether identity is
present on each record or carried forward from a separate identification event.
Do not enroll or export fingerprints for this investigation; fingerprint images
and templates do not belong in this integration.

The backend checks supplied device person IDs against exact tenant/reader-scoped
mappings and the occurrence-time device handover interval. Verify the reader's
identity semantics before using these mappings for uploads. Never silently
attribute a conflicting or unidentified record to the currently assigned guard.
No independent biometric identity verification is claimed by the ingestion API.

### Opt-in Windows USB tag uploads

[Send-JwmUsbCapture.ps1](tools/jwm-usb/Send-JwmUsbCapture.ps1) validates a completed
capture and previews the selected count by default. It issues no device commands.
Only `-Upload` sends events to the existing device-bound API. The capture/export
files are retained unchanged; no credentials or additional event files are saved.
Keep the seven capture/uploader source and test files together on Windows.

This adapter is deliberately limited to the observed 13-column TSV tag format:
type `2`, exact guard/checkpoint identifiers, `yyMMddHHmmss`, no image link and
empty `()` content. Years are explicitly 2000-2099, not the machine's two-digit
year pivot. It accepts ASCII data (optionally with a UTF-8 BOM), checks the
original export size/SHA-256 and expected physical reader number, and requires
an explicit fixed reader offset and UTC selection window. Manifest input is
limited to 64 KiB and exports to 10 MiB. The hash detects changed files; it is not
a cryptographic signature proving the reader's authenticity.

The window is start-inclusive/end-exclusive and must cover **one patrol
session**. Rows outside it are counted and excluded, not silently uploaded into
that session. Empty or space-only separator lines are counted and ignored without
rewriting the export or renumbering source lines. Tab-only or otherwise malformed
records are not ignored: each record must have 13 columns and a valid timestamp.
Unsupported types, content or image links inside the window stop the entire
preview before network access. Selected records must already be chronological.
All GPS metadata is intentionally omitted until its positioning semantics are verified; zeroes
are never turned into a claimed GPS position.

Because native `lineid` can be `-1`, event identity is
`usb-tag-v1-` plus SHA-256 of UTF-8
`jwm-usb-tag-v1<TAB>guardid<TAB>siteid<TAB>yyMMddHHmmss`.
It does not include file name, row number, native line ID, upload time, session,
offset or GPS metadata. Re-exporting the same scan therefore retains its key;
changing the selected session/offset conflicts rather than duplicating an
already accepted event. Two selected rows with the same guard/tag/device second
are ambiguous and rejected, not collapsed. This identity rule is USB-only;
do not ingest the same reader through another bridge/transport until shared
identity semantics are verified. No exactly-once physical-scan guarantee is
possible when the reader exports indistinguishable records.

Before uploading, register the physical reader, enable strict identification,
map its exact external guard/checkpoint IDs and record the guard-use interval
in **Patrol readers**. Start that guard's patrol in the application **before**
making the test scans. The session must still be in progress when new events
arrive; historical captures do not bypass the existing closed/out-of-order
patrol checks. Obtain a device-bound bridge token through the existing
`POST /api/v1/admin/jwm-devices/{device}/bridge-token` management endpoint.
Never use an ordinary admin token for ingestion or share either token in chat.

From the Windows tools folder, substitute your actual capture directory,
physical reader number, application patrol session ID and test time window:

```powershell
$selection = @{
    CaptureDirectory = 'C:\Private\JwmUsbExports\capture-YOUR-CAPTURE'
    ReaderNumber = '123456'
    PatrolSessionId = 7
    UtcOffset = '+00:00'
    FromUtc = '2026-11-16T20:00:00Z'
    BeforeUtc = '2026-11-16T21:00:00Z'
}
.\Send-JwmUsbCapture.ps1 @selection

# Only after reviewing the preview and destination:
.\Send-JwmUsbCapture.ps1 @selection -Upload -ApiBaseUrl 'https://YOUR-APP/api/v1' -DeviceId 5
```

`DeviceId` is the application's registered reader row ID; `ReaderNumber` is the
physical reader identity in the manifest. The token prompt is hidden and the
credential is used only in memory. HTTPS with normal certificate validation is
required, redirects are refused and cookies/default Windows credentials are
not sent. For local development only, `-AllowLoopbackHttp` permits an HTTP URL
with literal `127.0.0.1` or `[::1]`; it never permits a remote HTTP host. Ensure
the application is actually reachable at that loopback address from **Windows**,
not only from the Linux development environment.

Events are sent sequentially, paced at least 1.1 seconds apart. Only matching
`201/replayed:false` or `200/replayed:true` acknowledgments count as success.
Any HTTP, timeout, malformed acknowledgment or API validation error stops the
run; there is no automatic retry or changed-argument fallback. Already accepted
rows remain committed, and a timed-out request may have committed. Retain the
capture and rerun **identical arguments** after resolving the error; the server's
replay ledger prevents acknowledged events being created twice.
Validation/preview errors before the sender is called explicitly report that no
upload was attempted; partial-upload warnings apply only after calling the sender.

Run both isolated synthetic suites (no vendor DLL, USB or network access):

```powershell
.\Test-JwmUsb.ps1
.\Test-JwmUsbUpload.ps1
```

Implementation references:
[HttpClient redirect control](https://learn.microsoft.com/en-us/dotnet/api/system.net.http.httpclienthandler.allowautoredirect?view=netframework-4.8.1)
and [exact offset-aware parsing](https://learn.microsoft.com/en-us/dotnet/api/system.datetimeoffset.parseexact?view=netframework-4.8.1).

#### Enrollment contract verification

The supplied SDK contains more enrollment detail than the English workbook
alone. Static review found the following in `usb/Z interface.xlsx`:

| Location | Documented fact | Remaining uncertainty |
|---|---|---|
| Function introduction, row 39 | `CollFingerEx(long guardid, char* fea, int alert)`; feature index greater than 0 and less than 10000; up to 200 features/device; at least three captures per finger | Buffer length, input/output ownership, capture sequencing, supported models and how feature indexes map to guard IDs |
| Downloaded data type, rows 7-10 | Personnel data uses a card number **or ID**, plus a name | Relationship to fingerprint feature indexes and the `guardid` exported in patrol records |
| Function introduction, row 49 | `SetCheckPoint(char*)` downloads checkpoint data | Exact model support and replacement-versus-merge semantics |
| Function introduction, rows 55 and 57 | `Verify(0)` before reads; nonnegative function results mean communication succeeded | Conflicts with the other supplied workbook/demo; success is not a record-count guarantee |

The x64 DLL exports `CollFingerEx`, `StartCollFinger`, `StopCollFinger`,
`GetFingerFea`, `SetFingerFea`, `SetGuards` and `GetRecordCount`, among others.
An export name is **not** a safe interop contract. In particular, do not allocate
a guessed fingerprint buffer, infer that `GetRecordCount` takes no arguments,
or expose enrollment/configuration endpoints until the vendor supplies:

- The matching C/C++ header and x64 example for the exact DLL build and model.
- Fingerprint buffer size/encoding/ownership and enrollment/cancellation steps.
- The mapping between fingerprint feature slots, personnel IDs and patrol
  record `guardid`, including what happens when identification fails.
- Confirmation of the required `Verify` value and record-export behavior.
- The supported browser-to-local-agent connection procedure and how it reports
  busy/disconnected states.

Portal inspection showed fingerprint collection/transfer controls, but no
enrollment was invoked. Its disconnected indicator and failed Socket.IO requests
are observations from that browser session, not a diagnosis of a Windows USB
driver or evidence of a particular local-agent port. Do not change server ports,
disable TLS checks, copy vendor session tokens, or automate undocumented portal
endpoints to work around the connection problem.

#### Remaining SDK package review

The package inventory contains 1,752 files, of which 1,413 belong to the bundled
third-party JNA tree. The remaining vendor material was checked for enrollment
contracts and connection setup, including both legacy Word protocol documents,
the USB examples/backups in C#, C++, Java, Delphi and VB6, and the contents of
both vendor `.7z` archives. This was static inspection only, not hardware
verification or execution of the vendor demos.

- The archived C# main form, designer, project and entry point are identical to
  their unpacked copies. The archive does not supply an additional enrollment
  example or SDK header.
- Full text extraction of both Word documents confirms they describe TCP record
  parsing, guard ID tags and checkpoint tags, not fingerprint enrollment buffer
  layouts or a browser-to-local USB agent.
- The Java and VB6 examples also use `Verify(0)`, supporting the USB workbook's
  instruction, while the C# example uses `Verify(1)`. Some examples additionally
  set the clock or clear records; do not run them unmodified as read-only probes.
- The Delphi declarations include a read-only `GetVersion(var version:PAnsiChar)`
  function, but no fingerprint capture buffer contract. A declaration using a
  returned pointer still needs its lifetime/ownership clarified before reuse.
- No matching native enrollment header, USB driver installation package
  (`.inf`/`.sys`/`.cat`), MSI installer, or browser USB-agent implementation was
  found in the vendor material or listed vendor archive contents. The included
  install/uninstall scripts relate to the legacy TCP server, not proof of the
  portal's USB connection requirements.
- There are 13 loose `libJComm.dll` copies representing 11 distinct SHA-256
  builds. The archives add two more distinct builds. Their exported capabilities
  differ; same filename does not mean the same SDK.
- The top-level x64 DLL is 1,501,184 bytes, SHA-256
  `e8a732d375fceabe039a647813f5cc8faae86634f0b408190485c98d0af1613f`.
  This size matches the operator's reported file size, but size alone does not
  confirm the same build. Use the capture manifest's DLL hash for comparison;
  do not replace the vendor application's installed DLL with another copy.

The package therefore supports continuing read-only capture and designing
guard/checkpoint identifier mappings, but it does not yet provide a complete,
verified fingerprint-enrollment interop contract. Keep enrollment and automatic
uploads disabled until those specific gaps are resolved. Existing data-ingestion
tests do not establish device identification or biometric enrollment correctness.

## Important Note

Run `composer install` on your development machine or server if `vendor/` is not present.

## Documentation

- `docs/VPS-DEPLOYMENT.md` — Ubuntu VPS deploy guide
- `docs/PHASED-ROADMAP.md` — phased delivery status
- `docs/ENTERPRISE-GAP-REVIEW.md` — gap analysis
- `docs/FULL-ENTERPRISE-COMPLETION.md` — module inventory
- `docs/TENANCY.md` — multi-tenant architecture
- `docs/openapi.yaml` — API v1 reference stub
