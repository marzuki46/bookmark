<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Family;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\FamilyGoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class GoalController extends Controller
{
    public function index(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $data = $request->validate([
            'status' => ['nullable', 'in:active,completed,archived'],
        ]);

        $goals = $family->goals()
            ->when($data['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->orderBy('priority')
            ->orderBy('deadline')
            ->get()
            ->map(fn (FamilyGoal $goal): array => $this->present($goal));

        return response()->json(['data' => $goals]);
    }

    public function store(Request $request, Family $family): JsonResponse
    {
        $this->authorize('manage', $family);

        if (! $request->filled('type')) {
            $request->merge(['type' => 'custom']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:emergency_fund,custom'],
            'target_amount' => ['required', 'numeric', 'min:0.01'],
            'monthly_allocation' => ['nullable', 'numeric', 'min:0'],
            'deadline' => ['nullable', 'date'],
            'icon' => ['nullable', 'string', 'max:40'],
            'color' => ['nullable', 'string', 'max:9'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $goal = $family->goals()->create(
            $data + ['current_amount' => 0, 'status' => 'active']
        );

        return response()->json(['data' => $this->present($goal)], 201);
    }

    public function update(Request $request, Family $family, FamilyGoal $goal): JsonResponse
    {
        $this->authorize('manage', $family);

        abort_unless($goal->family_id === $family->id, 404);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'type' => ['sometimes', 'in:emergency_fund,custom'],
            'target_amount' => ['sometimes', 'numeric', 'min:0.01'],
            'monthly_allocation' => ['nullable', 'numeric', 'min:0'],
            'deadline' => ['nullable', 'date'],
            'icon' => ['nullable', 'string', 'max:40'],
            'color' => ['nullable', 'string', 'max:9'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['nullable', 'in:active,completed,archived'],
        ]);

        if (isset($data['target_amount']) && (float) $data['target_amount'] < (float) $goal->current_amount) {
            return response()->json([
                'message' => 'Target tidak boleh lebih kecil dari jumlah yang sudah terkumpul.',
                'errors' => ['target_amount' => ['Nilai lebih kecil dari dana terkumpul.']],
            ], 422);
        }

        $goal->update($data);

        return response()->json(['data' => $this->present($goal->fresh())]);
    }

    /**
     * Adds to a goal's saved amount and closes it out once the target is met.
     */
    public function contribute(Request $request, Family $family, FamilyGoal $goal): JsonResponse
    {
        $this->authorize('manage', $family);

        abort_unless($goal->family_id === $family->id, 404);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $updated = DB::transaction(function () use ($goal, $data): FamilyGoal {
            $locked = FamilyGoal::whereKey($goal->id)->lockForUpdate()->firstOrFail();

            $locked->current_amount = (float) $locked->current_amount + (float) $data['amount'];

            // Auto-complete on reaching the target, without needing a second call.
            if ((float) $locked->current_amount >= (float) $locked->target_amount
                && $locked->status === 'active') {
                $locked->status = 'completed';
            }

            $locked->save();

            return $locked;
        });

        return response()->json(['data' => $this->present($updated)]);
    }

    public function destroy(Request $request, Family $family, FamilyGoal $goal): JsonResponse
    {
        $this->authorize('manage', $family);

        abort_unless($goal->family_id === $family->id, 404);

        $goal->delete();

        return response()->json(status: 204);
    }

    private function present(FamilyGoal $goal): array
    {
        $target = (float) $goal->target_amount;
        $current = (float) $goal->current_amount;

        return [
            'id' => $goal->id,
            'name' => $goal->name,
            'type' => $goal->type,
            'target_amount' => $target,
            'current_amount' => $current,
            'remaining_amount' => max(0, $target - $current),
            'progress_percent' => $target > 0 ? round(min(100, $current / $target * 100), 1) : 0.0,
            'monthly_allocation' => (float) $goal->monthly_allocation,
            'deadline' => $goal->deadline?->toDateString(),
            'icon' => $goal->icon,
            'color' => $goal->color,
            'priority' => $goal->priority,
            'status' => $goal->status,
        ];
    }
}
