<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An uploaded Android APK release. The newest version_code wins the in-app
 * update check, mimicking a store-managed rollout without the store.
 */
final class AppRelease extends Model
{
    protected $fillable = [
        'version_code',
        'version_name',
        'notes',
        'file_path',
        'file_size',
        'sha256',
    ];

    protected function casts(): array
    {
        return [
            'version_code' => 'integer',
            'file_size' => 'integer',
        ];
    }
}