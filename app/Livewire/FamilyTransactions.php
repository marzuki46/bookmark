<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Family;
use App\Models\FamilyCategory;
use App\Models\FamilyTransaction;
use Livewire\Component;
use Livewire\WithPagination;

final class FamilyTransactions extends Component
{
    use WithPagination;

    public ?int $familyId = null;

    public string $search = '';

    public string $filterType = 'all';

    public ?int $filterCategory = null;

    public string $filterPayer = 'all';

    public string $month = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $formType = 'expense';

    public ?int $formCategoryId = null;

    public string $formAmount = '';

    public string $formDescription = '';

    public string $formDate = '';

    public string $formPaymentMethod = '';

    public string $formPayer = 'shared';

    public string $formNotes = '';

    public bool $showCategoryModal = false;

    public ?int $editingCategoryId = null;

    public string $catFormName = '';

    public string $catFormType = 'expense';

    public string $catFormIcon = '💳';

    public string $catFormColor = '#6366f1';

    public string $statusMessage = '';

    public string $statusType = 'success';

    protected string $paginationTheme = 'tailwind';

    public function mount(?int $familyId = null): void
    {
        $this->familyId = $familyId;
        $this->month = now()->format('Y-m');
        $this->formDate = now()->format('Y-m-d');
    }

    public function getFamilyProperty(): ?Family
    {
        if ($this->familyId) {
            abort_unless(auth()->user()?->is_admin === true, 403);

            return Family::query()->find($this->familyId);
        }

        return auth()->user()->family();
    }

    public function getCategoriesProperty()
    {
        $family = $this->family;

        return $family
            ? FamilyCategory::forFamily($family->id)->orderBy('type')->orderBy('name')->get()
            : collect();
    }

    public function getTransactionsProperty()
    {
        $family = $this->family;
        if (! $family) {
            return collect();
        }

        $query = FamilyTransaction::with('category', 'user')->forFamily($family->id);

        if ($this->month) {
            $query->where('date', 'like', $this->month.'%');
        }

        if ($this->search) {
            $query->where(fn ($q) => $q->where('description', 'like', "%{$this->search}%")
                ->orWhere('notes', 'like', "%{$this->search}%"));
        }

        if ($this->filterType !== 'all') {
            $query->where('type', $this->filterType);
        }

        if ($this->filterCategory) {
            $query->where('category_id', $this->filterCategory);
        }

        if ($this->filterPayer !== 'all') {
            $query->where('payer', $this->filterPayer);
        }

        return $query->latest('date')->latest('id')->paginate(15);
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

    public function updatedFilterPayer(): void
    {
        $this->resetPage();
    }

    public function updatedMonth(): void
    {
        $this->resetPage();
    }

    public function openCreate(string $type = 'expense'): void
    {
        $this->resetForm();
        $this->formType = $type;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $family = $this->family;
        $tx = FamilyTransaction::forFamily($family?->id ?? 0)->findOrFail($id);
        $this->editingId = $id;
        $this->formType = $tx->type;
        $this->formCategoryId = $tx->category_id;
        $this->formAmount = (string) $tx->amount;
        $this->formDescription = $tx->description;
        $this->formDate = $tx->date->format('Y-m-d');
        $this->formPaymentMethod = $tx->payment_method ?? '';
        $this->formPayer = $tx->payer ?? 'shared';
        $this->formNotes = $tx->notes ?? '';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate([
            'formType' => 'required|in:income,expense',
            'formCategoryId' => 'nullable|exists:family_categories,id',
            'formAmount' => 'required|numeric|min:0.01',
            'formDescription' => 'required|string|max:500',
            'formDate' => 'required|date',
            'formPaymentMethod' => 'nullable|string|max:100',
            'formPayer' => 'required|in:husband,wife,shared',
            'formNotes' => 'nullable|string|max:1000',
        ]);

        $family = $this->family;
        if (! $family) {
            return;
        }

        $data = [
            'family_id' => $family->id,
            'user_id' => auth()->id(),
            'type' => $this->formType,
            'category_id' => $this->formCategoryId ?: null,
            'amount' => $this->formAmount,
            'description' => $this->formDescription,
            'date' => $this->formDate,
            'payment_method' => $this->formPaymentMethod ?: null,
            'payer' => $this->formPayer,
            'notes' => $this->formNotes ?: null,
        ];

        if ($this->editingId) {
            FamilyTransaction::forFamily($family->id)->findOrFail($this->editingId)->update($data);
            $this->statusMessage = 'Transaksi berhasil diperbarui.';
        } else {
            FamilyTransaction::create($data);
            $this->statusMessage = 'Transaksi berhasil ditambahkan.';
        }

        $this->statusType = 'success';
        $this->closeModal();
    }

    public function delete(int $id): void
    {
        $family = $this->family;
        FamilyTransaction::forFamily($family?->id ?? 0)->findOrFail($id)->delete();
        $this->statusMessage = 'Transaksi berhasil dihapus.';
        $this->statusType = 'success';
    }

    public function openCreateCategory(string $type = 'expense'): void
    {
        $this->editingCategoryId = null;
        $this->catFormName = '';
        $this->catFormType = $type;
        $this->catFormIcon = '💳';
        $this->catFormColor = '#6366f1';
        $this->showCategoryModal = true;
    }

    public function openEditCategory(int $id): void
    {
        $family = $this->family;
        $cat = FamilyCategory::forFamily($family?->id ?? 0)->findOrFail($id);
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

        $family = $this->family;
        if (! $family) {
            return;
        }

        $data = [
            'family_id' => $family->id,
            'name' => $this->catFormName,
            'type' => $this->catFormType,
            'icon' => $this->catFormIcon,
            'color' => $this->catFormColor,
        ];

        if ($this->editingCategoryId) {
            FamilyCategory::forFamily($family->id)->findOrFail($this->editingCategoryId)->update($data);
            $this->statusMessage = 'Kategori berhasil diperbarui.';
        } else {
            FamilyCategory::create($data);
            $this->statusMessage = 'Kategori berhasil ditambahkan.';
        }

        $this->statusType = 'success';
        $this->closeCategoryModal();
    }

    public function deleteCategory(int $id): void
    {
        $family = $this->family;
        $cat = FamilyCategory::forFamily($family?->id ?? 0)->findOrFail($id);

        if ($cat->is_system) {
            $this->statusMessage = 'Tidak bisa menghapus kategori sistem.';
            $this->statusType = 'error';

            return;
        }

        FamilyTransaction::where('category_id', $id)->update(['category_id' => null]);
        $cat->delete();
        $this->statusMessage = 'Kategori berhasil dihapus.';
        $this->statusType = 'success';
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    public function render()
    {
        return view('livewire.family-transactions', [
            'family' => $this->family,
            'transactions' => $this->transactions,
            'categories' => $this->categories,
        ]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->formType = 'expense';
        $this->formCategoryId = null;
        $this->formAmount = '';
        $this->formDescription = '';
        $this->formDate = now()->format('Y-m-d');
        $this->formPaymentMethod = '';
        $this->formPayer = 'shared';
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
