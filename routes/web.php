<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\SsoController;
use App\Http\Controllers\GuardIdCardBulkDownloadController;
use App\Http\Controllers\GuardIdCardController;
use App\Http\Controllers\GuardIdCardPreviewController;
use App\Http\Controllers\GuardIdCardPrintController;
use App\Http\Controllers\GuardVerificationController;
use App\Http\Controllers\GuardVerificationLogoController;
use App\Http\Controllers\GuardVerificationPhotoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaystackCallbackController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\PlatformBrandAssetController;
use App\Http\Controllers\PlatformBrandingController;
use App\Http\Controllers\PlatformTenantContextController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\TenantFileController;
use App\Livewire\Analytics\AnalyticsDashboard;
use App\Livewire\Assets\AssetIndex;
use App\Livewire\Assets\CategoryIndex;
use App\Livewire\Assets\InventoryIndex;
use App\Livewire\Assets\Overview as AssetsOverview;
use App\Livewire\Assets\PurchaseOrderIndex;
use App\Livewire\Assets\VendorIndex;
use App\Livewire\Billing\BackOfficeHub;
use App\Livewire\Billing\EstimateForm;
use App\Livewire\Billing\EstimateIndex;
use App\Livewire\Billing\EstimateShow;
use App\Livewire\Billing\ExpenseIndex;
use App\Livewire\Billing\InvoiceForm;
use App\Livewire\Billing\InvoiceIndex;
use App\Livewire\Billing\InvoiceShow;
use App\Livewire\Billing\PaymentIndex;
use App\Livewire\Billing\PayrollBoard;
use App\Livewire\Billing\SubscriptionManager;
use App\Livewire\ClientPortal\Approvals;
use App\Livewire\ClientPortal\PortalDashboard;
use App\Livewire\ClientPortal\PortalInvoices;
use App\Livewire\ClientPortal\PortalInvoiceShow;
use App\Livewire\ClientPortal\PortalReportShow;
use App\Livewire\Clients\ClientIndex;
use App\Livewire\Clients\ClientProfile;
use App\Livewire\Clients\ComplaintBoard;
use App\Livewire\Compliance\ComplianceDashboard;
use App\Livewire\Compliance\PolicyCenter;
use App\Livewire\Dashboard\Overview;
use App\Livewire\Dispatch\DispatcherBoard;
use App\Livewire\Guard\MobileDashboard;
use App\Livewire\Guards\GuardIndex;
use App\Livewire\Guards\GuardProfile;
use App\Livewire\Incidents\IncidentIndex;
use App\Livewire\Messenger\MessengerIndex;
use App\Livewire\Mobile\OfflineSyncMonitor;
use App\Livewire\Notifications\NotificationCenter;
use App\Livewire\Passdown\PassdownIndex;
use App\Livewire\Patrols\FleetIndex;
use App\Livewire\Patrols\JwmDeviceManager;
use App\Livewire\Patrols\PatrolBoard;
use App\Livewire\Patrols\Playback;
use App\Livewire\Patrols\VehiclePatrolBoard;
use App\Livewire\Public\GuardApplicationForm;
use App\Livewire\Reports\DailyReportIndex;
use App\Livewire\Reports\ReportsHub;
use App\Livewire\Reports\ReportTemplateBuilder;
use App\Livewire\Scheduling\SchedulerWorkspace;
use App\Livewire\Settings\AuditLogIndex;
use App\Livewire\Settings\BranchIndex;
use App\Livewire\Settings\CompanyProfile;
use App\Livewire\Settings\IdCardSettings;
use App\Livewire\Settings\KygPageSettings;
use App\Livewire\Settings\NotificationTemplateIndex;
use App\Livewire\Settings\RolePermissionManager;
use App\Livewire\Settings\SettingsHub;
use App\Livewire\Settings\StaffUserIndex;
use App\Livewire\Settings\TeamPasswordReset;
use App\Livewire\Settings\ThemeSettings;
use App\Livewire\Settings\TwoFactorSetup;
use App\Livewire\Settings\WebhookManager;
use App\Livewire\Sites\SiteIndex;
use App\Livewire\Sites\SiteProfile;
use App\Livewire\Tenants\PlatformPlanManagement;
use App\Livewire\Tenants\PlatformSubscriptionManagement;
use App\Livewire\Tenants\TenantManagement;
use App\Livewire\Tracking\LiveTracker;
use App\Livewire\Visitors\VisitorLogIndex;
use Illuminate\Support\Facades\Route;

Route::post('/paystack/webhook', PaystackWebhookController::class)->name('paystack.webhook');
Route::get('/branding/{kind}/{version}', PlatformBrandAssetController::class)
    ->where(['kind' => 'logo|favicon|background', 'version' => '[a-f0-9]{40}'])
    ->name('platform.brand-asset');

Route::get('/g/{tenant}/{token}', GuardVerificationController::class)
    ->middleware('throttle:60,1')
    ->where(['tenant' => '[a-z0-9-]+', 'token' => '[A-Z0-9]{8,32}'])
    ->name('guard.verify');

Route::get('/g/{tenant}/{token}/photo', GuardVerificationPhotoController::class)
    ->middleware('throttle:120,1')
    ->where(['tenant' => '[a-z0-9-]+', 'token' => '[A-Z0-9]{8,32}'])
    ->name('guard.verify.photo');

Route::get('/g/{tenant}/{token}/logo', GuardVerificationLogoController::class)
    ->middleware('throttle:120,1')
    ->where(['tenant' => '[a-z0-9-]+', 'token' => '[A-Z0-9]{8,32}'])
    ->name('guard.verify.logo');

Route::get('/g/{token}', GuardVerificationController::class)
    ->middleware('throttle:60,1')
    ->where('token', '[A-Z0-9]{8,32}')
    ->name('guard.verify.legacy');

Route::get('/g/{token}/photo', GuardVerificationPhotoController::class)
    ->middleware('throttle:120,1')
    ->where('token', '[A-Z0-9]{8,32}')
    ->name('guard.verify.photo.legacy');

Route::get('/g/{token}/logo', GuardVerificationLogoController::class)
    ->middleware('throttle:120,1')
    ->where('token', '[A-Z0-9]{8,32}')
    ->name('guard.verify.logo.legacy');

Route::get('/apply/{tenant}', GuardApplicationForm::class)
    ->middleware('throttle:30,1')
    ->where('tenant', '[A-Za-z0-9\-]+')
    ->name('guards.apply');

Route::get('/', HomeController::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password/{token}', [PasswordResetController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
    Route::get('/auth/sso/redirect', [SsoController::class, 'redirect'])->name('sso.redirect');
    Route::get('/auth/sso/callback', [SsoController::class, 'callback'])->name('sso.callback');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'tenant', 'plan.feature', 'two-factor'])->group(function () {
    Route::get('/dashboard', Overview::class)->name('dashboard');
    Route::get('/clients', ClientIndex::class)->name('clients.index');
    Route::get('/clients/complaints', ComplaintBoard::class)->name('clients.complaints');
    Route::get('/clients/{clientAccount}', ClientProfile::class)->name('clients.show');
    Route::get('/sites', SiteIndex::class)->name('sites.index');
    Route::redirect('/sites/compliance', '/compliance');
    Route::get('/sites/{site}', SiteProfile::class)->name('sites.show');
    Route::get('/guards', GuardIndex::class)->name('guards.index');
    Route::redirect('/guards/know-your-guard', '/guards?tab=verification')->name('guards.kyg');
    Route::redirect('/guards/applications', '/guards?tab=applications')->name('guards.applications');
    Route::get('/guards/id-cards/bulk', GuardIdCardBulkDownloadController::class)->name('guards.id-cards.bulk');
    Route::get('/guards/{guard}', GuardProfile::class)->name('guards.show');
    Route::get('/guards/{guard}/id-card', GuardIdCardController::class)->name('guards.id-card');
    Route::get('/guards/{guard}/id-card/print', GuardIdCardPrintController::class)->name('guards.id-card.print');
    Route::get('/guards/{guard}/id-card/preview', [GuardIdCardPreviewController::class, 'guard'])->name('guards.id-card.preview');
    Route::get('/files/guards/{guard}/photo', [TenantFileController::class, 'guardPhoto'])->name('files.guard-photo');
    Route::get('/files/applications/{application}/photo', [TenantFileController::class, 'applicationPhoto'])->name('files.application-photo');
    Route::get('/files/id-card-logo', [TenantFileController::class, 'idCardLogo'])->name('files.id-card-logo');
    Route::get('/files/id-card-back-logo', [TenantFileController::class, 'idCardBackLogo'])->name('files.id-card-back-logo');
    Route::get('/files/id-card-signature', [TenantFileController::class, 'idCardSignature'])->name('files.id-card-signature');
    Route::get('/files/guard-documents/{document}', [TenantFileController::class, 'guardDocument'])->name('files.guard-document');
    Route::get('/files/client-documents/{document}', [TenantFileController::class, 'clientDocument'])->name('files.client-document');
    Route::get('/files/site-documents/{document}', [TenantFileController::class, 'siteDocument'])->name('files.site-document');
    Route::get('/files/expenses/{expense}/receipt', [TenantFileController::class, 'expenseReceipt'])->name('files.expense-receipt');
    Route::get('/schedules', SchedulerWorkspace::class)->name('schedules.index');
    Route::get('/schedules/templates', fn () => redirect()->route('schedules.index', array_merge(request()->query(), ['tab' => 'templates'])))->middleware('can:schedules.manage')->name('schedules.templates');
    Route::get('/schedules/attendance', fn () => redirect()->route('schedules.index', array_merge(request()->query(), ['tab' => 'attendance'])))->middleware('can:attendance.manage')->name('schedules.attendance');
    Route::get('/schedules/shift-status', fn () => redirect()->route('schedules.index', array_merge(request()->query(), ['tab' => 'shift-status'])))->middleware('can:schedules.manage')->name('schedules.shift-status');
    Route::get('/schedules/open-shifts', fn () => redirect()->route('schedules.index', array_merge(request()->query(), ['tab' => 'open-shifts'])))->middleware('can:schedules.manage')->name('schedules.open-shifts');
    Route::get('/schedules/shift-exchange', fn () => redirect()->route('schedules.index', array_merge(request()->query(), ['tab' => 'shift-exchange'])))->middleware('can:schedules.manage')->name('schedules.shift-exchange');
    Route::get('/schedules/time-off', fn () => redirect()->route('schedules.index', array_merge(request()->query(), ['tab' => 'time-off'])))->middleware('can:schedules.manage')->name('schedules.time-off');
    Route::redirect('/workforce', '/schedules/time-off');
    Route::redirect('/attendance/timekeeping', '/schedules/attendance');
    Route::redirect('/schedules/marketplace', '/schedules/open-shifts');
    Route::get('/patrols', PatrolBoard::class)->name('patrols.index');
    Route::get('/patrols/devices', JwmDeviceManager::class)
        ->middleware('can:patrols.manage')->name('patrols.devices');
    Route::get('/incidents', IncidentIndex::class)->name('incidents.index');
    Route::get('/reports', ReportsHub::class)->name('reports.hub');
    Route::get('/reports/daily', DailyReportIndex::class)->name('reports.daily');
    Route::get('/reports/templates', ReportTemplateBuilder::class)->name('reports.templates');
    Route::get('/tracking', LiveTracker::class)->name('tracking.live');
    Route::get('/notifications', NotificationCenter::class)->name('notifications.index');
    Route::get('/dispatch', DispatcherBoard::class)->name('dispatch.control-room');
    Route::middleware('client.portal')->group(function () {
        Route::get('/client-portal', PortalDashboard::class)->name('client-portal.dashboard');
        Route::get('/client-portal/reports/{reportId}', PortalReportShow::class)->name('client-portal.reports.show');
        Route::get('/client-portal/approvals', Approvals::class)->name('client-portal.approvals');
        Route::get('/client-portal/invoices', PortalInvoices::class)->name('client-portal.invoices');
        Route::get('/client-portal/invoices/{invoice}', PortalInvoiceShow::class)->name('client-portal.invoices.show');
    });
    Route::get('/billing', BackOfficeHub::class)->name('billing.hub');
    Route::get('/billing/invoices', InvoiceIndex::class)->name('billing.invoices');
    Route::get('/billing/invoices/create', InvoiceForm::class)->name('billing.invoices.create');
    Route::get('/billing/invoices/{invoice}/edit', InvoiceForm::class)->name('billing.invoices.edit');
    Route::get('/billing/invoices/{invoice}', InvoiceShow::class)->name('billing.invoices.show');
    Route::get('/billing/estimates', EstimateIndex::class)->name('billing.estimates');
    Route::get('/billing/estimates/create', EstimateForm::class)->name('billing.estimates.create');
    Route::get('/billing/estimates/{estimate}/edit', EstimateForm::class)->name('billing.estimates.edit');
    Route::get('/billing/estimates/{estimate}', EstimateShow::class)->name('billing.estimates.show');
    Route::get('/billing/payments', PaymentIndex::class)->name('billing.payments');
    Route::get('/billing/expenses', ExpenseIndex::class)->name('billing.expenses');
    Route::get('/billing/subscription', SubscriptionManager::class)->name('billing.subscription');
    Route::get('/billing/subscription/callback', PaystackCallbackController::class)->name('billing.paystack.callback');
    Route::get('/settings', SettingsHub::class)->name('settings.index');
    Route::get('/settings/company-profile', CompanyProfile::class)->name('settings.company-profile');
    Route::get('/settings/themes', ThemeSettings::class)->name('settings.themes');
    Route::get('/settings/branches', BranchIndex::class)->name('settings.branches');
    Route::get('/settings/id-card', IdCardSettings::class)->name('settings.id-card');
    Route::get('/settings/know-your-guard', KygPageSettings::class)->name('settings.kyg');
    Route::get('/settings/roles', RolePermissionManager::class)->name('settings.roles');
    Route::get('/settings/two-factor', TwoFactorSetup::class)->name('settings.two-factor');
    Route::get('/settings/webhooks', WebhookManager::class)->name('settings.webhooks');
    Route::get('/settings/notifications', NotificationTemplateIndex::class)->name('settings.notifications');
    Route::get('/settings/audit-log', AuditLogIndex::class)->name('settings.audit-log');
    Route::get('/settings/staff', StaffUserIndex::class)->name('settings.staff');
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
        Route::get('/saas/branding', [PlatformBrandingController::class, 'edit'])->name('saas.branding');
        Route::post('/saas/branding', [PlatformBrandingController::class, 'update'])->name('saas.branding.update');
        Route::post('/saas/exit-tenant', [PlatformTenantContextController::class, 'exit'])->name('saas.exit-tenant');
    });
    Route::get('/messenger', MessengerIndex::class)->name('messenger.index');
    Route::get('/passdown', PassdownIndex::class)->name('passdown.index');
    Route::get('/schedules/calendar', fn () => redirect()->route('schedules.index', array_merge(request()->query(), ['tab' => 'calendar'])))->middleware('can:schedules.manage')->name('schedules.calendar');
    Route::get('/schedules/deployment-sheet', fn () => redirect()->route('schedules.index', array_merge(request()->query(), ['tab' => 'deployment-sheet'])))->middleware('can:schedules.manage')->name('schedules.deployment-sheet');
    Route::get('/schedules/deploy', fn () => redirect()->route('schedules.index', array_merge(request()->query(), ['tab' => 'deploy'])))->middleware('can:schedules.manage')->name('schedules.deploy');
    Route::get('/schedules/reconciliation', fn () => redirect()->route('schedules.index', array_merge(request()->query(), ['tab' => 'reconciliation'])))->middleware('can:attendance.manage')->name('schedules.reconciliation');
    Route::redirect('/attendance/reconciliation', '/schedules/reconciliation')->name('attendance.reconciliation');
    Route::get('/patrols/playback', Playback::class)->name('patrols.playback');
    Route::get('/patrols/vehicles', VehiclePatrolBoard::class)->name('patrols.vehicles');
    Route::get('/patrols/fleet', FleetIndex::class)->name('patrols.fleet');
    Route::get('/compliance/policies', PolicyCenter::class)->name('compliance.policies');
    Route::get('/billing/payroll', PayrollBoard::class)->name('billing.payroll');
    Route::get('/analytics', AnalyticsDashboard::class)->name('analytics.dashboard');
    Route::get('/guards/hr-records', fn () => redirect()->route('guards.index'))->name('guards.hr-records');
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('/push/subscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
});
