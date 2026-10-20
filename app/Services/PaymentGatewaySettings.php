<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;

final class PaymentGatewaySettings
{
    public function get(string $key, ?string $default = null): ?string
    {
        $setting = SystemSetting::query()->where('key', $key)->first();

        return $setting?->value ?? config('services.'.$key, $default);
    }

    public function put(string $key, ?string $value): void
    {
        SystemSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public function provider(): string
    {
        return $this->get('payment.provider', 'midtrans') ?: 'midtrans';
    }

    public function duitkuMerchantCode(): string
    {
        return $this->get('payment.duitku.merchant_code', (string) config('services.duitku.merchant_code', '')) ?: '';
    }

    public function duitkuApiKey(): string
    {
        return $this->get('payment.duitku.api_key', (string) config('services.duitku.api_key', '')) ?: '';
    }

    public function duitkuProduction(): bool
    {
        return filter_var($this->get('payment.duitku.production', (string) config('services.duitku.production', false)), FILTER_VALIDATE_BOOL);
    }

    public function duitkuPaymentMethod(): string
    {
        return $this->get('payment.duitku.payment_method', (string) config('services.duitku.payment_method', 'VC')) ?: 'VC';
    }

    public function manualPayment(): array
    {
        return [
            'bank_name' => $this->get('payment.manual.bank_name', '') ?: '',
            'bank_account' => $this->get('payment.manual.bank_account', '') ?: '',
            'account_holder' => $this->get('payment.manual.account_holder', '') ?: '',
            'qris_image_url' => $this->get('payment.manual.qris_image_url', '') ?: '',
            'whatsapp' => $this->get('payment.manual.whatsapp', '') ?: '',
            'instructions' => $this->get('payment.manual.instructions', '') ?: '',
        ];
    }
}
