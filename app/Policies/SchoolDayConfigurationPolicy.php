<?php

namespace App\Policies;

use App\Models\SchoolDayConfiguration;
use App\Models\User;

class SchoolDayConfigurationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['academic.day_config.view', 'academic.day_config.manage']);
    }

    public function view(User $user, SchoolDayConfiguration $schoolDayConfiguration): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['academic.day_config.create', 'academic.day_config.manage']);
    }

    public function update(User $user, SchoolDayConfiguration $schoolDayConfiguration): bool
    {
        return $user->hasAnyPermission(['academic.day_config.update', 'academic.day_config.manage']);
    }

    public function lock(User $user, SchoolDayConfiguration $schoolDayConfiguration): bool
    {
        return $user->hasAnyPermission(['academic.day_config.lock', 'academic.day_config.manage']);
    }
}
