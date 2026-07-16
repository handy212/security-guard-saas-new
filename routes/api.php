<?php

use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\EnterpriseApiController;
use App\Http\Controllers\Api\MobileAppController;
use App\Http\Controllers\Api\Admin\AssetController;
use App\Http\Controllers\Api\Admin\BranchController;
use App\Http\Controllers\Api\Admin\ClientController;
use App\Http\Controllers\Api\Admin\ComplaintController;
use App\Http\Controllers\Api\Admin\EstimateController;
use App\Http\Controllers\Api\Admin\ExpenseController;
use App\Http\Controllers\Api\Admin\FleetVehicleController;
use App\Http\Controllers\Api\Admin\GuardController;
use App\Http\Controllers\Api\Admin\IncidentController;
use App\Http\Controllers\Api\Admin\NotificationTemplateController;
use App\Http\Controllers\Api\Admin\PassdownController;
use App\Http\Controllers\Api\Admin\PatrolCheckpointController;
use App\Http\Controllers\Api\Admin\PatrolRouteController;
use App\Http\Controllers\Api\Admin\ReportTemplateController;
use App\Http\Controllers\Api\Admin\ShiftController;
use App\Http\Controllers\Api\Admin\SiteController;
use App\Http\Controllers\Api\Admin\StaffUserController;
use App\Http\Controllers\Api\Admin\VisitorController;
use App\Http\Controllers\Api\Admin\WebhookController;
use App\Http\Controllers\Api\Admin\Nested\CheckpointTaskController;
use App\Http\Controllers\Api\Admin\Nested\ClientContactController;
use App\Http\Controllers\Api\Admin\Nested\ClientDocumentController;
use App\Http\Controllers\Api\Admin\Nested\ClientNoteController;
use App\Http\Controllers\Api\Admin\Nested\GuardCertificationController;
use App\Http\Controllers\Api\Admin\Nested\GuardDocumentController;
use App\Http\Controllers\Api\Admin\Nested\GuardNoteController;
use App\Http\Controllers\Api\Admin\Nested\GuardSkillController;
use App\Http\Controllers\Api\Admin\Nested\PostOrderController;
use App\Http\Controllers\Api\Admin\Nested\SiteDocumentController;
use App\Http\Controllers\Api\Admin\Nested\SiteNoteController;
use App\Http\Controllers\Api\Admin\Nested\SitePostController;
use App\Http\Controllers\Api\Admin\Nested\SiteSlaRequirementController;
use App\Http\Controllers\Api\Admin\Nested\TrainingRecordController;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Route;

Route::post('/v1/auth/token', [AuthTokenController::class, 'store'])
    ->middleware('throttle:60,1');

Route::middleware(['auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('v1')->group(function () {
    Route::delete('/auth/token', [AuthTokenController::class, 'destroy']);

    Route::get('/me/assignments', [MobileAppController::class, 'myAssignments']);
    Route::post('/attendance/clock-in', [MobileAppController::class, 'clockIn']);
    Route::post('/attendance/clock-out', [MobileAppController::class, 'clockOut']);
    Route::post('/patrols/scan', [MobileAppController::class, 'scanCheckpoint']);
    Route::post('/incidents', [MobileAppController::class, 'reportIncident']);
    Route::post('/sos', [MobileAppController::class, 'sos']);
    Route::get('/dispatches', [MobileAppController::class, 'myDispatches']);
    Route::post('/dispatches/{dispatchEvent}/advance', [MobileAppController::class, 'advanceDispatch']);
    Route::post('/location', [MobileAppController::class, 'updateLocation']);
    Route::post('/offline-sync', [MobileAppController::class, 'offlineSync']);
    Route::post('/visitors/check-in', [MobileAppController::class, 'visitorCheckIn']);
    Route::post('/visitors/{visitorLog}/check-out', [MobileAppController::class, 'visitorCheckOut']);
    Route::post('/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'store']);
    Route::delete('/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'destroy']);
    Route::post('/reports/custom/draft', [MobileAppController::class, 'saveReportDraft']);
    Route::post('/reports/custom/submit', [MobileAppController::class, 'submitCustomReport']);
    Route::post('/shifts/confirm', [MobileAppController::class, 'confirmShift']);
    Route::get('/open-shifts', [MobileAppController::class, 'openShifts']);
    Route::get('/my-bids', [MobileAppController::class, 'myBids']);
    Route::post('/open-shifts/{shift}/bid', [MobileAppController::class, 'bidOnOpenShift']);
    Route::get('/shift-swaps', [MobileAppController::class, 'myShiftSwaps']);
    Route::post('/shift-swaps', [MobileAppController::class, 'requestShiftSwap']);

    Route::prefix('admin')->name('api.admin.')->group(function () {
        Route::bind('staff_user', fn (string $value) => User::query()
            ->where('tenant_id', TenantContext::id())
            ->whereNull('client_account_id')
            ->findOrFail($value));

        Route::apiResource('clients', ClientController::class);
        Route::apiResource('sites', SiteController::class);
        Route::apiResource('guards', GuardController::class);
        Route::apiResource('branches', BranchController::class);
        Route::apiResource('assets', AssetController::class)->parameters(['assets' => 'asset']);
        Route::apiResource('shifts', ShiftController::class);
        Route::apiResource('incidents', IncidentController::class);
        Route::apiResource('expenses', ExpenseController::class);
        Route::apiResource('estimates', EstimateController::class);
        Route::apiResource('complaints', ComplaintController::class)->parameters(['complaints' => 'complaint']);
        Route::apiResource('visitors', VisitorController::class)->parameters(['visitors' => 'visitor']);
        Route::apiResource('passdowns', PassdownController::class)->parameters(['passdowns' => 'passdown']);
        Route::apiResource('fleet-vehicles', FleetVehicleController::class)->parameters(['fleet-vehicles' => 'fleetVehicle']);
        Route::apiResource('webhooks', WebhookController::class)->parameters(['webhooks' => 'webhook']);
        Route::apiResource('notification-templates', NotificationTemplateController::class)->parameters(['notification-templates' => 'notificationTemplate']);
        Route::apiResource('report-templates', ReportTemplateController::class)->parameters(['report-templates' => 'reportTemplate']);
        Route::apiResource('patrol-routes', PatrolRouteController::class)->parameters(['patrol-routes' => 'patrolRoute']);
        Route::apiResource('patrol-checkpoints', PatrolCheckpointController::class)->parameters(['patrol-checkpoints' => 'patrolCheckpoint']);
        Route::apiResource('staff-users', StaffUserController::class)->parameters(['staff-users' => 'staffUser']);

        Route::apiResource('sites.posts', SitePostController::class);
        Route::apiResource('sites.post-orders', PostOrderController::class)->parameters(['post-orders' => 'postOrder']);
        Route::apiResource('sites.notes', SiteNoteController::class);
        Route::apiResource('sites.documents', SiteDocumentController::class);
        Route::apiResource('sites.sla-requirements', SiteSlaRequirementController::class)->parameters(['sla-requirements' => 'slaRequirement']);
        Route::apiResource('sites.checkpoint-tasks', CheckpointTaskController::class)->parameters(['checkpoint-tasks' => 'checkpointTask']);

        Route::apiResource('clients.notes', ClientNoteController::class);
        Route::apiResource('clients.contacts', ClientContactController::class);
        Route::apiResource('clients.documents', ClientDocumentController::class);

        Route::apiResource('guards.notes', GuardNoteController::class);
        Route::apiResource('guards.training-records', TrainingRecordController::class)->parameters(['training-records' => 'trainingRecord']);
        Route::apiResource('guards.documents', GuardDocumentController::class);
        Route::apiResource('guards.certifications', GuardCertificationController::class);
        Route::apiResource('guards.skills', GuardSkillController::class);

        Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');
        Route::post('expenses/{expense}/mark-paid', [ExpenseController::class, 'markPaid'])->name('expenses.mark-paid');
        Route::post('estimates/{estimate}/accept', [EstimateController::class, 'accept'])->name('estimates.accept');
        Route::post('estimates/{estimate}/convert', [EstimateController::class, 'convert'])->name('estimates.convert');
        Route::post('estimates/{estimate}/send', [EstimateController::class, 'send'])->name('estimates.send');
        Route::post('incidents/{incident}/approve', [IncidentController::class, 'approve'])->name('incidents.approve');
        Route::post('incidents/{incident}/close', [IncidentController::class, 'close'])->name('incidents.close');
        Route::post('incidents/{incident}/reject', [IncidentController::class, 'reject'])->name('incidents.reject');
        Route::post('complaints/{complaint}/resolve', [ComplaintController::class, 'resolve'])->name('complaints.resolve');
        Route::post('visitors/{visitor}/check-out', [VisitorController::class, 'checkOut'])->name('visitors.check-out');
    });
});

Route::middleware(['auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('enterprise')->group(function () {
    Route::get('/analytics', [EnterpriseApiController::class, 'analytics']);
    Route::get('/deployment-sheet', [EnterpriseApiController::class, 'deploymentSheet']);
    Route::get('/patrol-playback/{session}', [EnterpriseApiController::class, 'patrolPlayback']);
    Route::post('/client-complaints', [EnterpriseApiController::class, 'storeComplaint']);
    Route::post('/offline/playback-points', [EnterpriseApiController::class, 'storePlaybackPoint']);
});
