<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Family;
use App\Models\FamilyBudget;
use App\Models\FamilyCategory;
use App\Models\FamilyDebt;
use App\Models\FamilyGoal;
use App\Models\FamilyTransaction;
use App\Models\Subscription;
use App\Models\User;
use App\Services\FamilyAIService;
use Livewire\Component;
use Livewire\WithPagination;

final class UserFinances extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public ?int $userId = null;

    public ?int $familyId = null;

    public ?int $editingMemberId = null;

    public string $memberRelationship = 'adult';

    public bool $memberCanViewIncome = true;

    public bool $memberCanViewExpense = true;

    public bool $memberCanViewDebts = true;

    public string $statusMessage = '';

    public string $section = 'ringkasan';

    public string $month = '';

    public string $familySearch = '';

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function updatedFamilySearch(): void
    {
        $this->resetPage();
        $this->familyId = null;
        $this->userId = null;
        $this->editingMemberId = null;
    }

    public function updatedFamilyId(): void
    {
        $this->editingMemberId = null;
        $this->statusMessage = '';
        $this->section = 'ringkasan';
    }

    public function selectFamily(int $familyId): void
    {
        $this->familyId = $familyId;
        $this->userId = null;
        $this->editingMemberId = null;
        $this->statusMessage = '';
        $this->section = 'ringkasan';
    }

    public function backToList(): void
    {
        $this->familyId = null;
        $this->userId = null;
        $this->editingMemberId = null;
        $this->statusMessage = '';
        $this->section = 'ringkasan';
    }

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

    public function getFamiliesProperty()
    {
        return Family::query()
            ->with(['owner:id,name,email', 'members.user:id,name,email'])
            ->withCount('members')
            ->when($this->familySearch !== '', fn ($q) => $q->where('name', 'like', "%{$this->familySearch}%"))
            ->orderBy('name')
            ->paginate(20);
    }

    private function family(): ?Family
    {
        if ($this->familyId) {
            return Family::query()
                ->with(['owner:id,name,email', 'members.user:id,name,email'])
                ->find($this->familyId);
        }

        $user = User::query()->find($this->userId);

        return $user?->family();
    }

    public function getLicenseProperty(): ?Subscription
    {
        $family = $this->family();

        return $family
            ? Subscription::query()->with('plan')->where('family_id', $family->id)->latest('id')->first()
            : null;
    }

    public function editMember(int $memberUserId): void
    {
        $this->authorizeAdmin();

        $member = $this->family()?->members()->where('user_id', $memberUserId)->firstOrFail();
        abort_if($member->role === 'owner', 422, 'Pemilik keluarga tidak dapat diubah dari menu anggota.');

        $visibility = $member->visibility ?? [];
        $this->editingMemberId = $member->user_id;
        $this->memberRelationship = $member->relationship ?? 'adult';
        $this->memberCanViewIncome = (bool) ($visibility['income'] ?? true);
        $this->memberCanViewExpense = (bool) ($visibility['expense'] ?? true);
        $this->memberCanViewDebts = (bool) ($visibility['debts'] ?? true);
    }

    public function saveMemberSettings(): void
    {
        $this->authorizeAdmin();

        $data = $this->validate([
            'editingMemberId' => ['required', 'integer'],
            'memberRelationship' => ['required', 'in:adult,child'],
            'memberCanViewIncome' => ['boolean'],
            'memberCanViewExpense' => ['boolean'],
            'memberCanViewDebts' => ['boolean'],
        ]);

        $member = $this->family()?->members()->where('user_id', $data['editingMemberId'])->firstOrFail();
        abort_if($member->role === 'owner', 422, 'Pemilik keluarga tidak dapat diubah dari menu anggota.');

        $member->update([
            'relationship' => $data['memberRelationship'],
            'visibility' => [
                'income' => (bool) $data['memberCanViewIncome'],
                'expense' => (bool) $data['memberCanViewExpense'],
                'debts' => (bool) $data['memberCanViewDebts'],
            ],
        ]);

        $this->editingMemberId = null;
        $this->statusMessage = 'Permission anggota diperbarui.';
    }

    /**
     * One row per month: label + income + expense, for the SVG bar pair.
     */
    public function getMonthlySeriesProperty(): array
    {
        $family = $this->family();
        if (! $family) {
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
        if (! $family) {
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
        if (! $family) {
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
        if (! $family) {
            return null;
        }

        return app(FamilyAIService::class)->healthScore($family);
    }

    public function getAnomaliesProperty(): array
    {
        $family = $this->family();
        if (! $family) {
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
        if (! $family) {
            return collect();
        }

        return FamilyTransaction::forFamily($family->id)
            ->with(['category', 'user'])
            ->latest('date')
            ->limit(10)
            ->get();
    }

    public function getTransactionsProperty()
    {
        $family = $this->family();
        if (! $family) {
            return collect();
        }

        $query = FamilyTransaction::forFamily($family->id)
            ->with(['category', 'user'])
            ->latest('date');

        if (preg_match('/^\d{4}-\d{2}$/', $this->month)) {
            $query->whereYear('date', substr($this->month, 0, 4))
                ->whereMonth('date', (int) substr($this->month, 5, 2));
        } else {
            $query->whereYear('date', now()->year)->whereMonth('date', now()->month);
        }

        return $query->limit(100)->get();
    }

    public function getBudgetRowsProperty(): array
    {
        $family = $this->family();
        if (! $family) {
            return [];
        }

        $year = (int) substr($this->month, 0, 4);
        $mon = (int) substr($this->month, 5, 2);

        $budgets = FamilyBudget::with('category')->forFamily($family->id)
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
                'category' => $cat->name,
                'amount' => $amount,
                'spent' => $spent,
                'remaining' => $amount - $spent,
                'percent' => $amount > 0 ? min(100, round($spent / $amount * 100)) : 0,
                'overspent' => $amount > 0 && $spent > $amount,
            ];
        }

        return $result;
    }

    public function getGoalRowsProperty()
    {
        $family = $this->family();
        if (! $family) {
            return collect();
        }

        return FamilyGoal::forFamily($family->id)
            ->whereIn('status', ['active', 'completed'])
            ->orderByRaw("case when status = 'active' then 0 else 1 end")
            ->orderBy('priority')
            ->limit(50)
            ->get(['id', 'name', 'type', 'target_amount', 'current_amount', 'monthly_allocation', 'deadline', 'icon', 'color', 'status']);
    }

    public function getDebtRowsProperty()
    {
        $family = $this->family();
        if (! $family) {
            return collect();
        }

        return FamilyDebt::forFamily($family->id)
            ->orderByRaw("case when status = 'settled' then 1 else 0 end")
            ->orderBy('due_date')
            ->limit(100)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.user-finances', [
            'users' => $this->users,
            'families' => $this->families,
            'selectedFamily' => $this->family(),
            'license' => $this->license,
            'monthlySeries' => $this->monthlySeries,
            'categoryBreakdown' => $this->categoryBreakdown,
            'debts' => $this->debts,
            'health' => $this->health,
            'anomalies' => $this->anomalies,
            'recentTransactions' => $this->recentTransactions,
            'transactions' => $this->transactions,
            'budgetRows' => $this->budgetRows,
            'goalRows' => $this->goalRows,
            'debtRows' => $this->debtRows,
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->is_admin === true, 403);
    }
}
