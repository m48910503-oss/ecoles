<?php

namespace App\Policies;

use App\Models\AcademicConductSetting;
use App\Models\User;

class AcademicConductSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['academic.conduct.view', 'academic.conduct.manage']);
    }

    public function view(User $user, AcademicConductSetting $academicConductSetting): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['academic.conduct.create', 'academic.conduct.manage']);
    }

    public function update(User $user, AcademicConductSetting $academicConductSetting): bool
    {
        return $user->hasAnyPermission(['academic.conduct.update', 'academic.conduct.manage']);
    }

    public function disable(User $user, AcademicConductSetting $academicConductSetting): bool
    {
        return $user->hasAnyPermission(['academic.conduct.disable', 'academic.conduct.manage']);
    }
}
