<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\PaymentGatewaySettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SubscriptionPlanController extends Controller
{
    /**
     * Sellable plans, for the Android "Langganan" screen.
     */
    public function index(Request $request, PaymentGatewaySettings $settings): JsonResponse
    {
        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->get()
            ->map(fn (SubscriptionPlan $plan) => [
                'id' => $plan->id,
                'slug' => $plan->slug,
                'name' => $plan->name,
                'description' => $plan->description,
                'duration_type' => $plan->duration_type,
                'duration_label' => $plan->durationLabel(),
                'price' => $plan->price,
                'manual_payment_amount' => $settings->manualAmount($request->user(), $plan),
            ]);

        return response()->json(['data' => $plans]);
    }

    /**
     * Where the caller currently stands, so the app knows what to show.
     */
    public function current(Request $request): JsonResponse
    {
        $subscription = $request->user()->activeSubscription();

        return response()->json(['data' => [
            'has_paid' => (bool) $subscription,
            'active' => $subscription?->isUsable() ?? false,
            'plan_name' => $subscription?->plan?->name,
            'plan_slug' => $subscription?->plan?->slug,
            'starts_at' => $subscription?->starts_at?->toIso8601String(),
            'expires_at' => $subscription?->expires_at?->toIso8601String(),
            'provider' => $subscription?->provider,
        ]]);
    }

    public function manualPayment(Request $request, PaymentGatewaySettings $settings): JsonResponse
    {
        return response()->json(['data' => array_merge($settings->manualPayment(), [
            'payment_mode' => $settings->provider(),
            'payment_id' => $settings->manualPaymentId($request->user()),
        ])]);
    }
}
