<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Company;
use App\Models\Invoice;
use Livewire\Component;

final class InvoiceForm extends Component
{
    public ?int $editingId = null;

    public int $companyId = 0;

    public string $clientName = '';

    public string $clientAddress = '';

    public string $clientEmail = '';

    public string $invNumber = '';

    public string $status = 'unpaid';

    public string $workStatus = 'on_progress';

    public ?string $internalDeadline = null;

    public string $dateIssue = '';

    public string $dateDue = '';

    public float $taxRate = 0;

    public array $items = [];

    public array $companies = [];

    public function mount(?int $id = null): void
    {
        $this->companies = Company::where('user_id', auth()->id())->get()->toArray();
        $this->dateIssue = now()->format('Y-m-d');
        $this->dateDue = now()->addDays(7)->format('Y-m-d');
        $this->items = [['description' => '', 'qty' => 1, 'price' => 0]];

        if ($id) {
            $invoice = Invoice::where('user_id', auth()->id())->with('items')->findOrFail($id);
            $this->editingId = $invoice->id;
            $this->companyId = $invoice->company_id;
            $this->clientName = $invoice->client_name ?? '';
            $this->clientAddress = $invoice->client_address ?? '';
            $this->clientEmail = $invoice->client_email ?? '';
            $this->invNumber = $invoice->inv_number;
            $this->status = $invoice->status;
            $this->workStatus = $invoice->work_status;
            $this->internalDeadline = $invoice->internal_deadline?->format('Y-m-d');
            $this->dateIssue = $invoice->date_issue?->format('Y-m-d') ?? now()->format('Y-m-d');
            $this->dateDue = $invoice->date_due?->format('Y-m-d') ?? now()->addDays(7)->format('Y-m-d');
            $this->taxRate = (float) $invoice->tax_rate;
            $this->items = $invoice->items->map(fn ($item) => [
                'description' => $item->description,
                'qty' => (float) $item->qty,
                'price' => (float) $item->price,
            ])->toArray();
        } elseif (! empty($this->companies)) {
            $this->companyId = $this->companies[0]['id'];
            $this->generateNumber();
        }
    }

    public function updatedCompanyId(): void
    {
        $this->generateNumber();
    }

    public function generateNumber(): void
    {
        $company = collect($this->companies)->firstWhere('id', $this->companyId);
        if (! $company) {
            return;
        }

        $initial = strtoupper(substr($company['name'], 0, 1));
        $dateStr = now()->format('ymd');
        $prefix = "INV-{$initial}{$dateStr}";

        $lastInvoice = Invoice::where('user_id', auth()->id())
            ->where('inv_number', 'like', "{$prefix}-%")
            ->orderByDesc('inv_number')
            ->first();

        if ($lastInvoice) {
            $lastSeq = (int) substr($lastInvoice->inv_number, -2);
            $seq = str_pad($lastSeq + 1, 2, '0', STR_PAD_LEFT);
        } else {
            $seq = '01';
        }

        $this->invNumber = "{$prefix}-{$seq}";
    }

    public function addRow(): void
    {
        $this->items[] = ['description' => '', 'qty' => 1, 'price' => 0];
    }

    public function removeRow(int $index): void
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function getSubtotalProperty(): float
    {
        return array_reduce($this->items, fn ($carry, $item) => $carry + ($item['qty'] * $item['price']), 0);
    }

    public function getTaxAmountProperty(): float
    {
        return $this->subtotal * $this->taxRate / 100;
    }

    public function getGrandTotalProperty(): float
    {
        return $this->subtotal + $this->taxAmount;
    }

    public function save(): void
    {
        $this->validate([
            'companyId' => 'required|exists:companies,id',
            'clientName' => 'required|string|max:255',
            'clientEmail' => 'nullable|email|max:255',
            'invNumber' => 'required|string|max:50',
            'status' => 'required|in:unpaid,paid',
            'workStatus' => 'required|in:on_progress,finished',
            'dateIssue' => 'required|date',
            'dateDue' => 'required|date',
            'internalDeadline' => 'nullable|date',
            'taxRate' => 'required|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $data = [
            'user_id' => auth()->id(),
            'company_id' => $this->companyId,
            'client_name' => $this->clientName,
            'client_address' => $this->clientAddress,
            'client_email' => $this->clientEmail,
            'inv_number' => $this->invNumber,
            'status' => $this->status,
            'work_status' => $this->workStatus,
            'internal_deadline' => $this->internalDeadline ?: null,
            'date_issue' => $this->dateIssue,
            'date_due' => $this->dateDue,
            'tax_rate' => $this->taxRate,
            'tax_amount' => $this->taxAmount,
            'grand_total' => $this->grandTotal,
        ];

        if ($this->editingId) {
            $invoice = Invoice::where('user_id', auth()->id())->findOrFail($this->editingId);
            $invoice->update($data);
            $invoice->items()->delete();
        } else {
            $invoice = Invoice::create($data);
        }

        foreach ($this->items as $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'qty' => $item['qty'],
                'price' => $item['price'],
                'total' => $item['qty'] * $item['price'],
            ]);
        }

        $this->dispatch('invoiceSaved');
        $this->redirect(route('invoices'));
    }

    public function render()
    {
        return view('livewire.invoice-form');
    }
}
