<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('core.schools.view');
    }

    public function view(User $user, School $school): bool
    {
        return $user->hasPermissionTo('core.schools.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('core.schools.create');
    }

    public function update(User $user, School $school): bool
    {
        return $user->hasPermissionTo('core.schools.update');
    }

    public function switch(User $user, School $school): bool
    {
        return $user->hasPermissionTo('core.schools.switch');
    }

    public function delete(User $user, School $school): bool
    {
        return $user->hasPermissionTo('core.schools.delete')
            && ! $school->is_active
            && ! $school->is_default;
    }
}
