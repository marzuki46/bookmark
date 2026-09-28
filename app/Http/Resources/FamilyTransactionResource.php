<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\FamilyTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FamilyTransaction
 */
class FamilyTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'family_id' => $this->family_id,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'description' => $this->description,
            'date' => $this->date?->toDateString(),
            'payer' => $this->payer,
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'recorded_by' => $this->whenLoaded('user', fn (): array => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ]),
            'category' => $this->whenLoaded('category', fn (): ?array => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'type' => $this->category->type,
            ] : null),
            'income_source' => $this->whenLoaded('incomeSource', fn (): ?array => $this->incomeSource ? [
                'id' => $this->incomeSource->id,
                'name' => $this->incomeSource->name,
                'type' => $this->incomeSource->type,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
