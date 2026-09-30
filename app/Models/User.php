<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'setup_completed', 'pin_hash', 'about', 'religion', 'is_admin'])]
#[Hidden(['password', 'remember_token', 'app_login_code'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'app_login_code_rotated_at' => 'datetime',
            'is_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    public function familyMemberships(): HasMany
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function families(): BelongsToMany
    {
        return $this->belongsToMany(Family::class, 'family_members')
            ->withPivot('role', 'is_family_only')
            ->withTimestamps();
    }

    public function family(): ?Family
    {
        return $this->families()->orderBy('id')->first();
    }

    /**
     * The single membership row for this user.
     *
     * Preferred over family() whenever the payer role is needed, because
     * family() goes through the pivot and therefore cannot reach family_members.
     */
    public function familyMember(): ?FamilyMember
    {
        return $this->familyMemberships()->orderBy('id')->first();
    }

    public function payerRole(): ?string
    {
        return $this->familyMember()?->payer_role;
    }

    public function payerLabel(): ?string
    {
        return $this->familyMember()?->payerLabel();
    }

    public function isFamilyOnly(): bool
    {
        return (bool) $this->familyMemberships()
            ->where('is_family_only', true)
            ->exists();
    }

    public function isFamilyOwner(): bool
    {
        return (bool) $this->familyMemberships()
            ->where('role', 'owner')
            ->exists();
    }

    /**
     * The latest usable entitlement (extends on renewal, never stacks).
     * Falls back to the newest row when nothing is active yet.
     */
    public function activeSubscription(): ?Subscription
    {
        $family = $this->family();
        if ($family) {
            $familySubscription = Subscription::query()
                ->with('plan')
                ->where('family_id', $family->id)
                ->active()
                ->orderByDesc('id')
                ->get()
                ->first(fn (Subscription $s) => $s->isUsable());

            if ($familySubscription) {
                return $familySubscription;
            }
        }

        $usable = $this->subscriptions()
            ->with('plan')
            ->active()
            ->orderByDesc('id')
            ->get()
            ->first(fn (Subscription $s) => $s->isUsable());

        return $usable ?? $this->subscriptions()
            ->with('plan')
            ->orderByDesc('id')
            ->first();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()?->isUsable() ?? false;
    }
}
