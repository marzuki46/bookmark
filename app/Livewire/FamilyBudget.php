<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Family;
use App\Models\FamilyBudget as FamilyBudgetModel;
use App\Models\FamilyCategory;
use App\Models\FamilyTransaction;
use Livewire\Component;

final class FamilyBudget extends Component
{
    public ?int $familyId = null;

    public string $month = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public ?int $formCategoryId = null;

    public string $formAmount = '';

    public string $statusMessage = '';

    public string $statusType = 'success';

    public function mount(?int $familyId = null): void
    {
        $this->familyId = $familyId;
        $this->month = now()->format('Y-m');
    }

    public function getFamilyProperty(): ?Family
    {
        if ($this->familyId) {
            abort_unless(auth()->user()?->is_admin === true, 403);

            return Family::query()->find($this->familyId);
        }

        return auth()->user()->family();
    }

    public function getMonthProperty(): int
    {
        return (int) substr($this->month, 5, 2);
    }

    public function getYearProperty(): int
    {
        return (int) substr($this->month, 0, 4);
    }

    public function getBudgetsProperty()
    {
        $family = $this->family;
        if (! $family) {
            return collect();
        }

        $month = $this->month;
        $year = (int) substr($month, 0, 4);
        $mon = (int) substr($month, 5, 2);

        $budgets = FamilyBudgetModel::with('category')->forFamily($family->id)
            ->where('month', $mon)->where('year', $year)
            ->get()->keyBy('category_id');

        $expenseCategories = FamilyCategory::forFamily($family->id)->where('type', 'expense')->get();

        $start = sprintf('%04d-%02d-01', $year, $mon);
        $end = date('Y-m-t', strtotime($start));

        $spentByCategory = FamilyTransaction::forFamily($family->id)
            ->where('type', 'expense')
            ->whereBetween('date', [$start, $end])
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $result = [];

        foreach ($expenseCategories as $cat) {
            $budget = $budgets->get($cat->id);
            $spent = (float) ($spentByCategory[$cat->id] ?? 0);
            $amount = $budget ? (float) $budget->amount : 0;

            $result[] = [
                'category' => $cat,
                'budget_id' => $budget?->id,
                'amount' => $amount,
                'spent' => $spent,
                'remaining' => $amount - $spent,
                'percent' => $amount > 0 ? min(100, round($spent / $amount * 100)) : 0,
                'overspent' => $amount > 0 && $spent > $amount,
            ];
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

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->formCategoryId = null;
        $this->formAmount = '';
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $family = $this->family;
        $budget = FamilyBudgetModel::forFamily($family?->id ?? 0)->findOrFail($id);
        $this->editingId = $id;
        $this->formCategoryId = $budget->category_id;
        $this->formAmount = (string) $budget->amount;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingId = null;
        $this->formCategoryId = null;
        $this->formAmount = '';
    }

    public function save(): void
    {
        $this->validate([
            'formCategoryId' => 'required|exists:family_categories,id',
            'formAmount' => 'required|numeric|min:0.01',
        ]);

        $family = $this->family;
        if (! $family) {
            return;
        }

        $month = $this->month;
        $year = (int) substr($month, 0, 4);
        $mon = (int) substr($month, 5, 2);

        FamilyBudgetModel::updateOrCreate(
            [
                'family_id' => $family->id,
                'category_id' => $this->formCategoryId,
                'month' => $mon,
                'year' => $year,
            ],
            ['amount' => $this->formAmount]
        );

        $this->statusMessage = 'Anggaran berhasil disimpan.';
        $this->statusType = 'success';
        $this->closeModal();
    }

    public function delete(int $id): void
    {
        $family = $this->family;
        FamilyBudgetModel::forFamily($family?->id ?? 0)->findOrFail($id)->delete();
        $this->statusMessage = 'Anggaran berhasil dihapus.';
        $this->statusType = 'success';
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    public function render()
    {
        return view('livewire.family-budget', [
            'family' => $this->family,
            'budgets' => $this->budgets,
            'totals' => $this->totals,
        ]);
    }
}
