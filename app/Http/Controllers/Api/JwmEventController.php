<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Rules\JwmExternalId;
use App\Rules\JwmTimestamp;
use App\Services\JwmDeviceIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class JwmEventController extends Controller
{
    public function store(Request $request, int $device, JwmDeviceIngestionService $service): JsonResponse
    {
        $user = $request->user();
        $token = $user->currentAccessToken();
        abort_unless(
            $token instanceof PersonalAccessToken
            && $token->exists
            && in_array('jwm:ingest', $token->abilities, true)
            && $user->status === 'active'
            && $user->tenant?->status === 'active'
            && $user->can('patrols.manage'),
            403, 'An authorized JWM bridge token is required.'
        );

        $unexpected = array_diff(array_keys($request->all()), [
            'event_id', 'type', 'occurred_at', 'latitude', 'longitude', 'patrol_session_id', 'checkpoint_code',
            'guard_external_id', 'checkpoint_external_id',
        ]);
        if ($unexpected) {
            throw ValidationException::withMessages([reset($unexpected) => 'Unexpected event field.']);
        }
        $data = $request->validate([
            'event_id' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9._:-]*$/D'],
            'type' => ['required', Rule::in(['gps', 'checkpoint'])],
            'occurred_at' => ['required', new JwmTimestamp, 'after_or_equal:1970-01-01T00:00:01Z', 'before_or_equal:now'],
            'latitude' => ['required_if:type,gps', 'required_with:longitude', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['required_if:type,gps', 'required_with:latitude', 'nullable', 'numeric', 'between:-180,180'],
            'patrol_session_id' => ['required_if:type,checkpoint', 'prohibited_if:type,gps', 'integer'],
            'guard_external_id' => ['sometimes', 'required', new JwmExternalId],
            'checkpoint_code' => [
                Rule::requiredIf(fn () => $request->input('type') === 'checkpoint' && ! $request->exists('checkpoint_external_id')),
                'prohibited_if:type,gps', 'prohibits:checkpoint_external_id', 'string', 'max:100',
            ],
            'checkpoint_external_id' => [
                Rule::requiredIf(fn () => $request->input('type') === 'checkpoint' && ! $request->exists('checkpoint_code')),
                'prohibited_if:type,gps', 'prohibits:checkpoint_code', 'string', new JwmExternalId,
            ],
        ]);

        $result = $service->ingest((int) $user->tenant_id, $device, $token->id, $data);

        return response()->json([
            'data' => $result['event'], 'replayed' => $result['replayed'],
        ], $result['replayed'] ? 200 : 201);
    }
}
