<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\AdminApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

abstract class AdminController extends Controller
{
    use AdminApiResponse, AuthorizesRequests;

    protected function perPage(Request $request): int
    {
        return min(max((int) $request->input('per_page', 15), 1), 100);
    }

    protected function serviceError(RuntimeException $exception): JsonResponse
    {
        return response()->json(['message' => $exception->getMessage()], 422);
    }
}
