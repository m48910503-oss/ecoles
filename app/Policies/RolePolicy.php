<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('core.roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('core.roles.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('core.roles.manage');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('core.roles.manage');
    }

    public function delete(User $user, Role $role): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Role $role): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Role $role): bool
    {
        return false;
    }
}
