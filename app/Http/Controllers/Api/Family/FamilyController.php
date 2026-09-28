<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Family;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use App\Services\FamilyAIService;
use App\Services\LoginCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class FamilyController extends Controller
{
    /** Hard cap so the household stays small and personal. */
    private const MAX_MEMBERS = 5;

    public function __construct(
        private readonly FamilyAIService $ai,
        private readonly LoginCodeService $codes,
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
        ]);

        $code = $this->codes->issueFor($user);

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'payer_role' => $user->familyMember()?->payer_role,
                'payer_label' => $user->familyMember()?->payerLabel(),
                'login_code' => $code,
            ],
        ], 201);
    }

    /**
     * Family-level health. Reuses the existing FamilyAIService so the web and
     * the app cannot drift apart on what "sehat" means.
     */
    public function summary(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        return response()->json([
            'data' => $this->ai->healthScore($family),
        ]);
    }
}
