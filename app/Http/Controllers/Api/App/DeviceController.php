<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeviceController extends Controller
{
    /**
     * Registers (or refreshes) the FCM token for the current device.
     *
     * FCM tokens rotate on reinstall and can be handed to a different app
     * instance, so the row is keyed by token rather than by user: re-posting
     * moves the token to whoever now owns it instead of leaving a stale row
     * that would keep receiving another user's notifications.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'in:android,ios'],
            'app_version' => ['nullable', 'string', 'max:32'],
        ]);

        $user = $request->user();

        $device = UserDevice::updateOrCreate(
            ['fcm_token' => $data['fcm_token']],
            [
                'user_id' => $user->id,
                'platform' => $data['platform'] ?? 'android',
                'app_version' => $data['app_version'] ?? null,
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'data' => [
                'id' => $device->id,
                'platform' => $device->platform,
                'registered_at' => $device->created_at?->toIso8601String(),
            ],
        ], $device->wasRecentlyCreated ? 201 : 200);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()->devices()
                ->orderByDesc('last_seen_at')
                ->get(['id', 'platform', 'app_version', 'last_seen_at']),
        ]);
    }

    public function destroy(Request $request, UserDevice $device): JsonResponse
    {
        $this->authorizeOwnership($request, $device);

        $device->delete();

        return response()->json(status: 204);
    }

    private function authorizeOwnership(Request $request, UserDevice $device): void
    {
        abort_unless($device->user_id === $request->user()->id, 404);
    }
}
