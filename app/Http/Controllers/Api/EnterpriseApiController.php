<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsSnapshot;
use App\Models\ClientComplaint;
use App\Models\PatrolPlaybackPoint;
use App\Models\PatrolSession;
use App\Models\ShiftAssignment;
use App\Support\TenantValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnterpriseApiController extends Controller
{
    public function analytics(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('analytics.view'), 403);

        return response()->json([
            'data' => AnalyticsSnapshot::query()->latest()->first(),
        ]);
    }

    public function deploymentSheet(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('schedules.manage'), 403);

        return response()->json([
            'data' => ShiftAssignment::with(['shift.site', 'assignedGuard'])
                ->latest()
                ->limit(100)
                ->get(),
        ]);
    }

    public function patrolPlayback(Request $request, PatrolSession $session): JsonResponse
    {
        $this->authorize('view', $session);

        return response()->json([
            'data' => PatrolPlaybackPoint::query()
                ->where('patrol_session_id', $session->id)
                ->orderBy('recorded_at')
                ->get(),
        ]);
    }

    public function storeComplaint(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()->can('clients.manage') || $request->user()->can('client_portal.view'),
            403
        );

        $tenantId = (int) $request->user()->tenant_id;

        $data = $request->validate([
            'client_account_id' => ['required', 'integer', TenantValidation::existsForTenant($tenantId, 'client_accounts')],
            'site_id' => ['nullable', 'integer', TenantValidation::existsForTenant($tenantId, 'sites')],
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string'],
            'priority' => ['nullable', 'string', 'max:40'],
        ]);

        $complaint = ClientComplaint::create($data + [
            'tenant_id' => $request->user()->tenant_id,
            'status' => 'open',
        ]);

        return response()->json($complaint, 201);
    }

    public function storePlaybackPoint(Request $request): JsonResponse
    {
        $tenantId = (int) $request->user()->tenant_id;

        $data = $request->validate([
            'patrol_session_id' => ['required', 'integer', TenantValidation::existsForTenant($tenantId, 'patrol_sessions')],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $session = PatrolSession::findOrFail($data['patrol_session_id']);
        $this->authorize('view', $session);

        $point = PatrolPlaybackPoint::create($data + [
            'tenant_id' => $request->user()->tenant_id,
        ]);

        return response()->json($point, 201);
    }
}
