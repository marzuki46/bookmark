<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['promo_code_id', 'user_id', 'subscription_id', 'redeemed_at'])]
final class PromoRedemption extends Model
{
    protected function casts(): array
    {
        return ['redeemed_at' => 'datetime'];
    }
}
