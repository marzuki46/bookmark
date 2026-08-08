<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyAllocation extends Model
{
    protected $fillable = [
        'family_id',
        'month',
        'year',
        'goal_id',
        'amount',
        'source',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'confirmed_at' => 'datetime',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(FamilyGoal::class, 'goal_id');
    }

    public function scopeForFamily($query, int $familyId)
    {
        return $query->where('family_id', $familyId);
    }
}
