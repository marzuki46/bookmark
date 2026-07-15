<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Bill;
use App\Models\Invoice;
use App\Models\Payment;
use Livewire\Component;

final class InvoiceDashboard extends Component
{
    public string $startDate = '';

    public string $endDate = '';

    public bool $showUnpaidModal = false;

    public bool $showBillModal = false;

    public bool $showProjectModal = false;

    public bool $showPaymentModal = false;

    public string $billFilter = 'this_month';

    public int $paymentInvoiceId = 0;

    public string $paymentAmount = '';

    public string $paymentDate = '';

    public string $paymentNote = '';

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->paymentDate = now()->format('Y-m-d');
    }

    public function getStatsProperty(): array
    {
        $invoices = Invoice::where('user_id', auth()->id())
            ->whereBetween('date_issue', [$this->startDate, $this->endDate])
            ->get();

        return [
            'total_invoiced' => $invoices->sum('grand_total'),
            'total_received' => $invoices->sum(fn ($i) => $i->total_paid),
            'total_unpaid' => $invoices->sum(fn ($i) => $i->remaining),
        ];
    }

    public function getInvoicesProperty()
    {
        return Invoice::with(['company', 'payments'])
            ->where('user_id', auth()->id())
            ->whereBetween('date_issue', [$this->startDate, $this->endDate])
            ->latest('date_issue')
            ->get();
    }

    public function getAllUnpaidProperty()
    {
        return Invoice::with(['company', 'payments'])
            ->where('user_id', auth()->id())
            ->where('status', '!=', 'paid')
            ->orderBy('date_issue')
            ->get()
            ->map(fn ($inv) => $inv->only(['id', 'inv_number', 'client_name', 'grand_total', 'total_paid', 'remaining', 'date_issue']));
    }

    public function getUnpaidTotalProperty(): float
    {
        return $this->allUnpaid->sum('remaining');
    }

    public function getUnfinishedProperty()
    {
        return Invoice::with(['company'])
            ->where('user_id', auth()->id())
            ->where('work_status', '!=', 'finished')
            ->orderBy('internal_deadline')
            ->get();
    }

    public function getBillStatsProperty(): array
    {
        $userId = auth()->id();
        $today = now();
        $thisMonth = $today->copy()->startOfMonth()->format('Y-m');
        $nextMonth = $today->copy()->addMonth()->startOfMonth()->format('Y-m');
        $thisYear = $today->format('Y');

        $bills = Bill::where('user_id', $userId)->get();

        return [
            'this_month' => $bills->where('status', 'unpaid')->filter(fn ($b) => $b->due_date->format('Y-m') === $thisMonth)->sum('amount'),
            'next_month' => $bills->where('status', 'unpaid')->filter(fn ($b) => $b->due_date->format('Y-m') === $nextMonth)->sum('amount'),
            'paid_year' => $bills->where('status', 'paid')->filter(fn ($b) => $b->due_date->format('Y') === $thisYear)->sum('amount'),
            'this_year' => $bills->filter(fn ($b) => $b->due_date->format('Y') === $thisYear)->sum('amount'),
        ];
    }

    public function getBillDetailsProperty(): array
    {
        $userId = auth()->id();
        $today = now();
        $thisMonth = $today->copy()->startOfMonth()->format('Y-m');
        $nextMonth = $today->copy()->addMonth()->startOfMonth()->format('Y-m');
        $thisYear = $today->format('Y');

        $bills = Bill::where('user_id', $userId)->orderBy('due_date')->get();

        return [
            'this_month' => $bills->where('status', 'unpaid')->filter(fn ($b) => $b->due_date->format('Y-m') === $thisMonth)->values(),
            'next_month' => $bills->where('status', 'unpaid')->filter(fn ($b) => $b->due_date->format('Y-m') === $nextMonth)->values(),
            'this_year' => $bills->filter(fn ($b) => $b->due_date->format('Y') === $thisYear)->values(),
            'paid_year' => $bills->where('status', 'paid')->filter(fn ($b) => $b->due_date->format('Y') === $thisYear)->values(),
        ];
    }

    public function openPayment(int $invoiceId): void
    {
        $invoice = Invoice::where('user_id', auth()->id())->findOrFail($invoiceId);
        $this->paymentInvoiceId = $invoice->id;
        $this->paymentAmount = '';
        $this->paymentDate = now()->format('Y-m-d');
        $this->paymentNote = '';
        $this->showPaymentModal = true;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->paymentInvoiceId = 0;
        $this->paymentAmount = '';
        $this->paymentNote = '';
    }

    public function addPayment(): void
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:0.01',
            'paymentDate' => 'required|date',
        ]);

        $invoice = Invoice::where('user_id', auth()->id())->findOrFail($this->paymentInvoiceId);

        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $this->paymentAmount,
            'payment_date' => $this->paymentDate,
            'note' => $this->paymentNote,
        ]);

        $totalPaid = $invoice->payments()->sum('amount');
        $newStatus = $totalPaid >= $invoice->grand_total ? 'paid' : 'partial';
        $invoice->update(['status' => $newStatus]);

        $this->closePaymentModal();
    }

    public function toggleWork(int $id): void
    {
        $invoice = Invoice::where('user_id', auth()->id())->findOrFail($id);
        $invoice->update([
            'work_status' => $invoice->work_status === 'finished' ? 'on_progress' : 'finished',
        ]);
    }

    public function deleteInvoice(int $id): void
    {
        $invoice = Invoice::where('user_id', auth()->id())->findOrFail($id);
        $invoice->items()->delete();
        $invoice->payments()->delete();
        $invoice->delete();
    }

    public function render()
    {
        return view('livewire.invoice-dashboard');
    }
}
