<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\User;

class StaffPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'people.staff.view',
            'people.staff.create',
            'people.staff.update',
            'people.staff.disable',
        ]);
    }

    public function view(User $user, Staff $staff): bool
    {
        if ($staff->user_id !== null && $staff->user_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('people.staff.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('people.staff.create');
    }

    public function update(User $user, Staff $staff): bool
    {
        if ($staff->user_id !== null && $staff->user_id === $user->id) {
            return false;
        }

        return $user->hasPermissionTo('people.staff.update');
    }

    public function disable(User $user, Staff $staff): bool
    {
        return $user->hasPermissionTo('people.staff.disable');
    }

    public function history(User $user, Staff $staff): bool
    {
        return $user->hasPermissionTo('people.history.view');
    }

    public function viewDocuments(User $user, Staff $staff): bool
    {
        return $this->view($user, $staff) && $user->hasPermissionTo('people.documents.view');
    }

    public function attachDocument(User $user, Staff $staff): bool
    {
        return $this->view($user, $staff) && $user->hasPermissionTo('people.documents.attach');
    }

    public function removeDocument(User $user, Staff $staff): bool
    {
        return $this->view($user, $staff) && $user->hasPermissionTo('people.documents.remove');
    }

    public function viewAccount(User $user, Staff $staff): bool
    {
        return $this->view($user, $staff) && $user->hasPermissionTo('people.accounts.view');
    }

    public function createAccount(User $user, Staff $staff): bool
    {
        return $this->view($user, $staff) && $user->hasPermissionTo('people.accounts.create');
    }

    public function resetAccount(User $user, Staff $staff): bool
    {
        return $this->view($user, $staff) && $staff->user_id !== null && $user->hasPermissionTo('people.accounts.reset');
    }

    public function toggleAccount(User $user, Staff $staff): bool
    {
        return $this->view($user, $staff) && $staff->user_id !== null && $user->hasPermissionTo('people.accounts.suspend');
    }

    public function unlinkAccount(User $user, Staff $staff): bool
    {
        return $this->view($user, $staff) && $staff->user_id !== null && $user->hasPermissionTo('people.accounts.unlink');
    }
}
