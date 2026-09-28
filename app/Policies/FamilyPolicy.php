<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Family;
use App\Models\User;

final class FamilyPolicy
{
    /**
     * Any member may read family data. This is the boundary that keeps a
     * housing-complex admin out: they are never added to family_members, so
     * every family-scoped endpoint rejects them.
     */
    public function view(User $user, Family $family): bool
    {
        return $family->isMember($user);
    }

    public function update(User $user, Family $family): bool
    {
        return $family->isMember($user);
    }

    public function manage(User $user, Family $family): bool
    {
        return $family->isOwner($user);
    }
}
