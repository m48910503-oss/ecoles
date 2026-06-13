<?php

namespace App\Policies;

use App\Models\AcademicConfiguration;
use App\Models\User;

class AcademicConfigurationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['academic.config.view', 'academic.config.manage']);
    }

    public function view(User $user, AcademicConfiguration $academicConfiguration): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['academic.config.update', 'academic.config.manage']);
    }

    public function update(User $user, AcademicConfiguration $academicConfiguration): bool
    {
        return $user->hasAnyPermission(['academic.config.update', 'academic.config.manage']);
    }
}
