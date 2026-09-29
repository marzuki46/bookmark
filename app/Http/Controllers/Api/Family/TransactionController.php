<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Family;

use App\Http\Controllers\Controller;
use App\Http\Resources\FamilyTransactionResource;
use App\Models\Family;
use App\Models\FamilyCategory;
use App\Models\FamilyTransaction;
use App\Models\IncomeSource;
use App\Services\FamilyVisibilityService;
use App\Services\NudgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class TransactionController extends Controller
{
    public function __construct(
        private readonly NudgeService $nudges,
        private readonly FamilyVisibilityService $visibility,
    ) {}

    public function index(Request $request, Family $family): AnonymousResourceCollection
    {
        $this->authorize('view', $family);

        $data = $request->validate([
            'type' => ['nullable', 'in:income,expense'],
            'payer' => ['nullable', 'in:husband,wife,shared'],
            'category_id' => ['nullable', 'integer'],
            'income_source_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $transactions = $this->visibility->scopeTransactions($family->transactions(), $request->user(), $family)
            ->with(['category:id,name,type', 'incomeSource:id,name,type', 'user:id,name'])
            ->when($data['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($data['payer'] ?? null, fn ($q, $v) => $q->where('payer', $v))
            ->when($data['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($data['income_source_id'] ?? null, fn ($q, $v) => $q->where('income_source_id', $v))
            ->when($data['from'] ?? null, fn ($q, $v) => $q->whereDate('date', '>=', $v))
            // Inclusive of the whole end day; a bare <= date would drop it when
            // the column is stored as a datetime.
            ->when($data['to'] ?? null, fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->when($data['q'] ?? null, fn ($q, $v) => $q->where('description', 'like', "%{$v}%"))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($data['per_page'] ?? 30);

        return FamilyTransactionResource::collection($transactions);
    }

    public function store(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);

        // Older APKs did not include type in the payload. Expense is the
        // safest default for a transaction created without an explicit type.
        if (! $request->filled('type')) {
            $request->merge(['type' => 'expense']);
        }

        $data = $this->validated($request, $family);

        $transaction = $family->transactions()->create(
            $data + ['user_id' => $request->user()->id]
        );

        $transaction->load(['category:id,name,type', 'incomeSource:id,name,type', 'user:id,name']);

        return $this->withNudge(
            (new FamilyTransactionResource($transaction))->response()->setStatusCode(201),
            $family,
            $transaction
        );
    }

    public function show(Request $request, Family $family, FamilyTransaction $transaction): JsonResponse
    {
        $this->authorize('view', $family);

        $this->ensureBelongsToFamily($transaction, $family);

        $transaction->load(['category:id,name,type', 'incomeSource:id,name,type', 'user:id,name']);

        return (new FamilyTransactionResource($transaction))->response();
    }

    public function update(Request $request, Family $family, FamilyTransaction $transaction): JsonResponse
    {
        $this->authorize('manage', $family);

        $this->ensureBelongsToFamily($transaction, $family);

        $data = $this->validated($request, $family, partial: true, currentType: $transaction->type);

        $transaction->update($data);
        $transaction->load(['category:id,name,type', 'incomeSource:id,name,type', 'user:id,name']);

        return $this->withNudge(
            (new FamilyTransactionResource($transaction->fresh()))->response(),
            $family,
            $transaction
        );
    }

    /**
     * Attaches the instant rule-based nudge to a transaction response, so the
     * app can show advice the moment a row is saved without a second request.
     * Evaluated against the saved row specifically, which makes the category
     * just touched the one checked first.
     */
    private function withNudge(JsonResponse $response, Family $family, FamilyTransaction $transaction): JsonResponse
    {
        $response->setData([
            'data' => $response->getData(true)['data'] ?? null,
            'nudge' => $this->nudges->evaluate($family, $transaction),
        ]);

        return $response;
    }

    public function destroy(Request $request, Family $family, FamilyTransaction $transaction): JsonResponse
    {
        $this->authorize('manage', $family);

        $this->ensureBelongsToFamily($transaction, $family);

        $transaction->delete();

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(
        Request $request,
        Family $family,
        bool $partial = false,
        ?string $currentType = null
    ): array {
        $data = $request->validate([
            'type' => [$partial ? 'sometimes' : 'required', 'in:income,expense'],
            'amount' => [$partial ? 'sometimes' : 'required', 'numeric', 'min:0.01'],
            'description' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'date' => [$partial ? 'sometimes' : 'required', 'date'],
            'category_id' => ['nullable', 'integer'],
            'income_source_id' => ['nullable', 'integer'],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'payer' => [$partial ? 'sometimes' : 'nullable', 'in:husband,wife,shared'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->ensureCategoryBelongsToFamily($family, $data['category_id'] ?? null);
        $this->ensureIncomeSourceBelongsToFamily($family, $data['income_source_id'] ?? null);

        // Resolve the effective type, then keep income_source_id only for income.
        // An income stream on an expense row is meaningless, so it is cleared
        // explicitly — omitting the key would leave the previous value in place.
        $type = $data['type'] ?? $currentType;

        if ($type !== 'income') {
            $data['income_source_id'] = null;
        }

        return $data;
    }

    private function ensureCategoryBelongsToFamily(Family $family, ?int $categoryId): void
    {
        if ($categoryId === null) {
            return;
        }

        abort_unless(
            FamilyCategory::where('id', $categoryId)->where('family_id', $family->id)->exists(),
            422,
            'Kategori tidak milik keluarga ini.'
        );
    }

    private function ensureIncomeSourceBelongsToFamily(Family $family, ?int $sourceId): void
    {
        if ($sourceId === null) {
            return;
        }

        abort_unless(
            IncomeSource::where('id', $sourceId)->where('family_id', $family->id)->exists(),
            422,
            'Sumber pemasukan tidak milik keluarga ini.'
        );
    }

    private function ensureBelongsToFamily(FamilyTransaction $transaction, Family $family): void
    {
        abort_unless($transaction->family_id === $family->id, 404);
    }
}
