<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\FinancialTransactionResource;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Services\AIService;
use App\Services\FinanceReportService;
use App\Services\FinancialAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AI endpoints for the Android app.
 *
 * Every route here uses the server-side AI configuration (per-user settings
 * file, falling back to config('services.ai')), so the app never holds an API
 * key and settings are managed in one place.
 */
final class FinanceAiController extends Controller
{
    public function __construct(
        private readonly FinanceReportService $reports,
        private readonly AIService $ai,
    ) {}

    /**
     * Proactive advice for the dashboard card. No question required.
     */
    public function advice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'nullable|string|in:'.implode(',', FinanceReportService::PERIODS),
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d',
        ]);

        $userId = (int) auth()->id();

        $range = $this->reports->resolveRange(
            $validated['period'] ?? null,
            $validated['from'] ?? null,
            $validated['to'] ?? null,
        );

        $stats = $this->reports->stats($userId, $range['from'], $range['to']);
        $insights = $this->reports->insights($userId, $range['from'], $range['to']);

        $service = new FinancialAIService($this->ai);
        $advice = $service->advice(
            array_merge($stats, $insights),
            $range['label'],
        );

        return response()->json([
            'advice' => $advice,
            'ai_enabled' => $this->ai->isConfigured(),
            'model' => $this->ai->getSettings()['model'] ?? null,
            'period' => $range,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Free-form financial question, answered from this user's own transactions.
     */
    public function ask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|min:3|max:500',
            'limit' => 'nullable|integer|min:10|max:200',
        ]);

        $userId = (int) auth()->id();

        $transactions = FinancialTransaction::with('category')
            ->where('user_id', $userId)
            ->latest('date')
            ->latest('id')
            ->limit((int) ($validated['limit'] ?? 200))
            ->get()
            ->map(fn (FinancialTransaction $tx): array => [
                'date' => $tx->date->format('Y-m-d'),
                'description' => $tx->description,
                'amount' => (float) $tx->amount,
                'type' => $tx->type,
                'category' => $tx->category?->name,
            ])
            ->all();

        $service = new FinancialAIService($this->ai);
        $answer = $service->answerQuery($validated['question'], $transactions);

        return response()->json([
            'question' => $validated['question'],
            'answer' => $answer,
            'ai_enabled' => $this->ai->isConfigured(),
            'model' => $this->ai->getSettings()['model'] ?? null,
        ]);
    }

    /**
     * Turn free text ("makan 30rb") into a transaction.
     *
     * By default this is a dry run so the app can show a confirmation screen
     * before anything is written; pass save=true to persist in one call.
     */
    public function parse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|min:2|max:500',
            'save' => 'nullable|boolean',
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $userId = (int) auth()->id();

        $service = new FinancialAIService($this->ai);
        $parsed = $service->parseTransaction($validated['text']);

        if (! $parsed || (float) $parsed['amount'] <= 0) {
            return response()->json([
                'message' => 'Tidak bisa memahami transaksi tersebut.',
                'examples' => ['makan 30000', 'gaji 5jt', 'beli bensin 100rb', 'jajan 15.000'],
            ], 422);
        }

        if (! ($validated['save'] ?? false)) {
            return response()->json([
                'parsed' => [
                    'type' => $parsed['type'],
                    'amount' => (float) $parsed['amount'],
                    'description' => $parsed['description'],
                    'category' => $parsed['category'],
                ],
                'saved' => false,
            ]);
        }

        $category = $this->resolveOrCreateCategory($userId, $parsed['type'], $parsed['category']);

        $transaction = FinancialTransaction::create([
            'user_id' => $userId,
            'category_id' => $category?->id,
            'type' => $parsed['type'],
            'amount' => $parsed['amount'],
            'description' => $parsed['description'],
            'date' => $validated['date'] ?? now()->format('Y-m-d'),
            'source' => 'ai',
        ]);

        return response()->json([
            'saved' => true,
            'transaction' => new FinancialTransactionResource($transaction->load('category')),
        ], 201);
    }

    /**
     * Reuse the user's existing category with that name, otherwise create a
     * user-owned (non-system) one so AI-parsed rows are categorised properly.
     */
    private function resolveOrCreateCategory(int $userId, string $type, string $name): ?FinancialCategory
    {
        $name = trim($name) ?: 'Lainnya';

        return FinancialCategory::firstOrCreate(
            ['user_id' => $userId, 'name' => $name, 'type' => $type],
            [
                'icon' => $type === 'income' ? '💵' : '💸',
                'color' => $type === 'income' ? '#10b981' : '#ef4444',
                'is_system' => false,
            ]
        );
    }
}
