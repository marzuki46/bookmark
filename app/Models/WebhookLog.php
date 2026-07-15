<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class WebhookLog extends Model
{
    protected $fillable = [
        'source',
        'method',
        'headers',
        'raw_input',
        'sender',
        'message',
        'message_id',
        'response',
        'response_status',
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
        ];
    }
}
