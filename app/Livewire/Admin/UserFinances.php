<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Family;
use App\Models\FamilyDebt;
use App\Models\FamilyTransaction;
use App\Models\User;
use App\Services\FamilyAIService;
use Livewire\Component;

final class UserFinances extends Component
{
    public ?int $userId = null;

    public function getUsersProperty()
    {
        return User::query()
            ->with('familyMemberships')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'has_family' => $u->family() !== null,
            ]);
    }

    private function family(): ?Family
    {
        $user = User::query()->find($this->userId);

        return $user?->family();
    }

    /**
     * One row per month: label + income + expense, for the SVG bar pair.
     */
    public function getMonthlySeriesProperty(): array
    {
        $family = $this->family();
        if (! $family || ! $this->userId) {
            return [];
        }

        $byMonth = FamilyTransaction::forFamily($family->id)
            ->where('date', '>=', now()->subMonths(11)->startOfMonth())
            ->get(['type', 'amount', 'date'])
            ->groupBy(fn ($t) => $t->date->format('Y-m'));

        $series = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $key = $month->format('Y-m');
            $rows = $byMonth->get($key, collect());

            $series[] = [
                'label' => $month->translatedFormat('M'),
                'income' => (float) $rows->where('type', 'income')->sum('amount'),
                'expense' => (float) $rows->where('type', 'expense')->sum('amount'),
            ];
        }

        return $series;
    }

    public function getCategoryBreakdownProperty(): array
    {
        $family = $this->family();
        if (! $family || ! $this->userId) {
            return [];
        }

        return FamilyTransaction::forFamily($family->id)
            ->where('type', 'expense')
            ->where('date', '>=', now()->startOfMonth())
            ->with('category')
            ->get()
            ->groupBy(fn ($t) => $t->category?->name ?? '(tanpa kategori)')
            ->map(fn ($rows, string $name) => [
                'name' => $name,
                'total' => (float) $rows->sum('amount'),
                'count' => $rows->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    public function getDebtsProperty(): array
    {
        $family = $this->family();
        if (! $family || ! $this->userId) {
            return ['total' => 0, 'installment' => 0, 'count' => 0];
        }

        $debts = FamilyDebt::forFamily($family->id)
            ->where('type', 'payable')
            ->where('status', '!=', 'settled')
            ->get(['amount', 'paid_amount', 'installment']);

        return [
            'total' => (float) $debts->sum(fn (FamilyDebt $debt) => $debt->remaining),
            'installment' => (float) $debts->sum('installment'),
            'count' => $debts->count(),
        ];
    }

    public function getHealthProperty(): ?array
    {
        $family = $this->family();
        if (! $family || ! $this->userId) {
            return null;
        }

        return app(FamilyAIService::class)->healthScore($family);
    }

    public function getAnomaliesProperty(): array
    {
        $family = $this->family();
        if (! $family || ! $this->userId) {
            return [];
        }

        return FamilyTransaction::forFamily($family->id)
            ->where('date', '>=', now()->startOfMonth())
            ->where(fn ($q) => $q->whereNull('category_id')->orWhere('amount', '<=', 0))
            ->with('user')
            ->latest('date')
            ->limit(10)
            ->get()
            ->map(fn (FamilyTransaction $t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => (float) $t->amount,
                'date' => $t->date->format('d M'),
                'description' => $t->description,
                'user' => $t->user?->name,
                'problem' => $t->category_id === null ? 'tanpa kategori' : 'nominal tidak wajar',
            ])
            ->all();
    }

    public function getRecentTransactionsProperty()
    {
        $family = $this->family();
        if (! $family || ! $this->userId) {
            return collect();
        }

        return FamilyTransaction::forFamily($family->id)
            ->with(['category', 'user'])
            ->latest('date')
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.user-finances', [
            'users' => $this->users,
            'monthlySeries' => $this->monthlySeries,
            'categoryBreakdown' => $this->categoryBreakdown,
            'debts' => $this->debts,
            'health' => $this->health,
            'anomalies' => $this->anomalies,
            'recentTransactions' => $this->recentTransactions,
        ]);
    }
}
