<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Services\FinanceReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __construct(private readonly FinanceReportService $reports) {}

    /**
     * Everything the app's dashboard needs in a single round trip:
     * headline cards, trend series, category splits and the latest rows.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => 'nullable|string|in:'.implode(',', FinanceReportService::PERIODS),
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d',
            'months' => 'nullable|integer|min:1|max:24',
            'recent_limit' => 'nullable|integer|min:1|max:50',
        ]);

        $userId = (int) auth()->id();

        $range = $this->reports->resolveRange(
            $validated['period'] ?? null,
            $validated['from'] ?? null,
            $validated['to'] ?? null,
        );

        $months = (int) ($validated['months'] ?? 6);
        $recentLimit = (int) ($validated['recent_limit'] ?? 10);

        $recent = FinancialTransaction::with('category')
            ->where('user_id', $userId)
            ->latest('date')
            ->latest('id')
            ->limit($recentLimit)
            ->get()
            ->map(fn (FinancialTransaction $tx): array => [
                'id' => $tx->id,
                'type' => $tx->type,
                'amount' => (float) $tx->amount,
                'description' => $tx->description,
                'date' => $tx->date->format('Y-m-d'),
                'category' => $tx->category ? [
                    'id' => $tx->category->id,
                    'name' => $tx->category->name,
                    'icon' => $tx->category->icon,
                    'color' => $tx->category->color,
                ] : null,
            ])
            ->all();

        return response()->json([
            'period' => $range,
            'stats' => $this->reports->stats($userId, $range['from'], $range['to']),
            'monthly' => $this->reports->monthlySeries($userId, $months),
            'daily' => $this->reports->dailySeries($userId, $range['from'], $range['to']),
            'expense_by_category' => $this->reports->categoryBreakdown($userId, $range['from'], $range['to'], 'expense'),
            'income_by_category' => $this->reports->categoryBreakdown($userId, $range['from'], $range['to'], 'income'),
            'insights' => $this->reports->insights($userId, $range['from'], $range['to']),
            'recent' => $recent,
        ]);
    }
}
