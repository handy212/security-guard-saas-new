<?php

namespace Database\Seeders;

use App\Enums\BidStatus;
use App\Enums\ConfirmationStatus;
use App\Enums\DispatchStatus;
use App\Enums\IncidentStatus;
use App\Enums\LeaveStatus;
use App\Enums\SwapStatus;
use App\Models\AnalyticsSnapshot;
use App\Models\AssetCategory;
use App\Models\AssetPurchaseOrder;
use App\Models\AssetPurchaseOrderItem;
use App\Models\AssetVendor;
use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\BreakLog;
use App\Models\CheckpointScan;
use App\Models\CheckpointTask;
use App\Models\ClientAccount;
use App\Models\ClientComplaint;
use App\Models\CustomReportSubmission;
use App\Models\DailyActivityReport;
use App\Models\DataRetentionPolicy;
use App\Models\DispatchActivityLog;
use App\Models\DispatchEvent;
use App\Models\EquipmentAsset;
use App\Models\EquipmentAssignment;
use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\GeofenceViolation;
use App\Models\Guard;
use App\Models\GuardAvailability;
use App\Models\GuardCertification;
use App\Models\GuardDocument;
use App\Models\GuardIdleAlert;
use App\Models\GuardLocation;
use App\Models\Incident;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\LeaveRequest;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\OpenShiftBid;
use App\Models\PassdownLog;
use App\Models\PatrolCheckpoint;
use App\Models\PatrolRoute;
use App\Models\PatrolSession;
use App\Models\PayrollExport;
use App\Models\PostOrder;
use App\Models\ReportTemplate;
use App\Models\ReportTemplateAssignment;
use App\Models\ReportTemplateField;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftConfirmation;
use App\Models\ShiftSwapRequest;
use App\Models\ShiftTemplate;
use App\Models\ShiftTemplateItem;
use App\Models\Site;
use App\Models\SitePost;
use App\Models\SiteSlaRequirement;
use App\Models\SosAlert;
use App\Models\TaskSubmission;
use App\Models\Tenant;
use App\Models\Timesheet;
use App\Models\TrainingRecord;
use App\Models\User;
use App\Models\VehiclePatrol;
use App\Models\VisitorLog;
use App\Models\WebhookSubscription;
use App\Services\TenantRoleProvisioner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ModuleDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-security')->firstOrFail();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        $guardUser = User::where('email', 'john.guard@test')->firstOrFail();
        $guard = Guard::where('tenant_id', $tenant->id)->where('employee_number', 'G-001')->firstOrFail();
        $client = ClientAccount::where('tenant_id', $tenant->id)->where('name', 'Gold Mine Ltd')->firstOrFail();
        $site = Site::where('tenant_id', $tenant->id)->where('name', 'Main Gate')->firstOrFail();
        $post = SitePost::where('tenant_id', $tenant->id)->where('name', 'Gatehouse A')->firstOrFail();
        $route = PatrolRoute::where('tenant_id', $tenant->id)->where('name', 'Night Round')->firstOrFail();
        $dayShift = Shift::where('tenant_id', $tenant->id)->where('title', 'Day Shift')->firstOrFail();
        $assignment = ShiftAssignment::where('tenant_id', $tenant->id)
            ->where('shift_id', $dayShift->id)
            ->where('guard_id', $guard->id)
            ->firstOrFail();
        $radioCategory = AssetCategory::where('tenant_id', $tenant->id)->where('name', 'Radios')->first();
        $uniformCategory = AssetCategory::where('tenant_id', $tenant->id)->where('name', 'Uniforms')->first();
        $vendor = AssetVendor::where('tenant_id', $tenant->id)->where('name', 'SecureGear Supply')->first();
        $radioAsset = EquipmentAsset::where('tenant_id', $tenant->id)->where('asset_tag', 'RAD-001')->first();

        $branch = Branch::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'ASH'],
            [
                'name' => 'Ashanti Region',
                'phone' => '+233 32 202 0000',
                'email' => 'ashanti@demosecurity.test',
                'address' => 'Obuasi Road',
                'city' => 'Kumasi',
                'country' => 'Ghana',
                'is_active' => true,
            ]
        );

        $provisioner = app(TenantRoleProvisioner::class);

        $guard2User = User::firstOrCreate(
            ['email' => 'sam.adeyemi@test'],
            ['tenant_id' => $tenant->id, 'name' => 'Sam Adeyemi', 'password' => Hash::make('password'), 'status' => 'active']
        );
        setPermissionsTeamId($tenant->id);
        if (! $guard2User->hasRole('guard')) {
            $provisioner->assignRole($guard2User, 'guard');
        }

        $guard2 = Guard::updateOrCreate(
            ['tenant_id' => $tenant->id, 'employee_number' => 'G-002'],
            [
                'user_id' => $guard2User->id,
                'branch_id' => $branch->id,
                'first_name' => 'Sam',
                'last_name' => 'Adeyemi',
                'phone' => '0241111111',
                'email' => 'sam.adeyemi@test',
                'status' => 'active',
                'monthly_rate' => 9.5,
                'license_number' => 'SEC-002',
                'rank' => 'Officer',
                'verification_status' => 'verified',
                'verified_at' => now()->subMonths(2),
                'verified_by_user_id' => $admin->id,
                'hire_date' => now()->subYear()->toDateString(),
            ]
        );

        $guard3User = User::firstOrCreate(
            ['email' => 'grace.okafor@test'],
            ['tenant_id' => $tenant->id, 'name' => 'Grace Okafor', 'password' => Hash::make('password'), 'status' => 'active']
        );
        if (! $guard3User->hasRole('guard')) {
            $provisioner->assignRole($guard3User, 'guard');
        }

        $guard3 = Guard::updateOrCreate(
            ['tenant_id' => $tenant->id, 'employee_number' => 'G-003'],
            [
                'user_id' => $guard3User->id,
                'branch_id' => $branch->id,
                'first_name' => 'Grace',
                'last_name' => 'Okafor',
                'phone' => '0242222222',
                'email' => 'grace.okafor@test',
                'status' => 'active',
                'monthly_rate' => 10,
                'license_number' => 'SEC-003',
                'rank' => 'Officer',
                'verification_status' => 'pending',
                'hire_date' => now()->subMonths(3)->toDateString(),
            ]
        );

        $client2 = ClientAccount::updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Harbor Logistics'],
            [
                'industry' => 'Logistics',
                'email' => 'ops@harborlogistics.test',
                'phone' => '+233 30 200 1234',
                'status' => 'active',
                'default_monthly_rate' => 22,
            ]
        );

        $site2 = Site::updateOrCreate(
            ['tenant_id' => $tenant->id, 'client_account_id' => $client2->id, 'name' => 'Warehouse Dock'],
            [
                'address' => 'Tema Port',
                'latitude' => 5.6698,
                'longitude' => -0.0167,
                'geofence_radius_meters' => 300,
                'status' => 'active',
                'instructions' => 'All visitors must wear hi-vis vests.',
            ]
        );

        SitePost::updateOrCreate(
            ['tenant_id' => $tenant->id, 'site_id' => $site2->id, 'name' => 'Loading Bay'],
            ['status' => 'active']
        );

        PostOrder::updateOrCreate(
            ['tenant_id' => $tenant->id, 'site_id' => $site->id, 'title' => 'Main Gate Standing Orders'],
            [
                'site_post_id' => $post->id,
                'instructions' => "1. Verify all vehicle manifests.\n2. Log visitors in the visitor module.\n3. Escalate trespassers to dispatch.",
                'version' => 2,
                'is_active' => true,
            ]
        );

        SiteSlaRequirement::updateOrCreate(
            ['tenant_id' => $tenant->id, 'site_id' => $site->id, 'metric' => 'patrol_completion'],
            ['target_value' => 95, 'frequency' => 'daily', 'grace_minutes' => 15, 'is_active' => true]
        );

        foreach ([1, 2, 3, 4, 5] as $weekday) {
            GuardAvailability::updateOrCreate(
                ['tenant_id' => $tenant->id, 'guard_id' => $guard->id, 'weekday' => $weekday],
                ['starts_at' => '06:00', 'ends_at' => '18:00', 'is_available' => true]
            );
        }

        TrainingRecord::updateOrCreate(
            ['tenant_id' => $tenant->id, 'guard_id' => $guard->id, 'course_name' => 'First Aid Level 1'],
            [
                'provider' => 'Ghana Red Cross',
                'completed_on' => now()->subMonths(8)->toDateString(),
                'expires_on' => now()->addMonths(4)->toDateString(),
                'status' => 'valid',
            ]
        );

        GuardCertification::updateOrCreate(
            ['tenant_id' => $tenant->id, 'guard_id' => $guard->id, 'name' => 'Security License'],
            [
                'issuer' => 'Private Security Org.',
                'issued_at' => now()->subYears(2)->toDateString(),
                'expires_at' => now()->addMonths(6)->toDateString(),
                'status' => 'valid',
            ]
        );

        GuardDocument::updateOrCreate(
            ['tenant_id' => $tenant->id, 'guard_id' => $guard2->id, 'type' => 'id_card'],
            ['file_path' => 'demo/guards/g-002-id.pdf', 'expires_at' => now()->addYear(), 'status' => 'active']
        );

        $nightShift = Shift::updateOrCreate(
            ['tenant_id' => $tenant->id, 'site_id' => $site->id, 'title' => 'Night Shift'],
            [
                'client_account_id' => $client->id,
                'site_post_id' => $post->id,
                'starts_at' => now()->copy()->addDay()->setTime(18, 0),
                'ends_at' => now()->copy()->addDays(2)->setTime(6, 0),
                'required_guards' => 1,
                'billing_rate' => 28,
                'billable_hours' => 12,
                'status' => 'scheduled',
            ]
        );

        $weekendOpenShift = Shift::updateOrCreate(
            ['tenant_id' => $tenant->id, 'site_id' => $site2->id, 'title' => 'Weekend Coverage'],
            [
                'client_account_id' => $client2->id,
                'starts_at' => now()->copy()->next('Saturday')->setTime(8, 0),
                'ends_at' => now()->copy()->next('Saturday')->setTime(20, 0),
                'required_guards' => 2,
                'billing_rate' => 30,
                'billable_hours' => 12,
                'status' => 'open',
            ]
        );

        $nightAssignment = ShiftAssignment::firstOrCreate(
            ['tenant_id' => $tenant->id, 'shift_id' => $nightShift->id, 'guard_id' => $guard2->id],
            ['status' => 'assigned', 'assigned_at' => now()]
        );

        $template = ShiftTemplate::updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Standard 2x12'],
            ['description' => 'Day and night coverage for mining sites', 'is_active' => true]
        );

        ShiftTemplateItem::updateOrCreate(
            ['shift_template_id' => $template->id, 'day_of_week' => 1, 'site_id' => $site->id],
            ['start_time' => '08:00', 'end_time' => '18:00', 'required_guards' => 2, 'billing_rate' => 25]
        );

        ShiftTemplateItem::updateOrCreate(
            ['shift_template_id' => $template->id, 'day_of_week' => 1, 'site_id' => $site2->id, 'start_time' => '18:00'],
            ['end_time' => '06:00', 'required_guards' => 1, 'billing_rate' => 28]
        );

        OpenShiftBid::updateOrCreate(
            ['tenant_id' => $tenant->id, 'shift_id' => $dayShift->id, 'guard_id' => $guard3->id],
            ['notes' => 'Available for overtime coverage.', 'status' => BidStatus::PENDING]
        );

        ShiftSwapRequest::updateOrCreate(
            ['tenant_id' => $tenant->id, 'shift_assignment_id' => $nightAssignment->id, 'requested_by_guard_id' => $guard2->id],
            [
                'replacement_guard_id' => $guard3->id,
                'reason' => 'Family commitment — need coverage for tomorrow night.',
                'status' => SwapStatus::PENDING,
            ]
        );

        ShiftConfirmation::updateOrCreate(
            ['tenant_id' => $tenant->id, 'shift_assignment_id' => $assignment->id, 'guard_id' => $guard->id],
            ['status' => ConfirmationStatus::CONFIRMED, 'confirmed_at' => now()->subHours(3)]
        );

        ShiftConfirmation::updateOrCreate(
            ['tenant_id' => $tenant->id, 'shift_assignment_id' => $nightAssignment->id, 'guard_id' => $guard2->id],
            ['status' => ConfirmationStatus::PENDING]
        );

        $assignment->update(['status' => 'in_progress']);

        $attendance = AttendanceLog::updateOrCreate(
            ['tenant_id' => $tenant->id, 'shift_assignment_id' => $assignment->id, 'guard_id' => $guard->id, 'type' => 'clock_in'],
            [
                'site_id' => $site->id,
                'recorded_at' => now()->subHours(2),
                'clock_in_at' => now()->subHours(2),
                'clock_in_latitude' => $site->latitude,
                'clock_in_longitude' => $site->longitude,
                'latitude' => $site->latitude,
                'longitude' => $site->longitude,
                'is_geofence_valid' => true,
                'geofence_validated' => true,
                'status' => 'on_time',
            ]
        );

        BreakLog::updateOrCreate(
            ['tenant_id' => $tenant->id, 'attendance_log_id' => $attendance->id, 'type' => 'meal'],
            ['started_at' => now()->subMinutes(45), 'ended_at' => now()->subMinutes(15)]
        );

        AttendanceLog::updateOrCreate(
            ['tenant_id' => $tenant->id, 'shift_assignment_id' => $nightAssignment->id, 'guard_id' => $guard2->id, 'type' => 'clock_in'],
            [
                'site_id' => $site->id,
                'recorded_at' => now()->subDays(1)->setTime(18, 5),
                'clock_in_at' => now()->subDays(1)->setTime(18, 5),
                'clock_out_at' => now()->subDays(1)->setTime(23, 58),
                'worked_minutes' => 353,
                'status' => 'completed',
            ]
        );

        LeaveRequest::updateOrCreate(
            ['tenant_id' => $tenant->id, 'guard_id' => $guard3->id, 'starts_on' => now()->addWeek()->toDateString()],
            [
                'ends_on' => now()->addWeek()->addDays(2)->toDateString(),
                'type' => 'annual',
                'reason' => 'Family event in Accra',
                'status' => LeaveStatus::PENDING,
            ]
        );

        LeaveRequest::updateOrCreate(
            ['tenant_id' => $tenant->id, 'guard_id' => $guard2->id, 'starts_on' => now()->subWeeks(2)->toDateString()],
            [
                'ends_on' => now()->subWeeks(2)->addDays(1)->toDateString(),
                'type' => 'sick',
                'reason' => 'Medical leave',
                'status' => LeaveStatus::APPROVED,
                'approved_by' => $admin->id,
                'approved_at' => now()->subWeeks(3),
            ]
        );

        $incidentSubmitted = Incident::updateOrCreate(
            ['tenant_id' => $tenant->id, 'site_id' => $site->id, 'title' => 'Unauthorized vehicle at perimeter'],
            [
                'shift_assignment_id' => $assignment->id,
                'reported_by_user_id' => $guardUser->id,
                'type' => 'security',
                'incident_type' => 'trespass',
                'severity' => 'medium',
                'description' => 'Unknown pickup attempted entry without manifest. Turned away and logged plate ABC-1234.',
                'status' => IncidentStatus::SUBMITTED->value,
                'latitude' => $site->latitude,
                'longitude' => $site->longitude,
                'reported_at' => now()->subHours(1),
                'occurred_at' => now()->subHours(1),
            ]
        );

        Incident::updateOrCreate(
            ['tenant_id' => $tenant->id, 'site_id' => $site2->id, 'title' => 'Loading bay door left open'],
            [
                'reported_by_user_id' => $admin->id,
                'type' => 'safety',
                'incident_type' => 'hazard',
                'severity' => 'low',
                'description' => 'Bay 3 roller door found open during patrol. Secured and client notified.',
                'status' => IncidentStatus::CLOSED->value,
                'reported_at' => now()->subDays(3),
                'occurred_at' => now()->subDays(3),
                'closed_at' => now()->subDays(2),
                'approved_by_user_id' => $admin->id,
                'approved_at' => now()->subDays(2),
                'resolution' => 'Client maintenance team repaired latch.',
            ]
        );

        $completedPatrol = PatrolSession::updateOrCreate(
            ['tenant_id' => $tenant->id, 'shift_assignment_id' => $assignment->id, 'guard_id' => $guard->id, 'status' => 'completed'],
            [
                'patrol_route_id' => $route->id,
                'started_at' => now()->subHours(4),
                'completed_at' => now()->subHours(3),
                'notes' => 'All checkpoints scanned. No issues.',
            ]
        );

        $activePatrol = PatrolSession::updateOrCreate(
            ['tenant_id' => $tenant->id, 'shift_assignment_id' => $assignment->id, 'guard_id' => $guard->id, 'status' => 'in_progress'],
            [
                'patrol_route_id' => $route->id,
                'started_at' => now()->subMinutes(20),
            ]
        );

        $checkpoints = PatrolCheckpoint::where('patrol_route_id', $route->id)->orderBy('sequence')->get();
        $firstCheckpoint = $checkpoints->first();

        if ($firstCheckpoint) {
            $checkpointTask = CheckpointTask::updateOrCreate(
                ['tenant_id' => $tenant->id, 'patrol_checkpoint_id' => $firstCheckpoint->id, 'title' => 'Gate secured?'],
                ['response_type' => 'yes_no', 'is_required' => true, 'sort_order' => 1]
            );

            foreach ($checkpoints as $index => $checkpoint) {
                CheckpointScan::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'patrol_session_id' => $completedPatrol->id,
                        'patrol_checkpoint_id' => $checkpoint->id,
                    ],
                    [
                        'guard_id' => $guard->id,
                        'scanned_at' => $completedPatrol->started_at->copy()->addMinutes(5 * ($index + 1)),
                        'latitude' => $site->latitude,
                        'longitude' => $site->longitude,
                        'status' => 'valid',
                    ]
                );
            }

            $activeScan = CheckpointScan::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'patrol_session_id' => $activePatrol->id,
                    'patrol_checkpoint_id' => $firstCheckpoint->id,
                ],
                [
                    'guard_id' => $guard->id,
                    'scanned_at' => now()->subMinutes(5),
                    'latitude' => $site->latitude,
                    'longitude' => $site->longitude,
                    'status' => 'valid',
                ]
            );

            TaskSubmission::updateOrCreate(
                ['tenant_id' => $tenant->id, 'checkpoint_scan_id' => $activeScan->id, 'checkpoint_task_id' => $checkpointTask->id],
                ['response' => 'yes', 'notes' => 'Padlock verified']
            );
        }

        VehiclePatrol::updateOrCreate(
            ['tenant_id' => $tenant->id, 'patrol_session_id' => $completedPatrol->id],
            [
                'vehicle_number' => 'DS-4451',
                'driver_name' => 'John Mensah',
                'start_odometer' => 45210,
                'end_odometer' => 45228,
                'fuel_log' => ['litres' => 12, 'cost' => 180],
            ]
        );

        PatrolSession::updateOrCreate(
            ['tenant_id' => $tenant->id, 'shift_assignment_id' => $nightAssignment->id, 'guard_id' => $guard2->id, 'status' => 'missed'],
            [
                'patrol_route_id' => $route->id,
                'started_at' => now()->subDays(1)->setTime(20, 0),
            ]
        );

        $sos = SosAlert::updateOrCreate(
            ['tenant_id' => $tenant->id, 'guard_id' => $guard->id, 'message' => 'Demo SOS — resolved during drill'],
            [
                'site_id' => $site->id,
                'latitude' => $site->latitude,
                'longitude' => $site->longitude,
                'status' => 'resolved',
                'raised_at' => now()->subDays(5),
                'acknowledged_by_user_id' => $admin->id,
                'acknowledged_at' => now()->subDays(5)->addMinutes(2),
            ]
        );

        $openDispatch = DispatchEvent::updateOrCreate(
            ['tenant_id' => $tenant->id, 'dispatch_number' => 'DSP-0001'],
            [
                'client_account_id' => $client->id,
                'site_id' => $site->id,
                'created_by_user_id' => $admin->id,
                'event_type' => 'alarm',
                'priority' => 'high',
                'caller_type' => 'client',
                'caller_name' => 'Gold Mine Control Room',
                'incident_location' => 'Perimeter fence, sector 4',
                'incident_date' => now()->toDateString(),
                'incident_time' => now()->format('H:i'),
                'status' => DispatchStatus::OPEN->value,
                'description' => 'Motion sensor triggered — guard dispatch requested.',
                'opened_at' => now()->subMinutes(12),
            ]
        );

        $onSceneDispatch = DispatchEvent::updateOrCreate(
            ['tenant_id' => $tenant->id, 'dispatch_number' => 'DSP-0002'],
            [
                'client_account_id' => $client2->id,
                'site_id' => $site2->id,
                'guard_id' => $guard2->id,
                'created_by_user_id' => $admin->id,
                'event_type' => 'medical',
                'priority' => 'normal',
                'caller_type' => 'site',
                'caller_name' => 'Dock supervisor',
                'incident_location' => 'Loading Bay',
                'incident_date' => now()->subDay()->toDateString(),
                'incident_time' => '14:30',
                'status' => DispatchStatus::ON_SCENE->value,
                'description' => 'Worker reported dizziness — first aid on scene.',
                'action_taken' => 'Ambulance called as precaution.',
                'opened_at' => now()->subDay()->setTime(14, 30),
                'assigned_at' => now()->subDay()->setTime(14, 32),
                'en_route_at' => now()->subDay()->setTime(14, 35),
                'on_scene_at' => now()->subDay()->setTime(14, 42),
                'incident_id' => $incidentSubmitted->id,
            ]
        );

        DispatchActivityLog::updateOrCreate(
            ['tenant_id' => $tenant->id, 'dispatch_event_id' => $onSceneDispatch->id, 'action' => 'status_change'],
            [
                'user_id' => $admin->id,
                'message' => 'Marked on scene',
                'metadata' => ['from' => 'en_route', 'to' => 'on_scene'],
            ]
        );

        VisitorLog::updateOrCreate(
            ['tenant_id' => $tenant->id, 'site_id' => $site->id, 'visitor_name' => 'Kwame Asante'],
            [
                'guard_id' => $guard->id,
                'visitor_phone' => '0243333333',
                'company' => 'Mine Supplies Co.',
                'purpose' => 'Equipment delivery',
                'id_type' => 'Ghana Card',
                'id_number' => 'GHA-123456789-0',
                'vehicle_plate' => 'GR-4521-21',
                'checked_in_at' => now()->subHours(3),
                'status' => 'checked_in',
            ]
        );

        VisitorLog::updateOrCreate(
            ['tenant_id' => $tenant->id, 'site_id' => $site->id, 'visitor_name' => 'Ama Boateng'],
            [
                'guard_id' => $guard->id,
                'company' => 'Audit Partners',
                'purpose' => 'Site inspection',
                'checked_in_at' => now()->subDays(1)->setTime(9, 0),
                'checked_out_at' => now()->subDays(1)->setTime(11, 30),
                'status' => 'checked_out',
            ]
        );

        ClientComplaint::updateOrCreate(
            ['tenant_id' => $tenant->id, 'client_account_id' => $client->id, 'subject' => 'Late guard arrival on Monday'],
            [
                'site_id' => $site->id,
                'description' => 'Relief guard arrived 25 minutes after scheduled handover.',
                'priority' => 'medium',
                'status' => 'open',
                'assigned_to' => $admin->id,
            ]
        );

        ClientComplaint::updateOrCreate(
            ['tenant_id' => $tenant->id, 'client_account_id' => $client2->id, 'subject' => 'Patrol log not received'],
            [
                'site_id' => $site2->id,
                'description' => 'Client did not receive Friday night patrol summary.',
                'priority' => 'low',
                'status' => 'resolved',
                'assigned_to' => $admin->id,
                'resolved_at' => now()->subDays(2),
            ]
        );

        DailyActivityReport::updateOrCreate(
            ['tenant_id' => $tenant->id, 'site_id' => $site->id, 'report_date' => now()->toDateString()],
            [
                'guard_id' => $guard->id,
                'shift_assignment_id' => $assignment->id,
                'title' => 'Day shift summary',
                'summary' => 'Quiet shift. One visitor delivery and perimeter check completed.',
                'handover_notes' => 'Night team: watch sector 4 sensor — flaky yesterday.',
                'status' => 'submitted',
            ]
        );

        PassdownLog::updateOrCreate(
            ['tenant_id' => $tenant->id, 'site_id' => $site->id, 'guard_id' => $guard->id, 'shift_assignment_id' => $assignment->id],
            [
                'site_post_id' => $post->id,
                'content' => 'All keys accounted for. Generator fuel at 78%. Visitor Kwame still on site.',
            ]
        );

        $estimate = Estimate::updateOrCreate(
            ['tenant_id' => $tenant->id, 'estimate_number' => 'EST-2026-001'],
            [
                'client_account_id' => $client2->id,
                'estimate_date' => now()->subDays(5)->toDateString(),
                'valid_until' => now()->addDays(25)->toDateString(),
                'status' => 'sent',
                'subtotal' => 3600,
                'tax_total' => 0,
                'grand_total' => 3600,
                'sent_at' => now()->subDays(4),
            ]
        );

        EstimateItem::updateOrCreate(
            ['tenant_id' => $tenant->id, 'estimate_id' => $estimate->id, 'description' => 'Weekend security coverage (2 guards x 12h)'],
            ['quantity' => 1, 'unit_price' => 3600, 'line_total' => 3600, 'is_taxable' => false]
        );

        $invoice = Invoice::updateOrCreate(
            ['tenant_id' => $tenant->id, 'invoice_number' => 'INV-2026-001'],
            [
                'client_account_id' => $client->id,
                'invoice_date' => now()->subDays(10)->toDateString(),
                'due_date' => now()->addDays(5)->toDateString(),
                'status' => 'sent',
                'subtotal' => 5000,
                'tax_total' => 0,
                'grand_total' => 5000,
                'sent_at' => now()->subDays(9),
            ]
        );

        InvoiceItem::updateOrCreate(
            ['tenant_id' => $tenant->id, 'invoice_id' => $invoice->id, 'description' => 'March security services — Main Gate'],
            ['quantity' => 1, 'unit_price' => 5000, 'line_total' => 5000]
        );

        $paidInvoice = Invoice::updateOrCreate(
            ['tenant_id' => $tenant->id, 'invoice_number' => 'INV-2026-002'],
            [
                'client_account_id' => $client->id,
                'invoice_date' => now()->subMonth()->toDateString(),
                'due_date' => now()->subDays(15)->toDateString(),
                'status' => 'paid',
                'subtotal' => 4800,
                'tax_total' => 0,
                'grand_total' => 4800,
                'sent_at' => now()->subMonth()->addDay(),
                'paid_at' => now()->subDays(20),
            ]
        );

        InvoiceItem::updateOrCreate(
            ['tenant_id' => $tenant->id, 'invoice_id' => $paidInvoice->id, 'description' => 'February security services — Main Gate'],
            ['quantity' => 1, 'unit_price' => 4800, 'line_total' => 4800]
        );

        InvoicePayment::updateOrCreate(
            ['tenant_id' => $tenant->id, 'invoice_id' => $paidInvoice->id, 'paid_at' => now()->subDays(20)],
            ['amount' => 4800, 'payment_method' => 'bank_transfer', 'notes' => 'Paid in full']
        );

        $thread = MessageThread::updateOrCreate(
            ['tenant_id' => $tenant->id, 'subject' => 'Main Gate — shift coordination'],
            ['site_id' => $site->id, 'type' => 'site']
        );

        foreach ([$admin, $guardUser, $guard2User] as $participant) {
            MessageThreadParticipant::firstOrCreate(
                ['message_thread_id' => $thread->id, 'user_id' => $participant->id]
            );
        }

        Message::updateOrCreate(
            ['message_thread_id' => $thread->id, 'user_id' => $admin->id, 'body' => 'Please confirm handover by 18:00. Sector 4 sensor is flaky.'],
            []
        );

        Message::updateOrCreate(
            ['message_thread_id' => $thread->id, 'user_id' => $guardUser->id, 'body' => 'Acknowledged. Completed afternoon patrol — all clear.'],
            []
        );

        Timesheet::updateOrCreate(
            ['tenant_id' => $tenant->id, 'guard_id' => $guard->id, 'period_start' => now()->startOfMonth()->toDateString()],
            [
                'period_end' => now()->endOfMonth()->toDateString(),
                'regular_hours' => 88,
                'overtime_hours' => 6,
                'gross_pay' => 970,
                'status' => 'pending',
            ]
        );

        Timesheet::updateOrCreate(
            ['tenant_id' => $tenant->id, 'guard_id' => $guard2->id, 'period_start' => now()->subMonth()->startOfMonth()->toDateString()],
            [
                'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
                'regular_hours' => 160,
                'overtime_hours' => 12,
                'gross_pay' => 1760,
                'status' => 'approved',
                'approved_by_user_id' => $admin->id,
                'approved_at' => now()->subDays(3),
            ]
        );

        PayrollExport::updateOrCreate(
            ['tenant_id' => $tenant->id, 'provider' => 'quickbooks', 'period_start' => now()->subMonth()->startOfMonth()->toDateString()],
            [
                'period_end' => now()->subMonth()->endOfMonth()->toDateString(),
                'file_path' => 'demo/payroll/export-feb-2026.csv',
                'exported_by_user_id' => $admin->id,
                'exported_at' => now()->subDays(2),
            ]
        );

        if ($radioAsset && $vendor && $radioCategory) {
            $purchaseOrder = AssetPurchaseOrder::updateOrCreate(
                ['tenant_id' => $tenant->id, 'po_number' => 'PO-2026-004'],
                [
                    'vendor_id' => $vendor->id,
                    'status' => 'partial',
                    'order_date' => now()->subDays(7)->toDateString(),
                    'expected_date' => now()->addDays(3)->toDateString(),
                    'subtotal' => 2250,
                    'tax_total' => 0,
                    'grand_total' => 2250,
                    'notes' => 'Reorder handheld radios',
                    'created_by_user_id' => $admin->id,
                ]
            );

            AssetPurchaseOrderItem::updateOrCreate(
                ['tenant_id' => $tenant->id, 'purchase_order_id' => $purchaseOrder->id, 'description' => 'Motorola DP4400 x5'],
                [
                    'asset_category_id' => $radioCategory->id,
                    'quantity' => 5,
                    'quantity_received' => 2,
                    'unit_cost' => 450,
                    'line_total' => 2250,
                ]
            );

            EquipmentAssignment::updateOrCreate(
                ['tenant_id' => $tenant->id, 'equipment_asset_id' => $radioAsset->id, 'guard_id' => $guard->id],
                [
                    'site_id' => $site->id,
                    'shift_assignment_id' => $assignment->id,
                    'issued_at' => now()->subHour(),
                    'issue_notes' => 'Signed out at shift start',
                    'status' => 'issued',
                    'returned_at' => null,
                ]
            );
            $radioAsset->update(['status' => \App\Enums\AssetStatus::ISSUED]);
        }

        $reportTemplate = ReportTemplate::updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Daily Site Inspection'],
            [
                'client_account_id' => $client->id,
                'description' => 'End-of-shift checklist for client delivery',
                'is_active' => true,
            ]
        );

        ReportTemplateField::updateOrCreate(
            ['report_template_id' => $reportTemplate->id, 'label' => 'Perimeter secure?'],
            ['field_type' => 'yes_no', 'is_required' => true, 'sort_order' => 1]
        );

        ReportTemplateField::updateOrCreate(
            ['report_template_id' => $reportTemplate->id, 'label' => 'Notes'],
            ['field_type' => 'textarea', 'is_required' => false, 'sort_order' => 2]
        );

        ReportTemplateAssignment::updateOrCreate(
            ['tenant_id' => $tenant->id, 'report_template_id' => $reportTemplate->id, 'site_id' => $site->id],
            ['site_post_id' => $post->id]
        );

        CustomReportSubmission::updateOrCreate(
            ['tenant_id' => $tenant->id, 'report_template_id' => $reportTemplate->id, 'guard_id' => $guard->id, 'site_id' => $site->id],
            [
                'shift_assignment_id' => $assignment->id,
                'status' => 'submitted',
                'data' => ['Perimeter secure?' => 'yes', 'Notes' => 'No issues observed.'],
                'submitted_at' => now()->subHours(1),
            ]
        );

        WebhookSubscription::updateOrCreate(
            ['tenant_id' => $tenant->id, 'event' => 'incident.submitted'],
            [
                'target_url' => 'https://example.com/webhooks/guardops',
                'secret' => 'demo-webhook-secret',
                'is_active' => true,
            ]
        );

        DataRetentionPolicy::updateOrCreate(
            ['tenant_id' => $tenant->id, 'record_type' => 'incidents'],
            ['retention_days' => 2555, 'legal_hold' => false]
        );

        DataRetentionPolicy::updateOrCreate(
            ['tenant_id' => $tenant->id, 'record_type' => 'attendance_logs'],
            ['retention_days' => 730, 'legal_hold' => false]
        );

        GuardLocation::updateOrCreate(
            ['tenant_id' => $tenant->id, 'guard_id' => $guard->id],
            [
                'latitude' => $site->latitude + 0.0001,
                'longitude' => $site->longitude + 0.0001,
                'accuracy_meters' => 8,
                'source' => 'gps',
                'recorded_at' => now()->subMinutes(2),
            ]
        );

        GeofenceViolation::updateOrCreate(
            ['tenant_id' => $tenant->id, 'guard_id' => $guard2->id, 'site_id' => $site->id],
            [
                'latitude' => $site->latitude + 0.01,
                'longitude' => $site->longitude,
                'distance_meters' => 320,
                'notified_at' => now()->subDays(2),
            ]
        );

        GuardIdleAlert::updateOrCreate(
            ['tenant_id' => $tenant->id, 'guard_id' => $guard2->id],
            [
                'last_location_at' => now()->subDays(1)->subMinutes(45),
                'idle_minutes' => 45,
                'alerted_at' => now()->subDays(1),
                'resolved_at' => now()->subDays(1)->addMinutes(10),
            ]
        );

        AnalyticsSnapshot::updateOrCreate(
            ['tenant_id' => $tenant->id, 'snapshot_date' => now()->toDateString()],
            [
                'active_guards' => 3,
                'active_sites' => 2,
                'missed_patrols' => 1,
                'incidents_by_severity' => ['low' => 1, 'medium' => 1, 'high' => 0],
                'late_shifts' => 1,
                'no_show_shifts' => 0,
                'patrol_completion_rate' => 87.5,
                'client_sla_performance' => 94.0,
                'revenue_total' => 9800,
                'guard_scores' => [
                    ['guard' => 'John Mensah', 'score' => 92],
                    ['guard' => 'Sam Adeyemi', 'score' => 88],
                ],
            ]
        );

        $this->command?->info('Module demo data seeded: workforce, scheduling, patrols, dispatch, incidents, visitors, billing, messenger, assets, compliance, and analytics.');
    }
}
