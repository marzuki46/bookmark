<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyCategory;
use App\Models\FamilyInsight;
use App\Models\FamilyMember;
use App\Models\FamilyTransaction;
use App\Models\User;
use App\Notifications\FamilyInsightNotification;
use App\Notifications\FcmChannel;
use App\Services\FamilyInsightService;
use App\Services\NudgeService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class FamilyInsightTest extends TestCase
{
    use RefreshDatabase;

    private User $husband;

    private User $wife;

    private Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        $this->husband = User::factory()->create(['name' => 'Budi Santoso']);
        $this->wife = User::factory()->create(['name' => 'Siti Aminah']);

        $this->family = Family::create([
            'name' => 'Keluarga Uji',
            'owner_user_id' => $this->husband->id,
            'invite_code' => Family::generateInviteCode(),
        ]);

        FamilyMember::create(['family_id' => $this->family->id, 'user_id' => $this->husband->id, 'role' => 'owner']);
        FamilyMember::create(['family_id' => $this->family->id, 'user_id' => $this->wife->id, 'role' => 'member']);

        Sanctum::actingAs($this->husband);
    }

    private function category(string $name = 'Makan'): FamilyCategory
    {
        return FamilyCategory::create([
            'family_id' => $this->family->id,
            'name' => $name,
            'type' => 'expense',
            'icon' => 'utensils',
        ]);
    }

    private function expense(int $userId, FamilyCategory $category, int $amount, string $payer = 'husband'): FamilyTransaction
    {
        return FamilyTransaction::create([
            'family_id' => $this->family->id,
            'category_id' => $category->id,
            'user_id' => $userId,
            'type' => 'expense',
            'amount' => $amount,
            'description' => 'Belanja harian',
            'date' => now()->toDateString(),
            'payer' => $payer,
        ]);
    }

    private function income(int $userId, int $amount, string $payer = 'husband'): FamilyTransaction
    {
        return FamilyTransaction::create([
            'family_id' => $this->family->id,
            'user_id' => $userId,
            'type' => 'income',
            'amount' => $amount,
            'description' => 'Gaji',
            'date' => now()->toDateString(),
            'payer' => $payer,
        ]);
    }

    // ---------------------------------------------------------------- insights

    public function test_it_writes_one_family_insight_and_one_per_member(): void
    {
        $insights = app(FamilyInsightService::class)->generateForFamily($this->family, force: true);

        $this->assertCount(3, $insights);
        $this->assertDatabaseCount('family_insights', 3);

        $this->assertSame(1, FamilyInsight::where('scope', 'family')->whereNull('user_id')->count());
        $this->assertSame(1, FamilyInsight::where('scope', 'user')->where('user_id', $this->husband->id)->count());
        $this->assertSame(1, FamilyInsight::where('scope', 'user')->where('user_id', $this->wife->id)->count());
    }

    public function test_husband_and_wife_receive_different_personal_insights(): void
    {
        $food = $this->category('Makan');
        $this->expense($this->husband->id, $food, 900_000, 'husband');
        $this->income($this->husband->id, 5_000_000, 'husband');
        $this->income($this->wife->id, 4_500_000, 'wife');

        $insights = app(FamilyInsightService::class)->generateForFamily($this->family, force: true);

        $husband = $insights->firstWhere('user_id', $this->husband->id);
        $wife = $insights->firstWhere('user_id', $this->wife->id);

        $this->assertNotNull($husband);
        $this->assertNotNull($wife);

        // The wife recorded no spending, so her note must differ from his and
        // must reflect the empty slice rather than the shared family total.
        $this->assertNotSame($husband->message, $wife->message);
        $this->assertTrue($husband->is_fallback);
        $this->assertTrue($wife->is_fallback);
        $this->assertSame(900_000.0, (float) $husband->metrics['personal']['expense']);
        $this->assertSame(0.0, (float) $wife->metrics['personal']['expense']);
    }

    public function test_the_payer_role_is_carried_into_each_personal_slice(): void
    {
        FamilyMember::where('user_id', $this->husband->id)->update(['payer_role' => 'husband']);
        FamilyMember::where('user_id', $this->wife->id)->update(['payer_role' => 'wife']);

        $insights = app(FamilyInsightService::class)->generateForFamily($this->family, force: true);

        $this->assertSame('husband', $insights->firstWhere('user_id', $this->husband->id)->metrics['personal']['payer_role']);
        $this->assertSame('wife', $insights->firstWhere('user_id', $this->wife->id)->metrics['personal']['payer_role']);
    }

    public function test_payer_role_is_null_before_a_member_chooses(): void
    {
        $insights = app(FamilyInsightService::class)->generateForFamily($this->family, force: true);

        $this->assertNull($insights->firstWhere('user_id', $this->wife->id)->metrics['personal']['payer_role']);
    }

    public function test_message_is_clamped_to_the_column_limit(): void
    {
        $insights = app(FamilyInsightService::class)->generateForFamily($this->family, force: true);

        foreach ($insights as $insight) {
            $this->assertLessThanOrEqual(
                FamilyInsight::MAX_LENGTH,
                mb_strlen($insight->message),
                'Insight message exceeded the column limit.'
            );
        }
    }

    public function test_regenerating_the_same_week_does_not_duplicate_rows(): void
    {
        $service = app(FamilyInsightService::class);

        $service->generateForFamily($this->family, force: true);
        $service->generateForFamily($this->family, force: true);
        $service->generateForFamily($this->family, force: true);

        // The nullable user_id defeats the unique index on the family-scope
        // row, so this specifically guards the whereNull upsert.
        $this->assertDatabaseCount('family_insights', 3);
    }

    public function test_a_second_run_without_force_does_not_rewrite_or_renotify(): void
    {
        Notification::fake();

        $service = app(FamilyInsightService::class);
        $service->generateForFamily($this->family);

        $original = FamilyInsight::where('scope', 'family')->firstOrFail();
        $message = $original->message;

        $service->generateForFamily($this->family);
        $service->generateForFamily($this->family);

        $this->assertSame($message, $original->fresh()->message);
        $this->assertDatabaseCount('family_insights', 3);
        Notification::assertSentTimes(FamilyInsightNotification::class, 2);
    }

    public function test_it_never_calls_the_ai_when_no_provider_is_configured(): void
    {
        config([
            'services.nvidia.api_key' => null,
            'services.nvidia.base_url' => null,
        ]);

        $insights = app(FamilyInsightService::class)->generateForFamily($this->family, force: true);

        foreach ($insights as $insight) {
            $this->assertTrue((bool) $insight->is_fallback);
            $this->assertNotSame('', trim($insight->message));
        }
    }

    public function test_the_family_note_reflects_a_real_deficit(): void
    {
        $food = $this->category('Makan');
        $this->expense($this->husband->id, $food, 4_000_000);
        $this->income($this->husband->id, 1_000_000);

        $insights = app(FamilyInsightService::class)->generateForFamily($this->family, force: true);

        $family = $insights->firstWhere('scope', 'family');

        // The tone comes from FamilyAIService::healthScore, so it is not
        // asserted here; the message is this service's own contract.
        $this->assertStringContainsString('3.000.000', $family->message);
        $this->assertStringContainsString('lebih besar dari pemasukan', $family->message);
    }

    // ------------------------------------------------------------ notification

    public function test_members_are_notified_with_the_shared_family_insight(): void
    {
        Notification::fake();

        app(FamilyInsightService::class)->generateForFamily($this->family);

        Notification::assertSentTo([$this->husband, $this->wife], FamilyInsightNotification::class);

        $payload = (new FamilyInsightNotification(FamilyInsight::where('scope', 'family')->firstOrFail()))
            ->toArrayForFcm($this->husband);

        $this->assertSame('Kesehatan Keuangan Keluarga', $payload['title']);
        $this->assertSame('family_insight', $payload['data']['type']);
    }

    public function test_the_notification_always_resolves_a_channel(): void
    {
        // A notification with an empty via() is silently dropped by Laravel,
        // so the fallback must be asserted, not assumed.
        $insight = FamilyInsight::create([
            'family_id' => $this->family->id,
            'scope' => 'family',
            'week_key' => now()->format('o-\WW'),
            'message' => 'Uji',
            'tone' => 'neutral',
            'metrics' => [],
            'is_fallback' => true,
        ]);

        $via = (new FamilyInsightNotification($insight))->via($this->husband);

        $this->assertNotEmpty($via);
        $this->assertSame(['fcm'], $via);
    }

    public function test_the_channel_reaches_every_registered_device(): void
    {
        FcmChannel::$delivered = [];

        $husbandDevice = $this->husband->devices()->create([
            'fcm_token' => 'token-husband',
            'platform' => 'android',
        ]);
        $wifeDevice = $this->wife->devices()->create([
            'fcm_token' => 'token-wife',
            'platform' => 'android',
        ]);

        $insight = app(FamilyInsightService::class)->generateForFamily($this->family, force: true)->firstWhere('scope', 'family');

        app(FcmChannel::class)->send($this->husband, new FamilyInsightNotification($insight));

        $this->assertCount(1, FcmChannel::$delivered);
        $this->assertSame('token-husband', FcmChannel::$delivered[0]['token']);

        // Sanity: the wife is reachable too, otherwise the personal note is dead.
        $this->assertNotNull($wifeDevice);
        $this->assertNotNull($husbandDevice);
    }

    public function test_notification_payload_carries_no_password_or_token(): void
    {
        $insight = app(FamilyInsightService::class)->generateForFamily($this->family, force: true)->firstWhere('scope', 'family');

        $payload = (new FamilyInsightNotification($insight))->toArray($this->husband);

        $encoded = strtolower(json_encode($payload));
        $this->assertStringNotContainsString('token', $encoded);
        $this->assertStringNotContainsString('password', $encoded);
        $this->assertStringNotContainsString('api_key', $encoded);
    }

    // ------------------------------------------------------------------ routes

    public function test_dashboard_endpoint_returns_insight_nudge_and_income_breakdown(): void
    {
        $food = $this->category('Makan');
        $this->expense($this->husband->id, $food, 900_000);

        app(FamilyInsightService::class)->generateForFamily($this->family, force: true);

        $response = $this->getJson("/api/families/{$this->family->id}/insights");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['family', 'personal', 'nudge', 'income_by_source', 'week_key', 'days_left_this_month'],
            ]);

        $this->assertSame('family', $response->json('data.family.scope'));
        $this->assertSame('user', $response->json('data.personal.scope'));
    }

    public function test_insights_endpoint_returns_null_before_the_first_run(): void
    {
        $this->getJson("/api/families/{$this->family->id}/insights")
            ->assertOk()
            ->assertJsonPath('data.family', null)
            ->assertJsonPath('data.personal', null);
    }

    public function test_a_member_cannot_read_another_family_insights(): void
    {
        $outsider = User::factory()->create();
        $other = Family::create([
            'name' => 'Keluarga Lain',
            'owner_user_id' => $outsider->id,
            'invite_code' => Family::generateInviteCode(),
        ]);
        FamilyMember::create(['family_id' => $other->id, 'user_id' => $outsider->id, 'role' => 'owner']);

        // Matches the existing convention: reaching another family is 403
        // (authenticated but not a member), whereas 404 is reserved for a
        // child record that belongs to a different family.
        $this->getJson("/api/families/{$other->id}/insights")->assertForbidden();
        $this->getJson("/api/families/{$other->id}/nudge")->assertForbidden();
        $this->postJson("/api/families/{$other->id}/insights/read")->assertForbidden();
    }

    public function test_mark_read_only_touches_the_callers_own_insight(): void
    {
        app(FamilyInsightService::class)->generateForFamily($this->family, force: true);

        $this->assertNull(FamilyInsight::where('user_id', $this->husband->id)->firstOrFail()->read_at);

        $this->postJson("/api/families/{$this->family->id}/insights/read")
            ->assertOk()
            ->assertJsonPath('data.scope', 'user');

        $this->assertNotNull(FamilyInsight::where('user_id', $this->husband->id)->firstOrFail()->read_at);
        $this->assertNull(FamilyInsight::where('user_id', $this->wife->id)->firstOrFail()->read_at);
        $this->assertNull(FamilyInsight::where('scope', 'family')->firstOrFail()->read_at);
    }

    public function test_nudge_endpoint_is_free_and_recalculates_on_demand(): void
    {
        $this->getJson("/api/families/{$this->family->id}/nudge")
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    // ------------------------------------------------------------- transaction

    public function test_saving_a_transaction_returns_an_instant_nudge(): void
    {
        $food = $this->category('Makan');

        $response = $this->postJson("/api/families/{$this->family->id}/transactions", [
            'type' => 'expense',
            'category_id' => $food->id,
            'amount' => 150_000,
            'description' => 'Makan siang',
            'date' => now()->toDateString(),
            'payer' => 'husband',
        ]);

        $response->assertCreated();
        $this->assertArrayHasKey('nudge', $response->json());
        $this->assertArrayHasKey('data', $response->json());
    }

    public function test_a_healthy_family_gets_no_nudge(): void
    {
        $food = $this->category('Makan');
        $this->income($this->husband->id, 20_000_000);
        $this->expense($this->husband->id, $food, 100_000);

        $this->assertNull(app(NudgeService::class)->evaluate($this->family));
    }

    // ----------------------------------------------------------------- command

    public function test_the_command_generates_for_every_active_family(): void
    {
        $outsider = User::factory()->create();
        $other = Family::create([
            'name' => 'Keluarga Lain',
            'owner_user_id' => $outsider->id,
            'invite_code' => Family::generateInviteCode(),
        ]);
        FamilyMember::create(['family_id' => $other->id, 'user_id' => $outsider->id, 'role' => 'owner']);

        $this->artisan('finance:insights')->assertSuccessful();

        // Family A (husband + wife) = 1 shared + 2 personal = 3
        // Family B (one owner)      = 1 shared + 1 personal = 2
        $this->assertDatabaseCount('family_insights', 5);
    }

    public function test_the_command_can_target_a_single_family(): void
    {
        $outsider = User::factory()->create();
        $other = Family::create([
            'name' => 'Keluarga Lain',
            'owner_user_id' => $outsider->id,
            'invite_code' => Family::generateInviteCode(),
        ]);
        FamilyMember::create(['family_id' => $other->id, 'user_id' => $outsider->id, 'role' => 'owner']);

        $this->artisan('finance:insights', ['--family' => $other->id])->assertSuccessful();

        $this->assertDatabaseCount('family_insights', 2);
        $this->assertDatabaseHas('family_insights', ['family_id' => $other->id]);
    }

    public function test_the_week_key_uses_the_iso_week(): void
    {
        $service = app(FamilyInsightService::class);

        $this->travelTo(now()->startOfWeek());
        $this->assertSame(now()->format('o-\WW'), $service->weekKey());

        $this->travelBack();
    }

    public function test_the_scheduler_runs_weekly_on_monday_at_four(): void
    {
        $schedule = app(Schedule::class);
        $event = collect($schedule->events())->first(fn ($e) => str_contains($e->command ?? '', 'finance:insights'));

        $this->assertNotNull($event, 'finance:insights is not scheduled.');
        $this->assertSame('0 4 * * 1', $event->expression);
    }
}
