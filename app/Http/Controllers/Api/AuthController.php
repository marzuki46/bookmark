<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\FamilyCategory;
use App\Models\FamilyMember;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => trim($data['name']),
                'email' => strtolower(trim($data['email'])),
                'password' => Hash::make($data['password']),
            ]);

            $family = Family::query()->create([
                'name' => 'Keluarga '.trim($data['name']),
                'owner_user_id' => $user->id,
                'invite_code' => Family::generateInviteCode(),
            ]);

            FamilyMember::query()->create([
                'family_id' => $family->id,
                'user_id' => $user->id,
                'role' => 'owner',
                'is_family_only' => false,
            ]);

            foreach (self::defaultCategories() as $category) {
                FamilyCategory::query()->create($category + [
                    'family_id' => $family->id,
                    'is_system' => true,
                ]);
            }

            $trial = SubscriptionPlan::query()
                ->where('slug', 'free-trial')
                ->where('is_active', true)
                ->first();
            if ($trial) {
                app(SubscriptionService::class)->activate($user, $trial, provider: 'trial');
            }

            return $user;
        });

        $token = $user->createToken($data['device_name'] ?? 'android')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    private static function defaultCategories(): array
    {
        return [
            ['name' => 'Makanan', 'type' => 'expense', 'icon' => '🍜', 'color' => '#ef4444'],
            ['name' => 'Transportasi', 'type' => 'expense', 'icon' => '🚗', 'color' => '#f97316'],
            ['name' => 'Belanja', 'type' => 'expense', 'icon' => '🛒', 'color' => '#eab308'],
            ['name' => 'Tagihan', 'type' => 'expense', 'icon' => '📄', 'color' => '#06b6d4'],
            ['name' => 'Kesehatan', 'type' => 'expense', 'icon' => '🏥', 'color' => '#ec4899'],
            ['name' => 'Pendidikan', 'type' => 'expense', 'icon' => '📚', 'color' => '#14b8a6'],
            ['name' => 'Hiburan', 'type' => 'expense', 'icon' => '🎮', 'color' => '#a855f7'],
            ['name' => 'Gaji', 'type' => 'income', 'icon' => '💰', 'color' => '#10b981'],
            ['name' => 'Freelance', 'type' => 'income', 'icon' => '💼', 'color' => '#3b82f6'],
            ['name' => 'Bisnis', 'type' => 'income', 'icon' => '🏪', 'color' => '#8b5cf6'],
            ['name' => 'Lainnya', 'type' => 'income', 'icon' => '✨', 'color' => '#0ea5e9'],
        ];
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $deviceName = $request->device_name ?? 'browser';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
