<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Family;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\IncomeSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class IncomeSourceController extends Controller
{
    public function index(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $totals = $family->transactions()
            ->where('type', 'income')
            ->whereNotNull('income_source_id')
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->get(['income_source_id', 'amount'])
            ->groupBy('income_source_id')
            ->map(fn ($rows) => (float) $rows->sum('amount'));

        $incomeSources = $family->incomeSources()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(fn (IncomeSource $incomeSource): array => [
                'id' => $incomeSource->id,
                'name' => $incomeSource->name,
                'type' => $incomeSource->type,
                'is_default' => (bool) $incomeSource->is_default,
                'is_active' => (bool) $incomeSource->is_active,
                'this_month_total' => $totals->get($incomeSource->id, 0.0),
            ]);

        return response()->json(['data' => $incomeSources]);
    }

    public function store(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'type' => ['required', 'in:salary,side,business,other'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $incomeSource = DB::transaction(function () use ($family, $data): IncomeSource {
            if ($data['is_default'] ?? false) {
                $family->incomeSources()->update(['is_default' => false]);
            }

            return $family->incomeSources()->create($data);
        });

        return response()->json(['data' => [
            'id' => $incomeSource->id,
            'name' => $incomeSource->name,
            'type' => $incomeSource->type,
            'is_default' => (bool) $incomeSource->is_default,
            'is_active' => true,
        ]], 201);
    }

    public function update(Request $request, Family $family, IncomeSource $incomeSource): JsonResponse
    {
        $this->authorize('view', $family);

        abort_unless($incomeSource->family_id === $family->id, 404);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:80'],
            'type' => ['sometimes', 'in:salary,side,business,other'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($family, $incomeSource, $data): void {
            if (($data['is_default'] ?? false) === true) {
                $family->incomeSources()->update(['is_default' => false]);
            }

            $incomeSource->update($data);
        });

        return response()->json(['data' => [
            'id' => $incomeSource->id,
            'name' => $incomeSource->name,
            'type' => $incomeSource->type,
            'is_default' => (bool) $incomeSource->is_default,
            'is_active' => (bool) $incomeSource->is_active,
        ]]);
    }

    public function destroy(Request $request, Family $family, IncomeSource $incomeSource): JsonResponse
    {
        $this->authorize('view', $family);

        abort_unless($incomeSource->family_id === $family->id, 404);

        // Historical income keeps its amount; the FK nulls out so the record
        // still shows up in the ledger with a missing stream.
        $incomeSource->delete();

        return response()->json(status: 204);
    }
}
