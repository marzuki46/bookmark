<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LinkScanSession extends Model
{
    protected $fillable = [
        'user_id',
        'is_scanning',
        'is_complete',
        'total',
        'processed',
        'progress',
        'alive_count',
        'dead_count',
        'timeout_count',
        'current_offset',
        'batch_size',
        'results',
        'selected_ids',
    ];

    protected $casts = [
        'is_scanning' => 'boolean',
        'is_complete' => 'boolean',
        'results' => 'array',
        'selected_ids' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
