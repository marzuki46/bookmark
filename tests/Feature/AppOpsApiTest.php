<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AppErrorLog;
use App\Models\AppRelease;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class AppOpsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();

        $this->family = Family::create([
            'name' => 'Keluarga Operasi',
            'owner_user_id' => $this->owner->id,
            'invite_code' => Family::generateInviteCode(),
        ]);

        FamilyMember::create([
            'family_id' => $this->family->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
        ]);
    }

    // ─── In-app update ───

    public function test_update_check_reports_available_version(): void
    {
        config(['app.version_code' => 12, 'app.version_name' => '2.1.0']);

        $response = $this->getJson('/api/app/updates?current_version_code=11')
            ->assertOk();

        $this->assertSame(12, $response->json('data.latest_version_code'));
        $this->assertSame('2.1.0', $response->json('data.latest_version_name'));
        $this->assertTrue($response->json('data.update_available'));
        $this->assertStringContainsString('perbaikan', strtolower((string) $response->json('data.notes')));
    }

    public function test_update_check_is_noop_when_current_is_newer(): void
    {
        config(['app.version_code' => 3]);

        $this->getJson('/api/app/updates?current_version_code=5')
            ->assertOk()
            ->assertJsonPath('data.update_available', false);
    }

    public function test_uploaded_release_takes_precedence_over_config(): void
    {
        Storage::fake('local');
        $admin = $this->owner;
        $admin->update(['is_admin' => true]);

        $apk = UploadedFile::fake()->create('app.apk', 2048);
        $apk->mimeType('application/vnd.android.package-archive');

        $this->actingAs($admin)->from(route('keuangan.aplikasi'))
            ->post(route('keuangan.aplikasi.store'), [
                'apk' => $apk,
                'version_code' => 24,
                'version_name' => '3.1.0',
                'notes' => 'Tema baru dan perbaikan',
            ])->assertRedirect(route('keuangan.aplikasi'));

        Storage::disk('local')->assertExists('apk/keuangan-3.1.0-24.apk');
        $this->assertDatabaseHas('app_releases', ['version_code' => 24, 'version_name' => '3.1.0']);

        config(['app.version_code' => 99]);

        $response = $this->getJson('/api/app/updates?current_version_code=23')
            ->assertOk()
            ->assertJsonPath('data.latest_version_code', 24)
            ->assertJsonPath('data.latest_version_name', '3.1.0');

        $this->assertStringContainsString('/apk/download/', (string) $response->json('data.download_url'));
        $this->assertTrue($response->json('data.update_available'));

        $this->get($response->json('data.download_url'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.android.package-archive');
    }

    // ─── Crash reporting ───

    public function test_anonymous_crash_report_is_stored(): void
    {
        $this->postJson('/api/app/errors', [
            'error_class' => 'java.lang.NullPointerException',
            'message' => 'Ulas stack meledak',
            'route' => 'ApiClient.getBudget',
            'stack_trace' => "java.lang.NullPointerException: Ulas\n\tat keuangan.app.ApiClient",
            'app_version' => '1.0.0',
            'platform' => 'android',
            'occurred_at' => '2026-09-28T10:00:00Z',
        ])->assertStatus(202)->assertJsonPath('ok', true);

        $this->assertDatabaseHas('app_error_logs', [
            'error_class' => 'java.lang.NullPointerException',
            'message' => 'Ulas stack meledak',
            'app_version' => '1.0.0',
            'platform' => 'android',
            'user_id' => null,
        ]);
    }

    public function test_authenticated_crash_report_keeps_user_and_family(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson('/api/app/errors', [
            'error_class' => 'java.io.IOException',
            'message' => 'Gagal muat dashboard',
        ])->assertStatus(202);

        $log = AppErrorLog::firstOrFail();
        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertSame($this->family->id, $log->family_id);
    }

    public function test_crash_report_rejects_oversized_stack(): void
    {
        $this->postJson('/api/app/errors', ['stack_trace' => str_repeat('x', 25_000)])
            ->assertStatus(422);
    }

    // ─── Family login code ───

    public function test_owner_can_see_their_own_login_code(): void
    {
        Sanctum::actingAs($this->owner);

        $this->getJson("/api/families/{$this->family->id}/login-code")
            ->assertOk()
            ->assertJsonStructure(['data' => ['code']]);
    }

    public function test_login_code_repeat_returns_the_same_code(): void
    {
        Sanctum::actingAs($this->owner);

        $first = $this->getJson("/api/families/{$this->family->id}/login-code")->json('data.code');
        $second = $this->getJson("/api/families/{$this->family->id}/login-code")->json('data.code');

        $this->assertSame($first, $second);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $first);
    }

    public function test_non_member_cannot_read_the_login_code(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/families/{$this->family->id}/login-code")->assertStatus(403);
    }

    // ─── Family member creation (spouse) ───

    public function test_owner_adds_the_wife_and_gets_her_login_code(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->postJson("/api/families/{$this->family->id}/members", [
            'name' => 'Siti',
            'payer_role' => 'wife',
        ])->assertStatus(201);

        $this->assertSame('Siti', $response->json('data.name'));
        $this->assertSame('wife', $response->json('data.payer_role'));
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $response->json('data.login_code'));

        $this->assertDatabaseHas('family_members', [
            'family_id' => $this->family->id,
            'user_id' => $response->json('data.user_id'),
            'role' => 'member',
            'is_family_only' => true,
            'payer_role' => 'wife',
        ]);
    }

    public function test_plain_member_cannot_add_members(): void
    {
        $wife = User::factory()->create();
        FamilyMember::create([
            'family_id' => $this->family->id,
            'user_id' => $wife->id,
            'role' => 'member',
        ]);

        Sanctum::actingAs($wife);

        $this->postJson("/api/families/{$this->family->id}/members", ['name' => 'Encik'])
            ->assertStatus(403);
    }

    public function test_taken_payer_role_is_rejected(): void
    {
        $wife = User::factory()->create();
        FamilyMember::create([
            'family_id' => $this->family->id,
            'user_id' => $wife->id,
            'role' => 'member',
            'payer_role' => 'wife',
        ]);

        Sanctum::actingAs($this->owner);

        $this->postJson("/api/families/{$this->family->id}/members", [
            'name' => 'Istri Baru',
            'payer_role' => 'wife',
        ])->assertStatus(422);
    }
}