<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for the personal-finance aggregates.
 *
 * Shared by the Livewire dashboard and the JSON API consumed by the Android
 * app so the two can never drift apart. Every query is scoped by user id and
 * runs as a single grouped statement rather than one query per month/category.
 */
final class FinanceReportService
{
    public const PERIODS = [
        'today',
        'yesterday',
        'this_week',
        'this_month',
        'last_month',
        'this_year',
        'all_time',
        'custom',
    ];

    /**
     * Turn a period alias (or explicit from/to pair) into a concrete range.
     *
     * @return array{from: ?string, to: ?string, label: string}
     */
    public function resolveRange(?string $period, ?string $from, ?string $to, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $period = in_array($period, self::PERIODS, true) ? $period : 'this_month';

        if ($period === 'custom') {
            $start = $from ? CarbonImmutable::parse($from)->startOfDay() : $now->startOfMonth();
            $end = $to ? CarbonImmutable::parse($to)->endOfDay() : $now->endOfDay();

            return [
                'from' => $start->format('Y-m-d'),
                'to' => $end->format('Y-m-d'),
                'label' => $start->format('j M Y').' - '.$end->format('j M Y'),
            ];
        }

        $start = match ($period) {
            'today' => $now->startOfDay(),
            'yesterday' => $now->subDay()->startOfDay(),
            'this_week' => $now->startOfWeek(),
            'this_month' => $now->startOfMonth(),
            'last_month' => $now->subMonth()->startOfMonth(),
            'this_year' => $now->startOfYear(),
            default => null,
        };

        $end = match ($period) {
            'today' => $now->endOfDay(),
            'yesterday' => $now->subDay()->endOfDay(),
            'last_month' => $now->subMonth()->endOfMonth(),
            'all_time' => $now->endOfDay(),
            default => $now->endOfDay(),
        };

        return [
            'from' => $start?->format('Y-m-d'),
            'to' => $end->format('Y-m-d'),
            'label' => $this->periodLabel($period, $start, $end),
        ];
    }

    private function periodLabel(string $period, ?CarbonImmutable $start, CarbonImmutable $end): string
    {
        return match ($period) {
            'today' => $end->format('j M Y'),
            'yesterday' => $end->format('j M Y'),
            'this_week' => $start?->format('j M').' - '.$end->format('j M Y'),
            'this_month' => $end->format('F Y'),
            'last_month' => $end->format('F Y'),
            'this_year' => $end->format('Y'),
            default => 'Semua Periode',
        };
    }

    /**
     * Headline totals for the income / expense / balance cards.
     *
     * @return array{total_income: float, total_expense: float, balance: float, count: int, savings_rate: float, avg_daily_expense: float}
     */
    public function stats(int $userId, ?string $from, ?string $to): array
    {
        $rows = $this->baseQuery($userId, $from, $to)
            ->selectRaw('type, COUNT(*) as tx_count, COALESCE(SUM(amount), 0) as total')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $income = (float) ($rows->get('income')->total ?? 0);
        $expense = (float) ($rows->get('expense')->total ?? 0);
        $count = (int) ($rows->sum('tx_count') ?? 0);

        $balance = $income - $expense;

        return [
            'total_income' => round($income, 2),
            'total_expense' => round($expense, 2),
            'balance' => round($balance, 2),
            'count' => $count,
            'savings_rate' => $income > 0 ? round($balance / $income * 100, 2) : 0.0,
            'avg_daily_expense' => $this->avgDailyExpense($userId, $from, $to),
        ];
    }

    /**
     * Average spend per calendar day that actually contains transactions,
     * so a single expense on the 1st does not look like a tiny daily average.
     */
    private function avgDailyExpense(int $userId, ?string $from, ?string $to): float
    {
        $row = $this->baseQuery($userId, $from, $to)
            ->where('type', 'expense')
            ->selectRaw('COUNT(DISTINCT date) as days, COALESCE(SUM(amount), 0) as total')
            ->first();

        $days = (int) ($row->days ?? 0);

        if ($days === 0) {
            return 0.0;
        }

        return round((float) $row->total / $days, 2);
    }

    /**
     * Trailing N-month income/expense series for the trend chart.
     * One query for the whole series, zero-filled so the chart has no gaps.
     *
     * @return list<array{month: string, label: string, income: float, expense: float, balance: float}>
     */
    public function monthlySeries(int $userId, int $months = 6, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $months = max(1, min($months, 24));
        $start = $now->subMonths($months - 1)->startOfMonth();

        $rows = $this->baseQuery($userId, $start->format('Y-m-d'), $now->endOfDay()->format('Y-m-d'))
            ->selectRaw($this->yearMonthExpression().' as period_key, type, COALESCE(SUM(amount), 0) as total')
            ->groupBy('period_key', 'type')
            ->get();

        // Flatten to totals[period][type] so the lookup below cannot confuse a
        // row index with a type name.
        $totals = [];
        foreach ($rows as $row) {
            $totals[$row->period_key][$row->type] = (float) $row->total;
        }

        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $cursor = $start->addMonths($i);
            $key = $cursor->format('Y-m');
            $income = $totals[$key]['income'] ?? 0.0;
            $expense = $totals[$key]['expense'] ?? 0.0;

            $series[] = [
                'month' => $key,
                'label' => $cursor->format('M'),
                'long_label' => $cursor->format('M Y'),
                'income' => round($income, 2),
                'expense' => round($expense, 2),
                'balance' => round($income - $expense, 2),
            ];
        }

        return $series;
    }

    /**
     * Per-day totals across the selected range for the chart on the dashboard.
     *
     * @return list<array{date: string, income: float, expense: float}>
     */
    public function dailySeries(int $userId, ?string $from, ?string $to): array
    {
        $rows = $this->baseQuery($userId, $from, $to)
            ->selectRaw('DATE(date) as day, type, COALESCE(SUM(amount), 0) as total')
            ->groupBy('day', 'type')
            ->get();

        $totals = [];
        foreach ($rows as $row) {
            $totals[$row->day][$row->type] = (float) $row->total;
        }

        $days = [];
        foreach ($totals as $day => $byType) {
            $days[] = [
                'date' => $day,
                'income' => round($byType['income'] ?? 0.0, 2),
                'expense' => round($byType['expense'] ?? 0.0, 2),
            ];
        }

        usort($days, fn (array $a, array $b): int => $a['date'] <=> $b['date']);

        return $days;
    }

    /**
     * Category totals for the donut/bar chart, with share-of-total computed.
     *
     * @return list<array{id: ?int, name: string, icon: ?string, color: ?string, total: float, count: int, share: float}>
     */
    public function categoryBreakdown(int $userId, ?string $from, ?string $to, string $type = 'expense'): array
    {
        $rows = $this->baseQuery($userId, $from, $to)
            ->where('type', $type)
            ->selectRaw('category_id, COUNT(*) as tx_count, COALESCE(SUM(amount), 0) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        $grandTotal = (float) $rows->sum('total');

        $categories = FinancialCategory::where('user_id', $userId)
            ->when(
                $rows->pluck('category_id')->filter()->unique()->isNotEmpty(),
                fn ($q) => $q->whereIn('id', $rows->pluck('category_id')->filter()->unique())
            )
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($categories): array {
            $category = $row->category_id ? $categories->get($row->category_id) : null;
            $total = (float) $row->total;

            return [
                'id' => $row->category_id,
                'name' => $category?->name ?? 'Tanpa Kategori',
                'icon' => $category?->icon,
                'color' => $category?->color ?? '#94a3b8',
                'total' => round($total, 2),
                'count' => (int) $row->tx_count,
                'share' => 0.0, // filled below once the grand total is known
            ];
        })->map(function (array $row) use ($grandTotal): array {
            $row['share'] = $grandTotal > 0 ? round($row['total'] / $grandTotal * 100, 2) : 0.0;

            return $row;
        })->values()->all();
    }

    /**
     * Derived, non-AI highlights shown on the dashboard cards.
     *
     * @return array<string, mixed>
     */
    public function insights(int $userId, ?string $from, ?string $to): array
    {
        $stats = $this->stats($userId, $from, $to);
        $expenseCategories = $this->categoryBreakdown($userId, $from, $to, 'expense');
        $incomeCategories = $this->categoryBreakdown($userId, $from, $to, 'income');

        $biggest = $this->baseQuery($userId, $from, $to)
            ->where('type', 'expense')
            ->orderByDesc('amount')
            ->first(['description', 'amount', 'date']);

        $previous = $this->previousPeriodTotals($userId, $from, $to);

        return [
            'top_expense_category' => $expenseCategories[0] ?? null,
            'top_income_category' => $incomeCategories[0] ?? null,
            'biggest_expense' => $biggest ? [
                'description' => $biggest->description,
                'amount' => (float) $biggest->amount,
                'date' => $biggest->date->format('Y-m-d'),
            ] : null,
            'expense_change_pct' => $previous['expense_pct'],
            'income_change_pct' => $previous['income_pct'],
            'health' => $this->healthBand((float) $stats['savings_rate'], (float) $stats['total_income']),
        ];
    }

    /**
     * Compare the range against the immediately preceding window of equal length.
     *
     * @return array{expense_pct: ?float, income_pct: ?float}
     */
    private function previousPeriodTotals(int $userId, ?string $from, ?string $to): array
    {
        if (! $from || ! $to) {
            return ['expense_pct' => null, 'income_pct' => null];
        }

        $start = CarbonImmutable::parse($from)->startOfDay();
        $end = CarbonImmutable::parse($to)->endOfDay();
        $days = $start->diffInDays($end) + 1;

        $prevTo = $start->subDay();
        $prevFrom = $prevTo->subDays($days - 1);

        $rows = $this->baseQuery($userId, $prevFrom->format('Y-m-d'), $prevTo->format('Y-m-d'))
            ->selectRaw('type, COALESCE(SUM(amount), 0) as total')
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $current = $this->stats($userId, $from, $to);

        return [
            'expense_pct' => $this->pctChange(
                (float) ($rows->get('expense')->total ?? 0),
                (float) $current['total_expense']
            ),
            'income_pct' => $this->pctChange(
                (float) ($rows->get('income')->total ?? 0),
                (float) $current['total_income']
            ),
        ];
    }

    private function pctChange(float $previous, float $current): ?float
    {
        if ($previous <= 0.0) {
            return null;
        }

        return round(($current - $previous) / $previous * 100, 2);
    }

    /**
     * @return array{band: string, label: string, savings_rate: float}
     */
    private function healthBand(float $savingsRate, float $income): array
    {
        if ($income <= 0) {
            return ['band' => 'unknown', 'label' => 'Belum ada pemasukan', 'savings_rate' => 0.0];
        }

        return match (true) {
            $savingsRate >= 30 => ['band' => 'excellent', 'label' => 'Sangat Sehat', 'savings_rate' => $savingsRate],
            $savingsRate >= 20 => ['band' => 'good', 'label' => 'Sehat', 'savings_rate' => $savingsRate],
            $savingsRate >= 10 => ['band' => 'fair', 'label' => 'Cukup Sehat', 'savings_rate' => $savingsRate],
            $savingsRate >= 0 => ['band' => 'poor', 'label' => 'Perlu Perhatian', 'savings_rate' => $savingsRate],
            default => ['band' => 'critical', 'label' => 'Kritis', 'savings_rate' => $savingsRate],
        };
    }

    private function baseQuery(int $userId, ?string $from, ?string $to)
    {
        return FinancialTransaction::where('user_id', $userId)->inRange($from, $to);
    }

    /**
     * SQL expression yielding a 'YYYY-MM' bucket, per driver, so the test
     * suite can exercise the aggregation on SQLite as well as MySQL.
     */
    private function yearMonthExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', date)",
            'pgsql' => "to_char(date, 'YYYY-MM')",
            default => "DATE_FORMAT(date, '%Y-%m')",
        };
    }
}
