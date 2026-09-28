<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Family;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\FamilyBudget;
use App\Models\FamilyCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

final class BudgetController extends Controller
{
    public function index(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $data = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,2100'],
        ]);

        $now = now();
        $month = $data['month'] ?? $now->month;
        $year = $data['year'] ?? $now->year;

        $budgets = $family->budgets()
            ->where('month', $month)
            ->where('year', $year)
            ->with('category:id,name,type')
            ->orderBy('category_id')
            ->get();

        $spent = $family->transactions()
            ->where('type', 'expense')
            ->whereBetween('date', [
                Carbon::create($year, $month, 1)->startOfMonth()->toDateString(),
                Carbon::create($year, $month, 1)->endOfMonth()->toDateString(),
            ])
            ->get(['category_id', 'amount'])
            ->groupBy('category_id')
            ->map(fn ($rows) => (float) $rows->sum('amount'));

        return response()->json([
            'data' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'budgets' => $budgets->map(fn (FamilyBudget $budget): array => [
                    'id' => $budget->id,
                    'category_id' => $budget->category_id,
                    'category_name' => $budget->category?->name,
                    'amount' => (float) $budget->amount,
                    'spent' => $spent->get($budget->category_id, 0.0),
                ])->values(),
            ],
        ]);
    }

    public function store(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $data = $request->validate([
            'category_id' => ['nullable', 'integer'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $this->ensureCategoryBelongsToFamily($family, $data['category_id'] ?? null);

        // MySQL treats NULLs as distinct in a unique index, so a budget with no
        // category would happily duplicate. Catch it in the app instead.
        $duplicate = $family->budgets()
            ->where('month', $data['month'])
            ->where('year', $data['year'])
            ->where(function ($q) use ($data): void {
                $data['category_id'] === null
                    ? $q->whereNull('category_id')
                    : $q->where('category_id', $data['category_id']);
            })
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'Anggaran untuk kategori dan bulan tersebut sudah ada.',
                'errors' => ['amount' => ['Duplikat. Ubah anggaran yang sudah ada.']],
            ], 422);
        }

        $budget = $family->budgets()->create($data);

        return response()->json(['data' => [
            'id' => $budget->id,
            'category_id' => $budget->category_id,
            'amount' => (float) $budget->amount,
            'month' => (int) $budget->month,
            'year' => (int) $budget->year,
        ]], 201);
    }

    public function update(Request $request, Family $family, FamilyBudget $budget): JsonResponse
    {
        $this->authorize('view', $family);

        abort_unless($budget->family_id === $family->id, 404);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $budget->update($data);

        return response()->json(['data' => [
            'id' => $budget->id,
            'category_id' => $budget->category_id,
            'amount' => (float) $budget->amount,
            'month' => (int) $budget->month,
            'year' => (int) $budget->year,
        ]]);
    }

    public function destroy(Request $request, Family $family, FamilyBudget $budget): JsonResponse
    {
        $this->authorize('view', $family);

        abort_unless($budget->family_id === $family->id, 404);

        $budget->delete();

        return response()->json(status: 204);
    }

    private function ensureCategoryBelongsToFamily(Family $family, ?int $categoryId): void
    {
        if ($categoryId === null) {
            return;
        }

        $exists = FamilyCategory::where('id', $categoryId)
            ->where('family_id', $family->id)
            ->exists();

        abort_unless($exists, 422, 'Kategori tidak milik keluarga ini.');
    }
}
