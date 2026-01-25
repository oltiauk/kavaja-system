<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isAdministration() || $user->isStaff();
    }

    public function view(User $user, Document $document): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function update(User $user, Document $document): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->isAdmin() || $user->isStaff();
    }

    public function restore(User $user, Document $document): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return $user->isAdmin();
    }
}
