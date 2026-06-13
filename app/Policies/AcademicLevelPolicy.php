<?php

namespace App\Policies;

use App\Models\AcademicLevel;
use App\Models\User;

class AcademicLevelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['academic.levels.view', 'academic.levels.manage']);
    }

    public function view(User $user, AcademicLevel $academicLevel): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['academic.levels.create', 'academic.levels.manage']);
    }

    public function update(User $user, AcademicLevel $academicLevel): bool
    {
        return $user->hasAnyPermission(['academic.levels.update', 'academic.levels.manage']);
    }

    public function disable(User $user, AcademicLevel $academicLevel): bool
    {
        return $user->hasAnyPermission(['academic.levels.disable', 'academic.levels.manage']);
    }

    public function delete(User $user, AcademicLevel $academicLevel): bool
    {
        return $user->hasAnyPermission(['academic.levels.manage']);
    }
}
