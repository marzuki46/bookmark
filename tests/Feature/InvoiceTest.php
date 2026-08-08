<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\InvoiceDashboard;
use App\Livewire\InvoiceForm;
use App\Livewire\InvoicePrint;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['setup_completed' => true]);
    }

    private function company(User $user): Company
    {
        return Company::create([
            'user_id' => $user->id,
            'name' => 'PT Contoh',
        ]);
    }

    private function invoice(User $user, Company $company, array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'inv_number' => 'INV-T260808-01',
            'client_name' => 'Klien A',
            'date_issue' => now(),
            'date_due' => now()->addDays(7),
            'status' => 'partial',
            'work_status' => 'on_progress',
            'tax_rate' => 0,
            'tax_amount' => 0,
            'grand_total' => 1000,
        ], $overrides));
    }

    public function test_authenticated_user_can_visit_invoice_pages(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get(route('invoices'))->assertOk();
        $this->actingAs($user)->get(route('invoices.create'))->assertOk();
    }

    public function test_print_page_does_not_show_print_and_back_buttons(): void
    {
        $user = $this->user();
        $company = $this->company($user);
        $invoice = $this->invoice($user, $company);
        $invoice->items()->create(['description' => 'Jasa', 'qty' => 1, 'price' => 1000, 'total' => 1000]);

        $this->actingAs($user)
            ->get(route('invoices.print', $invoice->id))
            ->assertOk()
            ->assertDontSee('Cetak Dokumen')
            ->assertDontSee('>Kembali');
    }

    public function test_print_summary_is_visible_by_default(): void
    {
        $user = $this->user();
        $company = $this->company($user);
        $invoice = $this->invoice($user, $company);
        $invoice->items()->create(['description' => 'Jasa', 'qty' => 1, 'price' => 1000, 'total' => 1000]);

        Livewire::actingAs($user)
            ->test(InvoicePrint::class, ['id' => $invoice->id])
            ->assertSet('showPaymentSummary', true)
            ->assertSee('SISA TAGIHAN')
            ->assertSee('Sudah Dibayar');
    }

    public function test_print_summary_can_be_hidden(): void
    {
        $user = $this->user();
        $company = $this->company($user);
        $invoice = $this->invoice($user, $company);
        $invoice->items()->create(['description' => 'Jasa', 'qty' => 1, 'price' => 1000, 'total' => 1000]);

        Livewire::actingAs($user)
            ->test(InvoicePrint::class, ['id' => $invoice->id])
            ->set('showPaymentSummary', false)
            ->assertDontSee('SISA TAGIHAN')
            ->assertDontSee('- Rp 1.000');
    }

    public function test_invoice_form_saves_invoice_and_items(): void
    {
        $user = $this->user();
        $company = $this->company($user);

        Livewire::actingAs($user)
            ->test(InvoiceForm::class)
            ->set('companyId', $company->id)
            ->set('clientName', 'Klien B')
            ->set('invNumber', 'INV-T260808-02')
            ->set('items', [['description' => 'Jasa Website', 'qty' => 2, 'price' => 500]])
            ->call('save');

        $this->assertDatabaseHas('invoices', ['user_id' => $user->id, 'client_name' => 'Klien B', 'grand_total' => 1000]);
        $this->assertDatabaseHas('invoice_items', ['description' => 'Jasa Website', 'total' => 1000]);
    }

    public function test_invoice_number_generates_next_sequence(): void
    {
        $user = $this->user();
        $company = $this->company($user);
        $this->invoice($user, $company, ['inv_number' => 'INV-P260808-01']);

        Livewire::actingAs($user)
            ->test(InvoiceForm::class)
            ->call('generateNumber')
            ->assertSet('invNumber', 'INV-P260808-02');
    }

    public function test_delete_invoice_cascades_items_and_payments(): void
    {
        $user = $this->user();
        $company = $this->company($user);
        $invoice = $this->invoice($user, $company);
        $invoice->items()->create(['description' => 'Jasa', 'qty' => 1, 'price' => 1000, 'total' => 1000]);
        $invoice->payments()->create(['amount' => 500, 'payment_date' => now()]);

        Livewire::actingAs($user)
            ->test(InvoiceDashboard::class)
            ->call('deleteInvoice', $invoice->id);

        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseMissing('invoice_items', ['invoice_id' => $invoice->id]);
        $this->assertDatabaseMissing('payments', ['invoice_id' => $invoice->id]);
    }
}
