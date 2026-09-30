<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Family;

use App\Http\Controllers\Controller;
use App\Models\AppRelease;
use App\Models\Family;
use App\Models\FamilyBudget;
use App\Models\FamilyDebt;
use App\Models\FamilyGoal;
use App\Models\FamilyMember;
use App\Models\FamilyTransaction;
use App\Models\User;
use App\Services\FamilyAIService;
use App\Services\FamilyVisibilityService;
use App\Services\LoginCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

final class FamilyController extends Controller
{
    /** Hard cap so the household stays small and personal. */
    private const MAX_MEMBERS = 5;

    public function __construct(
        private readonly FamilyAIService $ai,
        private readonly LoginCodeService $codes,
        private readonly FamilyVisibilityService $visibility,
    ) {}

    /**
     * The caller's own family.
     *
     * A user belongs to exactly one family (enforced by FamilyMember), so this
     * returns at most one entry. The array shape is kept so the app can treat
     * "no family yet" and "has a family" without a special case: an account
     * with no family gets [], which is what a fresh login-code account sees
     * before the husband creates the household.
     *
     * A housing-complex admin sees [] too, because admin status grants no
     * membership and therefore no visibility into any tenant's finances.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $family = $user->family();

        if ($family === null) {
            return response()->json(['data' => []]);
        }

        $family->load(['housingComplex:id,name,code'])->loadCount('members');

        return response()->json([
            'data' => [[
                'id' => $family->id,
                'name' => $family->name,
                'role' => $user->familyMember()?->role,
                'payer_role' => $user->payerRole(),
                'payer_label' => $user->payerLabel(),
                'members_count' => $family->members_count,
                'housing_complex' => $family->housingComplex ? [
                    'id' => $family->housingComplex->id,
                    'name' => $family->housingComplex->name,
                    'code' => $family->housingComplex->code,
                ] : null,
            ]],
        ]);
    }

    public function show(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $family->load(['housingComplex:id,name', 'members.user:id,name,email'])
            ->loadCount('members');

        return response()->json([
            'data' => [
                'id' => $family->id,
                'name' => $family->name,
                'role' => $request->user()->familyMember()?->role,
                'payer_role' => $request->user()->payerRole(),
                'payer_label' => $request->user()->payerLabel(),
                'members_count' => $family->members_count,
                'housing_complex' => $family->housingComplex ? [
                    'id' => $family->housingComplex->id,
                    'name' => $family->housingComplex->name,
                ] : null,
                'members' => $family->members->map(fn ($member): array => [
                    'user_id' => $member->user_id,
                    'role' => $member->role,
                    'name' => $member->user?->name,
                    'payer_role' => $member->payer_role,
                    'payer_label' => $member->payerLabel(),
                    'relationship' => $member->relationship,
                    'visibility' => $member->user_id === $request->user()->id || $request->user()->isFamilyOwner()
                        ? $member->visibility
                        : null,
                ])->values(),
            ],
        ]);
    }

    /**
     * Lets the caller declare themselves as the husband or the wife.
     *
     * Self-service and owner-only: it describes who the caller is, not anyone
     * else. A second member cannot relabel the spouse, because that would let
     * one partner rewrite whose spending the other one is charged for.
     */
    public function updateMyProfile(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $data = $request->validate([
            'payer_role' => ['required', 'in:husband,wife'],
        ]);

        $member = $request->user()->familyMember();

        abort_if($member === null || $member->family_id !== $family->id, 404);

        // Two members cannot both claim the same role, otherwise "husband's
        // spending" would be ambiguous.
        $taken = FamilyMember::query()
            ->where('family_id', $family->id)
            ->where('payer_role', $data['payer_role'])
            ->where('user_id', '!=', $request->user()->id)
            ->exists();

        if ($taken) {
            return response()->json([
                'message' => 'Peran itu sudah dipakai anggota keluarga lain.',
                'errors' => ['payer_role' => ['Peran ini sudah dipakai anggota keluarga lain.']],
            ], 422);
        }

        $member->update(['payer_role' => $data['payer_role']]);

        return response()->json([
            'data' => [
                'user_id' => $member->user_id,
                'payer_role' => $member->payer_role,
                'payer_label' => $member->payerLabel(),
            ],
        ]);
    }

    /**
     * The caller's own permanent login code, so the family screen can show it
     * and the spouse can log in on their own device. Re-shows the existing
     * code; only rotates when no stored plaintext survives (e.g. legacy user).
     */
    public function loginCode(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $user = $request->user();
        $member = $user->familyMember();

        abort_if($member === null || $member->family_id !== $family->id, 404);

        $code = $this->codes->displayCodeFor($user) ?? $this->codes->issueFor($user);

        return response()->json([
            'data' => ['code' => $code],
        ]);
    }

    /**
     * Owner-only: creates a family-only account (no email required) for the
     * spouse, attaches it to this family with the requested payer role, and
     * returns the freshly issued login code. The code is shown exactly once —
     * the owner hands it to the partner, who signs in like any other user.
     */
    public function storeMember(Request $request, Family $family): JsonResponse
    {
        $this->authorize('manage', $family);

        $currentCount = $family->members()->count();
        if ($currentCount >= self::MAX_MEMBERS) {
            return response()->json([
                'message' => 'Keluarga sudah penuh (maksimal '.self::MAX_MEMBERS.' orang).',
                'errors' => ['name' => ['Jumlah anggota keluarga sudah mencapai batas maksimal 5 orang.']],
            ], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'payer_role' => ['nullable', 'in:husband,wife'],
            'email' => ['nullable', 'email', 'max:255'],
            'relationship' => ['nullable', 'in:adult,child'],
        ]);

        if (($data['payer_role'] ?? null) !== null) {
            $taken = FamilyMember::query()
                ->where('family_id', $family->id)
                ->where('payer_role', $data['payer_role'])
                ->exists();

            if ($taken) {
                return response()->json([
                    'message' => 'Peran itu sudah dipakai anggota keluarga.',
                    'errors' => ['payer_role' => ['Peran ini sudah dipakai anggota keluarga.']],
                ], 422);
            }
        }

        $email = $data['email'] ?? ('family-'.$family->id.'-'.Str::lower(Str::random(8)).'@family.local');

        if (User::where('email', $email)->exists()) {
            return response()->json([
                'message' => 'Email sudah dipakai. Masukkan email lain atau kosongkan.',
                'errors' => ['email' => ['Email sudah dipakai.']],
            ], 422);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $email,
            'password' => Str::password(24),
            'setup_completed' => true,
        ]);

        FamilyMember::create([
            'family_id' => $family->id,
            'user_id' => $user->id,
            'role' => 'member',
            'is_family_only' => true,
            'payer_role' => $data['payer_role'] ?? null,
            'relationship' => $data['relationship'] ?? 'adult',
            'visibility' => ($data['relationship'] ?? 'adult') === 'child'
                ? ['income' => false, 'expense' => false, 'debts' => false]
                : null,
        ]);

        $code = $this->codes->issueFor($user);

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'payer_role' => $user->familyMember()?->payer_role,
                'payer_label' => $user->familyMember()?->payerLabel(),
                'login_code' => $code,
                'app' => $this->latestAppPayload(),
            ],
        ], 201);
    }

    /** Update a managed member without allowing ownership or family changes. */
    public function updateMember(Request $request, Family $family, int $memberUser): JsonResponse
    {
        $this->authorize('manage', $family);
        $member = $family->members()->where('user_id', $memberUser)->firstOrFail();
        $this->assertSameFamilyMember($family, $member);

        if ($member->role === 'owner') {
            return response()->json(['message' => 'Pemilik keluarga tidak dapat diubah dari menu anggota.'], 422);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'payer_role' => ['sometimes', 'nullable', 'in:husband,wife'],
            'relationship' => ['sometimes', 'in:adult,child'],
            'visibility' => ['sometimes', 'array'],
            'visibility.income' => ['sometimes', 'boolean'],
            'visibility.expense' => ['sometimes', 'boolean'],
            'visibility.debts' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('payer_role', $data) && $data['payer_role'] !== null) {
            $taken = FamilyMember::query()
                ->where('family_id', $family->id)
                ->where('payer_role', $data['payer_role'])
                ->where('user_id', '!=', $member->user_id)
                ->exists();

            if ($taken) {
                return response()->json([
                    'message' => 'Peran itu sudah dipakai anggota keluarga.',
                    'errors' => ['payer_role' => ['Peran ini sudah dipakai anggota keluarga.']],
                ], 422);
            }
        }

        if (array_key_exists('name', $data)) {
            $member->user()->update(['name' => trim($data['name'])]);
        }
        if (array_key_exists('payer_role', $data)) {
            $member->update(['payer_role' => $data['payer_role']]);
        }
        if (array_key_exists('relationship', $data)) {
            $member->update([
                'relationship' => $data['relationship'],
                ...($data['relationship'] === 'child' && ! array_key_exists('visibility', $data)
                    ? ['visibility' => ['income' => false, 'expense' => false, 'debts' => false]]
                    : []),
            ]);
        }
        if (array_key_exists('visibility', $data)) {
            $member->update(['visibility' => $data['visibility']]);
        }

        $member->load('user:id,name,email');

        return response()->json(['data' => $this->memberPayload($member)]);
    }

    /** Remove access while retaining the user's historical transactions. */
    public function destroyMember(Request $request, Family $family, int $memberUser): Response|JsonResponse
    {
        $this->authorize('manage', $family);
        $member = $family->members()->where('user_id', $memberUser)->firstOrFail();
        $this->assertSameFamilyMember($family, $member);

        if ($member->role === 'owner' || $member->user_id === $request->user()->id) {
            return response()->json(['message' => 'Pemilik atau akun sendiri tidak dapat dihapus dari keluarga.'], 422);
        }

        $user = $member->user;
        $member->delete();
        // A removed member must not retain an old bearer token into this family.
        $user?->tokens()->delete();

        return response()->noContent();
    }

    private function assertSameFamilyMember(Family $family, FamilyMember $member): void
    {
        abort_unless($member->family_id === $family->id, 404);
    }

    /** @return array<string, mixed> */
    private function memberPayload(FamilyMember $member): array
    {
        return [
            'user_id' => $member->user_id,
            'role' => $member->role,
            'name' => $member->user?->name,
            'email' => $member->user?->email,
            'payer_role' => $member->payer_role,
            'payer_label' => $member->payerLabel(),
            'relationship' => $member->relationship,
            'visibility' => $member->visibility ?? [],
        ];
    }

    private function latestAppPayload(): array
    {
        $release = AppRelease::query()->orderByDesc('version_code')->first();

        if ($release) {
            return [
                'latest_version_code' => $release->version_code,
                'latest_version_name' => $release->version_name,
                'download_url' => route('app-release.download', $release),
                'notes' => (string) $release->notes,
                'is_mandatory' => (bool) $release->is_mandatory,
            ];
        }

        return [
            'latest_version_code' => (int) config('app.version_code', 1),
            'latest_version_name' => (string) config('app.version_name', '1.0.0'),
            'download_url' => (string) config('app.apk_download_url', ''),
            'notes' => (string) config('app.apk_notes', ''),
            'is_mandatory' => false,
        ];
    }

    /**
     * Family-level health. Reuses the existing FamilyAIService so the web and
     * the app cannot drift apart on what "sehat" means.
     */
    public function summary(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        abort_unless(
            $this->visibility->canView($request->user(), $family, 'income')
                || $this->visibility->canView($request->user(), $family, 'expense'),
            403,
            'Ringkasan keuangan dibatasi oleh kepala keluarga.'
        );

        $viewer = $this->visibility->member($request->user(), $family);

        return response()->json([
            'data' => $this->ai->healthScore($family, $viewer),
        ]);
    }

    /**
     * Personal cash-flow forecast for the dashboard: today / this week / this
     * month, each paired with its previous window (yesterday / last week / last
     * month) plus the delta in rupiah and percent. Amounts a member may not see
     * are zeroed, mirroring the summary endpoint.
     */
    public function forecast(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        abort_unless(
            $this->visibility->canView($request->user(), $family, 'income')
                || $this->visibility->canView($request->user(), $family, 'expense'),
            403,
            'Forecast keuangan dibatasi oleh kepala keluarga.'
        );

        $viewer = $this->visibility->member($request->user(), $family);
        $incomeVisible = $viewer->role === 'owner' || $viewer->canView('income');
        $expenseVisible = $viewer->role === 'owner' || $viewer->canView('expense');

        $today = now()->startOfDay();
        $yesterday = $today->copy()->subDay()->startOfDay();
        $weekStart = $today->copy()->startOfWeek();
        $prevWeekStart = $weekStart->copy()->subWeek();
        $monthStart = $today->copy()->startOfMonth();
        $prevMonthStart = $monthStart->copy()->subMonthNoOverflow();

        $rows = $this->visibility->scopeTransactions(
            FamilyTransaction::query(),
            $request->user(),
            $family
        )
            ->where('family_id', $family->id)
            ->where('date', '>=', $prevMonthStart->toDateString())
            ->get(['type', 'amount', 'date']);

        $bucket = fn (string $from, string $to): array => [
            'income' => (float) $rows
                ->where('type', 'income')
                ->filter(fn (FamilyTransaction $tx): bool => substr((string) $tx->date, 0, 10) >= $from && substr((string) $tx->date, 0, 10) <= $to)
                ->sum('amount'),
            'expense' => (float) $rows
                ->where('type', 'expense')
                ->filter(fn (FamilyTransaction $tx): bool => substr((string) $tx->date, 0, 10) >= $from && substr((string) $tx->date, 0, 10) <= $to)
                ->sum('amount'),
        ];

        $todayData = $bucket($today->toDateString(), $today->toDateString());
        $yesterdayData = $bucket($yesterday->toDateString(), $today->copy()->subDay()->toDateString());
        $weekData = $bucket($weekStart->toDateString(), $today->toDateString());
        $prevWeekData = $bucket($prevWeekStart->toDateString(), $weekStart->copy()->subDay()->toDateString());
        $monthData = $bucket($monthStart->toDateString(), $today->toDateString());
        $prevMonthData = $bucket($prevMonthStart->toDateString(), $monthStart->copy()->subDay()->toDateString());

        if (! $incomeVisible) {
            $todayData['income'] = 0.0;
            $yesterdayData['income'] = 0.0;
            $weekData['income'] = 0.0;
            $prevWeekData['income'] = 0.0;
            $monthData['income'] = 0.0;
            $prevMonthData['income'] = 0.0;
        }
        if (! $expenseVisible) {
            $todayData['expense'] = 0.0;
            $yesterdayData['expense'] = 0.0;
            $weekData['expense'] = 0.0;
            $prevWeekData['expense'] = 0.0;
            $monthData['expense'] = 0.0;
            $prevMonthData['expense'] = 0.0;
        }

        $delay = fn (array $current, array $previous): array => [
            'income_delta' => round($current['income'] - $previous['income'], 2),
            'income_pct' => $previous['income'] > 0
                ? round(($current['income'] - $previous['income']) / $previous['income'] * 100, 1)
                : null,
            'expense_delta' => round($current['expense'] - $previous['expense'], 2),
            'expense_pct' => $previous['expense'] > 0
                ? round(($current['expense'] - $previous['expense']) / $previous['expense'] * 100, 1)
                : null,
        ];

        $period = function (array $current, array $previous, array $delta): array {
            $current['income'] = round($current['income'], 2);
            $current['expense'] = round($current['expense'], 2);
            $previous['income'] = round($previous['income'], 2);
            $previous['expense'] = round($previous['expense'], 2);

            return [
                'current' => $current,
                'previous' => $previous,
                'delta' => $delta,
            ];
        };

        return response()->json([
            'data' => [
                'today' => $period($todayData, $yesterdayData, $delay($todayData, $yesterdayData)),
                'week' => $period($weekData, $prevWeekData, $delay($weekData, $prevWeekData)),
                'month' => $period($monthData, $prevMonthData, $delay($monthData, $prevMonthData)),
            ],
            'license' => [
                'income_visible' => $incomeVisible,
                'expense_visible' => $expenseVisible,
            ],
        ]);
    }

    /**
     * Gentle, family-toned reminders built purely from the household's own data:
     * a nudge when nothing was recorded today, debts whose due date is close (or
     * past), goals nearing their deadline, and monthly budgets that are already
     * 80%+ spent. Used by the app's background worker to wake a local
     * notification — no FCM service required.
     */
    public function reminders(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $today = now()->startOfDay();
        $reminders = collect();
        $canSeeDebts = $this->visibility->canView($request->user(), $family, 'debts');
        $canSeeExpenses = $this->visibility->canView($request->user(), $family, 'expense');

        $debts = $canSeeDebts ? FamilyDebt::query()
            ->where('family_id', $family->id)
            ->whereIn('status', ['open', 'partial'])
            ->where('type', 'payable')
            ->whereNotNull('due_date')
            ->get() : collect();

        foreach ($debts as $debt) {
            $dueAt = now()->parse($debt->due_date)->startOfDay();
            $days = (int) $today->diffInDays($dueAt, false);
            if ($days > 7) {
                continue;
            }
            $when = match (true) {
                $days < 0 => 'sudah lewat '.abs($days).' hari',
                $days === 0 => 'jatuh tempo hari ini',
                default => 'tinggal '.$days.' hari lagi',
            };
            $reminders->push([
                'type' => 'debt',
                'message' => "Utang \"{$debt->name}\" {$when}. Semakin cepat dilunasi, semakin ringan bebannya — kamu pasti bisa. 😊",
            ]);
        }

        $goals = FamilyGoal::query()
            ->where('family_id', $family->id)
            ->where('status', 'active')
            ->whereNotNull('deadline')
            ->get();

        foreach ($goals as $goal) {
            $deadlineAt = now()->parse($goal->deadline)->startOfDay();
            $days = (int) $today->diffInDays($deadlineAt, false);
            if ($goal->current_amount >= $goal->target_amount || $days > 30) {
                continue;
            }
            $percent = $goal->target_amount > 0
                ? intdiv((int) $goal->current_amount, max(1, (int) ceil($goal->target_amount / 100)))
                : 0;
            $when = match (true) {
                $days < 0 => 'sudah lewat batas waktunya',
                $days === 0 => 'target waktunya hari ini',
                default => 'tinggal '.$days.' hari lagi',
            };
            $reminders->push([
                'type' => 'goal',
                'message' => "Target \"{$goal->name}\" {$when} dan baru terkumpul {$percent}%. Sedikit demi sedikit, terus dijaga ya! 🎯",
            ]);
        }

        $now = now();
        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];
        if ($canSeeExpenses && ! $this->visibility->scopeTransactions(
            FamilyTransaction::query(),
            $request->user(),
            $family
        )
            ->where('family_id', $family->id)
            ->whereDate('date', $now->toDateString())
            ->exists()) {
            $reminders->unshift([
                'type' => 'freshness',
                'message' => sprintf(
                    'Belum ada transaksi yang tercatat hari ini (%d %s). Catat pengeluaran atau pemasukanmu sekarang biar laporan keluarga tetap akurat. 🗒️',
                    (int) $now->day,
                    $monthNames[(int) $now->month] ?? ''
                ),
            ]);
        }

        $budgets = $canSeeExpenses ? FamilyBudget::query()
            ->where('family_id', $family->id)
            ->where('month', $now->month)
            ->where('year', $now->year)
            ->with('category')
            ->get() : collect();

        foreach ($budgets as $budget) {
            if ($budget->category === null) {
                continue;
            }
            $spent = (float) FamilyTransaction::query()
                ->where('family_id', $family->id)
                ->where('type', 'expense')
                ->where('category_id', $budget->category_id)
                ->whereYear('date', $now->year)
                ->whereMonth('date', $now->month)
                ->sum('amount');
            $percent = $budget->amount > 0
                ? intdiv((int) $spent, max(1, (int) ceil($budget->amount / 100)))
                : 100;
            if ($percent >= 80) {
                $reminders->push([
                    'type' => 'budget',
                    'message' => "Anggaran \"{$budget->category->name}\" bulan ini sudah terpakai {$percent}%. Sisa waktunya masih ada — gunakan dengan bijak. 💪",
                ]);
            }
        }

        return response()->json(['data' => $reminders->take(8)->values()]);
    }

    /**
     * Income/expense totals per month for the last N months, oldest first, so
     * the app can draw a home-made bar chart without charting libraries.
     */
    public function trend(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $months = max(1, min(12, $request->integer('months', 6) ?: 6));
        $start = now()->startOfMonth()->subMonths($months - 1);

        $rows = $this->visibility->scopeTransactions(
            FamilyTransaction::query(),
            $request->user(),
            $family
        )
            ->where('family_id', $family->id)
            ->where('date', '>=', $start->toDateString())
            ->get(['type', 'amount', 'date']);

        $byMonth = $rows->groupBy(fn ($tx) => substr((string) $tx->date, 0, 7));

        $labels = [
            '1' => 'Jan', '2' => 'Feb', '3' => 'Mar', '4' => 'Apr',
            '5' => 'Mei', '6' => 'Jun', '7' => 'Jul', '8' => 'Agu',
            '9' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des',
        ];

        $data = [];
        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $bucket = $byMonth->get($key, collect());
            $income = (float) $bucket->where('type', 'income')->sum('amount');
            $expense = (float) $bucket->where('type', 'expense')->sum('amount');
            $data[] = [
                'month' => $key,
                'label' => $labels[$month->format('n')].' '.$month->format('y'),
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
            ];
        }

        return response()->json(['data' => $data]);
    }
}
