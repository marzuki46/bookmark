<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyMember extends Model
{
    protected $fillable = [
        'family_id',
        'user_id',
        'role',
        'is_family_only',
        'payer_role',
    ];

    protected function casts(): array
    {
        return [
            'is_family_only' => 'boolean',
        ];
    }

    /**
     * One user belongs to exactly one family in the app.
     *
     * Enforced here rather than in a controller so every path is covered: the
     * web invite flow, the API, console commands and the tests all create
     * FamilyMember rows, and a membership created outside the app is exactly
     * how a user would end up with two households and two sets of finances.
     *
     * The legacy family_members table stays many-to-many so the existing web
     * invite_code flow keeps working; this only refuses the second household.
     */
    protected static function booted(): void
    {
        static::creating(function (self $member): void {
            $conflict = static::query()
                ->where('user_id', $member->user_id)
                ->where('family_id', '!=', $member->family_id)
                ->exists();

            if ($conflict) {
                throw new \RuntimeException(
                    'Pengguna ini sudah menjadi anggota keluarga lain. '
                    .'Satu akun hanya boleh punya satu keluarga.'
                );
            }
        });
    }

    public function isHusband(): bool
    {
        return $this->payer_role === 'husband';
    }

    public function isWife(): bool
    {
        return $this->payer_role === 'wife';
    }

    public function payerLabel(): ?string
    {
        return match ($this->payer_role) {
            'husband' => 'Suami',
            'wife' => 'Istri',
            default => null,
        };
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
