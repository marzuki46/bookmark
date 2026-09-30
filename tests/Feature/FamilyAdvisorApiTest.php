<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyCategory;
use App\Models\FamilyDebt;
use App\Models\FamilyGoal;
use App\Models\FamilyMember;
use App\Models\FamilyTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class FamilyAdvisorApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $wife;

    private User $child;

    private Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->wife = User::factory()->create();
        $this->child = User::factory()->create();

        $this->family = Family::create([
            'name' => 'Keluarga Kang Cuan',
            'owner_user_id' => $this->owner->id,
            'invite_code' => Family::generateInviteCode(),
        ]);

        FamilyMember::create(['family_id' => $this->family->id, 'user_id' => $this->owner->id, 'role' => 'owner']);
        FamilyMember::create(['family_id' => $this->family->id, 'user_id' => $this->wife->id, 'role' => 'member', 'relationship' => 'wife', 'payer_role' => 'wife']);
        FamilyMember::create(['family_id' => $this->family->id, 'user_id' => $this->child->id, 'role' => 'member', 'relationship' => 'child', 'visibility' => ['income' => false, 'expense' => true]]);

        Sanctum::actingAs($this->owner);
    }

    public function test_advisor_is_disabled_by_default(): void
    {
        $this->getJson("/api/families/{$this->family->id}/advisor")
            ->assertOk()
            ->assertJsonPath('data.enabled', false);
    }

    public function test_owner_can_enable_and_disable_advisor(): void
    {
        $this->postJson("/api/families/{$this->family->id}/advisor", ['enabled' => true])
            ->assertOk()
            ->assertJsonPath('data.enabled', true);

        $this->getJson("/api/families/{$this->family->id}/advisor")
            ->assertOk()
            ->assertJsonPath('data.enabled', true);

        $this->postJson("/api/families/{$this->family->id}/advisor", ['enabled' => false])
            ->assertOk()
            ->assertJsonPath('data.enabled', false);
    }

    public function test_non_owner_cannot_enable_advisor(): void
    {
        Sanctum::actingAs($this->wife);

        $this->postJson("/api/families/{$this->family->id}/advisor", ['enabled' => true])
            ->assertForbidden();

        $this->assertFalse($this->family->fresh()->advisor_enabled);
    }

    public function test_owner_gets_plan_when_enabled(): void
    {
        $this->family->update(['advisor_enabled' => true, 'advisor_profile' => ['monthly_income' => 8_000_000]]);

        $data = $this->getJson("/api/families/{$this->family->id}/advisor")
            ->assertOk()
            ->assertJsonPath('data.accessible', true)
            ->json('data');

        $this->assertSame(8_000_000.0, (float) $data['plan']['income']);
        $this->assertFalse($data['plan']['is_deficit']);
        $this->assertSame(4_000_000.0, (float) $data['plan']['posts']['essentials']['amount']);
        $this->assertSame(400_000.0, (float) $data['plan']['posts']['fun']['amount']);
    }

    public function test_plan_reports_deficit_when_debt_exceeds_income(): void
    {
        $this->family->update(['advisor_enabled' => true, 'advisor_profile' => ['monthly_income' => 5_000_000]]);
        FamilyDebt::create([
            'family_id' => $this->family->id,
            'user_id' => $this->owner->id,
            'name' => 'KPR',
            'type' => 'payable',
            'principal' => 500_000_000,
            'amount' => 500_000_000,
            'remaining' => 400_000_000,
            'installment' => 4_000_000,
        ]);

        $data = $this->getJson("/api/families/{$this->family->id}/advisor")
            ->assertOk()
            ->json('data');

        $this->assertTrue($data['plan']['is_deficit']);
        $this->assertGreaterThan(0.0, (float) $data['plan']['deficit']);
        $this->assertSame(0.0, (float) $data['plan']['posts']['fun']['amount']);
        $this->assertSame(4_000_000.0, (float) $data['plan']['posts']['debt']['amount']);
    }

    public function test_member_without_income_visibility_cannot_read_amounts(): void
    {
        $this->family->update(['advisor_enabled' => true, 'advisor_profile' => ['monthly_income' => 8_000_000]]);
        Sanctum::actingAs($this->child);

        $this->getJson("/api/families/{$this->family->id}/advisor")
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.accessible', false)
            ->assertJsonMissingPath('data.plan');
    }

    public function test_member_with_income_visibility_can_read_plan(): void
    {
        $this->family->update(['advisor_enabled' => true, 'advisor_profile' => ['monthly_income' => 8_000_000]]);
        $wifeMember = FamilyMember::where('family_id', $this->family->id)->where('user_id', $this->wife->id)->first();
        $wifeMember->update(['visibility' => array_merge($wifeMember->visibility ?? [], ['income' => true])]);
        Sanctum::actingAs($this->wife);

        $this->getJson("/api/families/{$this->family->id}/advisor")
            ->assertOk()
            ->assertJsonPath('data.accessible', true);
    }

    public function test_owner_can_save_and_read_profile(): void
    {
        $this->postJson("/api/families/{$this->family->id}/advisor", ['enabled' => true])->assertOk();

        $this->putJson("/api/families/{$this->family->id}/advisor/profile", [
            'monthly_income' => 7_500_000,
            'income_type' => 'fixed',
            'housing_type' => 'own_installment',
            'has_protection' => true,
            'uncovered_members' => 1,
            'priorities' => ['Dana pendidikan', 'Pelunasan KPR'],
        ])->assertOk()->assertJsonPath('data.profile.monthly_income', 7_500_000);

        $this->assertSame('own_installment', $this->family->fresh()->advisor_profile['housing_type']);
    }

    public function test_profile_rejects_invalid_values(): void
    {
        $this->postJson("/api/families/{$this->family->id}/advisor", ['enabled' => true])->assertOk();

        $this->putJson("/api/families/{$this->family->id}/advisor/profile", [
            'income_type' => 'sometimes',
            'monthly_income' => -5,
        ])->assertUnprocessable();
    }

    public function test_advisor_metrics_derive_from_recorded_transactions(): void
    {
        $this->family->update(['advisor_enabled' => true]);

        foreach ([1, 2, 3] as $i) {
            FamilyTransaction::create([
                'family_id' => $this->family->id,
                'user_id' => $this->owner->id,
                'type' => 'expense',
                'amount' => 2_000_000,
                'description' => 'Kebutuhan bulan ke-'.$i,
                'date' => now()->subMonths(4 - $i)->startOfMonth()->toDateString(),
            ]);
            FamilyTransaction::create([
                'family_id' => $this->family->id,
                'user_id' => $this->owner->id,
                'type' => 'income',
                'amount' => 5_000_000,
                'description' => 'Gaji bulan ke-'.$i,
                'date' => now()->subMonths(4 - $i)->startOfMonth()->toDateString(),
            ]);
        }

        FamilyGoal::create([
            'family_id' => $this->family->id,
            'user_id' => $this->owner->id,
            'name' => 'Dana darurat',
            'type' => 'emergency_fund',
            'target_amount' => 6_000_000,
            'current_amount' => 2_000_000,
        ]);

        $data = $this->getJson("/api/families/{$this->family->id}/advisor")
            ->assertOk()
            ->assertJsonPath('data.accessible', true)
            ->json('data');

        $this->assertSame(2_000_000.0, (float) $data['context']['emergency_current']);
        $this->assertSame(6_000_000.0, (float) $data['context']['emergency_target']);
        $this->assertSame(1.0, (float) $data['context']['emergency_month_coverage']);
    }

    public function test_health_score_reports_not_enough_data_without_any_activity(): void
    {
        $data = $this->getJson("/api/families/{$this->family->id}/summary")
            ->assertOk()
            ->json('data');

        $this->assertTrue($data['insufficient_data']);
        $this->assertSame(0, (int) $data['score']);
        $this->assertSame('Belum cukup data', $data['grade']);
    }

    public function test_plan_does_not_budget_installments_already_paid_this_month(): void
    {
        $this->family->update(['advisor_enabled' => true, 'advisor_profile' => ['monthly_income' => 8_000_000]]);

        FamilyDebt::create([
            'family_id' => $this->family->id,
            'name' => 'KPR',
            'type' => 'payable',
            'amount' => 400_000_000,
            'installment' => 4_000_000,
        ]);

        $cicilan = FamilyCategory::create([
            'family_id' => $this->family->id,
            'name' => 'Cicilan',
            'type' => 'expense',
        ]);

        FamilyTransaction::create([
            'family_id' => $this->family->id,
            'user_id' => $this->owner->id,
            'category_id' => $cicilan->id,
            'type' => 'expense',
            'amount' => 4_000_000,
            'description' => 'Angsuran KPR',
            'date' => now()->startOfMonth()->toDateString(),
        ]);

        $data = $this->getJson("/api/families/{$this->family->id}/advisor")
            ->assertOk()
            ->json('data');

        $this->assertSame(4_000_000.0, (float) $data['plan']['posts']['debt']['planned']);
        $this->assertSame(4_000_000.0, (float) $data['plan']['posts']['debt']['realized']);
        $this->assertSame(0.0, (float) $data['plan']['posts']['debt']['amount']);
        $this->assertSame(0.0, (float) $data['context']['uncovered_debt']);
    }

    public function test_health_score_uses_essential_needs_and_ignores_paid_installments(): void
    {
        FamilyDebt::create([
            'family_id' => $this->family->id,
            'name' => 'KPR',
            'type' => 'payable',
            'amount' => 50_000_000,
            'installment' => 4_000_000,
        ]);

        $cicilan = FamilyCategory::create([
            'family_id' => $this->family->id,
            'name' => 'Cicilan',
            'type' => 'expense',
        ]);

        FamilyTransaction::create([
            'family_id' => $this->family->id,
            'user_id' => $this->owner->id,
            'type' => 'income',
            'amount' => 8_000_000,
            'description' => 'Gaji',
            'date' => now()->startOfMonth()->toDateString(),
        ]);

        foreach ([['Angsuran KPR', 4_000_000, $cicilan->id], ['Belanja', 2_000_000, null]] as [$description, $amount, $categoryId]) {
            FamilyTransaction::create([
                'family_id' => $this->family->id,
                'user_id' => $this->owner->id,
                'category_id' => $categoryId,
                'type' => 'expense',
                'amount' => $amount,
                'description' => $description,
                'date' => now()->startOfMonth()->toDateString(),
            ]);
        }

        // Three full previous months of 2M spending -> essential monthly need.
        foreach ([1, 2, 3] as $i) {
            FamilyTransaction::create([
                'family_id' => $this->family->id,
                'user_id' => $this->owner->id,
                'type' => 'expense',
                'amount' => 2_000_000,
                'description' => 'Kebutuhan bulan lalu',
                'date' => now()->subMonths($i)->startOfMonth()->toDateString(),
            ]);
        }

        $data = $this->getJson("/api/families/{$this->family->id}/summary")
            ->assertOk()
            ->json('data');

        $this->assertFalse($data['insufficient_data']);
        $this->assertSame(2_000_000.0, (float) $data['essential_monthly']);
        $this->assertSame(4_000_000.0, (float) $data['realized_debt_this_month']);
        $this->assertSame(0.0, (float) $data['uncovered_debt']);
        // 60 base + 20 (savings 25%) - 10 (no emergency fund) - 10 (debt ratio) + 5 (essentials 25%)
        $this->assertSame(65, (int) $data['score']);
    }

    public function test_summary_masks_areas_a_member_cannot_view(): void
    {
        FamilyTransaction::create([
            'family_id' => $this->family->id,
            'user_id' => $this->owner->id,
            'type' => 'income',
            'amount' => 8_000_000,
            'description' => 'Gaji',
            'date' => now()->startOfMonth()->toDateString(),
        ]);
        FamilyTransaction::create([
            'family_id' => $this->family->id,
            'user_id' => $this->owner->id,
            'type' => 'expense',
            'amount' => 2_000_000,
            'description' => 'Belanja',
            'date' => now()->startOfMonth()->toDateString(),
        ]);
        FamilyDebt::create([
            'family_id' => $this->family->id,
            'name' => 'KPR',
            'type' => 'payable',
            'amount' => 50_000_000,
            'installment' => 1_000_000,
        ]);

        // The child sees expenses but has income and debts hidden.
        $childMember = FamilyMember::where('family_id', $this->family->id)->where('user_id', $this->child->id)->first();
        $childMember->update(['visibility' => ['income' => false, 'expense' => true, 'debts' => false]]);
        Sanctum::actingAs($this->child);

        $data = $this->getJson("/api/families/{$this->family->id}/summary")
            ->assertOk()
            ->json('data');

        $this->assertSame(0.0, (float) $data['income']);
        $this->assertSame(2_000_000.0, (float) $data['expense']);
        $this->assertSame(0.0, (float) $data['total_debt']);
        $this->assertSame(0.0, (float) $data['planned_debt']);
        $this->assertSame(0.0, (float) $data['realized_debt_this_month']);
        $this->assertSame(0.0, (float) $data['uncovered_debt']);

        foreach ($data['recommendations'] as $rec) {
            $this->assertStringNotContainsString('pemasukan', mb_strtolower($rec), 'Rekomendasi bocorkan pemasukan.');
            $this->assertStringNotContainsString('hutang', mb_strtolower($rec), 'Rekomendasi bocorkan hutang.');
        }
    }

    public function test_summary_is_full_for_the_owner(): void
    {
        FamilyTransaction::create([
            'family_id' => $this->family->id,
            'user_id' => $this->owner->id,
            'type' => 'income',
            'amount' => 8_000_000,
            'description' => 'Gaji',
            'date' => now()->startOfMonth()->toDateString(),
        ]);

        $data = $this->getJson("/api/families/{$this->family->id}/summary")
            ->assertOk()
            ->json('data');

        $this->assertSame(8_000_000.0, (float) $data['income']);
    }

    public function test_nudge_respects_hidden_areas(): void
    {
        $this->family->update(['advisor_enabled' => true, 'advisor_profile' => ['monthly_income' => 8_000_000]]);

        FamilyDebt::create([
            'family_id' => $this->family->id,
            'name' => 'KPR',
            'type' => 'payable',
            'amount' => 50_000_000,
            'installment' => 1_000_000,
            'due_date' => now()->startOfDay()->subDay()->toDateString(),
        ]);

        $ownerNudge = $this->getJson("/api/families/{$this->family->id}/nudge")->json('data');
        $this->assertNotNull($ownerNudge);
        $this->assertSame('debt_overdue', $ownerNudge['code']);

        $childMember = FamilyMember::where('family_id', $this->family->id)->where('user_id', $this->child->id)->first();
        $childMember->update(['visibility' => ['income' => false, 'expense' => true, 'debts' => false]]);
        Sanctum::actingAs($this->child);

        $childNudge = $this->getJson("/api/families/{$this->family->id}/nudge")->json('data');
        if ($childNudge !== null) {
            $this->assertNotSame('debt_overdue', $childNudge['code']);
            foreach (['pemasukan', 'income', 'hutang'] as $needle) {
                $this->assertStringNotContainsString($needle, mb_strtolower($childNudge['message']));
            }
        }
    }
}
