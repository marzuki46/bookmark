<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedLoginLog extends Model
{
    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
        'was_blocked',
    ];

    protected $casts = [
        'was_blocked' => 'boolean',
    ];

    public function ipBlock()
    {
        return $this->belongsTo(IpBlock::class, 'ip_address', 'ip_address');
    }

    public function scopeRecent($query, int $minutes = 15)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }
}
