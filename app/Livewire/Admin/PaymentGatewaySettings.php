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

    public string $manualBankName = '';
    public string $manualBankAccount = '';
    public string $manualAccountHolder = '';
    public string $manualQrisImageUrl = '';
    public string $manualWhatsapp = '';
    public string $manualInstructions = '';

    public string $statusMessage = '';

    public string $statusType = 'success';

    public function mount(GatewaySettings $settings): void
    {
        $this->provider = $settings->provider();
        $this->duitkuMerchantCode = $settings->duitkuMerchantCode();
        $this->duitkuProduction = $settings->duitkuProduction();
        $this->duitkuPaymentMethod = $settings->duitkuPaymentMethod();
        $this->duitkuApiKeyConfigured = $settings->duitkuApiKey() !== '';
        $manual = $settings->manualPayment();
        $this->manualBankName = $manual['bank_name'];
        $this->manualBankAccount = $manual['bank_account'];
        $this->manualAccountHolder = $manual['account_holder'];
        $this->manualQrisImageUrl = $manual['qris_image_url'];
        $this->manualWhatsapp = $manual['whatsapp'];
        $this->manualInstructions = $manual['instructions'];
    }

    public function save(GatewaySettings $settings): void
    {
        $data = $this->validate([
            'provider' => ['required', 'in:manual,midtrans,duitku'],
            'duitkuMerchantCode' => ['nullable', 'string', 'max:50'],
            'duitkuApiKey' => ['nullable', 'string', 'max:255'],
            'duitkuProduction' => ['boolean'],
            'duitkuPaymentMethod' => ['required', 'string', 'size:2'],
            'manualBankName' => ['nullable', 'string', 'max:80'],
            'manualBankAccount' => ['nullable', 'string', 'max:80'],
            'manualAccountHolder' => ['nullable', 'string', 'max:120'],
            'manualQrisImageUrl' => ['nullable', 'url', 'max:500'],
            'manualWhatsapp' => ['nullable', 'string', 'max:30'],
            'manualInstructions' => ['nullable', 'string', 'max:1000'],
        ]);

        $settings->put('payment.provider', $data['provider']);
        $settings->put('payment.duitku.merchant_code', trim($data['duitkuMerchantCode']));
        $settings->put('payment.duitku.production', $data['duitkuProduction'] ? '1' : '0');
        $settings->put('payment.duitku.payment_method', strtoupper($data['duitkuPaymentMethod']));
        $settings->put('payment.manual.bank_name', trim($data['manualBankName']));
        $settings->put('payment.manual.bank_account', trim($data['manualBankAccount']));
        $settings->put('payment.manual.account_holder', trim($data['manualAccountHolder']));
        $settings->put('payment.manual.qris_image_url', trim($data['manualQrisImageUrl']));
        $settings->put('payment.manual.whatsapp', trim($data['manualWhatsapp']));
        $settings->put('payment.manual.instructions', trim($data['manualInstructions']));

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
