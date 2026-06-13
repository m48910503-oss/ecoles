<?php

namespace App\Policies;

use App\Models\AcademicCoefficient;
use App\Models\User;

class AcademicCoefficientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['academic.coefficients.view', 'academic.coefficients.manage']);
    }

    public function view(User $user, AcademicCoefficient $academicCoefficient): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['academic.coefficients.create', 'academic.coefficients.manage']);
    }

    public function update(User $user, AcademicCoefficient $academicCoefficient): bool
    {
        return $user->hasAnyPermission(['academic.coefficients.update', 'academic.coefficients.manage']);
    }

    public function disable(User $user, AcademicCoefficient $academicCoefficient): bool
    {
        return $user->hasAnyPermission(['academic.coefficients.disable', 'academic.coefficients.manage']);
    }
}
