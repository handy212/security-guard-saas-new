<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EnterpriseApiController;
use App\Http\Controllers\Api\MobileAppController;

Route::middleware(['auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('v1')->group(function () {
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
});

Route::middleware(['auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('enterprise')->group(function () {
    Route::get('/analytics', [EnterpriseApiController::class, 'analytics']);
    Route::get('/deployment-sheet', [EnterpriseApiController::class, 'deploymentSheet']);
    Route::get('/patrol-playback/{session}', [EnterpriseApiController::class, 'patrolPlayback']);
    Route::post('/client-complaints', [EnterpriseApiController::class, 'storeComplaint']);
    Route::post('/offline/playback-points', [EnterpriseApiController::class, 'storePlaybackPoint']);
});
