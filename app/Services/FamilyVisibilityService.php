<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class FamilyVisibilityService
{
    public function member(User $user, Family $family): FamilyMember
    {
        return $family->members()->where('user_id', $user->id)->firstOrFail();
    }

    public function canView(User $user, Family $family, string $area): bool
    {
        return $this->member($user, $family)->canView($area);
    }

    /** Restrict hidden income/expense rows to the member's own or shared rows. */
    public function scopeTransactions($query, User $user, Family $family)
    {
        $member = $this->member($user, $family);
        if ($member->role === 'owner' || ($member->canView('income') && $member->canView('expense'))) {
            return $query;
        }

        return $query->where(function (Builder $visible) use ($member, $user): void {
            foreach (['income', 'expense'] as $type) {
                if ($member->canView($type)) {
                    $visible->orWhere('type', $type);

                    continue;
                }

                $visible->orWhere(function (Builder $own) use ($type, $user): void {
                    $own->where('type', $type)
                        ->where(function (Builder $rows) use ($user): void {
                            $rows->where('user_id', $user->id)->orWhere('payer', 'shared');
                        });
                });
            }
        });
    }
}
