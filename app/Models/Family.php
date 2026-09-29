<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    protected $fillable = [
        'name',
        'owner_user_id',
        'housing_complex_id',
        'invite_code',
        'advisor_enabled',
        'advisor_profile',
    ];

    protected $casts = [
        'advisor_enabled' => 'boolean',
        'advisor_profile' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function housingComplex(): BelongsTo
    {
        return $this->belongsTo(HousingComplex::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(FamilyCategory::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FamilyTransaction::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(FamilyBudget::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(FamilyGoal::class);
    }

    public function debts(): HasMany
    {
        return $this->hasMany(FamilyDebt::class);
    }

    public function incomeSources(): HasMany
    {
        return $this->hasMany(IncomeSource::class);
    }

    public function insights(): HasMany
    {
        return $this->hasMany(FamilyInsight::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(FamilyAllocation::class);
    }

    public static function generateInviteCode(): string
    {
        do {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while (static::where('invite_code', $code)->exists());

        return $code;
    }

    public function isMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function isOwner(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->where('role', 'owner')
            ->exists();
    }
}
