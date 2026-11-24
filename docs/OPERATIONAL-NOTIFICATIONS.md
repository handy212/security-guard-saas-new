# Operational notifications and alarms

## Audit

| Area | Findings |
| --- | --- |
| Inbox and delivery | The Laravel notifications table, queued mail, database, optional Web Push and SMS channels, and tenant templates were already in use. The header bell read only the latest 12 records and had no refresh signal, dedicated history, or critical acknowledgment. Existing template toggles control template selection and may fall back to default copy. |
| Dispatch and Patriot | Dispatch already persisted status transitions, assignment and response-team acknowledgment. Patriot intake was polled and alerted through a routine dispatch template. A stored dispatch could outlive a failed post-commit notification. Several operational status updates were limited to the Dispatch Center refresh path. |
| Broadcasts | Reverb and Echo were installed. Dispatch and SOS payloads used a public channel despite the existing authorization callback. The bell had no private per-user channel. The messenger also uses a public channel. Messenger thread authorization is a separate review. |
| SOS and incidents | SOS had a source-level acknowledgment, alongside a routine notification and dispatch broadcast. Incident submission used template or fallback notifications. There was no common acknowledgment deadline, alarm queue, or audit trail across modules. |
| Site protection | Tracking already calculated coverage using current shifts, verified attendance, and current GPS positions. Coverage changes were computed on demand and had no durable alarm lifecycle. Geofence, idle guard, patrol, and workforce services already sent selected routine notifications. |
| Routing, sound, and recovery | Routine notifications commonly targeted named tenant roles. Optional external channels depended on environment credentials. There was no common alarm audio readiness control, retry-safe acknowledgment deadline escalation, or durable reconnect path for critical attention. |

## Implementation plan

1. Preserve the existing notification channels and audit the source workflows.
2. Add indexed alarm persistence, transactional publication, acknowledgment and source-driven resolution.
3. Wire authenticated refresh events and reconnect recovery, then add the operator panel and history.
4. Connect SOS, Patriot, dispatch, incident and site coverage publishers. Add deadline escalation and tenant policy administration.
5. Verify the PHP workflows and browser behavior in an isolated tenant, then activate the feature in staging.

## Architecture

The existing `NotificationDispatcher` publishes alarm records and continues to send routine notifications through Laravel. The `notifications` table remains the per-user inbox. Its nullable `archived_at` column supports archive and restore. The `operational_alerts` table stores shared state, a unique tenant/event/source key, priority and severity, response links, acknowledgment deadline, and lifecycle timestamps. An existing `tenant_settings` key stores per-event overrides. Existing `audit_logs` records publication, the first acknowledgment, resolution, escalation, failed escalation routing, and policy edits.

New SOS, critical dispatch and critical Patriot publications are committed with their source records. Critical incident submission also persists its alarm in the incident transaction. The coverage monitor reads the Tracking Center's existing snapshot and persists one open alarm for each site and coverage condition. A successful transaction commits durable state before recording and queuing an empty refresh signal. The delivery row stores the configured broadcaster, number of attempts, most recent attempt time, handoff time, and terminal failure time. Failed attempts are added to the audit log. A socket event signifies a state change. Operators acknowledge explicitly.

## Event registry

| Event | Default presentation | Source and correlation |
| --- | --- | --- |
| `sos.raised` | Critical alarm | Guard SOS record ID. Dispatch created from that SOS shares the original alarm. |
| `patriot.intake` | Critical alarm | Patriot priority 1. Correlates by Patriot instance and activation when available, with a dispatch ID fallback. Other Patriot intake retains the existing routine notification. |
| `dispatch.critical` | Critical alarm | New critical Dispatch Center request by dispatch ID. |
| `incident.critical` | Critical alarm | New critical incident by incident ID. |
| `coverage.unprotected` | High or critical | Active coverage window. The site’s High or Critical coverage risk level makes loss of coverage critical. |
| `coverage.understaffed` | High | Active coverage window with some verified positions filled. |
| `coverage.restored` | Info | Verified protection after a monitored coverage deficit. |
| `dispatch.guard_declined` | High | Assigned guard submits a Patriot decline. |
| `patriot.connection_unavailable`, `patriot.connection_restored` | Warning, info | Scheduled Patriot intake changes health. Repeated polls in the same state reuse the recorded health state. |
| `patriot.writeback_failed`, `patriot.account_sync_failed` | High, warning | A writeback needs attention or an account enrichment job exhausts its retries. |
| `alarm.overdue` | Warning | Acknowledgment deadline elapsed. Creates one inbox reminder per eligible escalation recipient. |

The priority number controls queue order independently from severity. The critical panel shows up to five awaiting acknowledgment, ordered by priority and then oldest first. The Notification Center shows the complete paginated queue and history. Coverage observations that require verification leave the last confirmed alarm state intact.

## Operations

- Active tenant users with `dispatch.manage` can see and acknowledge the tenant’s dispatch, Patriot, SOS, and coverage alarms. Critical incidents additionally require `incidents.manage`. Routine inbox items remain scoped to their owner.
- The first authorized acknowledgment records the responsible user and timestamp under a database lock. Concurrent and repeated requests preserve that first action. Routine read and archive actions leave alarm state independent.
- The source workflow determines resolution. SOS response resolves through Dispatch. Patriot completion waits for confirmed writeback. Declined or withdrawn dispatches record their terminal outcome. Incident closure or rejection and verified site coverage also update alarm history.
- Use **Enable alarm audio** from the bell or the critical panel. Audio requires a browser gesture and may need to be enabled again after navigation. Volume and warning/high toast preferences are local to the browser and user. Audio is serialized within a tab and coordinated across tabs where Web Locks are available. Visual alerts stay available regardless of audio readiness.
- Open **Alarm policies** in the Notification Center with `dispatch.manage` and `settings.manage` to change an event’s acknowledgment deadline, numeric queue priority, or escalation recipient permission. An optional backup delay schedules a second in-app reminder after the acknowledgment deadline, including when the first stage has no eligible recipients. Acknowledgment or resolution stops both stages. Existing alarms retain their stored deadlines. The new deadline and priority apply when an alarm is published. Pending escalation resolves recipients under the current policy. The default is 120 seconds and escalation to active users with `settings.manage` and source access. Escalation with no eligible recipients stays pending and is retried.

## Deployment and troubleshooting

1. Leave `OPERATIONAL_ALERTS_ENABLED=false` during rollout. Run the migration and build frontend assets with the deployment’s public `VITE_REVERB_*` values. Configure `BROADCAST_CONNECTION=reverb` and the server-side `REVERB_*` credentials.
2. Set `OPERATIONAL_ALERTS_ENABLED=true` after checking recipient permissions and scheduled integrations. Reload cached configuration and restart the existing Reverb, queue workers and scheduler. It checks due alarms every ten seconds and coverage once a minute. Patriot’s existing scheduled polling controls detection latency upstream of GuardCore Pro.
3. Verify a private `user.{id}` subscription and, for a dispatch operator, a private `tenant.{id}.dispatch` subscription. Foreground clients reconcile every 15 seconds if the socket or subscriptions are unavailable and every minute when connected. Successful subscriptions refresh from the database after reconnect.
4. For a missing alarm, check the source record, tenant and permissions, `operational_alerts`, scheduler status, and queue worker or failed-job logs. For a missing reminder, check the registered deadline, acknowledgment status, and whether the tenant has users with the configured escalation permission.
5. Verify sound preview and synthetic event delivery in a local or staging tenant with external delivery disabled. A broadcast, an enabled AudioContext, and a stored inbox item are each independent from operator acknowledgment.

## Changed files

| Area | Files |
| --- | --- |
| Persistence and routing | `OperationalAlert.php`, `NotificationDispatcher.php`, `2026_11_24_161509_create_operational_alerts_table.php`, `2026_11_24_163438_add_backup_escalation_to_operational_alerts_table.php`, `config/notifications.php`, `.env.example` |
| Sources | `DispatchService.php`, `IncidentService.php`, `SiteProtectionService.php`, `DispatcherBoard.php`, `LiveTracker.php`, `SiteProfile.php`, `ImportPatriotDispatches.php`, `SendPatriotDispatchUpdates.php`, `EnrichPatriotDispatch.php`, `config/site_profile.php` |
| Broadcast and inbox | `OperationalAlertsChanged.php`, `DeliverOperationalBroadcast.php`, `OperationalBroadcastDelivery.php`, `2026_11_24_164420_create_operational_broadcast_deliveries_table.php`, `NotificationInboxChanged.php`, `BroadcastNotificationInboxChange.php`, `DispatchEventCreated.php`, `DispatchEventUpdated.php`, `SosAlertRaised.php`, `GenericGuardOpsNotification.php`, `IncidentSubmittedNotification.php`, `routes/channels.php` |
| Operator and administration UI | `NotificationBell.php`, `NotificationCenter.php`, `notification-bell.blade.php`, `notification-center.blade.php`, `dispatcher-board.blade.php`, `live-tracker.blade.php`, `site-profile.blade.php`, `resources/js/app.js`, `resources/js/operational-alarms.js`, `routes/web.php`, rebuilt `public/build` assets |
| Scheduling and verification | `bootstrap/app.php`, `phpunit.xml`, `OperationalAlertsTest.php`, `DispatchSystemTest.php`, `LiveTrackerTest.php`, `NotificationPipelineTest.php` |

## Verification

Synthetic Chromium checks exercised a mobile viewport, private channel subscriptions, live SOS delivery, shared acknowledgment across tabs, page refresh, reconnect recovery, and the visual alert when audio was unavailable. Tests ran against isolated data with external delivery disabled.

## Remaining scope

- Routing follows the existing tenant-wide dispatch and incident permissions. Branch, region, responsible team, and duty-roster restrictions require an operator assignment model and corresponding source policies.
- Escalation supports an initial reminder and an optional backup reminder. External emergency contacts, resolution deadlines, manual reassignment, and per-user acknowledgment receipts are outstanding.
- The registry covers the events above. Other dispatch exceptions, incident transitions, deployment alerts, and infrastructure health events still use existing module behavior or require publishers.
- The center reports alarm totals, acknowledgment and resolution metrics, and tenant-scoped pending, handed-off and failed broadcast counts with attempt totals and recent failure references. Pending counts cover tracked alarm broadcasts. Successful handoff records transport completion. Browser receipt, queue-wide telemetry, archived-alarm policies, parameterized retention, reporting by branch/team, and per-user email/push preferences are outstanding.
- Production concurrency, production queue recovery, and deployment-specific audio or external delivery still require staging validation. Existing open SOS and critical dispatch records are not backfilled by the migration. Active critical Patriot records observed by the importer can be published on its next run.
