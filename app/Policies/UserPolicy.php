<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('core.users.view');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('core.users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('core.users.create');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('core.users.update');
    }

    public function delete(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
