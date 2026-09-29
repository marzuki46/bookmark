<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['family_id', 'period_start', 'analysis_count'])]
class FamilyAiUsage extends Model
{
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'analysis_count' => 'integer',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }
}
