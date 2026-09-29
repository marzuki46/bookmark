<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\PaymentGatewaySettings as GatewaySettings;
use Livewire\Component;

final class PaymentGatewaySettings extends Component
{
    public string $provider = 'midtrans';

    public string $duitkuMerchantCode = '';

    public string $duitkuApiKey = '';

    public bool $duitkuProduction = false;

    public string $duitkuPaymentMethod = 'VC';

    public bool $duitkuApiKeyConfigured = false;

    public string $statusMessage = '';

    public string $statusType = 'success';

    public function mount(GatewaySettings $settings): void
    {
        $this->provider = $settings->provider();
        $this->duitkuMerchantCode = $settings->duitkuMerchantCode();
        $this->duitkuProduction = $settings->duitkuProduction();
        $this->duitkuPaymentMethod = $settings->duitkuPaymentMethod();
        $this->duitkuApiKeyConfigured = $settings->duitkuApiKey() !== '';
    }

    public function save(GatewaySettings $settings): void
    {
        $data = $this->validate([
            'provider' => ['required', 'in:midtrans,duitku'],
            'duitkuMerchantCode' => ['nullable', 'string', 'max:50'],
            'duitkuApiKey' => ['nullable', 'string', 'max:255'],
            'duitkuProduction' => ['boolean'],
            'duitkuPaymentMethod' => ['required', 'string', 'size:2'],
        ]);

        $settings->put('payment.provider', $data['provider']);
        $settings->put('payment.duitku.merchant_code', trim($data['duitkuMerchantCode']));
        $settings->put('payment.duitku.production', $data['duitkuProduction'] ? '1' : '0');
        $settings->put('payment.duitku.payment_method', strtoupper($data['duitkuPaymentMethod']));

        if (trim($data['duitkuApiKey']) !== '') {
            $settings->put('payment.duitku.api_key', trim($data['duitkuApiKey']));
            $this->duitkuApiKeyConfigured = true;
            $this->duitkuApiKey = '';
        }

        $this->statusMessage = 'Pengaturan pembayaran disimpan.';
        $this->statusType = 'success';
    }

    public function render()
    {
        return view('livewire.admin.payment-gateway-settings');
    }
}
