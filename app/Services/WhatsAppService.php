<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;

final class WhatsAppService
{
    private string $apiUrl;
    private ?int $userId = null;

    public function __construct(?int $userId = null)
    {
        $this->userId = $userId;
        $this->apiUrl = config('services.baileys.url', 'http://localhost:3001');
    }

    public function initializeForUser(int $userId): void
    {
        $this->userId = $userId;
    }

    public function isConfigured(): bool
    {
        try {
            $resp = Http::timeout(3)->get($this->apiUrl . '/health');
            return $resp->successful() && ($resp->json('status') ?? '') === 'connected';
        } catch (\Exception) {
            return false;
        }
    }

    public function sendMessage(string $target, string $message, ?string $schedule = null): array
    {
        if ($schedule) {
            logger()->warning('Baileys: schedule not supported, sending immediately');
        }

        try {
            $response = Http::timeout(10)->post($this->apiUrl . '/send', [
                'to' => $target,
                'message' => $message,
            ]);

            if (! $response->successful()) {
                logger()->warning('Baileys API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['status' => false, 'error' => 'HTTP ' . $response->status()];
            }

            return ['status' => true, 'data' => $response->json()];
        } catch (\Exception $e) {
            logger()->error('Baileys send failed', ['error' => $e->getMessage()]);
            return ['status' => false, 'error' => $e->getMessage()];
        }
    }

    public function getDeviceStatus(): array
    {
        try {
            $response = Http::timeout(5)->get($this->apiUrl . '/health');
            if (! $response->successful()) {
                return ['status' => false, 'error' => 'HTTP ' . $response->status()];
            }
            return $response->json();
        } catch (\Exception $e) {
            return ['status' => false, 'error' => $e->getMessage()];
        }
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
