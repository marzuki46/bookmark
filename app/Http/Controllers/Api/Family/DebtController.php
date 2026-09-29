<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Family;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\FamilyDebt;
use App\Services\FamilyVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class DebtController extends Controller
{
    public function __construct(private readonly FamilyVisibilityService $visibility) {}

    public function index(Request $request, Family $family): JsonResponse
    {
        $this->authorize('view', $family);
        $this->ensureVisible($request, $family);

        $data = $request->validate([
            'status' => ['nullable', 'in:open,partial,settled'],
            'type' => ['nullable', 'in:payable,receivable'],
        ]);

        $debts = $family->debts()
            ->when($data['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($data['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->orderBy('due_date')
            ->orderByDesc('priority')
            ->get()
            ->map(fn (FamilyDebt $debt): array => $this->present($debt));

        return response()->json(['data' => $debts]);
    }

    public function store(Request $request, Family $family): JsonResponse
    {
        $this->authorize('manage', $family);

        if (! $request->filled('type')) {
            $request->merge(['type' => 'payable']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:payable,receivable'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'installment' => ['nullable', 'numeric', 'min:0.01'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $debt = $family->debts()->create($data + ['status' => 'open', 'paid_amount' => 0]);

        return response()->json(['data' => $this->present($debt)], 201);
    }

    public function show(Request $request, Family $family, FamilyDebt $debt): JsonResponse
    {
        $this->authorize('view', $family);
        $this->ensureVisible($request, $family);

        $this->ensureBelongsToFamily($debt, $family);

        return response()->json(['data' => $this->present($debt)]);
    }

    public function update(Request $request, Family $family, FamilyDebt $debt): JsonResponse
    {
        $this->authorize('manage', $family);

        $this->ensureBelongsToFamily($debt, $family);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'type' => ['sometimes', 'in:payable,receivable'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'installment' => ['nullable', 'numeric', 'min:0.01'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        // Lowering the total below what has already been paid would silently
        // make the debt "overpaid"; keep the ceiling at the amount.
        if (isset($data['amount']) && (float) $data['amount'] < (float) $debt->paid_amount) {
            return response()->json([
                'message' => 'Total hutang tidak boleh lebih kecil dari yang sudah dibayar.',
                'errors' => ['amount' => ['Nilai lebih kecil dari total terbayar.']],
            ], 422);
        }

        $debt->update($data);
        $this->syncStatus($debt);

        return response()->json(['data' => $this->present($debt->fresh())]);
    }

    public function destroy(Request $request, Family $family, FamilyDebt $debt): JsonResponse
    {
        $this->authorize('manage', $family);

        $this->ensureBelongsToFamily($debt, $family);

        $debt->delete();

        return response()->json(status: 204);
    }

    /**
     * Records a payment against a debt and rolls the status forward.
     *
     * The remaining-amount check runs inside the same transaction as the write
     * so two concurrent payments cannot both slip past the ceiling.
     */
    public function pay(Request $request, Family $family, FamilyDebt $debt): JsonResponse
    {
        $this->authorize('manage', $family);

        $this->ensureBelongsToFamily($debt, $family);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $paid = DB::transaction(function () use ($debt, $data): FamilyDebt {
            $locked = FamilyDebt::whereKey($debt->id)->lockForUpdate()->firstOrFail();
            $remaining = (float) $locked->amount - (float) $locked->paid_amount;

            if ($remaining <= 0) {
                return abort(422, 'Hutang ini sudah lunas.');
            }

            if ((float) $data['amount'] > $remaining + 0.001) {
                return abort(422, 'Pembayaran melebihi sisa hutang.');
            }

            $locked->paid_amount = (float) $locked->paid_amount + (float) $data['amount'];
            $this->syncStatus($locked);
            $locked->save();

            return $locked;
        });

        return response()->json(['data' => $this->present($paid)]);
    }

    /**
     * open -> partial -> settled, driven purely off paid_amount so the status
     * can never drift from the arithmetic.
     */
    private function syncStatus(FamilyDebt $debt): void
    {
        $paid = (float) $debt->paid_amount;
        $amount = (float) $debt->amount;

        $debt->status = match (true) {
            $paid <= 0 => 'open',
            $paid + 0.001 >= $amount => 'settled',
            default => 'partial',
        };
    }

    private function ensureBelongsToFamily(FamilyDebt $debt, Family $family): void
    {
        // Guards against a member of one family addressing another's debt by ID.
        abort_unless($debt->family_id === $family->id, 404);
    }

    private function ensureVisible(Request $request, Family $family): void
    {
        abort_unless(
            $this->visibility->canView($request->user(), $family, 'debts'),
            403,
            'Data hutang dibatasi oleh kepala keluarga.'
        );
    }

    private function present(FamilyDebt $debt): array
    {
        return [
            'id' => $debt->id,
            'name' => $debt->name,
            'type' => $debt->type,
            'amount' => (float) $debt->amount,
            'paid_amount' => (float) $debt->paid_amount,
            'remaining_amount' => (float) $debt->remaining,
            'interest_rate' => $debt->interest_rate === null ? null : (float) $debt->interest_rate,
            'installment' => $debt->installment === null ? null : (float) $debt->installment,
            'due_date' => $debt->due_date?->toDateString(),
            'notes' => $debt->notes,
            'status' => $debt->status,
            'priority' => $debt->priority,
            'is_overdue' => $debt->due_date !== null
                && $debt->status !== 'settled'
                && $debt->due_date->isPast(),
        ];
    }
}
