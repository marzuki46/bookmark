<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyBudget extends Model
{
    protected $fillable = [
        'family_id',
        'category_id',
        'month',
        'year',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FamilyCategory::class, 'category_id');
    }

    public function scopeForFamily($query, int $familyId)
    {
        return $query->where('family_id', $familyId);
    }
}
