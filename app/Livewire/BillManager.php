<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Bill;
use Livewire\Component;

final class BillManager extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $formDescription = '';

    public float $formAmount = 0;

    public string $formDueDate = '';

    public string $formCategory = 'Lainnya';

    public string $formStatus = 'unpaid';

    public const CATEGORIES = ['Server', 'Domain', 'Tools', 'Internet', 'Lainnya'];

    public function mount(): void
    {
        $this->formDueDate = now()->format('Y-m-d');
    }

    public function getBillsProperty()
    {
        return Bill::where('user_id', auth()->id())
            ->orderBy('due_date')
            ->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $bill = Bill::where('user_id', auth()->id())->findOrFail($id);

        $this->editingId = $bill->id;
        $this->formDescription = $bill->description;
        $this->formAmount = (float) $bill->amount;
        $this->formDueDate = $bill->due_date->format('Y-m-d');
        $this->formCategory = $bill->category;
        $this->formStatus = $bill->status;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate([
            'formDescription' => 'required|string|max:255',
            'formAmount' => 'required|numeric|min:0',
            'formDueDate' => 'required|date',
            'formCategory' => 'required|in:Server,Domain,Tools,Internet,Lainnya',
            'formStatus' => 'required|in:unpaid,paid',
        ]);

        $data = [
            'description' => $this->formDescription,
            'amount' => $this->formAmount,
            'due_date' => $this->formDueDate,
            'category' => $this->formCategory,
            'status' => $this->formStatus,
        ];

        if ($this->editingId) {
            Bill::where('user_id', auth()->id())->findOrFail($this->editingId)->update($data);
        } else {
            Bill::create(array_merge($data, ['user_id' => auth()->id()]));
        }

        $this->closeForm();
    }

    public function payBill(int $id): void
    {
        Bill::where('user_id', auth()->id())->findOrFail($id)->update(['status' => 'paid']);
    }

    public function deleteBill(int $id): void
    {
        Bill::where('user_id', auth()->id())->findOrFail($id)->delete();
    }

    public function render()
    {
        return view('livewire.bill-manager');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->formDescription = '';
        $this->formAmount = 0;
        $this->formDueDate = now()->format('Y-m-d');
        $this->formCategory = 'Lainnya';
        $this->formStatus = 'unpaid';
    }
}
