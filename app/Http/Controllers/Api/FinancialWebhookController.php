<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\WebhookLog;
use App\Services\AIService;
use App\Services\FinancialAIService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class FinancialWebhookController extends Controller
{
    /**
     * Handle incoming WhatsApp message from webhook.
     * Supports both Cloud API (Meta) and Baileys formats.
     */
    public function handleIncoming(Request $request): JsonResponse|Response
    {
        // ─── Meta Webhook Verification (GET) ───
        if ($request->isMethod('get')) {
            $mode = $request->query('hub.mode');
            $token = $request->query('hub.verify_token');
            $challenge = $request->query('hub.challenge');

            $validTokens = [
                'knowledge-hub-webhook',
                config('services.whatsapp_cloud.verify_token', ''),
            ];

            logger()->info('Webhook GET verification', [
                'mode' => $mode,
                'token' => $token,
                'challenge' => $challenge,
                'valid_tokens' => $validTokens,
            ]);

            if ($mode === 'subscribe' && in_array($token, $validTokens)) {
                logger()->info('Webhook verified by Meta');
                return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
            }

            return response('Forbidden', 403);
        }

        $payload = $request->all();
        $source = 'whatsapp_cloud';

        // ─── Detect source ───
        if (isset($payload['source']) || isset($payload['sender'])) {
            $source = $payload['source'] ?? 'baileys';
        }

        // ─── Log ───
        $parsedMessage = $this->parseWebhookPayload($payload);

        WebhookLog::create([
            'source' => $source,
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'raw_input' => json_encode($payload),
            'sender' => $parsedMessage['sender'] ?? null,
            'message' => $parsedMessage['message'] ?? null,
            'message_id' => $parsedMessage['message_id'] ?? null,
        ]);

        logger()->info('WA Webhook received', [
            'source' => $source,
            'sender' => $parsedMessage['sender'] ?? null,
            'message' => $parsedMessage['message'] ?? null,
        ]);

        $sender = $parsedMessage['sender'] ?? null;
        $message = $parsedMessage['message'] ?? null;

        if (! $sender || ! $message) {
            logger()->warning('WA Webhook: no message content', ['source' => $source]);
            return response()->json(['status' => true, 'message' => 'No message content']);
        }

        // ─── Resolve user ───
        $userId = $this->resolveUserIdFromSender($sender);
        if (! $userId) {
            logger()->info('WA Webhook: unknown sender', ['sender' => $sender]);
            return response()->json(['status' => true, 'message' => 'Unknown sender']);
        }

        $message = trim($message);
        $lower = strtolower($message);

        $isCloud = $source === 'whatsapp_cloud';
        $waService = new WhatsAppService($userId);
        $aiService = new AIService($userId);
        $financialAi = new FinancialAIService($aiService);

        // ─── Commands ───
        if (in_array($lower, ['laporan', 'report', 'summary', 'ringkasan'])) {
            return $this->handleReportCommand($userId, $sender, $waService, $isCloud);
        }

        if (str_starts_with($lower, 'tanya ') || str_starts_with($lower, '?') || str_starts_with($lower, 'query ')) {
            $question = preg_replace('/^(tanya |\?|query )/i', '', $message);
            return $this->handleQueryCommand($userId, $sender, $question, $waService, $financialAi, $isCloud);
        }

        // ─── Default: parse as transaction ───
        return $this->handleTransactionMessage($userId, $sender, $message, $waService, $financialAi, $isCloud);
    }

    /**
     * Parse Cloud API or Baileys webhook payload into sender/message/id.
     */
    private function parseWebhookPayload(array $payload): array
    {
        // Cloud API format
        if (isset($payload['object']) && $payload['object'] === 'whatsapp_business_account') {
            $entry = $payload['entry'][0] ?? [];
            $change = $entry['changes'][0] ?? [];
            $value = $change['value'] ?? [];

            $messages = $value['messages'] ?? [];
            if (empty($messages)) {
                return ['sender' => null, 'message' => null, 'message_id' => null];
            }

            $msg = $messages[0];
            $sender = $msg['from'] ?? null;
            $messageId = $msg['id'] ?? null;

            $text = '';
            if (($msg['type'] ?? '') === 'text') {
                $text = $msg['text']['body'] ?? '';
            } elseif (($msg['type'] ?? '') === 'button') {
                $text = $msg['button']['text'] ?? '';
            }

            return ['sender' => $sender, 'message' => $text, 'message_id' => $messageId];
        }

        // Baileys format (legacy)
        return [
            'sender' => $payload['sender'] ?? null,
            'message' => $payload['message'] ?? null,
            'message_id' => $payload['id'] ?? null,
        ];
    }

    private function handleTransactionMessage(
        int $userId,
        string $sender,
        string $message,
        WhatsAppService $waService,
        FinancialAIService $aiService,
        bool $isCloud = false
    ): JsonResponse {
        $parsed = $aiService->parseTransaction($message);

        if (! $parsed || $parsed['amount'] <= 0) {
            $errorMsg =
                "❌ Maaf, saya tidak bisa memahami transaksi ini.\n\n" .
                "Contoh format:\n" .
                "• `makan 30000`\n" .
                "• `gaji 5jt`\n" .
                "• `beli domain 200rb`\n" .
                "• `tanya total pengeluaran bulan ini`";

            $waService->sendMessage($sender, $errorMsg);
            return response()->json(['status' => true, 'error' => 'Could not parse']);
        }

        $category = FinancialCategory::firstOrCreate(
            ['user_id' => $userId, 'name' => $parsed['category'], 'type' => $parsed['type']],
            [
                'user_id' => $userId,
                'name' => $parsed['category'],
                'type' => $parsed['type'],
                'icon' => $parsed['type'] === 'income' ? '💵' : '💸',
                'color' => $parsed['type'] === 'income' ? '#10b981' : '#ef4444',
                'is_system' => false,
            ]
        );

        $transaction = FinancialTransaction::create([
            'user_id' => $userId,
            'category_id' => $category->id,
            'type' => $parsed['type'],
            'amount' => $parsed['amount'],
            'description' => $parsed['description'],
            'date' => now()->format('Y-m-d'),
            'source' => 'wa_auto',
            'wa_sender' => $sender,
        ]);

        $icon = $parsed['type'] === 'income' ? '💵' : '💸';
        $typeLabel = $parsed['type'] === 'income' ? 'PEMASUKAN' : 'PENGELUARAN';

        $confirmation = "✅ *Transaksi Tercatat!*\n\n" .
            "{$icon} *{$typeLabel}*\n" .
            "📝 {$parsed['description']}\n" .
            "🏷 {$category->name}\n" .
            "💰 Rp " . number_format((float) $parsed['amount'], 0, ',', '.') . "\n" .
            "📅 " . now()->format('d/m/Y') . "\n\n" .
            "Balas dengan:\n" .
            "• `tanya [pertanyaan]`\n" .
            "• `laporan` untuk ringkasan";

        $todayTotal = FinancialTransaction::where('user_id', $userId)
            ->whereDate('date', now()->format('Y-m-d'))
            ->where('type', $parsed['type'])
            ->sum('amount');

        $todaySummary = "📊 *Ringkasan {$typeLabel} Hari Ini*\n\n" .
            "💰 Total {$typeLabel}: Rp " . number_format((float) $todayTotal, 0, ',', '.') . "\n" .
            "📅 Tanggal: " . now()->format('d/m/Y');

        $waService->sendMessage($sender, $confirmation . "\n\n" . $todaySummary);

        return response()->json([
            'status' => true,
            'transaction' => [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => $transaction->amount,
                'description' => $transaction->description,
                'category' => $category->name,
            ],
        ]);
    }

    private function handleReportCommand(int $userId, string $sender, WhatsAppService $waService, bool $isCloud = false): JsonResponse
    {
        $transactions = FinancialTransaction::where('user_id', $userId)
            ->thisMonth()
            ->get();

        $totalIncome = $transactions->where('type', 'income')->sum('amount');
        $totalExpense = $transactions->where('type', 'expense')->sum('amount');

        $topCategory = FinancialTransaction::where('user_id', $userId)
            ->thisMonth()
            ->where('type', 'expense')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->with('category')
            ->first();

        $topCategoryName = $topCategory->category->name ?? '-';

        $summaryMsg = "📊 *Laporan Keuangan*\n\n" .
            "📅 Periode: " . now()->format('F Y') . "\n\n" .
            "💵 *Pemasukan:* Rp " . number_format($totalIncome, 0, ',', '.') . "\n" .
            "💸 *Pengeluaran:* Rp " . number_format($totalExpense, 0, ',', '.') . "\n" .
            "💰 *Saldo:* Rp " . number_format($totalIncome - $totalExpense, 0, ',', '.') . "\n\n" .
            "📌 *Total Transaksi:* {$transactions->count()}\n" .
            "🏷 *Top Kategori:* {$topCategoryName}";

        $waService->sendMessage($sender, $summaryMsg);

        return response()->json(['status' => true]);
    }

    private function handleQueryCommand(
        int $userId,
        string $sender,
        string $question,
        WhatsAppService $waService,
        FinancialAIService $aiService,
        bool $isCloud = false
    ): JsonResponse {
        $transactions = FinancialTransaction::where('user_id', $userId)
            ->with('category')
            ->latest()
            ->take(100)
            ->get()
            ->toArray();

        $answer = $aiService->answerQuery($question, $transactions);

        $waService->sendMessage($sender, $answer);

        return response()->json(['status' => true]);
    }

    private function normalizePhoneNumber(string $number): string
    {
        $number = preg_replace('/[^0-9]/', '', $number);

        if (strlen($number) > 0 && $number[0] === '0') {
            $number = '62' . substr($number, 1);
        }

        return $number;
    }

    private function resolveUserIdFromSender(string $sender): ?int
    {
        $sender = $this->normalizePhoneNumber($sender);

        if (empty($sender)) {
            return null;
        }

        $storagePath = storage_path('app');
        $files = glob($storagePath . '/user-settings-*.json');

        foreach ($files as $file) {
            $settings = json_decode(file_get_contents($file), true) ?? [];
            $waConfig = $settings['wa_gateway'] ?? [];

            $waPhone = $this->normalizePhoneNumber($waConfig['phone_number'] ?? '');
            if ($waPhone !== '' && $this->phoneNumbersMatch($sender, $waPhone)) {
                preg_match('/user-settings-(\d+)\.json/', $file, $matches);
                return (int) ($matches[1] ?? 0);
            }

            $altNumbers = $waConfig['alt_numbers'] ?? [];
            foreach ($altNumbers as $alt) {
                $altNormalized = $this->normalizePhoneNumber($alt);
                if ($altNormalized !== '' && $this->phoneNumbersMatch($sender, $altNormalized)) {
                    preg_match('/user-settings-(\d+)\.json/', $file, $matches);
                    return (int) ($matches[1] ?? 0);
                }
            }
        }

        return null;
    }

    private function phoneNumbersMatch(string $normalizedSender, string $normalizedStored): bool
    {
        if ($normalizedSender === $normalizedStored) {
            return true;
        }

        if (str_contains($normalizedSender, $normalizedStored)) {
            return true;
        }
        if (str_contains($normalizedStored, $normalizedSender)) {
            return true;
        }

        $last10Sender = substr($normalizedSender, -10);
        $last10Stored = substr($normalizedStored, -10);
        if (strlen($last10Sender) >= 10 && $last10Sender === $last10Stored) {
            return true;
        }

        return false;
    }
}
