# GuardCore Pro Enterprise Implementation Review

This inventory describes application capabilities and identifies verification still required before production sign-off.

## Core module inventory

1. SaaS / Tenant Management
- Security companies / tenants
- Subscription plans
- Branches
- Tenant users foundation
- Tenant settings
- Billing limits for guards, sites, clients and storage

2. User & Role Management
- Roles: Super Admin, Company Admin, Operations Manager, Supervisor, Guard, Client, HR/Admin, Finance
- Permissions for sites, guard assignment, report approval, payroll, client portal, exports, tenant management, patrols, attendance, compliance, analytics and billing

3. Client & Site Management
- Clients and contacts
- Sites / geofence coordinates
- Site posts and post orders
- Emergency contacts
- Site documents
- SLA requirements
- Client complaints

4. Guard Management
- Guard profiles
- ID documents
- License / certification tracking
- Uniform/equipment issue and return
- Employment status
- Skills
- Availability and leave requests
- Training records
- Disciplinary records

5. Scheduling / Rostering
- Shift creation and guard assignment
- Conflict detection service foundation
- Overtime warning service foundation
- Guard availability
- Shift swap requests
- Open shift bidding
- Supervisor approval flow
- Calendar view
- Daily deployment sheet

6. Attendance & Timekeeping
- Clock in / clock out
- GPS attendance fields
- Geofence validation service foundation
- Late / no-show / early leave status fields
- Break tracking
- Timesheet generation foundation

7. Patrol / Guard Tour
- Patrol routes and checkpoints
- QR/NFC code fields
- Required checkpoint sequence
- Missed checkpoint statuses
- Patrol start/end
- Timestamp/GPS proof
- Patrol playback points
- Vehicle patrol support
- Checkpoint task checklists

8. Incident Reporting
- Incident type, severity and description
- Media table for photos/videos
- Witnesses/people/vehicle metadata foundation
- Police/emergency involvement fields foundation
- Client notification foundation
- Supervisor approval status
- PDF/export-ready report service foundation

9. Daily Activity Reports
- Shift notes
- Patrol summary
- Visitor entries
- Deliveries
- Maintenance observations
- Handover notes
- Guards submit a daily activity report for a shift they clocked into in the last seven days.
- Managers review the full summary and handover and approve submitted reports.
- Approved reports are available as PDFs to managers and the site’s client portal account.
- Scheduled report communications are reminders to check the portal.

10. Dispatch / Control Room
- Live Tracker map with site coverage, standby points, alarms, and GPS history
- Active shifts
- SOS alerts
- Emergency dispatch events
- Incident assignment
- Guard status foundation
- Real-time alerts foundation

11. Client Portal
- Assigned guards/sites foundation
- Site activity view
- Reports and incidents
- Patrol completion proof
- Attendance confirmation foundation
- Client complaints
- Client report sign-off records the authenticated approver and decision. Signature-file capture is outside the current sign-off workflow.

12. Compliance
- Guard license expiry
- Training expiry
- Site compliance checklist/SLA
- Equipment audit
- Incident escalation rules
- Data retention policies
- Audit log foundation

13. Billing & Payroll
- Client invoices by contract/site/post/hour foundation
- Invoice generation
- Timesheets
- Overtime service foundation
- Allowance/deduction extension points
- Accounting export CSV foundation

14. Analytics Dashboard
- Active guards
- Active sites
- Missed patrols
- Incidents by severity
- Late arrivals and no-shows by scheduled shift date
- Patrol completion rate
- Client SLA performance field
- Daily invoiced value by invoice date, excluding drafts and voids
- Historical snapshots show operational counts for their selected day. Active guard and site counts and SLA coverage reflect the time the snapshot was generated.
- Guard performance scoring and revenue breakdowns by client/site are outside this inventory of verified analytics.

## Production verification

Verify Paystack, queue and scheduler execution, real-time broadcasting, push and WhatsApp delivery, mail delivery, and backups in the target deployment. Exercise GPS, QR scanning, offline recovery, and emergency delivery on representative devices before using the system for live operations.

Use secret values provisioned for the target environment. The local environment file is ignored by Git. Rotate any historical repository credentials that were also used outside development.
