<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\GuardIdCardController;
use App\Http\Controllers\GuardIdCardPreviewController;
use App\Http\Controllers\GuardIdCardPrintController;
use App\Http\Controllers\GuardVerificationController;
use App\Http\Controllers\GuardVerificationPhotoController;
use App\Http\Controllers\PaystackCallbackController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\TenantFileController;
use Illuminate\Support\Facades\Route;
use App\Livewire\Analytics\AnalyticsDashboard;
use App\Livewire\Attendance\ReconciliationBoard;
use App\Livewire\Billing\EstimateIndex;
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
use App\Livewire\Assets\AssetIndex;
use App\Livewire\Assets\CategoryIndex;
use App\Livewire\Assets\InventoryIndex;
use App\Livewire\Assets\Overview as AssetsOverview;
use App\Livewire\Assets\PurchaseOrderIndex;
use App\Livewire\Assets\VendorIndex;
use App\Livewire\Dispatch\DispatcherBoard;
use App\Livewire\Guards\GuardHrRecords;
use App\Livewire\Guards\GuardIndex;
use App\Livewire\Guards\GuardProfile;
use App\Livewire\Guards\KnowYourGuardQueue;
use App\Livewire\Guard\MobileDashboard;
use App\Livewire\Incidents\IncidentIndex;
use App\Livewire\Messenger\MessengerIndex;
use App\Livewire\Mobile\OfflineSyncMonitor;
use App\Livewire\Passdown\PassdownIndex;
use App\Livewire\Patrols\PatrolBoard;
use App\Livewire\Patrols\Playback;
use App\Livewire\Patrols\VehiclePatrolBoard;
use App\Livewire\Reports\DailyReportIndex;
use App\Livewire\Reports\ReportTemplateBuilder;
use App\Livewire\Schedules\CalendarView;
use App\Livewire\Schedules\DeploymentSheet;
use App\Livewire\Scheduling\AttendanceIndex;
use App\Livewire\Scheduling\OpenShiftsIndex;
use App\Livewire\Scheduling\ScheduleIndex;
use App\Livewire\Scheduling\ShiftExchangeIndex;
use App\Livewire\Scheduling\ShiftStatusIndex;
use App\Livewire\Scheduling\ShiftTemplateIndex;
use App\Livewire\Scheduling\TimeOffIndex;
use App\Livewire\Settings\IdCardSettings;
use App\Livewire\Settings\SettingsHub;
use App\Livewire\Settings\AuditLogIndex;
use App\Livewire\Settings\RolePermissionManager;
use App\Livewire\Settings\TwoFactorSetup;
use App\Livewire\Settings\TeamPasswordReset;
use App\Livewire\Settings\WebhookManager;
use App\Livewire\Sites\SiteCompliance;
use App\Livewire\Sites\SiteIndex;
use App\Livewire\Tenants\PlatformPlanManagement;
use App\Livewire\Tenants\PlatformSubscriptionManagement;
use App\Livewire\Tenants\TenantManagement;
use App\Http\Controllers\PlatformTenantContextController;
use App\Livewire\Tracking\LiveTracker;
use App\Livewire\Visitors\VisitorLogIndex;
use App\Http\Controllers\PushSubscriptionController;

Route::post('/paystack/webhook', PaystackWebhookController::class)->name('paystack.webhook');

Route::get('/g/{tenant}/{token}', GuardVerificationController::class)
    ->middleware('throttle:60,1')
    ->where(['tenant' => '[a-z0-9-]+', 'token' => '[A-Z0-9]{8,32}'])
    ->name('guard.verify');

Route::get('/g/{tenant}/{token}/photo', GuardVerificationPhotoController::class)
    ->middleware('throttle:120,1')
    ->where(['tenant' => '[a-z0-9-]+', 'token' => '[A-Z0-9]{8,32}'])
    ->name('guard.verify.photo');

Route::get('/g/{token}', GuardVerificationController::class)
    ->middleware('throttle:60,1')
    ->where('token', '[A-Z0-9]{8,32}')
    ->name('guard.verify.legacy');

Route::get('/g/{token}/photo', GuardVerificationPhotoController::class)
    ->middleware('throttle:120,1')
    ->where('token', '[A-Z0-9]{8,32}')
    ->name('guard.verify.photo.legacy');

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/auth/sso/redirect', [SsoController::class, 'redirect'])->name('sso.redirect');
    Route::get('/auth/sso/callback', [SsoController::class, 'callback'])->name('sso.callback');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'tenant', 'plan.feature', 'two-factor'])->group(function () {
    Route::get('/dashboard', Overview::class)->name('dashboard');
    Route::get('/clients', ClientIndex::class)->name('clients.index');
    Route::get('/sites', SiteIndex::class)->name('sites.index');
    Route::get('/guards', GuardIndex::class)->name('guards.index');
    Route::get('/guards/know-your-guard', KnowYourGuardQueue::class)->name('guards.kyg');
    Route::get('/guards/{guard}', GuardProfile::class)->name('guards.show');
    Route::get('/guards/{guard}/id-card', GuardIdCardController::class)->name('guards.id-card');
    Route::get('/guards/{guard}/id-card/print', GuardIdCardPrintController::class)->name('guards.id-card.print');
    Route::get('/guards/{guard}/id-card/preview', [GuardIdCardPreviewController::class, 'guard'])->name('guards.id-card.preview');
    Route::get('/files/guards/{guard}/photo', [TenantFileController::class, 'guardPhoto'])->name('files.guard-photo');
    Route::get('/files/id-card-logo', [TenantFileController::class, 'idCardLogo'])->name('files.id-card-logo');
    Route::get('/files/id-card-back-logo', [TenantFileController::class, 'idCardBackLogo'])->name('files.id-card-back-logo');
    Route::get('/files/guard-documents/{document}', [TenantFileController::class, 'guardDocument'])->name('files.guard-document');
    Route::get('/schedules', ScheduleIndex::class)->name('schedules.index');
    Route::get('/schedules/templates', ShiftTemplateIndex::class)->name('schedules.templates');
    Route::get('/schedules/attendance', AttendanceIndex::class)->name('schedules.attendance');
    Route::get('/schedules/shift-status', ShiftStatusIndex::class)->name('schedules.shift-status');
    Route::get('/schedules/open-shifts', OpenShiftsIndex::class)->name('schedules.open-shifts');
    Route::get('/schedules/shift-exchange', ShiftExchangeIndex::class)->name('schedules.shift-exchange');
    Route::get('/schedules/time-off', TimeOffIndex::class)->name('schedules.time-off');
    Route::redirect('/workforce', '/schedules/time-off');
    Route::redirect('/attendance/timekeeping', '/schedules/attendance');
    Route::redirect('/schedules/marketplace', '/schedules/open-shifts');
    Route::get('/patrols', PatrolBoard::class)->name('patrols.index');
    Route::get('/incidents', IncidentIndex::class)->name('incidents.index');
    Route::get('/reports/daily', DailyReportIndex::class)->name('reports.daily');
    Route::get('/reports/templates', ReportTemplateBuilder::class)->name('reports.templates');
    Route::get('/tracking', LiveTracker::class)->name('tracking.live');
    Route::get('/dispatch', DispatcherBoard::class)->name('dispatch.control-room');
    Route::middleware('client.portal')->group(function () {
        Route::get('/client-portal', PortalDashboard::class)->name('client-portal.dashboard');
        Route::get('/client-portal/approvals', Approvals::class)->name('client-portal.approvals');
    });
    Route::get('/billing/invoices', InvoiceIndex::class)->name('billing.invoices');
    Route::get('/billing/estimates', EstimateIndex::class)->name('billing.estimates');
    Route::get('/billing/subscription', SubscriptionManager::class)->name('billing.subscription');
    Route::get('/billing/subscription/callback', PaystackCallbackController::class)->name('billing.paystack.callback');
    Route::get('/settings', SettingsHub::class)->name('settings.index');
    Route::get('/settings/id-card', IdCardSettings::class)->name('settings.id-card');
    Route::get('/settings/roles', RolePermissionManager::class)->name('settings.roles');
    Route::get('/settings/two-factor', TwoFactorSetup::class)->name('settings.two-factor');
    Route::get('/settings/webhooks', WebhookManager::class)->name('settings.webhooks');
    Route::get('/settings/audit-log', AuditLogIndex::class)->name('settings.audit-log');
    Route::get('/settings/team', TeamPasswordReset::class)->name('settings.team');
    Route::get('/guard', MobileDashboard::class)->name('guard.mobile');
    Route::get('/visitors', VisitorLogIndex::class)->name('visitors.index');
    Route::redirect('/equipment', '/assets/list');
    Route::get('/assets', AssetsOverview::class)->name('assets.overview');
    Route::get('/assets/list', AssetIndex::class)->name('assets.index');
    Route::get('/assets/categories', CategoryIndex::class)->name('assets.categories');
    Route::get('/assets/inventory', InventoryIndex::class)->name('assets.inventory');
    Route::get('/assets/vendors', VendorIndex::class)->name('assets.vendors');
    Route::get('/assets/purchase-orders', PurchaseOrderIndex::class)->name('assets.purchase-orders');
    Route::get('/compliance', ComplianceDashboard::class)->name('compliance.dashboard');
    Route::get('/mobile/offline-sync', OfflineSyncMonitor::class)->name('mobile.offline-sync');
    Route::middleware('platform')->group(function () {
        Route::redirect('/saas', '/saas/tenants')->name('saas');
        Route::get('/saas/tenants', TenantManagement::class)->name('saas.tenants');
        Route::get('/saas/plans', PlatformPlanManagement::class)->name('saas.plans');
        Route::get('/saas/subscriptions', PlatformSubscriptionManagement::class)->name('saas.subscriptions');
        Route::post('/saas/exit-tenant', [PlatformTenantContextController::class, 'exit'])->name('saas.exit-tenant');
    });
    Route::get('/messenger', MessengerIndex::class)->name('messenger.index');
    Route::get('/passdown', PassdownIndex::class)->name('passdown.index');
    Route::get('/schedules/calendar', CalendarView::class)->name('schedules.calendar');
    Route::get('/schedules/deployment-sheet', DeploymentSheet::class)->name('schedules.deployment-sheet');
    Route::get('/attendance/reconciliation', ReconciliationBoard::class)->name('attendance.reconciliation');
    Route::get('/patrols/playback', Playback::class)->name('patrols.playback');
    Route::get('/patrols/vehicles', VehiclePatrolBoard::class)->name('patrols.vehicles');
    Route::get('/clients/complaints', ComplaintBoard::class)->name('clients.complaints');
    Route::get('/compliance/policies', PolicyCenter::class)->name('compliance.policies');
    Route::get('/billing/payroll', PayrollBoard::class)->name('billing.payroll');
    Route::get('/analytics', AnalyticsDashboard::class)->name('analytics.dashboard');
    Route::get('/guards/hr-records', fn () => redirect()->route('guards.index'))->name('guards.hr-records');
    Route::get('/sites/compliance', SiteCompliance::class)->name('sites.compliance');
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('/push/subscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
});
