<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Services\FinanceReportService;
use App\Services\FinancialAIService;
use Livewire\Component;
use Livewire\WithPagination;

final class FinancialReport extends Component
{
    use WithPagination;

    // Filters
    public string $search = '';

    public string $filterType = 'all';

    public ?int $filterCategory = null;

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $period = 'this_month';

    // Transaction form
    public bool $showTransactionModal = false;

    public ?int $editingTransactionId = null;

    public string $formType = 'expense';

    public ?int $formCategoryId = null;

    public string $formAmount = '';

    public string $formDescription = '';

    public string $formDate = '';

    public string $formPaymentMethod = '';

    public string $formNotes = '';

    // Category form
    public bool $showCategoryModal = false;

    public ?int $editingCategoryId = null;

    public string $catFormName = '';

    public string $catFormType = 'expense';

    public string $catFormIcon = '💳';

    public string $catFormColor = '#6366f1';

    // AI Query
    public bool $showAiModal = false;

    public string $aiQuery = '';

    public string $aiAnswer = '';

    public bool $aiLoading = false;

    // Status
    public string $statusMessage = '';

    public string $statusType = 'success';

    protected string $paginationTheme = 'tailwind';

    public function rules(): array
    {
        return [
            'formType' => 'required|in:income,expense',
            'formCategoryId' => 'nullable|exists:financial_categories,id',
            'formAmount' => 'required|numeric|min:0.01',
            'formDescription' => 'required|string|max:500',
            'formDate' => 'required|date',
            'formPaymentMethod' => 'nullable|string|max:100',
            'formNotes' => 'nullable|string|max:1000',
        ];
    }

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->formDate = now()->format('Y-m-d');
    }

    // ─── Computed Properties ───

    public function getCategoriesProperty()
    {
        return FinancialCategory::where('user_id', auth()->id())
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    public function getStatsProperty(): array
    {
        return app(FinanceReportService::class)->stats(
            (int) auth()->id(),
            $this->dateFrom ?: null,
            $this->dateTo ?: null,
        );
    }

    public function getMonthlyStatsProperty(): array
    {
        return app(FinanceReportService::class)->monthlySeries((int) auth()->id(), 6);
    }

    public function getCategoryStatsProperty(): array
    {
        $userId = auth()->id();
        $query = FinancialTransaction::where('user_id', $userId);
        $query = $this->applyPeriodFilter($query);

        $expenseByCategory = (clone $query)
            ->where('type', 'expense')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->with('category')
            ->get();

        $incomeByCategory = (clone $query)
            ->where('type', 'income')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->with('category')
            ->get();

        return [
            'expense' => $expenseByCategory,
            'income' => $incomeByCategory,
        ];
    }

    public function getRecentTransactionsProperty()
    {
        $query = FinancialTransaction::with('category')
            ->where('user_id', auth()->id());
        $query = $this->applyPeriodFilter($query);
        $query = $this->applySearchFilter($query);

        return $query->latest('date')->latest('id')->paginate(15);
    }

    public function getIncomeTotalProperty(): float
    {
        return (float) FinancialTransaction::where('user_id', auth()->id())
            ->where('type', 'income')
            ->thisYear()
            ->sum('amount');
    }

    public function getExpenseTotalProperty(): float
    {
        return (float) FinancialTransaction::where('user_id', auth()->id())
            ->where('type', 'expense')
            ->thisYear()
            ->sum('amount');
    }

    // ─── Filters ───

    public function updatedPeriod(): void
    {
        match ($this->period) {
            'today' => $this->dateFrom = $this->dateTo = now()->format('Y-m-d'),
            'yesterday' => $this->dateFrom = $this->dateTo = now()->subDay()->format('Y-m-d'),
            'this_week' => $this->dateFrom = now()->startOfWeek()->format('Y-m-d'),
            'this_month' => $this->dateFrom = now()->startOfMonth()->format('Y-m-d'),
            'last_month' => $this->dateFrom = now()->subMonth()->startOfMonth()->format('Y-m-d'),
            'this_year' => $this->dateFrom = now()->startOfYear()->format('Y-m-d'),
            'custom' => null,
            default => null,
        };
        if ($this->period !== 'custom') {
            $this->dateTo = now()->format('Y-m-d');
        }
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCategory(): void
    {
        $this->resetPage();
    }

    private function applyPeriodFilter($query)
    {
        if ($this->dateFrom && $this->dateTo) {
            return $query->whereBetween('date', [$this->dateFrom, $this->dateTo]);
        }

        return $query->thisMonth();
    }

    private function applySearchFilter($query)
    {
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', "%{$this->search}%")
                    ->orWhere('notes', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterType !== 'all') {
            $query->where('type', $this->filterType);
        }

        if ($this->filterCategory) {
            $query->where('category_id', $this->filterCategory);
        }

        return $query;
    }

    // ─── Transaction CRUD ───

    public function openCreateTransaction(string $type = 'expense'): void
    {
        $this->resetTransactionForm();
        $this->formType = $type;
        $this->showTransactionModal = true;
    }

    public function openEditTransaction(int $id): void
    {
        $tx = FinancialTransaction::where('user_id', auth()->id())->with('category')->findOrFail($id);
        $this->editingTransactionId = $id;
        $this->formType = $tx->type;
        $this->formCategoryId = $tx->category_id;
        $this->formAmount = (string) $tx->amount;
        $this->formDescription = $tx->description;
        $this->formDate = $tx->date->format('Y-m-d');
        $this->formPaymentMethod = $tx->payment_method ?? '';
        $this->formNotes = $tx->notes ?? '';
        $this->showTransactionModal = true;
    }

    public function closeTransactionModal(): void
    {
        $this->showTransactionModal = false;
        $this->resetTransactionForm();
    }

    public function saveTransaction(): void
    {
        $this->validate();

        $data = [
            'user_id' => auth()->id(),
            'type' => $this->formType,
            'category_id' => $this->formCategoryId ?: null,
            'amount' => $this->formAmount,
            'description' => $this->formDescription,
            'date' => $this->formDate,
            'payment_method' => $this->formPaymentMethod ?: null,
            'notes' => $this->formNotes ?: null,
        ];

        if ($this->editingTransactionId) {
            $tx = FinancialTransaction::where('user_id', auth()->id())->findOrFail($this->editingTransactionId);
            $tx->update($data);
            $this->statusMessage = 'Transaksi berhasil diperbarui.';
        } else {
            FinancialTransaction::create($data);
            $this->statusMessage = 'Transaksi berhasil ditambahkan.';
        }

        $this->statusType = 'success';
        $this->closeTransactionModal();
    }

    public function deleteTransaction(int $id): void
    {
        FinancialTransaction::where('user_id', auth()->id())->findOrFail($id)->delete();
        $this->statusMessage = 'Transaksi berhasil dihapus.';
        $this->statusType = 'success';
    }

    // ─── Category CRUD ───

    public function openCreateCategory(string $type = 'expense'): void
    {
        $this->resetCategoryForm();
        $this->catFormType = $type;
        $this->showCategoryModal = true;
    }

    public function openEditCategory(int $id): void
    {
        $cat = FinancialCategory::where('user_id', auth()->id())->findOrFail($id);
        $this->editingCategoryId = $id;
        $this->catFormName = $cat->name;
        $this->catFormType = $cat->type;
        $this->catFormIcon = $cat->icon ?? '💳';
        $this->catFormColor = $cat->color ?? '#6366f1';
        $this->showCategoryModal = true;
    }

    public function closeCategoryModal(): void
    {
        $this->showCategoryModal = false;
        $this->resetCategoryForm();
    }

    public function saveCategory(): void
    {
        $this->validate([
            'catFormName' => 'required|string|max:100',
            'catFormType' => 'required|in:income,expense',
        ]);

        $data = [
            'user_id' => auth()->id(),
            'name' => $this->catFormName,
            'type' => $this->catFormType,
            'icon' => $this->catFormIcon,
            'color' => $this->catFormColor,
        ];

        if ($this->editingCategoryId) {
            FinancialCategory::where('user_id', auth()->id())->findOrFail($this->editingCategoryId)->update($data);
            $this->statusMessage = 'Kategori berhasil diperbarui.';
        } else {
            FinancialCategory::create($data);
            $this->statusMessage = 'Kategori berhasil ditambahkan.';
        }

        $this->statusType = 'success';
        $this->closeCategoryModal();
    }

    public function deleteCategory(int $id): void
    {
        $cat = FinancialCategory::where('user_id', auth()->id())->findOrFail($id);
        if ($cat->is_system) {
            $this->statusMessage = 'Tidak bisa menghapus kategori sistem.';
            $this->statusType = 'error';

            return;
        }
        // Set transactions to uncategorized
        FinancialTransaction::where('category_id', $id)->update(['category_id' => null]);
        $cat->delete();
        $this->statusMessage = 'Kategori berhasil dihapus.';
        $this->statusType = 'success';
    }

    // ─── AI Query ───

    public function askAi(): void
    {
        if (strlen($this->aiQuery) < 3) {
            return;
        }

        $this->aiLoading = true;
        $this->aiAnswer = '';

        try {
            $transactions = FinancialTransaction::with('category')
                ->where('user_id', auth()->id())
                ->latest()
                ->take(200)
                ->get()
                ->toArray();

            $aiService = new FinancialAIService;
            $this->aiAnswer = $aiService->answerQuery($this->aiQuery, $transactions);
        } catch (\Exception $e) {
            $this->aiAnswer = '❌ Error: '.$e->getMessage();
        }

        $this->aiLoading = false;
    }

    public function closeAiModal(): void
    {
        $this->showAiModal = false;
        $this->aiQuery = '';
        $this->aiAnswer = '';
    }

    // ─── Helpers ───

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    public static function formatRupiah(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    // ─── Render ───

    public function render()
    {
        $isFirstTime = FinancialTransaction::where('user_id', auth()->id())->count() === 0;

        return view('livewire.financial-report', [
            'stats' => $this->stats,
            'monthlyStats' => $this->monthlyStats,
            'categoryStats' => $this->categoryStats,
            'transactions' => $this->recentTransactions,
            'categories' => $this->categories,
            'isFirstTime' => $isFirstTime,
        ]);
    }

    // ─── Private ───

    private function resetTransactionForm(): void
    {
        $this->editingTransactionId = null;
        $this->formType = 'expense';
        $this->formCategoryId = null;
        $this->formAmount = '';
        $this->formDescription = '';
        $this->formDate = now()->format('Y-m-d');
        $this->formPaymentMethod = '';
        $this->formNotes = '';
        $this->clearValidation();
    }

    private function resetCategoryForm(): void
    {
        $this->editingCategoryId = null;
        $this->catFormName = '';
        $this->catFormType = 'expense';
        $this->catFormIcon = '💳';
        $this->catFormColor = '#6366f1';
        $this->clearValidation();
    }
}
