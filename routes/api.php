<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MobileAppController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('/me/assignments', [MobileAppController::class, 'myAssignments']);
    Route::post('/attendance/clock-in', [MobileAppController::class, 'clockIn']);
    Route::post('/attendance/clock-out', [MobileAppController::class, 'clockOut']);
    Route::post('/patrols/scan', [MobileAppController::class, 'scanCheckpoint']);
    Route::post('/incidents', [MobileAppController::class, 'reportIncident']);
    Route::post('/sos', [MobileAppController::class, 'sos']);
    Route::post('/offline-sync', [MobileAppController::class, 'offlineSync']);
    Route::post('/visitors/check-in', [MobileAppController::class, 'visitorCheckIn']);
    Route::post('/visitors/{visitorLog}/check-out', [MobileAppController::class, 'visitorCheckOut']);
});


Route::middleware(['api'])->prefix('enterprise')->group(function () {
    Route::get('/analytics', function () { return ['data' => \App\Models\AnalyticsSnapshot::latest()->first()]; });
    Route::get('/deployment-sheet', function () { return ['data' => \App\Models\ShiftAssignment::with(['shift.site','guard'])->latest()->limit(100)->get()]; });
    Route::get('/patrol-playback/{session}', function (\App\Models\PatrolSession $session) { return ['data' => \App\Models\PatrolPlaybackPoint::where('patrol_session_id',$session->id)->orderBy('recorded_at')->get()]; });
    Route::post('/client-complaints', function (\Illuminate\Http\Request $request) { return \App\Models\ClientComplaint::create($request->all()); });
    Route::post('/offline/playback-points', function (\Illuminate\Http\Request $request) { return \App\Models\PatrolPlaybackPoint::create($request->all()); });
});
