<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Family;
use App\Models\FamilyDebt;
use App\Services\FamilyAllocationService;
use Livewire\Component;

final class FamilyAppDebts extends Component
{
    public bool $showModal = false;

    public array $payAmount = [];

    public string $formName = '';

    public string $formType = 'payable';

    public string $formAmount = '';

    public string $formPaidAmount = '0';

    public string $formInterestRate = '';

    public string $formInstallment = '';

    public string $formDueDate = '';

    public string $formNotes = '';

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

        return FamilyDebt::forFamily($family->id)->where('type', 'payable')->where('status', '!=', 'settled')->orderBy('due_date')->get();
    }

    public function getProjectionProperty(): ?array
    {
        $family = $this->family;
        if (! $family) {
            return null;
        }

        return (new FamilyAllocationService)->debtFreeProjection($family);
    }

    public function openCreate(): void
    {
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

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->clearValidation();
    }

    public function save(): void
    {
        $this->validate([
            'formName' => 'required|string|max:150',
            'formAmount' => 'required|numeric|min:0.01',
            'formPaidAmount' => 'required|numeric|min:0',
        ]);

        $family = $this->family;
        if (! $family) {
            return;
        }

        FamilyDebt::create([
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
        ]);

        $this->statusMessage = 'Hutang berhasil dicatat!';
        $this->statusType = 'success';
        $this->closeModal();
    }

    public function recordPayment(int $id): void
    {
        $family = $this->family;
        $debt = FamilyDebt::forFamily($family?->id ?? 0)->findOrFail($id);
        $amount = (float) ($this->payAmount[$id] ?? 0);

        if ($amount <= 0) {
            return;
        }

        $newPaid = (float) $debt->paid_amount + $amount;
        $debt->update([
            'paid_amount' => $newPaid,
            'status' => $newPaid >= (float) $debt->amount ? 'settled' : 'partial',
        ]);

        $this->statusMessage = 'Pembayaran "'.$debt->name.'" tercatat.';
        $this->statusType = 'success';
        $this->payAmount[$id] = '';
    }

    public function clearStatusMessage(): void
    {
        $this->statusMessage = '';
    }

    public function render()
    {
        return view('livewire.family-app-debts', [
            'family' => $this->family,
            'debts' => $this->debts,
            'projection' => $this->projection,
        ]);
    }
}
