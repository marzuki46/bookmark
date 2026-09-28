<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\AppErrorLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Crash / error reporter for the Android app.
 *
 * Public on purpose: a crash on the login screen must still be captured. The
 * Sanctum guard is consulted opportunistically so we can attribute the report
 * to a user when a token happens to be present, without requiring one. The
 * route throttle keeps this from becoming a spam channel.
 */
final class AppErrorController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'error_class' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'route' => ['nullable', 'string', 'max:255'],
            'stack_trace' => ['nullable', 'string', 'max:20000'],
            'app_version' => ['nullable', 'string', 'max:64'],
            'platform' => ['nullable', 'string', 'max:32'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        $user = Auth::guard('sanctum')->user();

        AppErrorLog::create([
            'user_id' => $user?->id,
            'family_id' => $user?->family()?->id,
            'app_version' => $data['app_version'] ?? null,
            'platform' => $data['platform'] ?? null,
            'error_class' => $data['error_class'] ?? null,
            'message' => $data['message'] ?? null,
            'route' => $data['route'] ?? null,
            'stack_trace' => $data['stack_trace'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? now(),
        ]);

        Log::warning('App error reported', [
            'user_id' => $user?->id,
            'class' => $data['error_class'] ?? null,
            'message' => $data['message'] ?? null,
        ]);

        return response()->json(['ok' => true], 202);
    }
}