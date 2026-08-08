<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyDebt extends Model
{
    protected $fillable = [
        'family_id',
        'name',
        'type',
        'amount',
        'paid_amount',
        'interest_rate',
        'installment',
        'due_date',
        'notes',
        'status',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'installment' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function scopeForFamily($query, int $familyId)
    {
        return $query->where('family_id', $familyId);
    }

    public function getRemainingAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->paid_amount);
    }
}
