<?php

namespace App\Policies;

use App\Models\Encounter;
use App\Models\User;

class EncounterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isAdministration() || $user->isStaff();
    }

    public function view(User $user, Encounter $encounter): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isAdministration();
    }

    public function update(User $user, Encounter $encounter): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function delete(User $user, Encounter $encounter): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Encounter $encounter): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Encounter $encounter): bool
    {
        return $user->isAdmin();
    }
}
