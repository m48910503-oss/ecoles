<?php

namespace App\Policies;

use App\Models\AcademicCycle;
use App\Models\User;

class AcademicCyclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['academic.cycles.view', 'academic.cycles.manage']);
    }

    public function view(User $user, AcademicCycle $academicCycle): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['academic.cycles.create', 'academic.cycles.manage']);
    }

    public function update(User $user, AcademicCycle $academicCycle): bool
    {
        return $user->hasAnyPermission(['academic.cycles.update', 'academic.cycles.manage']);
    }

    public function disable(User $user, AcademicCycle $academicCycle): bool
    {
        return $user->hasAnyPermission(['academic.cycles.disable', 'academic.cycles.manage']);
    }

    public function delete(User $user, AcademicCycle $academicCycle): bool
    {
        return $user->hasAnyPermission(['academic.cycles.manage']);
    }
}
