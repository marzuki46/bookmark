<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\LicenseManager;
use App\Livewire\Admin\PlanManager;
use App\Livewire\Admin\UserFinances;
use App\Livewire\Admin\UserManager;
use App\Models\Family;
use App\Models\FamilyAiUsage;
use App\Models\FamilyDebt;
use App\Models\FamilyMember;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\FamilyEntitlementService;
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

        foreach (['', '/pengguna', '/paket', '/langganan', '/finansial', '/keluarga', '/log', '/aplikasi-manajemen', '/aplikasi'] as $path) {
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

    public function test_user_finances_debt_total_uses_remaining_accessor(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $family = Family::create([
            'name' => 'Keluarga Uji',
            'owner_user_id' => $user->id,
            'invite_code' => Family::generateInviteCode(),
        ]);
        FamilyMember::create(['family_id' => $family->id, 'user_id' => $user->id, 'role' => 'owner']);
        FamilyDebt::create([
            'family_id' => $family->id,
            'name' => 'KPR',
            'type' => 'payable',
            'amount' => 100_000,
            'paid_amount' => 40_000,
            'installment' => 25_000,
        ]);
        FamilyDebt::create([
            'family_id' => $family->id,
            'name' => 'Lunas',
            'type' => 'payable',
            'amount' => 30_000,
            'status' => 'settled',
        ]);

        $component = Livewire::actingAs($admin)->test(UserFinances::class, ['userId' => $user->id]);

        $component->assertOk();
        $component->assertSet('debts.total', 60_000.0);
        $component->assertSet('debts.installment', 25_000.0);
        $component->assertSet('debts.count', 1);
    }

    public function test_admin_can_update_family_member_visibility_from_family_management(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        $child = User::factory()->create();
        $family = Family::create([
            'name' => 'Keluarga Permission',
            'owner_user_id' => $owner->id,
            'invite_code' => Family::generateInviteCode(),
        ]);
        FamilyMember::create(['family_id' => $family->id, 'user_id' => $owner->id, 'role' => 'owner']);
        FamilyMember::create([
            'family_id' => $family->id,
            'user_id' => $child->id,
            'role' => 'member',
            'relationship' => 'child',
            'visibility' => ['income' => false, 'expense' => false, 'debts' => false],
        ]);

        Livewire::actingAs($admin)
            ->test(UserFinances::class, ['familyId' => $family->id])
            ->call('editMember', $child->id)
            ->assertSet('editingMemberId', $child->id)
            ->set('memberRelationship', 'adult')
            ->set('memberCanViewIncome', true)
            ->set('memberCanViewExpense', true)
            ->set('memberCanViewDebts', true)
            ->call('saveMemberSettings')
            ->assertSet('editingMemberId', null);

        $this->assertSame('adult', $child->familyMember()->fresh()->relationship);
        $this->assertSame([
            'income' => true,
            'expense' => true,
            'debts' => true,
        ], $child->familyMember()->fresh()->visibility);
    }

    public function test_license_manager_can_grant_family_license(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $family = Family::create([
            'name' => 'Keluarga Lisensi',
            'owner_user_id' => $user->id,
            'invite_code' => Family::generateInviteCode(),
        ]);
        FamilyMember::create(['family_id' => $family->id, 'user_id' => $user->id, 'role' => 'owner']);
        $plan = $this->plan('yearly');

        Livewire::actingAs($admin)
            ->test(LicenseManager::class)
            ->call('openGrant', $family->id)
            ->set('grantPlanId', $plan->id)
            ->call('grant')
            ->assertSet('editFamilyId', null)
            ->assertStatus(200);

        $sub = Subscription::query()->where('family_id', $family->id)->latest('id')->first();
        $this->assertNotNull($sub);
        $this->assertSame('active', $sub->status);
        $this->assertNotNull($sub->expires_at);
    }

    public function test_license_manager_can_set_custom_expiry(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $family = Family::create([
            'name' => 'Keluarga Expiry',
            'owner_user_id' => $user->id,
            'invite_code' => Family::generateInviteCode(),
        ]);
        FamilyMember::create(['family_id' => $family->id, 'user_id' => $user->id, 'role' => 'owner']);
        $plan = $this->plan('yearly');
        app(SubscriptionService::class)->activate($user, $plan);

        $future = now()->addMonths(3)->format('Y-m-d');

        Livewire::actingAs($admin)
            ->test(LicenseManager::class)
            ->call('openExtend', $family->id)
            ->set('editExpiresAt', $future)
            ->call('saveExpiry')
            ->assertSet('editFamilyId', null);

        $sub = Subscription::query()->where('family_id', $family->id)->latest('id')->first();
        $this->assertSame($future, $sub->expires_at->format('Y-m-d'));
    }

    public function test_license_manager_can_revoke_family_license(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $family = Family::create([
            'name' => 'Keluarga Cabut',
            'owner_user_id' => $user->id,
            'invite_code' => Family::generateInviteCode(),
        ]);
        FamilyMember::create(['family_id' => $family->id, 'user_id' => $user->id, 'role' => 'owner']);
        $plan = $this->plan('yearly');
        app(SubscriptionService::class)->activate($user, $plan);

        Livewire::actingAs($admin)
            ->test(LicenseManager::class)
            ->call('revoke', $family->id)
            ->assertStatus(200);

        $this->assertSame('cancelled', Subscription::query()->where('family_id', $family->id)->latest('id')->first()->status);
    }

    public function test_plan_manager_create_shows_form_and_saves(): void
    {
        $admin = $this->admin();

        $component = Livewire::actingAs($admin)
            ->test(PlanManager::class)
            ->call('create')
            ->assertSet('showForm', true);

        $component->set('name', 'Paket Premium')
            ->set('description', 'Akses semua fitur')
            ->set('durationType', 'custom')
            ->set('durationDays', 7)
            ->set('aiAnalysisLimit', 3)
            ->set('price', 250_000)
            ->call('save')
            ->assertSet('showForm', false)
            ->assertHasNoErrors();

        $plan = SubscriptionPlan::query()->where('slug', 'paket-premium')->first();
        $this->assertNotNull($plan);
        $this->assertSame('Paket Premium', $plan->name);
        $this->assertSame('monthly', $plan->duration_type);
        $this->assertSame(7, $plan->duration_days);
        $this->assertSame(3, $plan->ai_analysis_limit);
        $this->assertSame(250_000, $plan->price);
        $this->assertTrue($plan->is_active);
    }

    public function test_plan_manager_cancel_hides_form(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(PlanManager::class)
            ->call('create')
            ->assertSet('showForm', true)
            ->call('cancel')
            ->assertSet('showForm', false);
    }

    public function test_custom_plan_duration_and_ai_limit_are_enforced(): void
    {
        $user = User::factory()->create();
        $family = Family::create([
            'name' => 'Keluarga Trial',
            'owner_user_id' => $user->id,
            'invite_code' => Family::generateInviteCode(),
        ]);
        FamilyMember::create(['family_id' => $family->id, 'user_id' => $user->id, 'role' => 'owner']);
        $plan = $this->plan('monthly');
        $plan->update(['duration_days' => 7, 'ai_analysis_limit' => 1]);

        $subscription = app(SubscriptionService::class)->activate($user, $plan, provider: 'trial');
        $this->assertEqualsWithDelta(7, now()->diffInDays($subscription->expires_at), 0.001);

        $entitlements = app(FamilyEntitlementService::class);
        $this->assertTrue($entitlements->consumeAiAnalysis($family));
        $this->assertFalse($entitlements->consumeAiAnalysis($family));
        $this->assertSame(1, FamilyAiUsage::query()->where('family_id', $family->id)->value('analysis_count'));
    }
}
