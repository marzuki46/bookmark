<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'description', 'duration_type', 'duration_days', 'ai_analysis_limit', 'price', 'is_active'])]
class SubscriptionPlan extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'duration_days' => 'integer',
            'ai_analysis_limit' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    public function durationLabel(): string
    {
        if ($this->duration_days) {
            return $this->duration_days.' hari';
        }

        return match ($this->duration_type) {
            'lifetime' => 'Seumur hidup',
            'monthly' => 'Bulanan',
            'yearly' => 'Tahunan',
            default => ucfirst($this->duration_type),
        };
    }
}
