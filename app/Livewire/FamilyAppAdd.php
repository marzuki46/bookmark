<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Family;
use App\Models\FamilyCategory;
use App\Models\FamilyTransaction;
use Livewire\Component;

final class FamilyAppAdd extends Component
{
    public string $formType = 'expense';

    public ?int $formCategoryId = null;

    public string $formAmount = '';

    public string $formDescription = '';

    public string $formDate = '';

    public string $formPayer = 'shared';

    public string $statusMessage = '';

    public string $statusType = 'success';

    public function mount(): void
    {
        $this->formDate = now()->format('Y-m-d');
    }

    public function getFamilyProperty(): ?Family
    {
        return auth()->user()->family();
    }

    public function getCategoriesProperty()
    {
        $family = $this->family;
        if (! $family) {
            return collect();
        }

        return FamilyCategory::forFamily($family->id)->where('type', $this->formType)->orderBy('name')->get();
    }

    public function updatedFormType(): void
    {
        $this->formCategoryId = null;
    }

    public function save(): void
    {
        $this->validate([
            'formType' => 'required|in:income,expense',
            'formCategoryId' => 'nullable|exists:family_categories,id',
            'formAmount' => 'required|numeric|min:0.01',
            'formDescription' => 'required|string|max:500',
            'formDate' => 'required|date',
            'formPayer' => 'required|in:husband,wife,shared',
        ]);

        $family = $this->family;
        if (! $family) {
            return;
        }

        FamilyTransaction::create([
            'family_id' => $family->id,
            'user_id' => auth()->id(),
            'type' => $this->formType,
            'category_id' => $this->formCategoryId ?: null,
            'amount' => $this->formAmount,
            'description' => $this->formDescription,
            'date' => $this->formDate,
            'payer' => $this->formPayer,
        ]);

        $this->statusMessage = 'Transaksi berhasil dicatat!';
        $this->statusType = 'success';
        $this->formAmount = '';
        $this->formDescription = '';
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    public function render()
    {
        return view('livewire.family-app-add', [
            'family' => $this->family,
            'categories' => $this->categories,
        ]);
    }
}
