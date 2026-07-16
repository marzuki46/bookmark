<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class WhatsAppService
{
    private string $graphUrl;
    private string $phoneNumberId;
    private string $accessToken;

    public function __construct(?int $userId = null)
    {
        $this->graphUrl = config('services.whatsapp_cloud.graph_url', 'https://graph.facebook.com/v21.0');
        $this->phoneNumberId = config('services.whatsapp_cloud.phone_number_id', '');
        $this->accessToken = config('services.whatsapp_cloud.access_token', '');
    }

    public function initializeForUser(int $userId): void
    {
        $settings = json_decode(
            file_get_contents(storage_path("app/user-settings-{$userId}.json")),
            true
        ) ?? [];

        $wa = $settings['wa_gateway'] ?? [];
        $this->phoneNumberId = $wa['phone_number_id'] ?? $this->phoneNumberId;
        $this->accessToken = $wa['access_token'] ?? $this->accessToken;
    }

    public function isConfigured(): bool
    {
        return $this->phoneNumberId !== '' && $this->accessToken !== '';
    }

    public function sendMessage(string $target, string $message, ?string $schedule = null): array
    {
        if ($schedule) {
            logger()->warning('WhatsApp Cloud: schedule not supported, sending immediately');
        }

        $target = $this->normalizePhoneNumber($target);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$this->graphUrl}/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $target,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);

            if (! $response->successful()) {
                logger()->warning('WhatsApp Cloud API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['status' => false, 'error' => 'HTTP ' . $response->status()];
            }

            return ['status' => true, 'data' => $response->json()];
        } catch (\Exception $e) {
            logger()->error('WhatsApp Cloud send failed', ['error' => $e->getMessage()]);
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    public function getDeviceStatus(): array
    {
        if (! $this->isConfigured()) {
            return ['status' => 'not_configured', 'message' => 'Cloud API belum dikonfigurasi'];
        }

        try {
            $response = Http::timeout(5)
                ->withHeaders(['Authorization' => 'Bearer ' . $this->accessToken])
                ->get("{$this->graphUrl}/{$this->phoneNumberId}");

            if (! $response->successful()) {
                return ['status' => 'error', 'error' => 'HTTP ' . $response->status()];
            }

            $data = $response->json();
            return [
                'status' => 'connected',
                'phone_number' => $data['display_phone_number'] ?? '',
                'quality_rating' => $data['quality_rating'] ?? '',
                'platform' => 'WhatsApp Cloud API',
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }

    private function normalizePhoneNumber(string $number): string
    {
        $number = preg_replace('/[^0-9]/', '', $number);

        if (strlen($number) > 0 && $number[0] === '0') {
            $number = '62' . substr($number, 1);
        }

        return $number;
    }

    public function sendTransactionConfirmation(string $target, array $transaction, string $categoryName): void
    {
        $icon = $transaction['type'] === 'income' ? '💵' : '💸';
        $typeLabel = $transaction['type'] === 'income' ? 'PEMASUKAN' : 'PENGELUARAN';

        $message = "✅ *Transaksi Tercatat!*\n\n" .
            "{$icon} *{$typeLabel}*\n" .
            "📝 {$transaction['description']}\n" .
            "🏷 {$categoryName}\n" .
            "💰 Rp " . number_format((float) $transaction['amount'], 0, ',', '.') . "\n" .
            "📅 " . now()->format('d/m/Y') . "\n\n" .
            "Balas dengan:\n" .
            "• `tanya [pertanyaan]`\n" .
            "• `laporan` untuk ringkasan";

        $this->sendMessage($target, $message);
    }

    public function sendReport(string $target, array $summary): void
    {
        $message = "📊 *Laporan Keuangan*\n\n" .
            "📅 Periode: {$summary['period']}\n\n" .
            "💵 *Pemasukan:* Rp " . number_format($summary['total_income'], 0, ',', '.') . "\n" .
            "💸 *Pengeluaran:* Rp " . number_format($summary['total_expense'], 0, ',', '.') . "\n" .
            "💰 *Saldo:* Rp " . number_format($summary['balance'], 0, ',', '.') . "\n\n" .
            "📌 *Total Transaksi:* {$summary['count']}\n" .
            "🏷 *Top Kategori:* {$summary['top_category']}";

        $this->sendMessage($target, $message);
    }
}
