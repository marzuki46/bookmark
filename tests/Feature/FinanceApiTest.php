<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FinanceApiTest extends TestCase
{
    use RefreshDatabase;

    private function token(User $user): string
    {
        return $user->createToken('test-device')->plainTextToken;
    }

    private function asUser(User $user): self
    {
        return $this->withHeader('Authorization', 'Bearer '.$this->token($user));
    }

    private function category(User $user, string $name, string $type = 'expense'): FinancialCategory
    {
        return FinancialCategory::create([
            'user_id' => $user->id,
            'name' => $name,
            'type' => $type,
            'icon' => 'X',
            'color' => '#ef4444',
            'is_system' => false,
        ]);
    }

    // --- Auth ---

    public function test_finance_endpoints_require_authentication(): void
    {
        $this->getJson('/api/finance/dashboard')->assertUnauthorized();
        $this->getJson('/api/finance/transactions')->assertUnauthorized();
        $this->getJson('/api/finance/categories')->assertUnauthorized();
        $this->postJson('/api/finance/ai/advice', [])->assertUnauthorized();
        $this->postJson('/api/finance/ai/pricing', [])->assertUnauthorized();
    }

    // --- HPP pricing advice ---

    public function test_pricing_requires_a_positive_cost(): void
    {
        $user = User::factory()->create();

        $this->asUser($user)
            ->postJson('/api/finance/ai/pricing', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('hpp');

        $this->asUser($user)
            ->postJson('/api/finance/ai/pricing', ['hpp' => -1])
            ->assertStatus(422)
            ->assertJsonValidationErrors('hpp');
    }

    public function test_pricing_never_recommends_a_price_at_or_below_cost(): void
    {
        $user = User::factory()->create();
        $hpp = 10_000;

        $response = $this->asUser($user)->postJson('/api/finance/ai/pricing', [
            'hpp' => $hpp,
            'quantity' => 50,
        ])->assertOk();

        // AI is not configured under test, so this exercises the deterministic
        // fallback: the floor is the invariant that matters most to a seller.
        $this->assertGreaterThan($hpp, $response->json('recommended'));
        $this->assertGreaterThanOrEqual($hpp, $response->json('min'));
        $this->assertGreaterThanOrEqual($response->json('recommended'), $response->json('max'));
        $this->assertFalse($response->json('ai_enabled'));
        $this->assertNotEmpty($response->json('rationale'));
    }

    public function test_pricing_band_widens_when_waste_is_high(): void
    {
        $user = User::factory()->create();

        $clean = $this->asUser($user)->postJson('/api/finance/ai/pricing', [
            'hpp' => 10_000, 'waste_percent' => 2,
        ])->assertOk()->json();

        $wasteful = $this->asUser($user)->postJson('/api/finance/ai/pricing', [
            'hpp' => 10_000, 'waste_percent' => 15,
        ])->assertOk()->json();

        $this->assertGreaterThan($clean['recommended'], $wasteful['recommended']);
    }

    public function test_pricing_returns_zero_when_cost_is_zero(): void
    {
        $user = User::factory()->create();

        $this->asUser($user)
            ->postJson('/api/finance/ai/pricing', ['hpp' => 0])
            ->assertOk()
            ->assertJson(['recommended' => 0, 'ai_enabled' => false]);
    }

    // --- Dashboard ---

    public function test_dashboard_returns_cards_series_and_breakdowns(): void
    {
        $user = User::factory()->create();
        $makan = $this->category($user, 'Makanan');
        $gaji = $this->category($user, 'Gaji', 'income');

        FinancialTransaction::create([
            'user_id' => $user->id, 'category_id' => $gaji->id, 'type' => 'income',
            'amount' => 5_000_000, 'description' => 'Gaji bulan ini', 'date' => now()->format('Y-m-d'),
        ]);
        FinancialTransaction::create([
            'user_id' => $user->id, 'category_id' => $makan->id, 'type' => 'expense',
            'amount' => 150_000, 'description' => 'Makan siang', 'date' => now()->format('Y-m-d'),
        ]);
        FinancialTransaction::create([
            'user_id' => $user->id, 'category_id' => $makan->id, 'type' => 'expense',
            'amount' => 50_000, 'description' => 'Kopi', 'date' => now()->format('Y-m-d'),
        ]);

        $response = $this->asUser($user)->getJson('/api/finance/dashboard');

        $response->assertOk()->assertJsonStructure([
            'period' => ['from', 'to', 'label'],
            'stats' => ['total_income', 'total_expense', 'balance', 'count', 'savings_rate', 'avg_daily_expense'],
            'monthly',
            'daily',
            'expense_by_category',
            'income_by_category',
            'insights' => ['top_expense_category', 'biggest_expense', 'health'],
            'recent',
        ]);

        $stats = $response->json('stats');
        $this->assertEquals(5_000_000, $stats['total_income'], 'income total');
        $this->assertEquals(200_000, $stats['total_expense'], 'expense total');
        $this->assertEquals(4_800_000, $stats['balance'], 'balance');
        $this->assertSame(3, $stats['count'], 'transaction count');

        $monthly = $response->json('monthly');
        $this->assertCount(6, $monthly, 'six month series');
        $this->assertSame(now()->format('Y-m'), $monthly[5]['month'], 'series ends on current month');
        $this->assertEquals(5_000_000, $monthly[5]['income'], 'current month income');
        $this->assertEquals(200_000, $monthly[5]['expense'], 'current month expense');
        $this->assertEquals(0, $monthly[0]['income'], 'months without data are zero filled');

        $expenseCategories = $response->json('expense_by_category');
        $this->assertCount(1, $expenseCategories);
        $this->assertSame('Makanan', $expenseCategories[0]['name']);
        $this->assertEquals(200_000, $expenseCategories[0]['total']);
        $this->assertEquals(100.0, $expenseCategories[0]['share']);

        $daily = $response->json('daily');
        $this->assertCount(1, $daily, 'one day with activity');
        $this->assertSame(now()->format('Y-m-d'), $daily[0]['date']);
        $this->assertEquals(5_000_000, $daily[0]['income']);
        $this->assertEquals(200_000, $daily[0]['expense']);

        $this->assertSame('Makanan', $response->json('insights.top_expense_category.name'));
        $this->assertSame('Makan siang', $response->json('insights.biggest_expense.description'));
        $this->assertEquals(150_000, $response->json('insights.biggest_expense.amount'));
    }

    public function test_dashboard_hides_other_users_transactions(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otherCategory = $this->category($other, 'Makanan');

        FinancialTransaction::create([
            'user_id' => $other->id, 'category_id' => $otherCategory->id, 'type' => 'expense',
            'amount' => 999_999, 'description' => 'Rahasia', 'date' => now()->format('Y-m-d'),
        ]);

        $response = $this->asUser($user)->getJson('/api/finance/dashboard');

        $response->assertOk();
        $this->assertEquals(0, $response->json('stats.total_expense'));
        $this->assertCount(0, $response->json('recent'));
        $this->assertStringNotContainsString('Rahasia', (string) $response->getContent());
    }

    public function test_dashboard_period_filter_narrows_the_range(): void
    {
        $user = User::factory()->create();
        $category = $this->category($user, 'Makanan');

        FinancialTransaction::create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => 'expense',
            'amount' => 10_000, 'description' => 'Bulan lalu', 'date' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
        ]);
        FinancialTransaction::create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => 'expense',
            'amount' => 20_000, 'description' => 'Bulan ini', 'date' => now()->format('Y-m-d'),
        ]);

        $this->asUser($user)
            ->getJson('/api/finance/dashboard?period=this_month')
            ->assertOk()
            ->assertJsonPath('stats.total_expense', 20_000);

        $this->asUser($user)
            ->getJson('/api/finance/dashboard?period=last_month')
            ->assertOk()
            ->assertJsonPath('stats.total_expense', 10_000);
    }

    // --- Transactions ---

    public function test_can_create_list_update_and_delete_a_transaction(): void
    {
        $user = User::factory()->create();
        $category = $this->category($user, 'Makanan');

        $created = $this->asUser($user)->postJson('/api/finance/transactions', [
            'type' => 'expense',
            'amount' => 35_000,
            'description' => 'Nasi goreng',
            'date' => '2026-09-20',
            'category_id' => $category->id,
        ]);

        $created->assertCreated()->assertJsonPath('data.description', 'Nasi goreng');
        $id = $created->json('data.id');

        $this->asUser($user)
            ->getJson("/api/finance/transactions/{$id}")
            ->assertOk()
            ->assertJsonPath('data.amount', 35_000)
            ->assertJsonPath('data.category.name', 'Makanan');

        $this->asUser($user)
            ->getJson('/api/finance/transactions')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->asUser($user)->putJson("/api/finance/transactions/{$id}", [
            'amount' => 40_000,
        ])->assertOk()->assertJsonPath('data.amount', 40_000);

        $this->asUser($user)
            ->deleteJson("/api/finance/transactions/{$id}")
            ->assertOk();

        $this->assertDatabaseMissing('financial_transactions', ['id' => $id]);
    }

    public function test_transaction_list_can_be_filtered_and_searched(): void
    {
        $user = User::factory()->create();
        $makan = $this->category($user, 'Makanan');
        $bensin = $this->category($user, 'Transportasi');

        FinancialTransaction::create([
            'user_id' => $user->id, 'category_id' => $makan->id, 'type' => 'expense',
            'amount' => 10_000, 'description' => 'Makan pagi', 'date' => now()->format('Y-m-d'),
        ]);
        FinancialTransaction::create([
            'user_id' => $user->id, 'category_id' => $bensin->id, 'type' => 'expense',
            'amount' => 60_000, 'description' => 'Bensin pertamina', 'date' => now()->format('Y-m-d'),
        ]);

        $this->asUser($user)->getJson('/api/finance/transactions?search=bensin')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Bensin pertamina');

        $this->asUser($user)->getJson("/api/finance/transactions?category_id={$makan->id}")
            ->assertOk()->assertJsonCount(1, 'data');

        $this->asUser($user)->getJson('/api/finance/transactions?type=income')
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_cannot_touch_another_users_transaction(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otherCategory = $this->category($other, 'Makanan');

        $theirs = FinancialTransaction::create([
            'user_id' => $other->id, 'category_id' => $otherCategory->id, 'type' => 'expense',
            'amount' => 10_000, 'description' => 'Milik orang lain', 'date' => now()->format('Y-m-d'),
        ]);

        $this->asUser($user)->getJson("/api/finance/transactions/{$theirs->id}")->assertForbidden();
        $this->asUser($user)->putJson("/api/finance/transactions/{$theirs->id}", ['amount' => 1])->assertForbidden();
        $this->asUser($user)->deleteJson("/api/finance/transactions/{$theirs->id}")->assertForbidden();

        $this->assertDatabaseHas('financial_transactions', [
            'id' => $theirs->id,
            'description' => 'Milik orang lain',
            'user_id' => $other->id,
        ]);
    }

    public function test_cannot_use_another_users_category(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otherCategory = $this->category($other, 'Makanan');

        $this->asUser($user)->postJson('/api/finance/transactions', [
            'type' => 'expense',
            'amount' => 10_000,
            'description' => 'Mencurigakan',
            'date' => now()->format('Y-m-d'),
            'category_id' => $otherCategory->id,
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('financial_transactions', ['description' => 'Mencurigakan']);
    }

    public function test_category_type_must_match_transaction_type(): void
    {
        $user = User::factory()->create();
        $incomeCategory = $this->category($user, 'Gaji', 'income');

        $this->asUser($user)->postJson('/api/finance/transactions', [
            'type' => 'expense',
            'amount' => 10_000,
            'description' => 'Salah kategori',
            'date' => now()->format('Y-m-d'),
            'category_id' => $incomeCategory->id,
        ])->assertUnprocessable();
    }

    public function test_transaction_validation_rejects_bad_payloads(): void
    {
        $user = User::factory()->create();

        $this->asUser($user)->postJson('/api/finance/transactions', [
            'type' => 'transfer',
            'amount' => -5,
            'description' => '',
            'date' => 'not-a-date',
        ])->assertUnprocessable()->assertJsonValidationErrors(['type', 'amount', 'description', 'date']);
    }

    // --- Categories ---

    public function test_can_manage_categories_but_not_delete_system_ones(): void
    {
        $user = User::factory()->create();

        $created = $this->asUser($user)->postJson('/api/finance/categories', [
            'name' => 'Hewan Peliharaan',
            'type' => 'expense',
            'icon' => 'DOG',
            'color' => '#8b5cf6',
        ])->assertCreated()->assertJsonPath('data.name', 'Hewan Peliharaan');

        $id = $created->json('data.id');

        $this->asUser($user)->putJson("/api/finance/categories/{$id}", ['name' => 'Anjing'])
            ->assertOk()->assertJsonPath('data.name', 'Anjing');

        $this->asUser($user)->getJson('/api/finance/categories?type=expense')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->asUser($user)->deleteJson("/api/finance/categories/{$id}")->assertOk();
        $this->assertDatabaseMissing('financial_categories', ['id' => $id]);

        $system = FinancialCategory::create([
            'user_id' => $user->id, 'name' => 'Sistem', 'type' => 'expense',
            'icon' => 'S', 'color' => '#111111', 'is_system' => true,
        ]);

        $this->asUser($user)->deleteJson("/api/finance/categories/{$system->id}")
            ->assertUnprocessable();
        $this->assertDatabaseHas('financial_categories', ['id' => $system->id]);
    }

    public function test_deleting_a_category_keeps_its_transactions(): void
    {
        $user = User::factory()->create();
        $category = $this->category($user, 'Hapus Saya');

        $transaction = FinancialTransaction::create([
            'user_id' => $user->id, 'category_id' => $category->id, 'type' => 'expense',
            'amount' => 10_000, 'description' => 'Tetap ada', 'date' => now()->format('Y-m-d'),
        ]);

        $this->asUser($user)->deleteJson("/api/finance/categories/{$category->id}")->assertOk();

        $this->assertDatabaseHas('financial_transactions', ['id' => $transaction->id, 'category_id' => null]);
    }

    public function test_cannot_manage_another_users_category(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otherCategory = $this->category($other, 'Milik Orang');

        $this->asUser($user)->deleteJson("/api/finance/categories/{$otherCategory->id}")->assertForbidden();
        $this->assertDatabaseHas('financial_categories', ['id' => $otherCategory->id]);
    }
}
