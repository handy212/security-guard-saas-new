<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{AttendanceLog, CheckpointScan, Incident, PatrolSession, ShiftAssignment, SosAlert};
use App\Services\{AttendanceService, PatrolService, IncidentService, DispatchService};
use Illuminate\Http\Request;

class MobileAppController extends Controller
{
    public function myAssignments(Request $request)
    {
        return ShiftAssignment::with(['shift.site','shift.sitePost'])
            ->where('guard_id', $request->user()->guardProfile?->id)
            ->latest()->limit(30)->get();
    }

    public function clockIn(Request $request, AttendanceService $service)
    {
        $data = $request->validate([
            'shift_assignment_id' => ['required','integer'],
            'latitude' => ['required','numeric'],
            'longitude' => ['required','numeric'],
        ]);
        return $service->clockIn($data['shift_assignment_id'], $data['latitude'], $data['longitude']);
    }

    public function clockOut(Request $request, AttendanceService $service)
    {
        $data = $request->validate([
            'attendance_log_id' => ['required','integer'],
            'latitude' => ['required','numeric'],
            'longitude' => ['required','numeric'],
        ]);
        return $service->clockOut($data['attendance_log_id'], $data['latitude'], $data['longitude']);
    }

    public function scanCheckpoint(Request $request, PatrolService $service)
    {
        $data = $request->validate([
            'patrol_session_id' => ['required','integer'],
            'checkpoint_code' => ['required','string'],
            'latitude' => ['required','numeric'],
            'longitude' => ['required','numeric'],
            'notes' => ['nullable','string'],
        ]);
        return $service->scanCheckpoint($data);
    }

    public function reportIncident(Request $request, IncidentService $service)
    {
        $data = $request->validate([
            'site_id' => ['required','integer'],
            'shift_assignment_id' => ['nullable','integer'],
            'title' => ['required','string','max:180'],
            'type' => ['required','string','max:80'],
            'severity' => ['required','string'],
            'description' => ['required','string'],
            'latitude' => ['nullable','numeric'],
            'longitude' => ['nullable','numeric'],
        ]);
        return $service->submit($data + ['reported_by_user_id' => $request->user()->id]);
    }

    public function sos(Request $request, DispatchService $service)
    {
        $data = $request->validate([
            'site_id' => ['nullable','integer'],
            'latitude' => ['required','numeric'],
            'longitude' => ['required','numeric'],
            'message' => ['nullable','string'],
        ]);
        return $service->raiseSos($request->user(), $data);
    }

    public function offlineSync(Request $request)
    {
        $batch = \App\Models\OfflineSyncBatch::create([
            'tenant_id' => $request->user()->tenant_id,
            'user_id' => $request->user()->id,
            'device_uuid' => $request->input('device_uuid'),
            'payload' => $request->input('payload', []),
            'status' => 'pending',
        ]);
        return response()->json(['batch_id' => $batch->id, 'status' => 'queued']);
    }

    public function visitorCheckIn(Request $request)
    {
        $visitor = \App\Models\VisitorLog::create($request->validate([
            'site_id' => ['required','integer'],
            'guard_id' => ['nullable','integer'],
            'visitor_name' => ['required','string'],
            'visitor_phone' => ['nullable','string'],
            'company' => ['nullable','string'],
            'purpose' => ['nullable','string'],
            'vehicle_plate' => ['nullable','string'],
        ]) + ['tenant_id' => $request->user()->tenant_id, 'checked_in_at' => now()]);
        return response()->json($visitor, 201);
    }

    public function visitorCheckOut(Request $request, \App\Models\VisitorLog $visitorLog)
    {
        $visitorLog->update(['checked_out_at' => now(), 'status' => 'checked_out']);
        return response()->json($visitorLog);
    }
}
