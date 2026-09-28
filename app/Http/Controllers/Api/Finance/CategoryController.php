<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\FinancialCategoryResource;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CategoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'type' => 'nullable|in:income,expense',
        ]);

        $categories = FinancialCategory::where('user_id', auth()->id())
            ->when(isset($validated['type']), fn ($q) => $q->where('type', $validated['type']))
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return FinancialCategoryResource::collection($categories);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $request->filled('type')) {
            $request->merge(['type' => 'expense']);
        }

        $data = $this->validated($request);
        $data['user_id'] = auth()->id();
        $data['is_system'] = false;

        $category = FinancialCategory::create($data);

        return (new FinancialCategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, FinancialCategory $category): FinancialCategoryResource
    {
        $this->authorizeOwnership($category);

        $data = $this->validated($request, true);

        // The type of a system category is fixed so existing transactions keep
        // resolving to a sensible label; the name may still be personalised.
        if ($category->is_system) {
            unset($data['type']);
        }

        $category->update($data);

        return new FinancialCategoryResource($category->fresh());
    }

    public function destroy(FinancialCategory $category): JsonResponse
    {
        $this->authorizeOwnership($category);

        if ($category->is_system) {
            return response()->json(['message' => 'Kategori sistem tidak bisa dihapus.'], 422);
        }

        // Orphan the transactions rather than deleting money records.
        FinancialTransaction::where('user_id', auth()->id())
            ->where('category_id', $category->id)
            ->update(['category_id' => null]);

        $category->delete();

        return response()->json(['message' => 'Deleted'], 200);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $partial = false): array
    {
        $sometimes = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => $sometimes.'|string|max:100',
            'type' => $sometimes.'|in:income,expense',
            'icon' => 'nullable|string|max:16',
            'color' => 'nullable|string|max:9',
        ]);
    }

    private function authorizeOwnership(FinancialCategory $category): void
    {
        abort_if($category->user_id !== auth()->id(), 403, 'Forbidden');
    }
}
