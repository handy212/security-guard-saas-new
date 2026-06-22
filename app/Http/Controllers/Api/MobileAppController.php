<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessOfflineSyncBatch;
use App\Models\AttendanceLog;
use App\Models\OfflineSyncBatch;
use App\Models\ShiftAssignment;
use App\Models\VisitorLog;
use App\Services\AttendanceService;
use App\Services\DispatchService;
use App\Services\IncidentService;
use App\Services\PatrolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Enums\IncidentSeverity;

class MobileAppController extends Controller
{
    public function myAssignments(Request $request): JsonResponse
    {
        $guardId = $this->guardId($request);

        return response()->json(
            ShiftAssignment::with(['shift.site', 'shift.sitePost'])
                ->where('guard_id', $guardId)
                ->where('tenant_id', $request->user()->tenant_id)
                ->latest()
                ->limit(30)
                ->get()
        );
    }

    public function clockIn(Request $request, AttendanceService $service): AttendanceLog
    {
        $data = $request->validate([
            'shift_assignment_id' => ['required', 'integer'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        $assignment = $this->ownedAssignment($request, $data['shift_assignment_id']);

        return $service->clockIn($assignment->id, $data['latitude'], $data['longitude']);
    }

    public function clockOut(Request $request, AttendanceService $service): AttendanceLog
    {
        $data = $request->validate([
            'attendance_log_id' => ['required', 'integer'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
        ]);

        $log = AttendanceLog::query()
            ->where('id', $data['attendance_log_id'])
            ->where('guard_id', $this->guardId($request))
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        return $service->clockOut($log->id, $data['latitude'], $data['longitude']);
    }

    public function scanCheckpoint(Request $request, PatrolService $service)
    {
        $data = $request->validate([
            'patrol_session_id' => ['required', 'integer'],
            'checkpoint_code' => ['required', 'string'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $session = \App\Models\PatrolSession::query()
            ->where('id', $data['patrol_session_id'])
            ->where('guard_id', $this->guardId($request))
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $data['patrol_session_id'] = $session->id;

        return $service->scanCheckpoint($data);
    }

    public function reportIncident(Request $request, IncidentService $service)
    {
        $data = $request->validate([
            'site_id' => ['required', 'integer'],
            'shift_assignment_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', 'string', 'max:80'],
            'severity' => ['required', Rule::enum(IncidentSeverity::class)],
            'description' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        if (! empty($data['shift_assignment_id'])) {
            $this->ownedAssignment($request, $data['shift_assignment_id']);
        }

        return $service->submit($data + [
            'tenant_id' => $request->user()->tenant_id,
            'reported_by_user_id' => $request->user()->id,
        ]);
    }

    public function sos(Request $request, DispatchService $service)
    {
        $data = $request->validate([
            'site_id' => ['nullable', 'integer'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'message' => ['nullable', 'string'],
        ]);

        return $service->raiseSos($request->user(), $data);
    }

    public function offlineSync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_uuid' => ['nullable', 'uuid'],
            'payload' => ['required', 'array'],
        ]);

        $batch = OfflineSyncBatch::create([
            'tenant_id' => $request->user()->tenant_id,
            'user_id' => $request->user()->id,
            'device_uuid' => $data['device_uuid'] ?? null,
            'payload' => $data['payload'],
            'status' => 'pending',
        ]);

        ProcessOfflineSyncBatch::dispatch($batch);

        return response()->json(['batch_id' => $batch->id, 'status' => 'queued']);
    }

    public function visitorCheckIn(Request $request): JsonResponse
    {
        $visitor = VisitorLog::create($request->validate([
            'site_id' => ['required', 'integer'],
            'guard_id' => ['nullable', 'integer'],
            'visitor_name' => ['required', 'string'],
            'visitor_phone' => ['nullable', 'string'],
            'company' => ['nullable', 'string'],
            'purpose' => ['nullable', 'string'],
            'vehicle_plate' => ['nullable', 'string'],
        ]) + [
            'tenant_id' => $request->user()->tenant_id,
            'guard_id' => $this->guardId($request),
            'checked_in_at' => now(),
        ]);

        return response()->json($visitor, 201);
    }

    public function visitorCheckOut(Request $request, VisitorLog $visitorLog): JsonResponse
    {
        abort_unless($visitorLog->tenant_id === $request->user()->tenant_id, 403);

        $visitorLog->update(['checked_out_at' => now(), 'status' => 'checked_out']);

        return response()->json($visitorLog);
    }

    private function guardId(Request $request): int
    {
        $guardId = $request->user()->guardProfile?->id;

        abort_unless($guardId, 403, 'Guard profile is required for this action.');

        return $guardId;
    }

    private function ownedAssignment(Request $request, int $assignmentId): ShiftAssignment
    {
        return ShiftAssignment::query()
            ->where('id', $assignmentId)
            ->where('guard_id', $this->guardId($request))
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();
    }
}
