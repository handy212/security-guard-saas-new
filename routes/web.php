<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\PaystackCallbackController;
use App\Http\Controllers\PaystackWebhookController;
use Illuminate\Support\Facades\Route;
use App\Livewire\Analytics\AnalyticsDashboard;
use App\Livewire\Attendance\TimekeepingBoard;
use App\Livewire\Billing\InvoiceIndex;
use App\Livewire\Billing\PayrollBoard;
use App\Livewire\Billing\SubscriptionManager;
use App\Livewire\ClientPortal\Approvals;
use App\Livewire\ClientPortal\PortalDashboard;
use App\Livewire\Clients\ClientIndex;
use App\Livewire\Clients\ComplaintBoard;
use App\Livewire\Compliance\ComplianceDashboard;
use App\Livewire\Compliance\PolicyCenter;
use App\Livewire\Dashboard\Overview;
use App\Livewire\Dispatch\ControlRoom;
use App\Livewire\Equipment\EquipmentIndex;
use App\Livewire\Guards\GuardHrRecords;
use App\Livewire\Guards\GuardIndex;
use App\Livewire\Guard\MobileDashboard;
use App\Livewire\Incidents\IncidentIndex;
use App\Livewire\Mobile\OfflineSyncMonitor;
use App\Livewire\Patrols\PatrolBoard;
use App\Livewire\Patrols\Playback;
use App\Livewire\Patrols\VehiclePatrolBoard;
use App\Livewire\Reports\DailyReportIndex;
use App\Livewire\Schedules\CalendarView;
use App\Livewire\Schedules\DeploymentSheet;
use App\Livewire\Schedules\ShiftMarketplace;
use App\Livewire\Settings\RolePermissionManager;
use App\Livewire\Settings\TwoFactorSetup;
use App\Livewire\Settings\WebhookManager;
use App\Livewire\Shifts\ScheduleBoard;
use App\Livewire\Sites\SiteCompliance;
use App\Livewire\Sites\SiteIndex;
use App\Livewire\Tenants\TenantManagement;
use App\Livewire\Visitors\VisitorLogIndex;

Route::post('/paystack/webhook', PaystackWebhookController::class)->name('paystack.webhook');

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/dashboard', Overview::class)->name('dashboard');
    Route::get('/clients', ClientIndex::class)->name('clients.index');
    Route::get('/sites', SiteIndex::class)->name('sites.index');
    Route::get('/guards', GuardIndex::class)->name('guards.index');
    Route::get('/schedules', ScheduleBoard::class)->name('schedules.index');
    Route::get('/patrols', PatrolBoard::class)->name('patrols.index');
    Route::get('/incidents', IncidentIndex::class)->name('incidents.index');
    Route::get('/reports/daily', DailyReportIndex::class)->name('reports.daily');
    Route::get('/dispatch', ControlRoom::class)->name('dispatch.control-room');
    Route::get('/client-portal', PortalDashboard::class)->name('client-portal.dashboard');
    Route::get('/billing/invoices', InvoiceIndex::class)->name('billing.invoices');
    Route::get('/billing/subscription', SubscriptionManager::class)->name('billing.subscription');
    Route::get('/billing/subscription/callback', PaystackCallbackController::class)->name('billing.paystack.callback');
    Route::get('/settings/roles', RolePermissionManager::class)->name('settings.roles');
    Route::get('/settings/two-factor', TwoFactorSetup::class)->name('settings.two-factor');
    Route::get('/settings/webhooks', WebhookManager::class)->name('settings.webhooks');
    Route::get('/guard', MobileDashboard::class)->name('guard.mobile');
    Route::get('/visitors', VisitorLogIndex::class)->name('visitors.index');
    Route::get('/equipment', EquipmentIndex::class)->name('equipment.index');
    Route::get('/compliance', ComplianceDashboard::class)->name('compliance.dashboard');
    Route::get('/client-portal/approvals', Approvals::class)->name('client-portal.approvals');
    Route::get('/mobile/offline-sync', OfflineSyncMonitor::class)->name('mobile.offline-sync');
    Route::get('/saas/tenants', TenantManagement::class)->name('saas.tenants');
    Route::get('/schedules/marketplace', ShiftMarketplace::class)->name('schedules.marketplace');
    Route::get('/schedules/calendar', CalendarView::class)->name('schedules.calendar');
    Route::get('/schedules/deployment-sheet', DeploymentSheet::class)->name('schedules.deployment-sheet');
    Route::get('/attendance/timekeeping', TimekeepingBoard::class)->name('attendance.timekeeping');
    Route::get('/patrols/playback', Playback::class)->name('patrols.playback');
    Route::get('/patrols/vehicles', VehiclePatrolBoard::class)->name('patrols.vehicles');
    Route::get('/clients/complaints', ComplaintBoard::class)->name('clients.complaints');
    Route::get('/compliance/policies', PolicyCenter::class)->name('compliance.policies');
    Route::get('/billing/payroll', PayrollBoard::class)->name('billing.payroll');
    Route::get('/analytics', AnalyticsDashboard::class)->name('analytics.dashboard');
    Route::get('/guards/hr-records', GuardHrRecords::class)->name('guards.hr-records');
    Route::get('/sites/compliance', SiteCompliance::class)->name('sites.compliance');
});
