<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    // Zarządzanie użytkownikami zarezerwowane dla administratora (w obrębie tenanta).
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin() && $user->tenant_id === $target->tenant_id;
    }

    public function delete(User $user, User $target): bool
    {
        // Administrator nie może usunąć samego siebie.
        return $user->isAdmin()
            && $user->tenant_id === $target->tenant_id
            && $user->id !== $target->id;
    }
}
