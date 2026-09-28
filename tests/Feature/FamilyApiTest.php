<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\HousingComplex;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class FamilyApiTest extends TestCase
{
    use RefreshDatabase;

    private function familyWith(?User $husband = null, ?User $wife = null, ?HousingComplex $complex = null): Family
    {
        $husband ??= User::factory()->create();
        $wife ??= User::factory()->create();

        $family = Family::create([
            'name' => 'Keluarga Uji',
            'owner_user_id' => $husband->id,
            'housing_complex_id' => $complex?->id,
            'invite_code' => Family::generateInviteCode(),
        ]);

        FamilyMember::create(['family_id' => $family->id, 'user_id' => $husband->id, 'role' => 'owner']);
        FamilyMember::create(['family_id' => $family->id, 'user_id' => $wife->id, 'role' => 'member']);

        return $family->fresh();
    }

    // ── families index ────────────────────────────────────────────────

    public function test_index_lists_only_families_the_user_belongs_to(): void
    {
        $husband = User::factory()->create();
        $mine = $this->familyWith($husband);
        $this->familyWith(); // someone else's family

        Sanctum::actingAs($husband);

        $response = $this->getJson('/api/families')->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertSame([$mine->id], $ids->all());
    }

    public function test_index_exposes_housing_complex_context(): void
    {
        $husband = User::factory()->create();
        $complex = HousingComplex::create([
            'name' => 'Perumahan Kenanga',
            'code' => HousingComplex::generateCode(),
        ]);
        $this->familyWith($husband, complex: $complex);

        Sanctum::actingAs($husband);

        $response = $this->getJson('/api/families')->assertOk();

        $this->assertSame('Perumahan Kenanga', $response->json('data.0.housing_complex.name'));
        $this->assertSame(2, $response->json('data.0.members_count'));
        $this->assertSame('owner', $response->json('data.0.role'));
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/families')->assertStatus(401);
    }

    // ── cross-tenant isolation ────────────────────────────────────────

    public function test_member_can_view_own_family(): void
    {
        $wife = User::factory()->create();
        $family = $this->familyWith(wife: $wife);

        Sanctum::actingAs($wife);

        $this->getJson("/api/families/{$family->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $family->id)
            ->assertJsonCount(2, 'data.members');
    }

    public function test_non_member_is_rejected_from_family(): void
    {
        $family = $this->familyWith();
        $stranger = User::factory()->create();

        Sanctum::actingAs($stranger);

        $this->getJson("/api/families/{$family->id}")->assertStatus(403);
    }

    public function test_housing_complex_admin_cannot_read_family_financials(): void
    {
        $admin = User::factory()->create();
        $complex = HousingComplex::create([
            'name' => 'Per proclaman',
            'code' => HousingComplex::generateCode(),
            'admin_user_id' => $admin->id,
        ]);
        $family = $this->familyWith(complex: $complex);

        Sanctum::actingAs($admin);

        $this->getJson("/api/families/{$family->id}")->assertStatus(403);
        $this->getJson("/api/families/{$family->id}/summary")->assertStatus(403);
        $this->getJson('/api/families')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_summary_is_forbidden_to_non_members(): void
    {
        $family = $this->familyWith();
        $stranger = User::factory()->create();

        Sanctum::actingAs($stranger);

        $this->getJson("/api/families/{$family->id}/summary")->assertStatus(403);
    }

    // ── devices ───────────────────────────────────────────────────────

    public function test_device_registration_stores_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/app/devices', [
            'fcm_token' => 'token-abc-123',
            'app_version' => '1.0.0',
        ])->assertStatus(201)->assertJsonPath('data.platform', 'android');

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'fcm_token' => 'token-abc-123',
            'app_version' => '1.0.0',
        ]);
    }

    public function test_re_posting_the_same_token_does_not_duplicate(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/app/devices', ['fcm_token' => 'same'])->assertStatus(201);
        $this->postJson('/api/app/devices', ['fcm_token' => 'same', 'app_version' => '1.1.0'])
            ->assertStatus(200);

        $this->assertCount(1, $user->devices);
        $this->assertDatabaseHas('user_devices', ['fcm_token' => 'same', 'app_version' => '1.1.0']);
    }

    public function test_token_is_reassigned_when_a_new_owner_posts_it(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        Sanctum::actingAs($first);
        $this->postJson('/api/app/devices', ['fcm_token' => 'shared'])->assertStatus(201);

        Sanctum::actingAs($second);
        $this->postJson('/api/app/devices', ['fcm_token' => 'shared'])->assertStatus(200);

        // Ownership moves; the first user must not keep receiving pushes to a
        // device that is no longer theirs.
        $this->assertCount(0, $first->fresh()->devices);
        $this->assertCount(1, $second->fresh()->devices);
    }

    public function test_device_registration_requires_authentication(): void
    {
        $this->postJson('/api/app/devices', ['fcm_token' => 'x'])->assertStatus(401);
    }

    public function test_device_token_is_required(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/app/devices', [])->assertStatus(422);
    }

    public function test_user_cannot_delete_another_users_device(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $device = UserDevice::create([
            'user_id' => $owner->id,
            'fcm_token' => 'victim-device',
            'last_seen_at' => now(),
        ]);

        Sanctum::actingAs($attacker);

        // 404 rather than 403: do not confirm the device exists.
        $this->deleteJson("/api/app/devices/{$device->id}")->assertStatus(404);
        $this->assertDatabaseHas('user_devices', ['id' => $device->id]);
    }

    public function test_user_can_delete_their_own_device(): void
    {
        $user = User::factory()->create();
        $device = UserDevice::create([
            'user_id' => $user->id,
            'fcm_token' => 'mine',
            'last_seen_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/app/devices/{$device->id}")->assertStatus(204);
        $this->assertDatabaseMissing('user_devices', ['id' => $device->id]);
    }
}
