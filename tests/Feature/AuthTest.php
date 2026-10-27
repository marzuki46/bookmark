<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register_from_the_app_without_otp(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Pengguna Baru',
            'email' => 'baru@example.com',
            'password' => 'rahasia-kuat',
            'password_confirmation' => 'rahasia-kuat',
            'device_name' => 'android',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'baru@example.com', 'name' => 'Pengguna Baru']);
        $this->assertDatabaseHas('families', ['owner_user_id' => User::where('email', 'baru@example.com')->value('id')]);
        $this->assertDatabaseHas('family_members', ['user_id' => User::where('email', 'baru@example.com')->value('id'), 'role' => 'owner']);
        $this->assertTrue(Hash::check('rahasia-kuat', User::where('email', 'baru@example.com')->firstOrFail()->password));
    }

    public function test_registration_rejects_duplicate_email_and_mismatched_password(): void
    {
        User::factory()->create(['email' => 'sudah@example.com']);

        $this->postJson('/api/register', [
            'name' => 'Pengguna',
            'email' => 'sudah@example.com',
            'password' => 'rahasia-kuat',
            'password_confirmation' => 'berbeda',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'marzuki@localhost',
            'password' => bcrypt('Sugihwara5'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'marzuki@localhost',
            'password' => 'Sugihwara5',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
    }

    public function test_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'marzuki@localhost',
            'password' => bcrypt('Sugihwara5'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'marzuki@localhost',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }
}
