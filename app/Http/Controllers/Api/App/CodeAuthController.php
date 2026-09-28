<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Services\LoginCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CodeAuthController extends Controller
{
    public function __construct(private readonly LoginCodeService $codes) {}

    /**
     * Exchanges a permanent login code for a Sanctum token.
     *
     * Registered under throttle:5,1 so codes cannot be brute-forced even
     * though they are already high-entropy.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'min:8', 'max:64'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $this->codes->findUserByCode($data['code']);

        if (! $user) {
            // One message for every failure mode, so the endpoint cannot be
            // used to discover whether a given code was ever issued.
            return response()->json([
                'message' => 'Kode login tidak valid.',
            ], 401);
        }

        $token = $user->createToken($data['device_name'] ?? 'android')->plainTextToken;

        activity('login')->causedBy($user)->log('App login sukses via kode');

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Rotates the caller's login code.
     *
     * Every existing token is revoked in the same breath — a rotation is what
     * you do when a code leaked, so old sessions must die too. The caller
     * receives a fresh token so they are not left logged out.
     */
    public function rotate(Request $request): JsonResponse
    {
        $user = $request->user();
        $plain = $this->codes->issueFor($user);

        $user->tokens()->delete();
        $token = $user->createToken('android')->plainTextToken;

        return response()->json([
            'code' => $plain,
            'token' => $token,
            'rotated_at' => $user->app_login_code_rotated_at?->toIso8601String(),
            'message' => 'Simpan kode ini sekarang. Kode lama sudah tidak berlaku.',
        ]);
    }
}
