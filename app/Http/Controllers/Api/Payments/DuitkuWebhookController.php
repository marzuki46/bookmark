<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use App\Services\DuitkuService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class DuitkuWebhookController extends Controller
{
    public function __construct(
        private readonly DuitkuService $duitku,
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function callback(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (! $this->duitku->verifyCallback($payload)) {
            Log::warning('Duitku callback with bad signature', [
                'order_id' => $payload['merchantOrderId'] ?? null,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Signature tidak valid.'], 403);
        }

        $payment = SubscriptionPayment::query()
            ->where('order_id', (string) ($payload['merchantOrderId'] ?? ''))
            ->first();

        if (! $payment) {
            return response()->json(['message' => 'Order tidak dikenal.'], 404);
        }

        if ($payment->status === 'paid') {
            return response()->json(['message' => 'ok']);
        }

        $outcome = $this->duitku->paymentStatus($payload);
        $payment->forceFill([
            'status' => $outcome,
            'transaction_id' => $payload['reference'] ?? null,
            'payment_type' => $payload['paymentCode'] ?? null,
            'raw' => $payload,
        ]);

        if ($outcome === 'paid') {
            $payment->save();
            $this->subscriptions->activate(
                user: $payment->user,
                plan: $payment->plan,
                orderId: $payment->order_id,
                provider: 'duitku',
            );
            $payment->refresh()->markPaid();
        } else {
            $payment->save();
        }

        return response()->json(['message' => 'ok']);
    }
}
