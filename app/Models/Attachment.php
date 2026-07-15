<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $fillable = [
        'item_id', 'filename', 'original_name',
        'mime_type', 'size', 'collection_name', 'custom_properties',
    ];

    protected function casts(): array
    {
        return [
            'custom_properties' => 'array',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
