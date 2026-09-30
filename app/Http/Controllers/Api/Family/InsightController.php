<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Family;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\FamilyInsight;
use App\Models\FamilyMember;
use App\Services\FamilyVisibilityService;
use App\Services\NudgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InsightController extends Controller
{
    public function __construct(
        private readonly NudgeService $nudges,
        private readonly FamilyVisibilityService $visibility,
    ) {}

    /**
     * Everything the dashboard needs on open, in one round trip:
     * the shared family-health note, the caller's personal note, and an
     * instant rule-based nudge. None of this calls the AI — the weekly text is
     * read from storage, so opening the app is always fast and free.
     */
    public function index(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $user = $request->user();
        $member = $this->visibility->member($user, $family);
        $canSeeIncome = $this->visibility->canView($user, $family, 'income');
        $canSeeExpenses = $this->visibility->canView($user, $family, 'expense');
        $visibleAreas = $this->visibleAreas($member);

        // The shared family note is built from the whole household's numbers.
        // A member who is not allowed to see all of them must not receive it;
        // their personal note (built from their own rows) is unaffected.
        $familyInsight = $this->hasFullVisibility($member)
            ? $family->insights()
                ->where('scope', 'family')
                ->orderByDesc('week_key')
                ->first()
            : null;

        $personalInsight = FamilyInsight::query()
            ->where('family_id', $family->id)
            ->where('scope', 'user')
            ->where('user_id', $user->id)
            ->orderByDesc('week_key')
            ->first();

        return response()->json([
            'data' => [
                'family' => $this->present($familyInsight),
                'personal' => $this->present($personalInsight),
                'nudge' => ($canSeeIncome || $canSeeExpenses) ? $this->nudges->evaluate($family, null, $visibleAreas) : null,
                'income_by_source' => $canSeeIncome ? $this->nudges->incomeBySource($family) : [],
                'week_key' => now()->format('o-\WW'),
                'days_left_this_month' => $this->nudges->daysLeftThisMonth(),
            ],
        ]);
    }

    /**
     * Marks the caller's own insight as read. The family-health note is
     * intentionally not markable per-user, since it is a shared state.
     */
    public function markRead(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $insight = FamilyInsight::query()
            ->where('family_id', $family->id)
            ->where('scope', 'user')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('week_key')
            ->first();

        if ($insight !== null) {
            $insight->update(['read_at' => now()]);
        }

        return response()->json(['data' => $this->present($insight?->fresh())]);
    }

    /**
     * Re-evaluates the instant rules on demand. Free and synchronous, so the
     * app can call it after any change without budgeting for an AI call.
     */
    public function nudge(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $member = $this->visibility->member($request->user(), $family);

        return response()->json(['data' => $this->nudges->evaluate($family, null, $this->visibleAreas($member))]);
    }

    private function present(?FamilyInsight $insight): ?array
    {
        if ($insight === null) {
            return null;
        }

        return [
            'id' => $insight->id,
            'scope' => $insight->scope,
            'message' => $insight->message,
            'tone' => $insight->tone,
            'week_key' => $insight->week_key,
            'is_fallback' => (bool) $insight->is_fallback,
            'generated_at' => $insight->created_at?->toIso8601String(),
            'read_at' => $insight->read_at?->toIso8601String(),
        ];
    }

    /**
     * The visibility areas a member may feed into rules, as a bool map.
     */
    private function visibleAreas(FamilyMember $member): array
    {
        return [
            'income' => $member->canView('income'),
            'expense' => $member->canView('expense'),
            'debts' => $member->canView('debts'),
        ];
    }

    /**
     * Whether a member may receive the shared household note, which is
     * generated from every area at once. The owner always qualifies.
     */
    private function hasFullVisibility(FamilyMember $member): bool
    {
        return $member->role === 'owner'
            || ($member->canView('income') && $member->canView('expense') && $member->canView('debts'));
    }
}
