<?php

namespace App\Policies;

use App\Models\AcademicPeriod;
use App\Models\User;

class AcademicPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['academic.periods.view', 'academic.periods.manage']);
    }

    public function view(User $user, AcademicPeriod $academicPeriod): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['academic.periods.create', 'academic.periods.manage']);
    }

    public function update(User $user, AcademicPeriod $academicPeriod): bool
    {
        return $user->hasAnyPermission(['academic.periods.update', 'academic.periods.manage']);
    }

    public function lock(User $user, AcademicPeriod $academicPeriod): bool
    {
        return $user->hasAnyPermission(['academic.periods.lock', 'academic.periods.manage']);
    }
}
