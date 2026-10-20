<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ApkDownload extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'app_release_id',
        'downloaded_at',
    ];

    protected function casts(): array
    {
        return ['downloaded_at' => 'datetime'];
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(AppRelease::class, 'app_release_id');
    }
}
