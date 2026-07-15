<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Invoice;
use Livewire\Component;

final class InvoicePrint extends Component
{
    public ?Invoice $invoice = null;

    public $items = [];

    public $payments = [];

    public function mount(int $id): void
    {
        $this->invoice = Invoice::with(['company', 'items', 'payments'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        $this->items = $this->invoice->items;
        $this->payments = $this->invoice->payments->sortBy('payment_date');
    }

    public function render()
    {
        return view('livewire.invoice-print');
    }
}
