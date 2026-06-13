<?php

namespace App\Policies;

use App\Models\AcademicYear;
use App\Models\User;

class AcademicYearPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('core.academic_years.view');
    }

    public function view(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasPermissionTo('core.academic_years.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('core.academic_years.create');
    }

    public function update(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasPermissionTo('core.academic_years.update')
            || $user->hasPermissionTo('core.academic_years.activate');
    }

    public function delete(User $user, AcademicYear $academicYear): bool
    {
        return $user->hasPermissionTo('core.academic_years.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AcademicYear $academicYear): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AcademicYear $academicYear): bool
    {
        return false;
    }
}
