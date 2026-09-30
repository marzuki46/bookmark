<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class ProfileController extends Controller
{
    /**
     * The caller's profile plus entitlement summary, so the Android personal
     * menu can render "about" and the current plan in one round trip.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->activeSubscription();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'about' => $user->about,
                'religion' => $user->religion,
                'is_admin' => (bool) $user->is_admin,
                'setup_completed' => (bool) $user->setup_completed,
                'subscription' => $subscription ? [
                    'active' => $subscription->isUsable(),
                    'plan_name' => $subscription->plan?->name,
                    'plan_slug' => $subscription->plan?->slug,
                    'starts_at' => $subscription->starts_at?->toIso8601String(),
                    'expires_at' => $subscription->expires_at?->toIso8601String(),
                    'provider' => $subscription->provider,
                ] : null,
            ],
        ]);
    }

    /**
     * Self-service profile edit: display name and the free-text "about" shown
     * on the personal menu. Other fields stay admin-managed.
     */
    public function update(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $user = $request->user();
        $user->fill([
            'name' => $data['name'] ?? $user->name,
            'about' => array_key_exists('about', $data) ? ($data['about'] ?: null) : $user->about,
            'religion' => array_key_exists('religion', $data) ? ($data['religion'] ?: null) : $user->religion,
        ])->save();

        activity('profile')->causedBy($user)->log('Profil diubah dari aplikasi');

        return response()->json(['message' => 'Profil diperbarui.']);
    }

    /**
     * @return array{name?: string, about?: string|null}
     */
    private function validated(Request $request): array
    {
        if ($request->missing(['name', 'about', 'religion']) && ! $request->json()->has('about') && ! $request->json()->has('religion')) {
            throw ValidationException::withMessages(['name' => 'Minimal isi nama, about, atau agama.']);
        }

        return $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'about' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'religion' => ['sometimes', 'nullable', 'string', 'max:30'],
        ]);
    }
}
