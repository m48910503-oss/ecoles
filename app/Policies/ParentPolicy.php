<?php

namespace App\Policies;

use App\Models\Guardian;
use App\Models\User;

class ParentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'people.parents.view',
            'people.parents.create',
            'people.parents.update',
            'people.parents.link_student',
        ]);
    }

    public function view(User $user, Guardian $parent): bool
    {
        if ($parent->user_id !== null && $parent->user_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('people.parents.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('people.parents.create');
    }

    public function update(User $user, Guardian $parent): bool
    {
        if ($parent->user_id !== null && $parent->user_id === $user->id) {
            return false;
        }

        return $user->hasPermissionTo('people.parents.update');
    }

    public function history(User $user, Guardian $parent): bool
    {
        return $user->hasPermissionTo('people.history.view');
    }

    public function linkStudent(User $user, Guardian $parent): bool
    {
        return $user->hasAnyPermission([
            'people.parents.link_student',
            'people.students.update',
        ]);
    }

    public function viewDocuments(User $user, Guardian $parent): bool
    {
        return $this->view($user, $parent) && $user->hasPermissionTo('people.documents.view');
    }

    public function attachDocument(User $user, Guardian $parent): bool
    {
        return $this->view($user, $parent) && $user->hasPermissionTo('people.documents.attach');
    }

    public function removeDocument(User $user, Guardian $parent): bool
    {
        return $this->view($user, $parent) && $user->hasPermissionTo('people.documents.remove');
    }

    public function viewAccount(User $user, Guardian $parent): bool
    {
        return $this->view($user, $parent) && $user->hasPermissionTo('people.accounts.view');
    }

    public function createAccount(User $user, Guardian $parent): bool
    {
        return $this->view($user, $parent) && $user->hasPermissionTo('people.accounts.create');
    }

    public function resetAccount(User $user, Guardian $parent): bool
    {
        return $this->view($user, $parent) && $parent->user_id !== null && $user->hasPermissionTo('people.accounts.reset');
    }

    public function toggleAccount(User $user, Guardian $parent): bool
    {
        return $this->view($user, $parent) && $parent->user_id !== null && $user->hasPermissionTo('people.accounts.suspend');
    }

    public function unlinkAccount(User $user, Guardian $parent): bool
    {
        return $this->view($user, $parent) && $parent->user_id !== null && $user->hasPermissionTo('people.accounts.unlink');
    }
}
