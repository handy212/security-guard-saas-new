<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => 'required|url',
            'public_key' => 'nullable|string',
            'auth_token' => 'nullable|string',
            'content_encoding' => 'nullable|string',
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            $data + ['user_id' => $request->user()->id],
        );

        return response()->json(['status' => 'subscribed']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => 'required|url']);
        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint', $data['endpoint'])
            ->delete();

        return response()->json(['status' => 'unsubscribed']);
    }
}
