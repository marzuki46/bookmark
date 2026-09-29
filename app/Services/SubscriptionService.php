<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;

/**
 * Owns the "one active subscription per user" invariant: a new grant cancels
 * any current entitlement and starts a fresh row, while renewing before
 * expiry carries the window forward instead of throwing days away.
 */
final class SubscriptionService
{
    public function activate(User $user, SubscriptionPlan $plan, ?string $orderId = null, string $provider = 'manual'): Subscription
    {
        $family = $user->family();
        $current = ($family
            ? Subscription::query()->where('family_id', $family->id)
            : $user->subscriptions())
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();

        $base = $current?->expires_at?->isFuture() ? $current->expires_at : now();

        if ($family) {
            Subscription::query()->where('family_id', $family->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);
        } else {
            $user->subscriptions()->where('status', 'active')->update(['status' => 'cancelled']);
        }

        $expiresAt = match ($plan->duration_type) {
            'lifetime' => null,
            'yearly' => $base->copy()->addYear(),
            'monthly' => $base->copy()->addMonth(),
            default => $base->copy()->addMonth(),
        };

        $subscription = Subscription::query()->create([
            'user_id' => $user->id,
            'family_id' => $family?->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => $expiresAt,
            'provider' => $provider,
            'order_id' => $orderId,
        ]);

        if ($orderId) {
            SubscriptionPayment::query()
                ->where('order_id', $orderId)
                ->update(['subscription_id' => $subscription->id, 'plan_id' => $plan->id]);
        }

        return $subscription;
    }

    public function revoke(User $user): void
    {
        if ($family = $user->family()) {
            Subscription::query()->where('family_id', $family->id)
                ->where('status', 'active')
                ->update(['status' => 'cancelled']);
            return;
        }

        $user->subscriptions()->where('status', 'active')->update(['status' => 'cancelled']);
    }
}
