<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyInsight extends Model
{
    public const MAX_LENGTH = 240;

    protected $fillable = [
        'family_id',
        'user_id',
        'scope',
        'week_key',
        'message',
        'tone',
        'metrics',
        'is_fallback',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'is_fallback' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForWeek(Builder $query, string $weekKey): Builder
    {
        return $query->where('week_key', $weekKey);
    }

    /**
     * Trims to the contract the notification title relies on. Substr can cut a
     * multi-byte character in half, so the length is measured in characters.
     */
    public static function clamp(string $message): string
    {
        $message = trim(preg_replace('/\s+/u', ' ', $message) ?? '');

        if (mb_strlen($message) <= self::MAX_LENGTH) {
            return $message;
        }

        return rtrim(mb_substr($message, 0, self::MAX_LENGTH - 1)).'…';
    }
}
