<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class DuitkuService
{
    public function __construct(private readonly PaymentGatewaySettings $settings) {}

    public function isConfigured(): bool
    {
        return $this->settings->duitkuMerchantCode() !== '' && $this->settings->duitkuApiKey() !== '';
    }

    public function charge(User $user, SubscriptionPlan $plan, string $orderId): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Duitku belum dikonfigurasi. Isi merchant code dan API key terlebih dahulu.');
        }

        $amount = (int) $plan->price;
        $merchantCode = $this->settings->duitkuMerchantCode();
        $apiKey = $this->settings->duitkuApiKey();
        $payload = [
            'merchantCode' => $merchantCode,
            'paymentAmount' => $amount,
            'paymentMethod' => $this->settings->duitkuPaymentMethod(),
            'merchantOrderId' => $orderId,
            'productDetails' => Str::limit($plan->name, 255),
            'customerVaName' => Str::limit($user->name, 20),
            'email' => $user->email,
            'itemDetails' => [[
                'name' => Str::limit($plan->name, 255),
                'price' => $amount,
                'quantity' => 1,
            ]],
            'callbackUrl' => route('payments.duitku.callback'),
            'returnUrl' => route('payments.duitku.return'),
            'signature' => hash_hmac('sha256', $merchantCode.$orderId.$amount, $apiKey),
            'expiryPeriod' => 1440,
        ];

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(20)
                ->connectTimeout(5)
                ->post($this->baseUrl().'/webapi/api/merchant/v2/inquiry', $payload);
        } catch (ConnectionException $e) {
            Log::error('Duitku unreachable', ['error' => $e->getMessage()]);
            throw new \RuntimeException('Gateway pembayaran sedang tidak tersedia.');
        }

        if ($response->failed() || $response->json('statusCode') !== '00' || ! $response->json('paymentUrl')) {
            Log::warning('Duitku charge failed', ['status' => $response->status()]);
            throw new \RuntimeException('Gagal membuat transaksi pembayaran Duitku.');
        }

        return [
            'payment_url' => (string) $response->json('paymentUrl'),
            'reference' => (string) $response->json('reference'),
            'raw' => $response->json(),
        ];
    }

    public function verifyCallback(array $payload): bool
    {
        $merchantCode = (string) ($payload['merchantCode'] ?? '');
        $amount = (string) ($payload['amount'] ?? '');
        $orderId = (string) ($payload['merchantOrderId'] ?? '');
        $signature = (string) ($payload['signature'] ?? '');

        if ($merchantCode === '' || $amount === '' || $orderId === '' || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $merchantCode.$amount.$orderId, $this->settings->duitkuApiKey());

        return hash_equals($expected, $signature)
            && hash_equals($this->settings->duitkuMerchantCode(), $merchantCode);
    }

    public function paymentStatus(array $payload): string
    {
        return (string) ($payload['resultCode'] ?? '') === '00' ? 'paid' : 'failed';
    }

    private function baseUrl(): string
    {
        return $this->settings->duitkuProduction()
            ? 'https://passport.duitku.com'
            : 'https://sandbox.duitku.com';
    }
}
