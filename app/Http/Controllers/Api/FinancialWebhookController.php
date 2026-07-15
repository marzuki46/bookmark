<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendFinancialAnswer;
use App\Jobs\SendFinancialReport;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Services\AIService;
use App\Services\FinancialAIService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FinancialWebhookController extends Controller
{
    /**
     * Handle incoming WhatsApp message from Fonnte webhook.
     *
     * Fonnte sends: { "sender": "08123456789", "message": "makan 30000", "id": "..." }
     */
    public function handleIncoming(Request $request): JsonResponse
    {
        // Log incoming webhook for debugging
        logger()->info('WA Webhook received', $request->all());

        // Handle GET requests — Fonnte sends GET to verify the endpoint
        if ($request->isMethod('get')) {
            return response()->json(['status' => true, 'message' => 'Webhook active']);
        }

        $sender = $request->input('sender') ?? $request->input('data.sender');
        $message = $request->input('message') ?? $request->input('data.message');
        $messageId = $request->input('id') ?? $request->input('data.id');

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

        // Initialize services with the resolved userId
        $aiService = new AIService($userId);
        $waService = new WhatsAppService($userId);
        $financialAi = new FinancialAIService($aiService);

        // Handle special commands
        if (in_array($lower, ['laporan', 'report', 'summary', 'ringkasan'])) {
            return $this->handleReportCommand($userId, $sender, $waService);
        }

        if (str_starts_with($lower, 'tanya ') || str_starts_with($lower, '?') || str_starts_with($lower, 'query ')) {
            $question = preg_replace('/^(tanya |\?|query )/i', '', $message);
            return $this->handleQueryCommand($userId, $sender, $question, $waService, $financialAi);
        }

        // Default: parse as transaction
        return $this->handleTransactionMessage($userId, $sender, $message, $waService, $financialAi);
    }

    /**
     * Handle a transaction message (e.g., "makan 30000").
     */
    private function handleTransactionMessage(
        int $userId,
        string $sender,
        string $message,
        WhatsAppService $waService,
        FinancialAIService $aiService
    ): JsonResponse {
        // Parse the message with AI
        $parsed = $aiService->parseTransaction($message);

        if (! $parsed || $parsed['amount'] <= 0) {
            $waService->sendMessage($sender,
                "❌ Maaf, saya tidak bisa memahami transaksi ini.\n\n" .
                "Contoh format:\n" .
                "• `makan 30000`\n" .
                "• `gaji 5jt`\n" .
                "• `beli domain 200rb`\n" .
                "• `tanya total pengeluaran bulan ini`"
            );
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

        // Send confirmation via WhatsApp
        $waService->sendTransactionConfirmation($sender, $transaction->toArray(), $category->name);

        // Also send today's total summary
        $todayTotal = FinancialTransaction::where('user_id', $userId)
            ->whereDate('date', now()->format('Y-m-d'))
            ->where('type', $parsed['type'])
            ->sum('amount');

        $typeLabel = $parsed['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran';
        $waService->sendMessage($sender,
            "📊 *Ringkasan {$typeLabel} Hari Ini*\n\n" .
            "💰 Total {$typeLabel}: Rp " . number_format((float) $todayTotal, 0, ',', '.') . "\n" .
            "📅 Tanggal: " . now()->format('d/m/Y')
        );

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
    private function handleReportCommand(int $userId, string $sender, WhatsAppService $waService): JsonResponse
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

        $summary = [
            'period' => now()->format('F Y'),
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
            'count' => $transactions->count(),
            'top_category' => $topCategory?->category?->name ?? '-',
        ];

        // Dispatch job to send report via WhatsApp (async)
        SendFinancialReport::dispatch($userId, $sender, $summary);

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
        FinancialAIService $aiService
    ): JsonResponse {
        // Get recent transactions for context
        $transactions = FinancialTransaction::where('user_id', $userId)
            ->with('category')
            ->latest()
            ->take(100)
            ->get()
            ->toArray();

        $answer = $aiService->answerQuery($question, $transactions);

        // Dispatch job to send answer via WhatsApp (async)
        SendFinancialAnswer::dispatch($userId, $sender, $answer);

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
