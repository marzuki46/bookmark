<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code_hash', 'duration_days', 'max_redemptions', 'redemptions_count', 'starts_at', 'expires_at', 'is_active', 'created_by'])]
final class PromoCode extends Model
{
    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'max_redemptions' => 'integer',
            'redemptions_count' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(PromoRedemption::class);
    }
}
