<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Family;
use App\Models\FamilyBudget;
use App\Models\FamilyCategory;
use App\Models\FamilyTransaction;
use Livewire\Component;

final class FamilyAppBudget extends Component
{
    public string $statusMessage = '';

    public string $statusType = 'success';

    public function getFamilyProperty(): ?Family
    {
        return auth()->user()->family();
    }

    public function getBudgetsProperty()
    {
        $family = $this->family;
        if (! $family) {
            return collect();
        }

        $month = now()->month;
        $year = now()->year;

        $budgets = FamilyBudget::with('category')->forFamily($family->id)
            ->where('month', $month)->where('year', $year)->get()->keyBy('category_id');

        $start = now()->startOfMonth()->format('Y-m-d');
        $end = now()->endOfMonth()->format('Y-m-d');

        $spentByCategory = FamilyTransaction::forFamily($family->id)
            ->where('type', 'expense')->whereBetween('date', [$start, $end])
            ->selectRaw('category_id, SUM(amount) as total')->groupBy('category_id')
            ->pluck('total', 'category_id');

        $result = [];
        foreach ($budgets as $catId => $budget) {
            $spent = (float) ($spentByCategory[$catId] ?? 0);
            $amount = (float) $budget->amount;

            $result[] = [
                'category' => $budget->category,
                'amount' => $amount,
                'spent' => $spent,
                'remaining' => $amount - $spent,
                'percent' => $amount > 0 ? min(100, round($spent / $amount * 100)) : 0,
                'overspent' => $amount > 0 && $spent > $amount,
            ];
        }

        $result = collect($result)->sortByDesc('overspent')->sortByDesc('percent')->values();

        // fallback: categories without budget
        $budgetedIds = $budgets->keys();
        $uncategorized = FamilyCategory::forFamily($family->id)->where('type', 'expense')->whereNotIn('id', $budgetedIds)->get();
        foreach ($uncategorized as $cat) {
            $spent = (float) ($spentByCategory[$cat->id] ?? 0);
            if ($spent > 0) {
                $result->push([
                    'category' => $cat,
                    'amount' => 0,
                    'spent' => $spent,
                    'remaining' => -$spent,
                    'percent' => 100,
                    'overspent' => true,
                ]);
            }
        }

        return $result;
    }

    public function getTotalsProperty(): array
    {
        $budgets = collect($this->budgets);

        return [
            'budget' => $budgets->sum('amount'),
            'spent' => $budgets->sum('spent'),
        ];
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    public function render()
    {
        return view('livewire.family-app-budget', [
            'family' => $this->family,
            'budgets' => $this->budgets,
            'totals' => $this->totals,
        ]);
    }
}
