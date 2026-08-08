<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Family;
use App\Models\FamilyDebt;
use App\Services\FamilyAllocationService;
use Livewire\Component;

final class FamilyDebts extends Component
{
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $formName = '';

    public string $formType = 'payable';

    public string $formAmount = '';

    public string $formPaidAmount = '0';

    public string $formInterestRate = '';

    public string $formInstallment = '';

    public string $formDueDate = '';

    public string $formNotes = '';

    public string $strategy = 'avalanche';

    public array $payAmount = [];

    public string $statusMessage = '';

    public string $statusType = 'success';

    public function getFamilyProperty(): ?Family
    {
        return auth()->user()->family();
    }

    public function getDebtsProperty()
    {
        $family = $this->family;
        if (! $family) {
            return collect();
        }

        return FamilyDebt::forFamily($family->id)->where('status', '!=', 'settled')->orderBy('status')->orderBy('due_date')->get();
    }

    public function getSettledDebtsProperty()
    {
        $family = $this->family;
        if (! $family) {
            return collect();
        }

        return FamilyDebt::forFamily($family->id)->where('status', 'settled')->latest()->get();
    }

    public function getPayoffOrderProperty(): array
    {
        $family = $this->family;
        if (! $family) {
            return [];
        }

        return (new FamilyAllocationService)->debtPayoffOrder($family, $this->strategy);
    }

    public function getProjectionProperty(): ?array
    {
        $family = $this->family;
        if (! $family) {
            return null;
        }

        return (new FamilyAllocationService)->debtFreeProjection($family);
    }

    public function updatedStrategy(): void
    {
        // recompute view
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->formName = '';
        $this->formType = 'payable';
        $this->formAmount = '';
        $this->formPaidAmount = '0';
        $this->formInterestRate = '';
        $this->formInstallment = '';
        $this->formDueDate = '';
        $this->formNotes = '';
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $family = $this->family;
        $debt = FamilyDebt::forFamily($family?->id ?? 0)->findOrFail($id);
        $this->editingId = $id;
        $this->formName = $debt->name;
        $this->formType = $debt->type;
        $this->formAmount = (string) $debt->amount;
        $this->formPaidAmount = (string) $debt->paid_amount;
        $this->formInterestRate = $debt->interest_rate !== null ? (string) $debt->interest_rate : '';
        $this->formInstallment = $debt->installment !== null ? (string) $debt->installment : '';
        $this->formDueDate = $debt->due_date?->format('Y-m-d') ?? '';
        $this->formNotes = $debt->notes ?? '';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingId = null;
        $this->clearValidation();
    }

    public function save(): void
    {
        $this->validate([
            'formName' => 'required|string|max:150',
            'formType' => 'required|in:payable,receivable',
            'formAmount' => 'required|numeric|min:0.01',
            'formPaidAmount' => 'required|numeric|min:0',
            'formInterestRate' => 'nullable|numeric|min:0|max:100',
            'formInstallment' => 'nullable|numeric|min:0',
            'formDueDate' => 'nullable|date',
        ]);

        $family = $this->family;
        if (! $family) {
            return;
        }

        $data = [
            'family_id' => $family->id,
            'name' => $this->formName,
            'type' => $this->formType,
            'amount' => $this->formAmount,
            'paid_amount' => $this->formPaidAmount,
            'interest_rate' => $this->formInterestRate !== '' ? $this->formInterestRate : null,
            'installment' => $this->formInstallment !== '' ? $this->formInstallment : null,
            'due_date' => $this->formDueDate ?: null,
            'notes' => $this->formNotes ?: null,
            'status' => (float) $this->formPaidAmount >= (float) $this->formAmount ? 'settled' : ((float) $this->formPaidAmount > 0 ? 'partial' : 'open'),
        ];

        if ($this->editingId) {
            FamilyDebt::forFamily($family->id)->findOrFail($this->editingId)->update($data);
            $this->statusMessage = 'Hutang berhasil diperbarui.';
        } else {
            FamilyDebt::create($data);
            $this->statusMessage = 'Hutang berhasil ditambahkan.';
        }

        $this->statusType = 'success';
        $this->closeModal();
    }

    public function recordPayment(int $id, string $amount): void
    {
        $family = $this->family;
        $debt = FamilyDebt::forFamily($family?->id ?? 0)->findOrFail($id);
        $amount = (float) $amount;

        if ($amount <= 0) {
            return;
        }

        $newPaid = (float) $debt->paid_amount + $amount;
        $debt->update([
            'paid_amount' => $newPaid,
            'status' => $newPaid >= (float) $debt->amount ? 'settled' : 'partial',
        ]);

        $this->statusMessage = 'Pembayaran tercatat: '.$debt->name.'.';
        $this->statusType = 'success';
    }

    public function delete(int $id): void
    {
        $family = $this->family;
        FamilyDebt::forFamily($family?->id ?? 0)->findOrFail($id)->delete();
        $this->statusMessage = 'Hutang berhasil dihapus.';
        $this->statusType = 'success';
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    public function render()
    {
        return view('livewire.family-debts', [
            'family' => $this->family,
            'debts' => $this->debts,
            'settledDebts' => $this->settledDebts,
            'payoffOrder' => $this->payoffOrder,
            'projection' => $this->projection,
        ]);
    }
}
