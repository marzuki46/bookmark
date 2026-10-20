<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PromoCode;
use App\Models\PromoRedemption;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PromoCodeService
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    public function redeem(User $user, string $rawCode): PromoRedemption
    {
        $hash = hash('sha256', $this->normalize($rawCode));

        return DB::transaction(function () use ($user, $hash): PromoRedemption {
            $current = $user->activeSubscription();
            if ($current?->expires_at === null && $current?->isUsable()) {
                throw ValidationException::withMessages(['code' => 'Akun ini sudah memiliki paket seumur hidup aktif.']);
            }

            $promo = PromoCode::query()->where('code_hash', $hash)->lockForUpdate()->first();
            if (! $promo || ! $promo->is_active) {
                throw ValidationException::withMessages(['code' => 'Kode promo tidak valid atau sudah dinonaktifkan.']);
            }
            if ($promo->starts_at?->isFuture() || $promo->expires_at?->isPast()) {
                throw ValidationException::withMessages(['code' => 'Masa berlaku kode promo sudah habis atau belum dimulai.']);
            }
            if ($promo->max_redemptions !== null && $promo->redemptions_count >= $promo->max_redemptions) {
                throw ValidationException::withMessages(['code' => 'Kuota kode promo sudah habis.']);
            }
            if (PromoRedemption::query()->where('promo_code_id', $promo->id)->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages(['code' => 'Kamu sudah pernah memakai kode promo ini.']);
            }

            $plan = SubscriptionPlan::query()->where('is_active', true)->orderBy('price')->first();
            if (! $plan) {
                throw ValidationException::withMessages(['code' => 'Belum ada paket aktif untuk menerima promo.']);
            }

            $subscription = $this->subscriptions->activateForDays($user, $plan, $promo->duration_days, 'promo');
            $redemption = PromoRedemption::query()->create([
                'promo_code_id' => $promo->id,
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'redeemed_at' => now(),
            ]);
            $promo->increment('redemptions_count');

            return $redemption;
        });
    }

    public function normalize(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($code))) ?? '');
    }
}
