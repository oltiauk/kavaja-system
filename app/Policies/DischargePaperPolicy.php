<?php

namespace App\Policies;

use App\Models\DischargePaper;
use App\Models\User;

class DischargePaperPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isAdministration() || $user->isStaff();
    }

    public function view(User $user, DischargePaper $dischargePaper): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function update(User $user, DischargePaper $dischargePaper): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function delete(User $user, DischargePaper $dischargePaper): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function restore(User $user, DischargePaper $dischargePaper): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, DischargePaper $dischargePaper): bool
    {
        return $user->isAdmin();
    }
}
