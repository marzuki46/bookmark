<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyDebt;
use App\Models\FamilyGoal;
use App\Models\FamilyTransaction;

final class FamilyAllocationService
{
    public const int EMERGENCY_FUND_MONTHS = 3;

    /**
     * Calculate the monthly surplus available for goals after expenses
     * and mandatory debt installments.
     */
    public function monthlySurplus(Family $family, int $month, int $year): float
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start));

        $income = (float) FamilyTransaction::forFamily($family->id)
            ->where('type', 'income')
            ->whereBetween('date', [$start, $end])
            ->sum('amount');

        $expense = (float) FamilyTransaction::forFamily($family->id)
            ->where('type', 'expense')
            ->whereBetween('date', [$start, $end])
            ->sum('amount');

        $mandatoryDebt = (float) FamilyDebt::forFamily($family->id)
            ->where('status', '!=', 'settled')
            ->where('type', 'payable')
            ->sum('installment');

        return max(0, $income - $expense - $mandatoryDebt);
    }

    /**
     * Average monthly expense over the last 3 full months.
     */
    public function averageMonthlyExpense(Family $family, int $monthsBack = 3): float
    {
        $total = 0.0;
        $count = 0;

        for ($i = $monthsBack; $i >= 1; $i--) {
            $date = now()->subMonths($i);
            $start = $date->copy()->startOfMonth()->format('Y-m-d');
            $end = $date->copy()->endOfMonth()->format('Y-m-d');

            $total += (float) FamilyTransaction::forFamily($family->id)
                ->where('type', 'expense')
                ->whereBetween('date', [$start, $end])
                ->sum('amount');
            $count++;
        }

        return $count > 0 ? $total / $count : 0.0;
    }

    /**
     * The default emergency fund target = 3x average monthly expense.
     */
    public function emergencyFundTarget(Family $family): float
    {
        return round($this->averageMonthlyExpense($family, self::EMERGENCY_FUND_MONTHS), 2);
    }

    /**
     * Build an allocation suggestion: goals sorted by priority, each receiving
     * its monthly_allocation until the surplus runs out.
     *
     * @return array<int, array{goal: FamilyGoal, amount: float, percent: float}>
     */
    public function suggestAllocation(Family $family, int $month, int $year): array
    {
        $surplus = $this->monthlySurplus($family, $month, $year);
        $goals = FamilyGoal::forFamily($family->id)
            ->where('status', 'active')
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        $remaining = $surplus;
        $suggestions = [];

        foreach ($goals as $goal) {
            if ($remaining <= 0) {
                break;
            }

            $alreadyAllocated = (float) FamilyGoal::forFamily($family->id)
                ->where('id', $goal->id)
                ->value('current_amount');

            $gap = max(0, (float) $goal->target_amount - $alreadyAllocated);

            if ($gap <= 0) {
                continue;
            }

            $amount = min($remaining, (float) $goal->monthly_allocation, $gap);

            if ($amount <= 0) {
                continue;
            }

            $suggestions[] = [
                'goal' => $goal,
                'amount' => round($amount, 2),
                'percent' => $surplus > 0 ? round(($amount / $surplus) * 100, 1) : 0,
            ];

            $remaining -= $amount;
        }

        return $suggestions;
    }

    /**
     * Persist the confirmed allocation: bump goal.current_amount and record
     * the allocation rows.
     *
     * @param array<int, array{goal_id: int, amount: float}> $items
     */
    public function confirmAllocation(Family $family, int $month, int $year, array $items): void
    {
        foreach ($items as $item) {
            $goal = FamilyGoal::forFamily($family->id)->findOrFail($item['goal_id']);
            $amount = (float) $item['amount'];

            if ($amount <= 0) {
                continue;
            }

            $goal->increment('current_amount', $amount);

            if ((float) $goal->current_amount >= (float) $goal->target_amount) {
                $goal->update(['status' => 'completed']);
            }

            \App\Models\FamilyAllocation::create([
                'family_id' => $family->id,
                'month' => $month,
                'year' => $year,
                'goal_id' => $goal->id,
                'amount' => $amount,
                'source' => 'income',
                'confirmed_at' => now(),
            ]);
        }
    }

    /**
     * Recommended debt payoff order. Avalanche = highest interest first,
     * Snowball = smallest remaining balance first.
     *
     * @return array<int, array{debt: FamilyDebt, remaining: float}>
     */
    public function debtPayoffOrder(Family $family, string $method = 'avalanche'): array
    {
        $debts = FamilyDebt::forFamily($family->id)
            ->where('type', 'payable')
            ->where('status', '!=', 'settled')
            ->get()
            ->map(fn (FamilyDebt $debt) => [
                'debt' => $debt,
                'remaining' => $debt->remaining,
            ]);

        $sorted = $debts->sortBy(function (array $item) use ($method) {
            $debt = $item['debt'];

            return $method === 'snowball'
                ? [$item['remaining'], $debt->id]
                : [0 - (float) ($debt->interest_rate ?? 0), $debt->id];
        });

        return $sorted->values()->all();
    }

    /**
     * Estimate debt-free month (best effort) given current installment.
     */
    public function debtFreeProjection(Family $family): ?array
    {
        $totalRemaining = 0.0;
        $totalInstallment = 0.0;
        $nextDue = null;

        foreach (FamilyDebt::forFamily($family->id)->where('type', 'payable')->where('status', '!=', 'settled')->get() as $debt) {
            $totalRemaining += $debt->remaining;
            $totalInstallment += (float) $debt->installment;
            if ($debt->due_date && ($nextDue === null || $debt->due_date->lt($nextDue))) {
                $nextDue = $debt->due_date;
            }
        }

        if ($totalRemaining <= 0 || $totalInstallment <= 0) {
            return null;
        }

        $months = (int) ceil($totalRemaining / $totalInstallment);

        return [
            'total_remaining' => round($totalRemaining, 2),
            'total_installment' => round($totalInstallment, 2),
            'months' => $months,
            'free_at' => now()->addMonths($months)->format('M Y'),
            'next_due' => $nextDue,
        ];
    }
}
