<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Thin client for the Midtrans Snap payment gateway.
 *
 * Charge() asks Snap for a one-time token the mobile app opens in a web view;
 * verifyNotification() + paymentStatus() reconcile the async webhook. Without
 * a server key every call degrades to a clear exception so nothing silently
 * looks "paid".
 */
final class MidtransService
{
    private string $serverKey;

    private string $baseUrl;

    public function __construct()
    {
        $this->serverKey = (string) (config('services.midtrans.server_key') ?: '');

        $this->baseUrl = config('services.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/v1'
            : 'https://app.sandbox.midtrans.com/snap/v1';
    }

    public function isConfigured(): bool
    {
        return $this->serverKey !== '';
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Generate a globally-unique, human-legible order id.
     */
    public static function newOrderId(User $user): string
    {
        return sprintf('KUA-%s-%s-%s', now()->format('ymdHi'), $user->id, Str::upper(Str::random(4)));
    }

    /**
     * Ask Snap to mint a payment token. Returns ['token', 'redirect_url'].
     *
     * @throws \RuntimeException when the gateway is not configured or unreachable.
     */
    public function charge(User $user, SubscriptionPlan $plan, string $orderId): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Midtrans belum dikonfigurasi (MIDTRANS_SERVER_KEY kosong).');
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic '.base64_encode($this->serverKey.':'),
            ])->post($this->baseUrl.'/transactions', [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $plan->price,
                ],
                'item_details' => [[
                    'id' => $plan->slug,
                    'price' => $plan->price,
                    'quantity' => 1,
                    'name' => Str::limit($plan->name, 50),
                ]],
                'customer_details' => [
                    'first_name' => Str::limit($user->name, 50),
                    'email' => $user->email,
                ],
            ]);
        } catch (ConnectionException $e) {
            Log::error('Midtrans unreachable', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Gateway pembayaran sedang tidak tersedia.');
        }

        if ($response->failed() || ! $response->json('token')) {
            $status = $response->status();
            Log::warning('Midtrans charge failed', ['status' => $status, 'body' => $response->body()]);
            throw new \RuntimeException('Gagal membuat transaksi pembayaran (status '.$status.').');
        }

        return [
            'token' => (string) $response->json('token'),
            'redirect_url' => (string) $response->json('redirect_url'),
        ];
    }

    /**
     * The webhook must prove its authenticity: server key + order + status +
     * amount, sha512'd on Midtrans' side, echoed back as signature_key.
     */
    public function verifySignature(array $payload): bool
    {
        if ($this->serverKey === '') {
            return false;
        }

        $signature = (string) ($payload['signature_key'] ?? '');
        if ($signature === '') {
            return false;
        }

        $expected = hash(
            'sha512',
            ($payload['order_id'] ?? '').($payload['status_code'] ?? '').($payload['gross_amount'] ?? '').$this->serverKey,
        );

        return hash_equals($expected, $signature);
    }

    /**
     * Map a Midtrans transaction_status onto a deterministic outcome:
     * 'paid', 'pending', or 'failed'.
     */
    public function paymentStatus(array $payload): string
    {
        $status = (string) ($payload['transaction_status'] ?? '');
        $fraud = (string) ($payload['fraud_status'] ?? '');

        if (in_array($status, ['settlement', 'capture'], true)) {
            return ($status === 'capture' && $fraud === 'challenge') ? 'pending' : 'paid';
        }

        if ($status === 'pending') {
            return 'pending';
        }

        return 'failed';
    }
}
