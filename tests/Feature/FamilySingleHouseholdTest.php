<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\HousingComplex;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class FamilySingleHouseholdTest extends TestCase
{
    use RefreshDatabase;

    private User $husband;

    private User $wife;

    private Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        $this->husband = User::factory()->create(['name' => 'Budi']);
        $this->wife = User::factory()->create(['name' => 'Siti']);

        $this->family = Family::create([
            'name' => 'Keluarga Uji',
            'owner_user_id' => $this->husband->id,
            'invite_code' => Family::generateInviteCode(),
        ]);

        FamilyMember::create([
            'family_id' => $this->family->id,
            'user_id' => $this->husband->id,
            'role' => 'owner',
            'payer_role' => 'husband',
        ]);
        FamilyMember::create([
            'family_id' => $this->family->id,
            'user_id' => $this->wife->id,
            'role' => 'member',
            'payer_role' => 'wife',
        ]);

        Sanctum::actingAs($this->husband);
    }

    private function otherFamily(User $owner): Family
    {
        $family = Family::create([
            'name' => 'Keluarga Lain',
            'owner_user_id' => $owner->id,
            'invite_code' => Family::generateInviteCode(),
        ]);

        FamilyMember::create([
            'family_id' => $family->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        return $family;
    }

    // ------------------------------------------------- one household per user

    public function test_a_user_cannot_belong_to_a_second_family(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('sudah menjadi anggota keluarga lain');

        FamilyMember::create([
            'family_id' => $this->otherFamily(User::factory()->create())->id,
            'user_id' => $this->husband->id,
            'role' => 'member',
        ]);
    }

    public function test_the_guard_fires_for_every_creation_path_not_just_the_api(): void
    {
        $second = $this->otherFamily(User::factory()->create());

        // 2 members in the test family + 1 owner in the second family.
        $this->assertDatabaseCount('family_members', 3);

        // The web invite flow inserts the model directly; the rule has to hold
        // there too or the app and the web would disagree about tenancy.
        try {
            FamilyMember::create([
                'family_id' => $second->id,
                'user_id' => $this->wife->id,
                'role' => 'member',
            ]);
            $this->fail('The second household should have been refused.');
        } catch (\RuntimeException $e) {
            $this->assertDatabaseCount('family_members', 3);
        }
    }

    public function test_re_joining_the_same_family_is_still_rejected_by_the_unique_index(): void
    {
        $this->expectException(QueryException::class);

        FamilyMember::create([
            'family_id' => $this->family->id,
            'user_id' => $this->husband->id,
            'role' => 'member',
        ]);
    }

    public function test_two_members_can_share_one_family(): void
    {
        $this->assertDatabaseCount('family_members', 2);
        $this->assertSame(2, $this->family->members()->count());
    }

    public function test_index_returns_only_the_callers_own_family(): void
    {
        $this->getJson('/api/families')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->family->id)
            ->assertJsonPath('data.0.payer_role', 'husband')
            ->assertJsonPath('data.0.payer_label', 'Suami');
    }

    public function test_index_is_empty_for_a_user_with_no_family(): void
    {
        $loner = User::factory()->create();
        Sanctum::actingAs($loner);

        $this->getJson('/api/families')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_show_exposes_payer_roles_for_every_member(): void
    {
        $this->getJson("/api/families/{$this->family->id}")
            ->assertOk()
            ->assertJsonPath('data.payer_role', 'husband')
            ->assertJsonFragment(['user_id' => $this->wife->id, 'payer_role' => 'wife', 'payer_label' => 'Istri']);
    }

    public function test_a_housing_admin_still_sees_no_family(): void
    {
        $admin = User::factory()->create();
        $complex = HousingComplex::create([
            'name' => 'Perumahan Uji',
            'code' => 'PRM-UIJI',
            'admin_user_id' => $admin->id,
        ]);
        $this->family->update(['housing_complex_id' => $complex->id]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/families')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("/api/families/{$this->family->id}")->assertForbidden();
    }

    // ---------------------------------------------------------- payer role

    public function test_a_member_can_declare_themselves(): void
    {
        $fresh = User::factory()->create();
        $new = Family::create([
            'name' => 'Keluarga Baru',
            'owner_user_id' => $this->husband->id,
            'invite_code' => Family::generateInviteCode(),
        ]);
        FamilyMember::create(['family_id' => $new->id, 'user_id' => $fresh->id, 'role' => 'member']);

        Sanctum::actingAs($fresh);

        $this->patchJson("/api/families/{$new->id}/me", ['payer_role' => 'wife'])
            ->assertOk()
            ->assertJsonPath('data.payer_label', 'Istri');

        $this->assertDatabaseHas('family_members', [
            'user_id' => $fresh->id,
            'payer_role' => 'wife',
        ]);
    }

    public function test_two_members_cannot_claim_the_same_payer_role(): void
    {
        Sanctum::actingAs($this->wife);

        $this->patchJson("/api/families/{$this->family->id}/me", ['payer_role' => 'husband'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('payer_role');
    }

    public function test_a_member_cannot_label_the_spouse(): void
    {
        Sanctum::actingAs($this->wife);

        // Targeting another user is not part of the contract at all.
        $this->patchJson("/api/families/{$this->family->id}/me", [
            'payer_role' => 'husband',
            'user_id' => $this->husband->id,
        ])->assertStatus(422)->assertJsonValidationErrors('payer_role');

        $this->assertDatabaseHas('family_members', [
            'user_id' => $this->husband->id,
            'payer_role' => 'husband',
        ]);
    }

    public function test_payer_role_is_validated(): void
    {
        $this->patchJson("/api/families/{$this->family->id}/me", ['payer_role' => 'anak'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('payer_role');
    }

    public function test_payer_role_requires_a_value(): void
    {
        $this->patchJson("/api/families/{$this->family->id}/me", [])->assertStatus(422);
    }

    public function test_profile_update_is_scoped_to_the_own_family(): void
    {
        $other = $this->otherFamily(User::factory()->create());

        $this->patchJson("/api/families/{$other->id}/me", ['payer_role' => 'wife'])->assertForbidden();
    }

    public function test_user_helpers_expose_the_payer(): void
    {
        $user = $this->husband->fresh();

        $this->assertSame('husband', $user->payerRole());
        $this->assertSame('Suami', $user->payerLabel());
        $this->assertTrue($user->familyMember()->isHusband());
        $this->assertFalse($user->familyMember()->isWife());
        $this->assertSame('Istri', $this->wife->fresh()->familyMember()->payerLabel());
    }

    public function test_payer_helpers_are_null_before_choosing(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->payerRole());
        $this->assertNull($user->payerLabel());
    }
}
