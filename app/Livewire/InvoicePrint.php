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

    public bool $showPaymentSummary = true;

    public bool $showPaymentMethod = true;

    public bool $showMergeReport = true;

    public array $mergeReport = [];

    public function mount(int $id): void
    {
        $this->invoice = Invoice::with(['company', 'items', 'payments'])
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        $this->items = $this->invoice->items;
        $this->payments = $this->invoice->payments->sortBy('payment_date');
        $this->showPaymentSummary = (bool) $this->invoice->show_payment_summary;
        $this->showPaymentMethod = (bool) $this->invoice->show_payment_method;

        $mergeIds = array_map('intval', session("invoice_merge_".auth()->id(), []));

        if (! empty($mergeIds)) {
            $this->mergeReport = Invoice::with('payments')
                ->where('user_id', auth()->id())
                ->whereIn('id', $mergeIds)
                ->get()
                ->map(fn ($inv) => [
                    'inv_number' => $inv->inv_number,
                    'client_name' => $inv->client_name,
                    'grand_total' => $inv->grand_total,
                    'total_paid' => $inv->total_paid,
                    'remaining' => $inv->remaining,
                    'payments' => $inv->payments->sortBy('payment_date')->values(),
                ])
                ->values()
                ->all();
        }
    }

    public function render()
    {
        return view('livewire.invoice-print');
    }
}
