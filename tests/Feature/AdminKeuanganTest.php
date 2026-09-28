<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\UserManager;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

final class AdminKeuanganTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function plan(string $duration = 'yearly', int $price = 99_000): SubscriptionPlan
    {
        return SubscriptionPlan::query()->create([
            'slug' => 'test-'.str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT),
            'name' => 'Paket Uji',
            'duration_type' => $duration,
            'price' => $price,
            'is_active' => true,
        ]);
    }

    public function test_keuangan_pages_are_admin_only(): void
    {
        $normal = User::factory()->create();
        $admin = $this->admin();

        $this->get('/keuangan')->assertRedirect('/login');

        $this->actingAs($admin)->get('/keuangan')->assertOk();
        $this->actingAs($normal)->get('/keuangan')->assertForbidden();
    }

    public function test_make_admin_command_promotes_user(): void
    {
        $user = User::factory()->create();

        $this->artisan('users:make-admin', ['email' => $user->email])
            ->expectsOutputToContain('admin')
            ->assertExitCode(0);

        $this->assertTrue($user->fresh()->is_admin);
    }

    public function test_request_log_records_url_and_actor(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/keuangan');

        $this->assertDatabaseHas('request_logs', [
            'user_id' => $admin->id,
            'method' => 'GET',
            'status_code' => 200,
        ]);

        $this->assertDatabaseMissing('request_logs', ['url' => 'http://localhost/up']);
    }

    public function test_admin_can_create_user_and_issue_login_code(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('create')
            ->set('name', 'Pengguna Baru')
            ->set('email', 'baru@example.com')
            ->set('password', 'rahasia123')
            ->call('save');

        $this->assertDatabaseHas('users', ['email' => 'baru@example.com', 'setup_completed' => true]);

        $user = User::where('email', 'baru@example.com')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('issueCode', $user->id)
            ->assertOk()
            ->assertSee($user->email);

        $code = User::where('email', 'baru@example.com')->value('app_login_code');
        $this->assertStringStartsWith('sha256:', $code);
        $this->assertNotSame('', $code);
    }

    public function test_profile_about_is_updated_via_api(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->patchJson('/api/me', ['about' => 'Kepala keluarga, suka nabung', 'name' => 'Pa Bagus'])
            ->assertOk();

        $this->assertDatabaseHas('users', ['about' => 'Kepala keluarga, suka nabung', 'name' => 'Pa Bagus']);
    }

    public function test_charge_requires_midtrans_configuration(): void
    {
        $user = User::factory()->create();
        $plan = $this->plan();

        Sanctum::actingAs($user);

        $this->postJson('/api/subscription/charge', ['plan_id' => $plan->id])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Midtrans belum dikonfigurasi (MIDTRANS_SERVER_KEY kosong).']);

        $this->assertDatabaseHas('subscription_payments', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'pending',
        ]);
    }

    public function test_webhook_rejects_bad_signature(): void
    {
        $this->postJson('/api/payments/midtrans/notification', [
            'order_id' => 'KUA-1',
            'status_code' => '200',
            'gross_amount' => '99000',
            'signature_key' => 'wrong',
        ])->assertForbidden();
    }

    public function test_webhook_activates_subscription_on_settlement(): void
    {
        config(['services.midtrans.server_key' => 'SB-Mid-server-uji']);
        $user = User::factory()->create();
        $plan = $this->plan('yearly', 99_000);
        $payment = SubscriptionPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'order_id' => 'KUA-ORDER-1',
            'gross_amount' => 99_000,
            'status' => 'pending',
        ]);

        $signature = hash('sha512', 'KUA-ORDER-1'.'200'.'99000'.config('services.midtrans.server_key'));

        $this->postJson('/api/payments/midtrans/notification', [
            'order_id' => 'KUA-ORDER-1',
            'transaction_id' => 'TRX-1',
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'settlement',
            'status_code' => '200',
            'gross_amount' => '99000',
            'signature_key' => $signature,
        ])->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('TRX-1', $payment->fresh()->transaction_id);

        $subscription = Subscription::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('active', $subscription->status);
        $this->assertTrue($subscription->isUsable());
        $this->assertTrue($subscription->expires_at->isFuture());
    }

    public function test_webhook_is_idempotent(): void
    {
        config(['services.midtrans.server_key' => 'SB-Mid-server-uji']);
        $user = User::factory()->create();
        $plan = $this->plan('lifetime', 499_000);
        $payment = SubscriptionPayment::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'order_id' => 'KUA-IDEM-1',
            'gross_amount' => 499_000,
            'status' => 'pending',
        ]);

        $payload = [
            'order_id' => 'KUA-IDEM-1',
            'transaction_status' => 'settlement',
            'status_code' => '200',
            'gross_amount' => '499000',
            'signature_key' => hash('sha512', 'KUA-IDEM-1'.'200'.'499000'.config('services.midtrans.server_key')),
        ];

        $this->postJson('/api/payments/midtrans/notification', $payload)->assertOk();
        $this->postJson('/api/payments/midtrans/notification', $payload)->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame(1, Subscription::where('user_id', $user->id)->count(), 'Retry tidak boleh membuat langganan ganda.');
        $this->assertNull(Subscription::where('user_id', $user->id)->value('expires_at'), 'Lifetime tidak punya tanggal kedaluwarsa.');
    }

    public function test_current_subscription_shown_in_api_me(): void
    {
        $user = User::factory()->create();
        $plan = $this->plan();

        app(SubscriptionService::class)->activate($user, $plan, orderId: 'KUA-MANUAL-1', provider: 'manual');

        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.subscription.active', true)
            ->assertJsonPath('data.subscription.plan_name', $plan->name);

        $this->getJson('/api/subscription')
            ->assertOk()
            ->assertJsonPath('data.has_paid', true);
    }

    public function test_all_keuangan_pages_render_for_admin(): void
    {
        $admin = $this->admin();
        User::factory()->create();
        $this->plan('monthly');

        foreach (['', '/pengguna', '/paket', '/langganan', '/finansial', '/log'] as $path) {
            $this->actingAs($admin)->get('/keuangan'.$path)->assertOk();
        }
    }

    public function test_admin_can_grant_subscription(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $plan = $this->plan();

        $this->actingAs($admin)->get('/keuangan/langganan')->assertOk();

        app(SubscriptionService::class)->activate($user, $plan);

        $this->assertTrue($user->fresh()->hasActiveSubscription());
    }
}
