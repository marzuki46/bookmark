<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyGoal extends Model
{
    protected $fillable = [
        'family_id',
        'name',
        'type',
        'target_amount',
        'current_amount',
        'monthly_allocation',
        'priority',
        'deadline',
        'icon',
        'color',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'current_amount' => 'decimal:2',
            'monthly_allocation' => 'decimal:2',
            'deadline' => 'date',
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

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getProgressAttribute(): float
    {
        if ((float) $this->target_amount <= 0) {
            return 0;
        }

        return min(100, ((float) $this->current_amount / (float) $this->target_amount) * 100);
    }
}
