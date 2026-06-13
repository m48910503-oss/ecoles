<?php

namespace App\Policies;

use App\Models\AcademicSeries;
use App\Models\User;

class AcademicSeriesPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['academic.series.view', 'academic.series.manage']);
    }

    public function view(User $user, AcademicSeries $academicSeries): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['academic.series.create', 'academic.series.manage']);
    }

    public function update(User $user, AcademicSeries $academicSeries): bool
    {
        return $user->hasAnyPermission(['academic.series.update', 'academic.series.manage']);
    }

    public function disable(User $user, AcademicSeries $academicSeries): bool
    {
        return $user->hasAnyPermission(['academic.series.disable', 'academic.series.manage']);
    }

    public function delete(User $user, AcademicSeries $academicSeries): bool
    {
        return $user->hasAnyPermission(['academic.series.manage']);
    }
}
