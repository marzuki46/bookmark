<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyCategory;
use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class FamilyCategoryApiTest extends TestCase
{
    use RefreshDatabase;

    private User $husband;

    private Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        $this->husband = User::factory()->create();
        $this->family = Family::create([
            'name' => 'Keluarga Uji',
            'owner_user_id' => $this->husband->id,
            'invite_code' => Family::generateInviteCode(),
        ]);
        FamilyMember::create(['family_id' => $this->family->id, 'user_id' => $this->husband->id, 'role' => 'owner']);

        Sanctum::actingAs($this->husband);
    }

    public function test_it_lists_categories_and_can_filter_by_type(): void
    {
        FamilyCategory::create(['family_id' => $this->family->id, 'name' => 'Makan', 'type' => 'expense']);
        FamilyCategory::create(['family_id' => $this->family->id, 'name' => 'Gaji', 'type' => 'income']);

        $this->getJson("/api/families/{$this->family->id}/categories")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => 'Makan', 'type' => 'expense']);

        $this->getJson("/api/families/{$this->family->id}/categories?type=income")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Gaji');
    }

    public function test_it_rejects_an_unknown_type_filter(): void
    {
        $this->getJson("/api/families/{$this->family->id}/categories?type=transfer")->assertStatus(422);
    }

    public function test_it_creates_a_category(): void
    {
        $this->postJson("/api/families/{$this->family->id}/categories", [
            'name' => 'Bensin',
            'type' => 'expense',
            'icon' => 'fuel',
            'color' => '#D97706',
        ])->assertCreated()->assertJsonPath('data.name', 'Bensin');

        $this->assertDatabaseHas('family_categories', [
            'family_id' => $this->family->id,
            'name' => 'Bensin',
        ]);
    }

    public function test_it_rejects_a_duplicate_name_case_insensitively(): void
    {
        FamilyCategory::create(['family_id' => $this->family->id, 'name' => 'Makan', 'type' => 'expense']);

        $this->postJson("/api/families/{$this->family->id}/categories", [
            'name' => ' makan ',
            'type' => 'expense',
        ])->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_the_same_name_is_allowed_under_a_different_type(): void
    {
        FamilyCategory::create(['family_id' => $this->family->id, 'name' => 'Bonus', 'type' => 'income']);

        $this->postJson("/api/families/{$this->family->id}/categories", [
            'name' => 'Bonus',
            'type' => 'expense',
        ])->assertCreated();
    }

    public function test_it_validates_input(): void
    {
        $this->postJson("/api/families/{$this->family->id}/categories", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type']);

        $this->postJson("/api/families/{$this->family->id}/categories", [
            'name' => str_repeat('a', 61),
            'type' => 'expense',
        ])->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_it_updates_a_category(): void
    {
        $category = FamilyCategory::create([
            'family_id' => $this->family->id,
            'name' => 'Makan',
            'type' => 'expense',
        ]);

        $this->patchJson("/api/families/{$this->family->id}/categories/{$category->id}", ['name' => 'Makan & Minum'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Makan & Minum');
    }

    public function test_it_cannot_update_another_family_category(): void
    {
        $outsider = User::factory()->create();
        $other = Family::create([
            'name' => 'Keluarga Lain',
            'owner_user_id' => $outsider->id,
            'invite_code' => Family::generateInviteCode(),
        ]);
        FamilyMember::create(['family_id' => $other->id, 'user_id' => $outsider->id, 'role' => 'owner']);
        $foreign = FamilyCategory::create(['family_id' => $other->id, 'name' => 'Milik Mereka', 'type' => 'expense']);

        $this->patchJson("/api/families/{$this->family->id}/categories/{$foreign->id}", ['name' => 'Dibajak'])
            ->assertNotFound();

        $this->assertDatabaseHas('family_categories', ['id' => $foreign->id, 'name' => 'Milik Mereka']);
    }

    public function test_a_non_member_cannot_touch_categories(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/families/{$this->family->id}/categories")->assertForbidden();
        $this->postJson("/api/families/{$this->family->id}/categories", [
            'name' => 'X',
            'type' => 'expense',
        ])->assertForbidden();
    }

    public function test_it_deletes_custom_category_without_touching_the_family(): void
    {
        $category = FamilyCategory::create([
            'family_id' => $this->family->id,
            'name' => 'Kategori sementara',
            'type' => 'expense',
            'is_system' => false,
        ]);

        $this->deleteJson("/api/families/{$this->family->id}/categories/{$category->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('family_categories', ['id' => $category->id]);
    }
}
