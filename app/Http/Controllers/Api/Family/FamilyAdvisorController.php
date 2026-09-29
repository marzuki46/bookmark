<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Family;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Services\FamilyAdvisorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Kang Cuan — the family financial advisor ("Pendamping Keuangan").
 *
 * Opt-in per family. Only the family owner can enable/disable the advisor and
 * save the advisor profile. Members with "income" visibility can read the plan;
 * other members only learn whether the advisor is on (never amounts).
 */
final class FamilyAdvisorController extends Controller
{
    public function __construct(
        private readonly FamilyAdvisorService $advisor = new FamilyAdvisorService,
    ) {
    }

    public function status(Request $request, Family $family): JsonResponse
    {
        $user = $request->user();
        $member = FamilyMember::where('family_id', $family->id)
            ->where('user_id', $user->id)->firstOrFail();

        $this->authorize('view', $family);

        if (! $family->advisor_enabled) {
            return response()->json([
                'data' => [
                    'enabled' => false,
                    'profile' => $family->advisor_profile ?? [],
                ],
            ]);
        }

        $canSeeIncome = (bool) ($member->visibility['income'] ?? ($member->role === 'owner'));

        if (! $canSeeIncome) {
            return response()->json([
                'data' => [
                    'enabled' => true,
                    'accessible' => false,
                    'message' => 'Pendamping keuangan aktif untuk keluarga ini. Minta kepala keluarga untuk membuka akses pemasukan jika kamu ingin melihat rinciannya.',
                ],
            ]);
        }

        $plan = $this->advisor->plan($family);

        return response()->json([
            'data' => [
                'enabled' => true,
                'accessible' => true,
                'context' => $this->advisor->context($family),
                'plan' => $plan,
            ],
        ]);
    }

    public function update(Request $request, Family $family): JsonResponse
    {
        $this->authorize('manage', $family);

        if (! $family->isOwner($request->user())) {
            return response()->json([
                'message' => 'Hanya kepala keluarga yang dapat mengubah pengaturan pendamping keuangan.',
            ], 403);
        }

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $family->update(['advisor_enabled' => (bool) $data['enabled']]);

        return response()->json([
            'data' => [
                'enabled' => $family->advisor_enabled,
            ],
        ]);
    }

    public function saveProfile(Request $request, Family $family): JsonResponse
    {
        $this->authorize('manage', $family);

        if (! $family->isOwner($request->user())) {
            return response()->json([
                'message' => 'Hanya kepala keluarga yang dapat mengubah profil keuangan keluarga.',
            ], 403);
        }

        $data = $request->validate([
            'monthly_income' => ['nullable', 'numeric', 'min:0'],
            'income_type' => ['nullable', Rule::in(['fixed', 'irregular'])],
            'members_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'dependents_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'housing_type' => ['nullable', Rule::in(['own_paid', 'own_installment', 'rent', 'stay_family'])],
            'has_protection' => ['nullable', 'boolean'],
            'uncovered_members' => ['nullable', 'integer', 'min:0', 'max:20'],
            'priorities' => ['nullable', 'array'],
            'priorities.*' => ['string', 'max:50'],
            'monthly_essential_override' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $profile = array_replace($family->advisor_profile ?? [], $data);

        $family->update(['advisor_profile' => $profile]);

        return response()->json([
            'data' => [
                'profile' => $family->advisor_profile,
            ],
        ]);
    }
}