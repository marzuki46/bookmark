<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use App\Services\MidtransService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class MidtransWebhookController extends Controller
{
    public function __construct(
        private readonly MidtransService $midtrans,
        private readonly SubscriptionService $subscriptions,
    ) {}

    /**
     * Midtrans notifies us asynchronously about every transaction state
     * change. The whole handler is idempotent: an already-paid order is
     * returned as 200 without touching anything, so Midtrans retries cannot
     * double-activate a subscription.
     */
    public function notification(Request $request): JsonResponse
    {
        $payload = $request->all();

        if (! $this->midtrans->verifySignature($payload)) {
            Log::warning('Midtrans webhook with bad signature', [
                'order_id' => $payload['order_id'] ?? null,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Signature tidak valid.'], 403);
        }

        $orderId = (string) ($payload['order_id'] ?? '');

        $payment = SubscriptionPayment::query()
            ->where('order_id', $orderId)
            ->first();

        if (! $payment) {
            Log::warning('Midtrans webhook for unknown order', ['order_id' => $orderId]);

            return response()->json(['message' => 'Order tidak dikenal.'], 404);
        }

        $outcome = $this->midtrans->paymentStatus($payload);

        if ($payment->status === 'paid') {
            return response()->json(['message' => 'ok']);
        }

        $payment->forceFill([
            'status' => $outcome,
            'raw' => $payload,
        ]);

        switch ($outcome) {
            case 'paid':
                $payment->forceFill([
                    'transaction_id' => $payload['transaction_id'] ?? null,
                    'payment_type' => $payload['payment_type'] ?? null,
                    'gross_amount' => (int) ($payload['gross_amount'] ?? $payment->gross_amount),
                ])->save();

                $this->subscriptions->activate(
                    user: $payment->user,
                    plan: $payment->plan,
                    orderId: $payment->order_id,
                    provider: 'midtrans',
                );

                $payment->refresh()->markPaid();

                activity('payment')->causedBy($payment->user)->log("Pembayaran {$payment->order_id} lunas ({$payment->payment_type})");
                break;

            default:
                $payment->save();
                break;
        }

        return response()->json(['message' => 'ok']);
    }
}
