<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CheckoutController extends Controller
{
    public function __construct(private readonly MidtransService $midtrans) {}

    /**
     * Starts a purchase: create the pending payment row first, then ask Snap
     * for a token. The order exists in our DB even if the app never opens the
     * payment page, so the webhook always has something to reconcile.
     */
    public function charge(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
        ]);

        $plan = SubscriptionPlan::query()->whereKey($data['plan_id'])->where('is_active', true)->first();

        if (! $plan) {
            return response()->json(['message' => 'Paket tidak aktif lagi. Pilih paket lain.'], 422);
        }

        $user = $request->user();
        $orderId = MidtransService::newOrderId($user);

        SubscriptionPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'order_id' => $orderId,
            'gross_amount' => $plan->price,
            'status' => 'pending',
        ]);

        try {
            $snap = $this->midtrans->charge($user, $plan, $orderId);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'order_id' => $orderId,
                'token' => $snap['token'],
                'redirect_url' => $snap['redirect_url'],
                'price' => $plan->price,
            ],
        ]);
    }
}
