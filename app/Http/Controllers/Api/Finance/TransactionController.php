<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\FinancialTransactionResource;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Services\FinanceReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class TransactionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'period' => 'nullable|string|in:'.implode(',', FinanceReportService::PERIODS),
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d',
            'type' => 'nullable|in:income,expense',
            'category_id' => 'nullable|integer',
            'search' => 'nullable|string|max:200',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $range = app(FinanceReportService::class)->resolveRange(
            $validated['period'] ?? null,
            $validated['from'] ?? null,
            $validated['to'] ?? null,
        );

        $transactions = FinancialTransaction::with('category')
            ->where('user_id', auth()->id())
            ->inRange($range['from'], $range['to'])
            ->when(isset($validated['type']), fn ($q) => $q->where('type', $validated['type']))
            ->when(isset($validated['category_id']), fn ($q) => $q->where('category_id', $validated['category_id']))
            ->when(
                ! empty($validated['search']),
                fn ($q) => $q->where(function ($sub) use ($validated): void {
                    // LOWER() on both sides keeps the match case-insensitive
                    // regardless of the column collation (MySQL vs SQLite).
                    $like = '%'.mb_strtolower($validated['search']).'%';
                    $sub->whereRaw('LOWER(description) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(notes) LIKE ?', [$like]);
                })
            )
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate((int) ($validated['per_page'] ?? 20))
            ->withQueryString();

        return FinancialTransactionResource::collection($transactions);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $request->filled('type')) {
            $request->merge(['type' => 'expense']);
        }

        $data = $this->validated($request);

        $data['category_id'] = $this->resolveOwnedCategory($data['category_id'] ?? null, $data['type']);
        $data['user_id'] = auth()->id();
        $data['source'] ??= 'manual';

        $transaction = FinancialTransaction::create($data);

        return (new FinancialTransactionResource($transaction->load('category')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(FinancialTransaction $transaction): FinancialTransactionResource
    {
        $this->authorizeOwnership($transaction);

        return new FinancialTransactionResource($transaction->load('category'));
    }

    public function update(Request $request, FinancialTransaction $transaction): FinancialTransactionResource
    {
        $this->authorizeOwnership($transaction);

        $data = $this->validated($request, true);

        if (array_key_exists('category_id', $data)) {
            $data['category_id'] = $this->resolveOwnedCategory($data['category_id'], $data['type'] ?? $transaction->type);
        }

        $transaction->update($data);

        return new FinancialTransactionResource($transaction->fresh()->load('category'));
    }

    public function destroy(FinancialTransaction $transaction): JsonResponse
    {
        $this->authorizeOwnership($transaction);

        $transaction->delete();

        return response()->json(['message' => 'Deleted'], 200);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $partial = false): array
    {
        $sometimes = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'type' => $sometimes.'|in:income,expense',
            'amount' => $sometimes.'|numeric|min:0.01|max:999999999999',
            'description' => ($partial ? 'sometimes' : 'required').'|string|max:500',
            'date' => $sometimes.'|date_format:Y-m-d',
            'category_id' => 'nullable|integer',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'source' => 'nullable|in:manual,ai',
        ]);
    }

    /**
     * A category must exist, belong to this user, and match the transaction type.
     * Passing an id owned by somebody else is rejected rather than trusted.
     */
    private function resolveOwnedCategory(?int $categoryId, string $type): ?int
    {
        if ($categoryId === null) {
            return null;
        }

        $category = FinancialCategory::where('user_id', auth()->id())->find($categoryId);

        abort_if($category === null, 422, 'Kategori tidak ditemukan.');
        abort_if($category->type !== $type, 422, 'Kategori tidak cocok dengan jenis transaksi.');

        return $category->id;
    }

    private function authorizeOwnership(FinancialTransaction $transaction): void
    {
        abort_if($transaction->user_id !== auth()->id(), 403, 'Forbidden');
    }
}
