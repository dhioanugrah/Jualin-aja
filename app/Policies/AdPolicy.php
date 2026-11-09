<?php

namespace App\Policies;

use App\Models\Ad;
use App\Models\User;

/**
 * Banner iklan hanya dikelola admin (F15).
 */
class AdPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Ad $ad): bool
    {
        return $user->isAdmin();
    }
}
