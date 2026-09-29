<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyCategory;
use App\Models\FamilyDebt;
use App\Models\FamilyGoal;
use App\Models\FamilyTransaction;

/**
 * Kang Cuan — the family financial advisor ("Pendamping Keuangan").
 *
 * The advisor is opt-in (enabled only by the family owner) and only ever
 * *suggests* a monthly plan. It never writes transactions or budgets by itself.
 * All numbers come from deterministic rules so they can be unit-tested; the AI
 * (FamilyAIService) is only used to explain them, never to invent figures.
 */
final class FamilyAdvisorService
{
    public function __construct(
        private readonly FamilyAllocationService $allocation = new FamilyAllocationService,
    ) {}

    /**
     * Current-month income and expense for the family, avoiding double counting
     * by summing only the two transaction types from the transactions table.
     *
     * @return array{income: float, expense: float}
     */
    public function cashflow(Family $family): array
    {
        $month = now()->startOfMonth()->format('Y-m-d');
        $income = (float) FamilyTransaction::forFamily($family->id)
            ->where('type', 'income')->where('date', '>=', $month)->sum('amount');
        $expense = (float) FamilyTransaction::forFamily($family->id)
            ->where('type', 'expense')->where('date', '>=', $month)->sum('amount');

        return ['income' => $income, 'expense' => $expense];
    }

    /**
     * Income base for the plan: the profile's declared monthly income wins when
     * set (more stable than a partial current month), otherwise the current
     * month's recorded income is used.
     */
    public function incomeBase(Family $family): float
    {
        $profile = $family->advisor_profile ?? [];
        if (isset($profile['monthly_income']) && (float) $profile['monthly_income'] > 0) {
            return (float) $profile['monthly_income'];
        }

        return $this->cashflow($family)['income'];
    }

    /**
     * Total mandatory debt installment for the current month (payable, unsettled).
     * This is the *planned* obligation ("rencana").
     */
    public function mandatoryDebt(Family $family): float
    {
        return (float) FamilyDebt::forFamily($family->id)
            ->where('type', 'payable')
            ->where('status', '!=', 'settled')
            ->sum('installment');
    }

    /**
     * Debt installments already paid this month ("realisasi"): expense
     * transactions recorded in the family's Cicilan/Hutang categories.
     *
     * Keeping this separate from the planned installment prevents the same
     * rupiah from being counted twice — once as a monthly expense and again
     * as a fresh obligation in the plan/score.
     */
    public function realizedDebtThisMonth(Family $family): float
    {
        $month = now()->startOfMonth()->format('Y-m-d');

        $debtCategoryIds = FamilyCategory::forFamily($family->id)
            ->where('type', 'expense')
            ->get()
            ->filter(fn (FamilyCategory $c) => in_array(
                mb_strtolower(trim($c->name)),
                ['cicilan', 'hutang'],
                true,
            ))
            ->pluck('id');

        if ($debtCategoryIds->isEmpty()) {
            return 0.0;
        }

        return (float) FamilyTransaction::forFamily($family->id)
            ->where('type', 'expense')
            ->whereIn('category_id', $debtCategoryIds)
            ->where('date', '>=', $month)
            ->sum('amount');
    }

    /**
     * Still owing on this month's installments after what has already been paid.
     */
    public function uncoveredDebtObligation(Family $family): float
    {
        return max(0.0, $this->mandatoryDebt($family) - $this->realizedDebtThisMonth($family));
    }

    /**
     * Approachable context: cashflow + emergency fund position + debt overview.
     *
     * @return array<string, mixed>
     */
    public function context(Family $family): array
    {
        $cash = $this->cashflow($family);
        $emergencyCurrent = (float) FamilyGoal::forFamily($family->id)
            ->where('type', 'emergency_fund')->sum('current_amount');
        $emergencyTarget = $this->emergencyTarget($family);
        $debts = FamilyDebt::forFamily($family->id)
            ->where('type', 'payable')->where('status', '!=', 'settled')->get();

        return [
            'income' => round($cash['income'], 2),
            'expense' => round($cash['expense'], 2),
            'savings' => round($cash['income'] - $cash['expense'], 2),
            'average_monthly_expense' => round($this->allocation->averageMonthlyExpense($family), 2),
            'emergency_current' => round($emergencyCurrent, 2),
            'emergency_target' => round($emergencyTarget, 2),
            'emergency_month_coverage' => $this->emergencyMonthCoverage($family),
            'mandatory_debt' => round($this->mandatoryDebt($family), 2),
            'planned_debt' => round($this->mandatoryDebt($family), 2),
            'realized_debt_this_month' => round($this->realizedDebtThisMonth($family), 2),
            'uncovered_debt' => round($this->uncoveredDebtObligation($family), 2),
            'total_debt' => round((float) $debts->sum(fn ($d) => $d->remaining), 2),
        ];
    }

    /**
     * Emergency fund target in months of essential monthly expense.
     * Uses real recorded expense history when available, otherwise the
     * essentials share of the income base.
     */
    public function emergencyTarget(Family $family): float
    {
        $fromHistory = $this->allocation->emergencyFundTarget($family);
        $months = $this->emergencyMonths($family);

        if ($fromHistory > 0) {
            return round($fromHistory, 2);
        }

        return round($this->incomeBase($family) * (float) config('advisor.defaults.essentials', 0.5) * $months, 2);
    }

    public function emergencyMonths(Family $family): int
    {
        $irregular = (($family->advisor_profile ?? [])['income_type'] ?? 'fixed') === 'irregular';

        return $irregular
            ? (int) config('advisor.emergency_months_irregular', 6)
            : (int) config('advisor.emergency_months', 3);
    }

    public function emergencyMonthCoverage(Family $family): ?float
    {
        $essentialMonthly = $this->essentialMonthlyExpense($family);
        if ($essentialMonthly <= 0) {
            return null;
        }

        $current = (float) FamilyGoal::forFamily($family->id)
            ->where('type', 'emergency_fund')->sum('current_amount');

        return round($current / $essentialMonthly, 1);
    }

    public function essentialMonthlyExpense(Family $family): float
    {
        $avg = $this->allocation->averageMonthlyExpense($family);

        return $avg > 0 ? round($avg, 2) : round($this->incomeBase($family) * (float) config('advisor.defaults.essentials', 0.5), 2);
    }

    /**
     * Build a deterministic monthly plan from the income base.
     *
     * Order: essentials + health + mandatory debt first. Whatever remains is
     * split by priority into emergency, goals, fun and a flexible buffer.
     * If essential obligations exceed income the plan reports a deficit instead
     * of inventing money.
     *
     * @return array<string, mixed>
     */
    public function plan(Family $family): array
    {
        $d = config('advisor.defaults');
        $income = $this->incomeBase($family);
        $posts = [
            'essentials' => ['label' => 'Kebutuhan pokok & hunian', 'amount' => round($income * (float) $d['essentials'], 2)],
            'health' => ['label' => 'Kesehatan & proteksi', 'amount' => round($income * (float) $d['health'], 2)],
        ];

        $mandatory = $this->mandatoryDebt($family);
        $realized = $this->realizedDebtThisMonth($family);
        $netObligation = max(0.0, $mandatory - $realized);

        // A debt post only asks for the unpaid share of the month's installments.
        // Installments already recorded as expense transactions (realisasi)
        // are deliberately not requested again, so the money frees up for
        // emergency/goals/fun instead of being double-budgeted.
        $posts['debt'] = [
            'label' => 'Cicilan wajib',
            'amount' => $mandatory > 0 ? round($netObligation, 2) : round($income * (float) $d['debt'], 2),
            'source' => $mandatory > 0 ? 'installment' : 'default',
            'planned' => round($mandatory, 2),
            'realized' => round($realized, 2),
        ];

        $remaining = $income - $posts['essentials']['amount'] - $posts['health']['amount'] - $posts['debt']['amount'];

        $defict = max(0.0, -$remaining);
        $remaining = max(0.0, $remaining);

        $emergency = min($remaining, $income * (float) $d['emergency']);
        $remaining -= $emergency;
        $goals = min($remaining, $income * (float) $d['goals']);
        $remaining -= $goals;
        $fun = min($remaining, $income * (float) $d['fun']);
        $remaining -= $fun;

        $emergencyCap = (float) $d['emergency'] + (float) $d['buffer'];
        $emergencyExtra = min($remaining, $income * $emergencyCap - $emergency);
        $emergency += max(0.0, $emergencyExtra);
        $remaining -= max(0.0, $emergencyExtra);
        $buffer = max(0.0, $remaining);

        $posts['emergency'] = ['label' => 'Dana darurat', 'amount' => round($emergency, 2)];
        $posts['goals'] = ['label' => 'Tujuan & tabungan', 'amount' => round($goals, 2)];
        $posts['fun'] = ['label' => 'Jajan & hiburan', 'amount' => round($fun, 2)];
        $posts['buffer'] = ['label' => 'Cadangan tidak rutin', 'amount' => round($buffer, 2)];

        $total = round(array_sum(array_map(fn ($p) => $p['amount'], $posts)), 2);

        return [
            'income' => round($income, 2),
            'deficit' => round($defict, 2),
            'is_deficit' => $defict > 0,
            'posts' => $posts,
            'total' => $total,
            'emergency_target' => round($this->emergencyTarget($family), 2),
            'emergency_current' => round((float) FamilyGoal::forFamily($family->id)
                ->where('type', 'emergency_fund')->sum('current_amount'), 2),
            'emergency_month_coverage' => $this->emergencyMonthCoverage($family),
            'budget_suggestion' => $this->budgetSuggestion($family, $posts),
        ];
    }

    /**
     * Convert the advisor plan into amounts per existing expense categories so
     * the family can apply it to the real budget module after confirmation.
     *
     * @return array<int, array{category_id: int|null, name: string, amount: float}>
     */
    public function budgetSuggestion(Family $family, array $posts): array
    {
        $mapping = [
            'essentials' => ['Kebutuhan', 'Hunian', 'Belanja', 'Makanan', 'Transportasi'],
            'health' => ['Kesehatan'],
            'debt' => ['Cicilan', 'Hutang'],
            'goals' => ['Pendidikan', 'Tabungan'],
            'fun' => ['Hiburan', 'Jajan'],
        ];

        $categories = FamilyCategory::forFamily($family->id)
            ->where('type', 'expense')->pluck('name', 'id');

        $suggestions = [];
        foreach ($posts as $key => $post) {
            if (! isset($mapping[$key]) || $post['amount'] <= 0) {
                continue;
            }

            $name = collect($mapping[$key])->first(fn ($candidate) => $categories->contains(mb_strtolower($candidate))) ?? $mapping[$key][0];
            $categoryId = $categories->search(mb_strtolower($name), true) ?: null;

            $suggestions[] = [
                'category_id' => $categoryId,
                'name' => $name,
                'amount' => round($post['amount'], 2),
            ];
        }

        return $suggestions;
    }
}
