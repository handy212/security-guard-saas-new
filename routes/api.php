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
    Route::post('/location', [MobileAppController::class, 'updateLocation']);
    Route::post('/offline-sync', [MobileAppController::class, 'offlineSync']);
    Route::post('/visitors/check-in', [MobileAppController::class, 'visitorCheckIn']);
    Route::post('/visitors/{visitorLog}/check-out', [MobileAppController::class, 'visitorCheckOut']);
});

Route::middleware(['auth:sanctum', 'tenant', 'throttle:60,1'])->prefix('enterprise')->group(function () {
    Route::get('/analytics', [EnterpriseApiController::class, 'analytics']);
    Route::get('/deployment-sheet', [EnterpriseApiController::class, 'deploymentSheet']);
    Route::get('/patrol-playback/{session}', [EnterpriseApiController::class, 'patrolPlayback']);
    Route::post('/client-complaints', [EnterpriseApiController::class, 'storeComplaint']);
    Route::post('/offline/playback-points', [EnterpriseApiController::class, 'storePlaybackPoint']);
});
