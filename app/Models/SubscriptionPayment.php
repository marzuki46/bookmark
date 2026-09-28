<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'subscription_id',
    'plan_id',
    'order_id',
    'transaction_id',
    'gross_amount',
    'payment_type',
    'status',
    'raw',
    'paid_at',
])]
class SubscriptionPayment extends Model
{
    protected function casts(): array
    {
        return [
            'gross_amount' => 'integer',
            'raw' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function markPaid(): void
    {
        $this->forceFill(['status' => 'paid', 'paid_at' => now()])->save();
    }
}
