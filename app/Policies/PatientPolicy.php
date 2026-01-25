<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isAdministration() || $user->isStaff();
    }

    public function view(User $user, Patient $patient): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isAdministration();
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->isAdmin() || $user->isAdministration();
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Patient $patient): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Patient $patient): bool
    {
        return $user->isAdmin();
    }
}
