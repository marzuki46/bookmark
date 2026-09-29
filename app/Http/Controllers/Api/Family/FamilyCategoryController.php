<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Family;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\FamilyCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Expense/income categories for a household.
 *
 * The app needs these to build a transaction, and they were previously only
 * manageable from the web Livewire screens, so an app user could record a
 * transaction but could not create the category it needed.
 *
 * Custom categories may be deleted safely because family_transactions.category_id
 * is nullable; historical entries remain in the ledger without a category.
 */
final class FamilyCategoryController extends Controller
{
    public function index(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        $data = $request->validate([
            'type' => ['nullable', 'in:income,expense'],
        ]);

        $categories = $family->categories()
            ->when($data['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $categories->map(fn (FamilyCategory $c): array => [
            'id' => $c->id,
            'name' => $c->name,
            'type' => $c->type,
            'icon' => $c->icon,
            'color' => $c->color,
            'is_system' => (bool) $c->is_system,
        ])->values()]);
    }

    public function store(Request $request, Family $family): JsonResponse
    {
        $this->authorize('manage', $family);

        if (! $request->filled('type')) {
            $request->merge(['type' => 'expense']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'type' => ['required', 'in:income,expense'],
            'icon' => ['nullable', 'string', 'max:40'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $duplicate = $family->categories()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['name']))])
            ->where('type', $data['type'])
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'Kategori dengan nama itu sudah ada.',
                'errors' => ['name' => ['Kategori dengan nama itu sudah ada.']],
            ], 422);
        }

        $category = $family->categories()->create($data);

        return response()->json(['data' => [
            'id' => $category->id,
            'name' => $category->name,
            'type' => $category->type,
            'icon' => $category->icon,
            'color' => $category->color,
            'is_system' => (bool) $category->is_system,
        ]], 201);
    }

    public function update(Request $request, Family $family, FamilyCategory $category): JsonResponse
    {
        $this->authorize('manage', $family);

        $this->assertSameFamily($family, $category->id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:60'],
            'type' => ['sometimes', 'in:income,expense'],
            'icon' => ['nullable', 'string', 'max:40'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $category->fill($data)->save();

        return response()->json(['data' => [
            'id' => $category->id,
            'name' => $category->name,
            'type' => $category->type,
            'icon' => $category->icon,
            'color' => $category->color,
            'is_system' => (bool) $category->is_system,
        ]]);
    }

    public function destroy(Request $request, Family $family, FamilyCategory $category): Response|JsonResponse
    {
        $this->authorize('manage', $family);
        $this->assertSameFamily($family, $category->id);

        if ($category->is_system) {
            return response()->json(['message' => 'Kategori bawaan tidak dapat dihapus.'], 422);
        }

        // The foreign key is nullable, so deleting a custom category keeps
        // historical transactions and marks them uncategorised.
        $category->delete();

        return response()->noContent();
    }

    private function assertSameFamily(Family $family, int $categoryId): void
    {
        abort_unless($family->categories()->whereKey($categoryId)->exists(), 404);
    }
}
