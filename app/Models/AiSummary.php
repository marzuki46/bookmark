<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiSummary extends Model
{
    protected $fillable = ['item_id', 'summary', 'model'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
