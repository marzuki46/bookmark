<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PromoCode;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class PromoCodeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_redeem_a_promo_once(): void
    {
        $user = User::factory()->create();
        SubscriptionPlan::create([
            'slug' => 'monthly', 'name' => 'Bulanan', 'duration_type' => 'monthly',
            'duration_days' => null, 'price' => 50000, 'is_active' => true,
        ]);
        PromoCode::create([
            'name' => 'Promo chat', 'code_hash' => hash('sha256', 'KANGCUAN1'),
            'duration_days' => 30, 'is_active' => true,
        ]);
        Sanctum::actingAs($user);

        $this->postJson('/api/subscription/redeem-promo', ['code' => 'KANG-CUAN-1'])
            ->assertOk()
            ->assertJsonPath('message', 'Kode promo berhasil digunakan. Langganan gratis sudah aktif.');

        $this->postJson('/api/subscription/redeem-promo', ['code' => 'KANGCUAN1'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'provider' => 'promo',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('promo_redemptions', ['user_id' => $user->id]);
    }

    public function test_same_promo_can_be_used_by_another_user_but_is_limited_by_total_quota(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        SubscriptionPlan::create([
            'slug' => 'monthly', 'name' => 'Bulanan', 'duration_type' => 'monthly',
            'duration_days' => null, 'price' => 50000, 'is_active' => true,
        ]);
        PromoCode::create([
            'name' => 'Promo terbatas', 'code_hash' => hash('sha256', 'TERBATAS'),
            'duration_days' => 60, 'max_redemptions' => 1, 'is_active' => true,
        ]);

        Sanctum::actingAs($user);
        $this->postJson('/api/subscription/redeem-promo', ['code' => 'TERBATAS'])->assertOk();
        Sanctum::actingAs($other);
        $this->postJson('/api/subscription/redeem-promo', ['code' => 'TERBATAS'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }
}
