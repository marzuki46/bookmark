<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\LoginCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class CodeAuthTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create();
    }

    public function test_issued_code_is_stored_hashed_never_in_plaintext(): void
    {
        $user = $this->user();
        $plain = app(LoginCodeService::class)->issueFor($user);

        $stored = $user->fresh()->app_login_code;

        $this->assertNotSame($plain, $stored, 'Kode tidak boleh disimpan apa adanya.');
        $this->assertNotSame('', $stored);
        $this->assertStringStartsWith('sha256:', $stored);
        $this->assertStringNotContainsString(
            str_replace('-', '', $plain),
            $stored,
            'Hash tidak boleh memuat isi kode.'
        );
    }

    public function test_generated_code_has_expected_shape_and_omits_ambiguous_characters(): void
    {
        $codes = app(LoginCodeService::class);

        for ($i = 0; $i < 40; $i++) {
            $code = $codes->generate();

            $this->assertMatchesRegularExpression(
                '/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{4}-[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{4}-[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{4}-[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{4}$/',
                $code,
            );

            // I, O, 0 and 1 are excluded so the code can be read aloud.
            $this->assertDoesNotMatchRegularExpression('/[IO01]/', $code);
        }
    }

    public function test_generated_codes_are_unique(): void
    {
        $codes = app(LoginCodeService::class);
        $seen = [];

        for ($i = 0; $i < 200; $i++) {
            $code = $codes->generate();
            $this->assertArrayNotHasKey($code, $seen, 'Kode duplikat: '.$code);
            $seen[$code] = true;
        }
    }

    public function test_lookup_is_insensitive_to_case_and_dashes(): void
    {
        $service = app(LoginCodeService::class);
        $user = $this->user();
        $plain = $service->issueFor($user);

        $mangled = strtolower(str_replace('-', '', $plain));
        $spaced = substr($plain, 0, 4).' '.substr($plain, 5);

        $this->assertTrue($service->findUserByCode($mangled)?->is($user));
        $this->assertTrue($service->findUserByCode($spaced)?->is($user));
    }

    public function test_login_with_valid_code_returns_token(): void
    {
        $user = $this->user();
        $code = app(LoginCodeService::class)->issueFor($user);

        $response = $this->postJson('/api/app/login', [
            'code' => $code,
            'device_name' => 'pixel-test',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']])
            ->assertJsonPath('user.id', $user->id);

        $this->assertCount(1, $user->tokens);
    }

    public function test_login_never_leaks_the_code_digest(): void
    {
        $user = $this->user();
        $code = app(LoginCodeService::class)->issueFor($user);

        $body = $this->postJson('/api/app/login', ['code' => $code])
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString($user->fresh()->app_login_code, $body);
    }

    public function test_login_with_unknown_code_returns_generic_401(): void
    {
        $response = $this->postJson('/api/app/login', ['code' => 'AAAA-BBBB-CCCC-DDDD']);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Kode login tidak valid.']);
    }

    public function test_user_without_code_cannot_log_in(): void
    {
        $user = $this->user();

        $this->assertNull($user->app_login_code);

        $this->postJson('/api/app/login', ['code' => 'ZZZZ-YYYY-XXXX-WWWW'])
            ->assertStatus(401);
    }

    public function test_malformed_code_length_is_rejected_without_db_roundtrip(): void
    {
        $service = app(LoginCodeService::class);

        $this->assertNull($service->findUserByCode('ABCD'));
        $this->assertNull($service->findUserByCode(''));
        $this->assertNull($service->findUserByCode(str_repeat('A', 200)));
    }

    public function test_login_is_rate_limited(): void
    {
        // Route is throttled 5/min, so the 6th attempt must be blocked.
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/app/login', ['code' => 'AAAA-BBBB-CCCC-DDDD'])
                ->assertStatus(401);
        }

        $this->postJson('/api/app/login', ['code' => 'AAAA-BBBB-CCCC-DDDD'])
            ->assertStatus(429);
    }

    public function test_rotate_requires_authentication(): void
    {
        $this->postJson('/api/app/login-code/rotate')->assertStatus(401);
    }

    public function test_rotate_returns_new_code_and_revokes_previous_tokens(): void
    {
        $user = $this->user();
        $service = app(LoginCodeService::class);
        $old = $service->issueFor($user);
        $staleToken = $user->createToken('old-device')->plainTextToken;

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/app/login-code/rotate');

        $response->assertOk()->assertJsonStructure(['code', 'token', 'rotated_at', 'message']);

        $new = $response->json('code');
        $this->assertNotSame($old, $new);

        // Exactly one token survives: the previous device was revoked and the
        // caller was handed a replacement.
        $this->assertCount(1, $user->fresh()->tokens);

        // The old code no longer authenticates.
        $this->postJson('/api/app/login', ['code' => $old])->assertStatus(401);

        // The new one does.
        $this->postJson('/api/app/login', ['code' => $new])->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $staleToken]);
    }
}
