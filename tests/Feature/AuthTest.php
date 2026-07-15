<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuthTest extends TestCase
{
    use RefreshDatabase;

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
