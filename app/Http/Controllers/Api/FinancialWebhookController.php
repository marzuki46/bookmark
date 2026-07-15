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

final class FinancialWebhookController extends Controller
{
    /**
     * Handle incoming WhatsApp message from webhook (Baileys or others).
     *
     * Expected: { "sender": "08123456789", "message": "makan 30000", "id": "...", "source": "baileys" }
     */
    public function handleIncoming(Request $request): JsonResponse
    {
        $source = $request->input('source', 'baileys');

        // Log EVERY incoming request to database for debugging
        WebhookLog::create([
            'source' => $source,
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'raw_input' => json_encode($request->all()),
            'sender' => $request->input('sender') ?? $request->input('data.sender'),
            'message' => $request->input('message') ?? $request->input('data.message'),
            'message_id' => $request->input('id') ?? $request->input('data.id'),
        ]);

        // Log EVERYTHING for debugging - method, headers, content type, and data
        logger()->info('WA Webhook received', [
            'source' => $source,
            'method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'headers' => $request->headers->all(),
            'all_input' => $request->all(),
            'json' => $request->json()->all(),
            'server_ip' => $request->server('REMOTE_ADDR'),
        ]);

        // Handle GET requests — some gateways verify the endpoint via GET
        if ($request->isMethod('get')) {
            return response()->json(['status' => true, 'message' => 'Webhook active']);
        }

        $sender = $request->input('sender') ?? $request->input('data.sender') ?? $request->json('sender');
        $message = $request->input('message') ?? $request->input('data.message') ?? $request->json('message');
        $messageId = $request->input('id') ?? $request->input('data.id') ?? $request->json('id');

        if (! $sender || ! $message) {
            logger()->warning('WA Webhook: missing sender or message', $request->all());
            return response()->json(['status' => false, 'error' => 'Missing sender or message'], 200);
        }

        // Find which user this sender belongs to
        $userId = $this->resolveUserIdFromSender($sender);
        if (! $userId) {
            logger()->info('WA Webhook: unknown sender - number not registered in any account', [
                'sender' => $sender,
                'hint' => 'User must register this number in Financial Report → WA Gateway settings first',
            ]);
            return response()->json(['status' => true, 'message' => 'Unknown sender']);
        }

        // Process the message
        $message = trim($message);
        $lower = strtolower($message);

        $isBaileys = $source === 'baileys';

        // Initialize services with the resolved userId
        $aiService = new AIService($userId);
        $waService = new WhatsAppService($userId);
        $financialAi = new FinancialAIService($aiService);

        // Handle special commands
        if (in_array($lower, ['laporan', 'report', 'summary', 'ringkasan'])) {
            return $this->handleReportCommand($userId, $sender, $waService, $isBaileys);
        }

        if (str_starts_with($lower, 'tanya ') || str_starts_with($lower, '?') || str_starts_with($lower, 'query ')) {
            $question = preg_replace('/^(tanya |\?|query )/i', '', $message);
            return $this->handleQueryCommand($userId, $sender, $question, $waService, $financialAi, $isBaileys);
        }

        // Default: parse as transaction
        return $this->handleTransactionMessage($userId, $sender, $message, $waService, $financialAi, $isBaileys);
    }

    /**
     * Handle a transaction message (e.g., "makan 30000").
     */
    private function handleTransactionMessage(
        int $userId,
        string $sender,
        string $message,
        WhatsAppService $waService,
        FinancialAIService $aiService,
        bool $isBaileys = false
    ): JsonResponse {
        // Parse the message with AI
        $parsed = $aiService->parseTransaction($message);

        if (! $parsed || $parsed['amount'] <= 0) {
            $errorMsg =
                "❌ Maaf, saya tidak bisa memahami transaksi ini.\n\n" .
                "Contoh format:\n" .
                "• `makan 30000`\n" .
                "• `gaji 5jt`\n" .
                "• `beli domain 200rb`\n" .
                "• `tanya total pengeluaran bulan ini`";

            if ($isBaileys) {
                return response()->json(['status' => true, 'reply' => $errorMsg, 'error' => 'Could not parse']);
            }

            $waService->sendMessage($sender, $errorMsg);
            return response()->json(['status' => true, 'error' => 'Could not parse']);
        }

        // Find or create category
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

        // Save transaction
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

        // Also compute today's total
        $todayTotal = FinancialTransaction::where('user_id', $userId)
            ->whereDate('date', now()->format('Y-m-d'))
            ->where('type', $parsed['type'])
            ->sum('amount');

        $todaySummary = "📊 *Ringkasan {$typeLabel} Hari Ini*\n\n" .
            "💰 Total {$typeLabel}: Rp " . number_format((float) $todayTotal, 0, ',', '.') . "\n" .
            "📅 Tanggal: " . now()->format('d/m/Y');

        if ($isBaileys) {
            return response()->json([
                'status' => true,
                'reply' => $confirmation . "\n\n" . $todaySummary,
                'transaction' => [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'description' => $transaction->description,
                    'category' => $category->name,
                ],
            ]);
        }

        // Send via gateway (backward compatibility)
        $waService->sendMessage($sender, $confirmation);
        $waService->sendMessage($sender, $todaySummary);

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

    /**
     * Handle a report command ("laporan", "report", etc.).
     */
    private function handleReportCommand(int $userId, string $sender, WhatsAppService $waService, bool $isBaileys = false): JsonResponse
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

        $summaryMsg = "📊 *Laporan Keuangan*\n\n" .
            "📅 Periode: " . now()->format('F Y') . "\n\n" .
            "💵 *Pemasukan:* Rp " . number_format($totalIncome, 0, ',', '.') . "\n" .
            "💸 *Pengeluaran:* Rp " . number_format($totalExpense, 0, ',', '.') . "\n" .
            "💰 *Saldo:* Rp " . number_format($totalIncome - $totalExpense, 0, ',', '.') . "\n\n" .
            "📌 *Total Transaksi:* {$transactions->count()}\n" .
            "🏷 *Top Kategori:* {$topCategory?->category?->name ?? '-'}";

        $summary = [
            'period' => now()->format('F Y'),
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
            'count' => $transactions->count(),
            'top_category' => $topCategory?->category?->name ?? '-',
        ];

        if ($isBaileys) {
            return response()->json(['status' => true, 'reply' => $summaryMsg, 'summary' => $summary]);
        }

        $waService->sendReport($sender, $summary);
        return response()->json(['status' => true, 'summary' => $summary]);
    }

    /**
     * Handle a query command ("tanya ...", "? ...").
     */
    private function handleQueryCommand(
        int $userId,
        string $sender,
        string $question,
        WhatsAppService $waService,
        FinancialAIService $aiService,
        bool $isBaileys = false
    ): JsonResponse {
        $transactions = FinancialTransaction::where('user_id', $userId)
            ->with('category')
            ->latest()
            ->take(100)
            ->get()
            ->toArray();

        $answer = $aiService->answerQuery($question, $transactions);

        if ($isBaileys) {
            return response()->json(['status' => true, 'reply' => $answer]);
        }

        $waService->sendMessage($sender, $answer);
        return response()->json(['status' => true, 'answer' => $answer]);
    }

    /**
     * Normalize a phone number to international format (62xxx) for consistent matching.
     * Handles: 08123456789, +628123456789, 628123456789, 0812-3456-789, etc.
     */
    private function normalizePhoneNumber(string $number): string
    {
        // Strip all non-digit characters (handles +, -, spaces, etc.)
        $number = preg_replace('/[^0-9]/', '', $number);

        // Convert local prefix (0xxx) to international (62xxx)
        if (strlen($number) > 0 && $number[0] === '0') {
            $number = '62' . substr($number, 1);
        }

        return $number;
    }

    /**
     * Resolve user_id from WhatsApp sender number.
     * Check settings for mapped phone numbers.
     *
     * Users MUST register their WhatsApp number in the system settings
     * (Financial Report → WA Gateway) before the system can accept
     * their incoming messages.
     */
    private function resolveUserIdFromSender(string $sender): ?int
    {
        // Normalize sender number to international format (62xxx)
        $sender = $this->normalizePhoneNumber($sender);

        if (empty($sender)) {
            return null;
        }

        // Check all user settings files for WA gateway config
        $storagePath = storage_path('app');
        $files = glob($storagePath . '/user-settings-*.json');

        foreach ($files as $file) {
            $settings = json_decode(file_get_contents($file), true) ?? [];
            $waConfig = $settings['wa_gateway'] ?? [];

            // Check primary phone number
            $waPhone = $this->normalizePhoneNumber($waConfig['phone_number'] ?? '');
            if ($waPhone !== '' && $this->phoneNumbersMatch($sender, $waPhone)) {
                preg_match('/user-settings-(\d+)\.json/', $file, $matches);
                return (int) ($matches[1] ?? 0);
            }

            // Also check alternative numbers
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

    /**
     * Check if two phone numbers match, considering:
     * - The sender may include extra prefixes (e.g., "628123456789" vs "628123456789")
     * - We check both directions: does one contain the other?
     * - We also check the last 10+ digits to handle edge cases
     */
    private function phoneNumbersMatch(string $normalizedSender, string $normalizedStored): bool
    {
        // Exact match
        if ($normalizedSender === $normalizedStored) {
            return true;
        }

        // One contains the other (handles cases where extra digits are present)
        if (str_contains($normalizedSender, $normalizedStored)) {
            return true;
        }
        if (str_contains($normalizedStored, $normalizedSender)) {
            return true;
        }

        // Compare last 10 digits (handles edge cases with different prefixes)
        $last10Sender = substr($normalizedSender, -10);
        $last10Stored = substr($normalizedStored, -10);
        if (strlen($last10Sender) >= 10 && $last10Sender === $last10Stored) {
            return true;
        }

        return false;
    }
}
