<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class WhatsAppService
{
    private string $apiKey;
    private string $apiUrl;
    private ?int $userId = null;

    public function __construct(?int $userId = null)
    {
        $this->userId = $userId;
        $settings = $this->loadSettings();
        $this->apiKey = $settings['wa_api_key'] ?? '';
        $this->apiUrl = 'https://api.fonnte.com';
    }

    /**
     * Reinitialize with a specific user context (for webhook usage).
     */
    public function initializeForUser(int $userId): void
    {
        $this->userId = $userId;
        $settings = $this->loadSettings();
        $this->apiKey = $settings['wa_api_key'] ?? '';
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Send a message via Fonnte WhatsApp Gateway.
     */
    public function sendMessage(string $target, string $message, ?string $schedule = null): array
    {
        if (! $this->isConfigured()) {
            return ['status' => false, 'error' => 'WA Gateway not configured'];
        }

        try {
            $payload = [
                'target' => $target,
                'message' => $message,
                'countryCode' => '62',
            ];

            if ($schedule) {
                $payload['schedule'] = $schedule;
            }

            $response = Http::timeout(15)
                ->withToken($this->apiKey)
                ->asMultipart()
                ->post($this->apiUrl . '/send', $payload);

            if (! $response->successful()) {
                logger()->warning('Fonnte API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['status' => false, 'error' => 'HTTP ' . $response->status()];
            }

            $result = $response->json();
            return [
                'status' => ($result['status'] ?? false) === true,
                'id' => $result['id'] ?? null,
                'data' => $result,
            ];
        } catch (\Exception $e) {
            logger()->error('Fonnte send failed', ['error' => $e->getMessage()]);
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get device status from Fonnte.
     */
    public function getDeviceStatus(): array
    {
        if (! $this->isConfigured()) {
            return ['status' => false, 'error' => 'Not configured'];
        }

        try {
            $response = Http::timeout(10)
                ->withToken($this->apiKey)
                ->get($this->apiUrl . '/device');

            if (! $response->successful()) {
                return ['status' => false, 'error' => 'HTTP ' . $response->status()];
            }

            return $response->json();
        } catch (\Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send a structured financial notification.
     */
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
            "Balas dengan format:\n" .
            "• `tanya [pertanyaan]` untuk bertanya\n" .
            "• `batal [id]` untuk menghapus transaksi terakhir\n" .
            "• `laporan` untuk ringkasan";

        $this->sendMessage($target, $message);
    }

    /**
     * Send a financial report summary via WhatsApp.
     */
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

    private function loadSettings(): array
    {
        $userId = $this->userId ?? auth()->id();
        if (! $userId) {
            return [];
        }

        $path = storage_path('app/user-settings-'.$userId.'.json');
        if (file_exists($path)) {
            $all = json_decode(file_get_contents($path), true) ?? [];
            return $all['wa_gateway'] ?? [];
        }

        return [];
    }
}
