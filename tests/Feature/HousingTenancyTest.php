<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\FamilyTransaction;
use App\Models\HousingComplex;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HousingTenancyTest extends TestCase
{
    use RefreshDatabase;

    private function family(?HousingComplex $complex = null, ?User $owner = null): Family
    {
        return Family::create([
            'name' => 'Keluarga_test',
            'owner_user_id' => ($owner ?? User::factory()->create())->id,
            'housing_complex_id' => $complex?->id,
            'invite_code' => Family::generateInviteCode(),
        ]);
    }

    public function test_complex_code_generator_produces_unique_codes(): void
    {
        $seen = [];

        for ($i = 0; $i < 100; $i++) {
            $code = HousingComplex::generateCode();
            $this->assertArrayNotHasKey($code, $seen, 'Kode perumahan duplikat: '.$code);
            $seen[$code] = true;
        }
    }

    public function test_family_can_belong_to_a_complex(): void
    {
        $complex = HousingComplex::create([
            'name' => 'Perumnya Melati',
            'address' => 'Jl. Melati No. 1',
            'code' => HousingComplex::generateCode(),
        ]);

        $family = $this->family($complex);

        $this->assertTrue($family->housingComplex->is($complex));
        $this->assertCount(1, $complex->fresh()->families);
    }

    public function test_existing_families_without_a_complex_still_load(): void
    {
        $family = $this->family();

        $this->assertNull($family->housing_complex_id);
        $this->assertNull($family->fresh()->housingComplex);
    }

    public function test_deleting_a_complex_does_not_delete_its_families(): void
    {
        $complex = HousingComplex::create([
            'name' => 'Per tenuous',
            'code' => HousingComplex::generateCode(),
        ]);
        $family = $this->family($complex);

        $complex->delete();

        $this->assertDatabaseHas('families', ['id' => $family->id]);
        $this->assertNull($family->fresh()->housing_complex_id, 'Harus jadi null, bukan ikut terhapus.');
    }

    public function test_complex_admin_is_not_linked_to_family_financial_data(): void
    {
        $admin = User::factory()->create();
        $complex = HousingComplex::create([
            'name' => 'Perumahankses',
            'code' => HousingComplex::generateCode(),
            'admin_user_id' => $admin->id,
        ]);

        $husband = User::factory()->create();
        $family = $this->family($complex, $husband);
        FamilyMember::create(['family_id' => $family->id, 'user_id' => $husband->id, 'role' => 'owner']);

        FamilyTransaction::create([
            'family_id' => $family->id,
            'user_id' => $husband->id,
            'type' => 'income',
            'amount' => 5_000_000,
            'description' => 'Gaji',
            'date' => now()->toDateString(),
        ]);

        // The admin is not a member, so no family-scoped access may reach them.
        $this->assertFalse($family->fresh()->isMember($admin));
        $this->assertNull($admin->family());
        $this->assertCount(0, $admin->familyMemberships);
    }

    public function test_user_resolves_to_their_own_family(): void
    {
        $user = User::factory()->create();
        $family = $this->family(owner: $user);
        FamilyMember::create(['family_id' => $family->id, 'user_id' => $user->id, 'role' => 'owner']);

        $this->assertTrue($user->fresh()->family()->is($family));
        $this->assertTrue($user->fresh()->isFamilyOwner());
    }

    public function test_family_code_cannot_be_reused_across_complexes(): void
    {
        $code = HousingComplex::generateCode();
        HousingComplex::create(['name' => 'A', 'code' => $code]);

        $this->expectException(QueryException::class);

        HousingComplex::create(['name' => 'B', 'code' => $code]);
    }
}
