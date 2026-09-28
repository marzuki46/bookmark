<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyCategory;
use App\Models\FamilyDebt;
use App\Models\FamilyGoal;
use App\Models\FamilyMember;
use App\Models\FamilyTransaction;
use App\Models\IncomeSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class FamilyHouseholdApiTest extends TestCase
{
    use RefreshDatabase;

    private User $husband;

    private User $wife;

    private Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        $this->husband = User::factory()->create();
        $this->wife = User::factory()->create();

        $this->family = Family::create([
            'name' => 'Keluarga Uji',
            'owner_user_id' => $this->husband->id,
            'invite_code' => Family::generateInviteCode(),
        ]);

        FamilyMember::create(['family_id' => $this->family->id, 'user_id' => $this->husband->id, 'role' => 'owner']);
        FamilyMember::create(['family_id' => $this->family->id, 'user_id' => $this->wife->id, 'role' => 'member']);

        Sanctum::actingAs($this->husband);
    }

    private function otherFamily(): Family
    {
        $outsider = User::factory()->create();

        $family = Family::create([
            'name' => 'Keluarga Lain',
            'owner_user_id' => $outsider->id,
            'invite_code' => Family::generateInviteCode(),
        ]);

        FamilyMember::create(['family_id' => $family->id, 'user_id' => $outsider->id, 'role' => 'owner']);

        return $family;
    }

    public function test_create_forms_default_missing_type_for_older_app_clients(): void
    {
        $category = $this->postJson("/api/families/{$this->family->id}/categories", [
            'name' => 'Belanja lama',
        ])->assertCreated()->json('data');

        $source = $this->postJson("/api/families/{$this->family->id}/income-sources", [
            'name' => 'Pemasukan lama',
        ])->assertCreated()->json('data');

        $transaction = $this->postJson("/api/families/{$this->family->id}/transactions", [
            'amount' => 25_000,
            'description' => 'Transaksi dari aplikasi lama',
            'date' => now()->toDateString(),
        ])->assertCreated()->json('data');

        $debt = $this->postJson("/api/families/{$this->family->id}/debts", [
            'name' => 'Utang lama',
            'amount' => 100_000,
        ])->assertCreated()->json('data');

        $goal = $this->postJson("/api/families/{$this->family->id}/goals", [
            'name' => 'Target lama',
            'target_amount' => 500_000,
        ])->assertCreated()->json('data');

        $this->assertSame('expense', $category['type']);
        $this->assertSame('other', $source['type']);
        $this->assertSame('expense', $transaction['type']);
        $this->assertSame('payable', $debt['type']);
        $this->assertSame('custom', $goal['type']);
    }

    // â”€â”€ DEBTS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function test_can_create_a_debt(): void
    {
        $response = $this->postJson("/api/families/{$this->family->id}/debts", [
            'name' => 'Kredit motor',
            'type' => 'payable',
            'amount' => 20_000_000,
            'installment' => 1_500_000,
            'due_date' => now()->addMonth()->toDateString(),
        ])->assertStatus(201);

        $this->assertSame(20_000_000.0, (float) $response->json('data.amount'));
        $this->assertSame('open', $response->json('data.status'));
        $this->assertSame(20_000_000.0, (float) $response->json('data.remaining_amount'));
        $this->assertFalse($response->json('data.is_overdue'));
    }

    public function test_payment_moves_status_through_partial_to_settled(): void
    {
        $debt = $this->postJson("/api/families/{$this->family->id}/debts", [
            'name' => 'Pinjaman discernment',
            'type' => 'payable',
            'amount' => 1_000_000,
        ])->json('data');

        $partial = $this->postJson("/api/families/{$this->family->id}/debts/{$debt['id']}/pay", [
            'amount' => 400_000,
        ])->assertOk();

        $this->assertSame('partial', $partial->json('data.status'));
        $this->assertSame(600_000.0, (float) $partial->json('data.remaining_amount'));

        $settled = $this->postJson("/api/families/{$this->family->id}/debts/{$debt['id']}/pay", [
            'amount' => 600_000,
        ])->assertOk();

        $this->assertSame('settled', $settled->json('data.status'));
        $this->assertSame(0.0, (float) $settled->json('data.remaining_amount'));
    }

    public function test_payment_cannot_exceed_the_remaining_amount(): void
    {
        $debt = $this->postJson("/api/families/{$this->family->id}/debts", [
            'name' => 'Hutang kecil',
            'type' => 'payable',
            'amount' => 100_000,
        ])->json('data');

        $this->postJson("/api/families/{$this->family->id}/debts/{$debt['id']}/pay", [
            'amount' => 100_001,
        ])->assertStatus(422);

        $this->assertSame(0.0, (float) FamilyDebt::find($debt['id'])->paid_amount);
    }

    public function test_paying_a_settled_debt_is_rejected(): void
    {
        $debt = $this->postJson("/api/families/{$this->family->id}/debts", [
            'name' => 'Lunas',
            'type' => 'payable',
            'amount' => 50_000,
        ])->json('data');

        $this->postJson("/api/families/{$this->family->id}/debts/{$debt['id']}/pay", ['amount' => 50_000])
            ->assertOk();

        $this->postJson("/api/families/{$this->family->id}/debts/{$debt['id']}/pay", ['amount' => 1])
            ->assertStatus(422);
    }

    public function test_amount_cannot_be_lowered_below_paid(): void
    {
        $debt = $this->postJson("/api/families/{$this->family->id}/debts", [
            'name' => 'Turun amount',
            'type' => 'payable',
            'amount' => 1_000_000,
        ])->json('data');

        $this->postJson("/api/families/{$this->family->id}/debts/{$debt['id']}/pay", ['amount' => 600_000])
            ->assertOk();

        $this->patchJson("/api/families/{$this->family->id}/debts/{$debt['id']}", [
            'amount' => 500_000,
        ])->assertStatus(422);
    }

    public function test_overdue_debt_is_flagged(): void
    {
        $response = $this->postJson("/api/families/{$this->family->id}/debts", [
            'name' => 'Telat',
            'type' => 'payable',
            'amount' => 10_000,
            'due_date' => now()->subWeek()->toDateString(),
        ])->assertStatus(201);

        $this->assertTrue($response->json('data.is_overdue'));
    }

    public function test_member_cannot_touch_another_familys_debt(): void
    {
        $other = $this->otherFamily();
        $foreign = FamilyDebt::create([
            'family_id' => $other->id,
            'name' => 'Hutang privasi',
            'type' => 'payable',
            'amount' => 1_000,
        ]);

        $this->getJson("/api/families/{$this->family->id}/debts/{$foreign->id}")->assertStatus(404);
        $this->patchJson("/api/families/{$this->family->id}/debts/{$foreign->id}", ['name' => 'x'])->assertStatus(404);
        $this->deleteJson("/api/families/{$this->family->id}/debts/{$foreign->id}")->assertStatus(404);
        $this->postJson("/api/families/{$this->family->id}/debts/{$foreign->id}/pay", ['amount' => 10])
            ->assertStatus(404);

        $this->assertDatabaseHas('family_debts', ['id' => $foreign->id, 'name' => 'Hutang privasi']);
    }

    public function test_debt_list_excludes_other_families(): void
    {
        $other = $this->otherFamily();
        FamilyDebt::create(['family_id' => $other->id, 'name' => 'Asing', 'type' => 'payable', 'amount' => 1]);
        $this->postJson("/api/families/{$this->family->id}/debts", [
            'name' => 'Sendiri', 'type' => 'payable', 'amount' => 1,
        ])->assertStatus(201);

        $names = collect($this->getJson("/api/families/{$this->family->id}/debts")->json('data'))
            ->pluck('name');

        $this->assertSame(['Sendiri'], $names->all());
    }

    // â”€â”€ INCOME SOURCES â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function test_can_create_income_sources_for_salary_and_business(): void
    {
        $this->postJson("/api/families/{$this->family->id}/income-sources", [
            'name' => 'Gaji Suami', 'type' => 'salary', 'is_default' => true,
        ])->assertStatus(201);

        $this->postJson("/api/families/{$this->family->id}/income-sources", [
            'name' => 'Warung', 'type' => 'business',
        ])->assertStatus(201);

        $this->assertCount(2, $this->family->incomeSources);
        $this->assertTrue($this->family->incomeSources()->where('name', 'Gaji Suami')->first()->is_default);
    }

    public function test_only_one_default_income_source_at_a_time(): void
    {
        $this->postJson("/api/families/{$this->family->id}/income-sources", [
            'name' => 'A', 'type' => 'salary', 'is_default' => true,
        ])->assertStatus(201);

        $this->postJson("/api/families/{$this->family->id}/income-sources", [
            'name' => 'B', 'type' => 'business', 'is_default' => true,
        ])->assertStatus(201);

        $this->assertSame(1, $this->family->incomeSources()->where('is_default', true)->count());
    }

    public function test_income_source_totals_are_reported_for_the_month(): void
    {
        $source = $this->postJson("/api/families/{$this->family->id}/income-sources", [
            'name' => 'Warung', 'type' => 'business',
        ])->json('data');

        foreach ([500_000, 300_000] as $amount) {
            $this->postJson("/api/families/{$this->family->id}/transactions", [
                'type' => 'income',
                'amount' => $amount,
                'description' => 'Omzet',
                'date' => now()->toDateString(),
                'income_source_id' => $source['id'],
            ])->assertStatus(201);
        }

        $list = collect($this->getJson("/api/families/{$this->family->id}/income-sources")->json('data'));

        $this->assertSame(800_000.0, (float) $list->firstWhere('id', $source['id'])['this_month_total']);
    }

    public function test_deleting_a_source_keeps_the_transactions(): void
    {
        $source = $this->postJson("/api/families/{$this->family->id}/income-sources", [
            'name' => 'Warung', 'type' => 'business',
        ])->json('data');

        $tx = $this->postJson("/api/families/{$this->family->id}/transactions", [
            'type' => 'income',
            'amount' => 250_000,
            'description' => 'Omzet',
            'date' => now()->toDateString(),
            'income_source_id' => $source['id'],
        ])->json('data');

        $this->deleteJson("/api/families/{$this->family->id}/income-sources/{$source['id']}")
            ->assertStatus(204);

        // The ledger entry survives with a null stream rather than vanishing.
        $this->assertDatabaseHas('family_transactions', [
            'id' => $tx['id'],
            'income_source_id' => null,
            'amount' => 250_000,
        ]);
    }

    public function test_cannot_attach_another_familys_income_source(): void
    {
        $other = $this->otherFamily();
        $foreign = IncomeSource::create(['family_id' => $other->id, 'name' => 'Usaha Asing', 'type' => 'business']);

        $this->postJson("/api/families/{$this->family->id}/transactions", [
            'type' => 'income',
            'amount' => 1000,
            'description' => 'x',
            'date' => now()->toDateString(),
            'income_source_id' => $foreign->id,
        ])->assertStatus(422);
    }

    // â”€â”€ TRANSACTIONS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function test_can_create_a_transaction_attributed_to_the_wife(): void
    {
        $response = $this->postJson("/api/families/{$this->family->id}/transactions", [
            'type' => 'expense',
            'amount' => 350_000,
            'description' => 'Belanja bulanan',
            'date' => now()->toDateString(),
            'payer' => 'wife',
        ])->assertStatus(201);

        $this->assertSame('wife', $response->json('data.payer'));
        $this->assertSame($this->husband->id, $response->json('data.recorded_by.id'));
    }

    public function test_income_source_is_stripped_from_expense_rows(): void
    {
        $source = $this->postJson("/api/families/{$this->family->id}/income-sources", [
            'name' => 'Gaji', 'type' => 'salary',
        ])->json('data');

        $response = $this->postJson("/api/families/{$this->family->id}/transactions", [
            'type' => 'expense',
            'amount' => 10_000,
            'description' => 'Makan',
            'date' => now()->toDateString(),
            'income_source_id' => $source['id'],
        ])->assertStatus(201);

        $this->assertNull($response->json('data.income_source'));
        $this->assertNull(FamilyTransaction::find($response->json('data.id'))->income_source_id);
    }

    public function test_flipping_income_to_expense_clears_the_stream(): void
    {
        $source = $this->postJson("/api/families/{$this->family->id}/income-sources", [
            'name' => 'Gaji', 'type' => 'salary',
        ])->json('data');

        $tx = $this->postJson("/api/families/{$this->family->id}/transactions", [
            'type' => 'income',
            'amount' => 5_000_000,
            'description' => 'Gaji bulan ini',
            'date' => now()->toDateString(),
            'income_source_id' => $source['id'],
        ])->json('data');

        $this->patchJson("/api/families/{$this->family->id}/transactions/{$tx['id']}", [
            'type' => 'expense',
        ])->assertOk();

        $this->assertNull(FamilyTransaction::find($tx['id'])->income_source_id);
    }

    public function test_category_must_belong_to_the_family(): void
    {
        $other = $this->otherFamily();
        $foreign = FamilyCategory::create([
            'family_id' => $other->id, 'name' => 'Makan', 'type' => 'expense',
        ]);

        $this->postJson("/api/families/{$this->family->id}/transactions", [
            'type' => 'expense',
            'amount' => 10_000,
            'description' => 'Makan',
            'date' => now()->toDateString(),
            'category_id' => $foreign->id,
        ])->assertStatus(422);
    }

    public function test_transaction_filters_and_search(): void
    {
        foreach ([
            ['type' => 'expense', 'amount' => 100_000, 'description' => 'Makan siang', 'payer' => 'wife'],
            ['type' => 'income', 'amount' => 5_000_000, 'description' => 'Gaji', 'payer' => 'husband'],
        ] as $row) {
            $this->postJson("/api/families/{$this->family->id}/transactions", $row + [
                'date' => now()->toDateString(),
            ])->assertStatus(201);
        }

        $expenses = $this->getJson("/api/families/{$this->family->id}/transactions?type=expense")->json('data');
        $this->assertCount(1, $expenses);
        $this->assertSame('Makan siang', $expenses[0]['description']);

        $search = $this->getJson("/api/families/{$this->family->id}/transactions?q=gaji")->json('data');
        $this->assertCount(1, $search);
        $this->assertSame('Gaji', $search[0]['description']);
    }

    public function test_transaction_date_range_includes_the_end_day(): void
    {
        // Guards the same off-by-one class of bug fixed on the personal table.
        $this->postJson("/api/families/{$this->family->id}/transactions", [
            'type' => 'expense',
            'amount' => 10_000,
            'description' => 'Hari terakhir',
            'date' => '2026-03-31',
        ])->assertStatus(201);

        $result = $this->getJson("/api/families/{$this->family->id}/transactions?from=2026-03-01&to=2026-03-31")
            ->json('data');

        $this->assertCount(1, $result);
    }

    public function test_cannot_read_another_familys_transaction(): void
    {
        $other = $this->otherFamily();
        $foreign = FamilyTransaction::create([
            'family_id' => $other->id,
            'user_id' => $other->owner_user_id,
            'type' => 'expense',
            'amount' => 999,
            'description' => 'Rahasia',
            'date' => now()->toDateString(),
        ]);

        $this->getJson("/api/families/{$this->family->id}/transactions/{$foreign->id}")->assertStatus(404);
        $this->deleteJson("/api/families/{$this->family->id}/transactions/{$foreign->id}")->assertStatus(404);
    }

    public function test_non_member_is_rejected_from_every_household_endpoint(): void
    {
        $stranger = User::factory()->create();
        Sanctum::actingAs($stranger);

        foreach (['debts', 'budgets', 'goals', 'income-sources', 'transactions'] as $resource) {
            $this->getJson("/api/families/{$this->family->id}/{$resource}")->assertStatus(403);
        }
    }

    // â”€â”€ BUDGETS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function test_budget_creation_and_spent_calculation(): void
    {
        $category = FamilyCategory::create([
            'family_id' => $this->family->id, 'name' => 'Makan', 'type' => 'expense',
        ]);

        $this->postJson("/api/families/{$this->family->id}/budgets", [
            'category_id' => $category->id,
            'month' => (int) now()->month,
            'year' => (int) now()->year,
            'amount' => 1_500_000,
        ])->assertStatus(201);

        $this->postJson("/api/families/{$this->family->id}/transactions", [
            'type' => 'expense',
            'amount' => 400_000,
            'description' => 'Makan',
            'date' => now()->toDateString(),
            'category_id' => $category->id,
        ])->assertStatus(201);

        $data = $this->getJson("/api/families/{$this->family->id}/budgets")->json('data');

        $this->assertSame(1_500_000.0, (float) $data['budgets'][0]['amount']);
        $this->assertSame(400_000.0, (float) $data['budgets'][0]['spent']);
        $this->assertSame('Makan', $data['budgets'][0]['category_name']);
    }

    public function test_duplicate_budget_is_rejected_including_null_category(): void
    {
        $payload = [
            'category_id' => null,
            'month' => (int) now()->month,
            'year' => (int) now()->year,
            'amount' => 500_000,
        ];

        $this->postJson("/api/families/{$this->family->id}/budgets", $payload)->assertStatus(201);

        // MySQL would allow this duplicate because NULLs are distinct in a
        // unique index; the controller is what has to stop it.
        $this->postJson("/api/families/{$this->family->id}/budgets", $payload)->assertStatus(422);
    }

    public function test_budget_spent_ignores_other_months(): void
    {
        $category = FamilyCategory::create([
            'family_id' => $this->family->id, 'name' => 'Makan', 'type' => 'expense',
        ]);

        $this->postJson("/api/families/{$this->family->id}/budgets", [
            'category_id' => $category->id,
            'month' => (int) now()->month,
            'year' => (int) now()->year,
            'amount' => 1_000_000,
        ])->assertStatus(201);

        $this->postJson("/api/families/{$this->family->id}/transactions", [
            'type' => 'expense',
            'amount' => 900_000,
            'description' => 'Bulan lalu',
            'date' => now()->subMonth()->startOfMonth()->toDateString(),
            'category_id' => $category->id,
        ])->assertStatus(201);

        $data = $this->getJson("/api/families/{$this->family->id}/budgets")->json('data');

        $this->assertSame(0.0, (float) $data['budgets'][0]['spent']);
    }

    // â”€â”€ GOALS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    public function test_goal_contribution_completes_at_target(): void
    {
        $goal = $this->postJson("/api/families/{$this->family->id}/goals", [
            'name' => 'Dana darurat',
            'type' => 'emergency_fund',
            'target_amount' => 10_000_000,
        ])->json('data');

        $this->assertSame(0.0, (float) $goal['progress_percent']);

        $partial = $this->postJson("/api/families/{$this->family->id}/goals/{$goal['id']}/contribute", [
            'amount' => 2_500_000,
        ])->assertOk();

        $this->assertSame('active', $partial->json('data.status'));
        $this->assertSame(25.0, (float) $partial->json('data.progress_percent'));

        $done = $this->postJson("/api/families/{$this->family->id}/goals/{$goal['id']}/contribute", [
            'amount' => 7_500_000,
        ])->assertOk();

        $this->assertSame('completed', $done->json('data.status'));
        $this->assertSame(100.0, (float) $done->json('data.progress_percent'));
    }

    public function test_goal_target_cannot_drop_below_saved(): void
    {
        $goal = $this->postJson("/api/families/{$this->family->id}/goals", [
            'name' => 'Renovasi',
            'type' => 'custom',
            'target_amount' => 5_000_000,
        ])->json('data');

        $this->postJson("/api/families/{$this->family->id}/goals/{$goal['id']}/contribute", [
            'amount' => 3_000_000,
        ])->assertOk();

        $this->patchJson("/api/families/{$this->family->id}/goals/{$goal['id']}", [
            'target_amount' => 1_000_000,
        ])->assertStatus(422);
    }

    public function test_cannot_contribute_to_another_familys_goal(): void
    {
        $other = $this->otherFamily();
        $foreign = FamilyGoal::create([
            'family_id' => $other->id,
            'name' => 'Goal privasi',
            'type' => 'custom',
            'target_amount' => 1_000_000,
        ]);

        $this->postJson("/api/families/{$this->family->id}/goals/{$foreign->id}/contribute", [
            'amount' => 1000,
        ])->assertStatus(404);

        $this->assertSame(0.0, (float) $foreign->fresh()->current_amount);
    }
}
