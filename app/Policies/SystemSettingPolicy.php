<?php

namespace App\Policies;

use App\Models\SystemSetting;
use App\Models\User;

class SystemSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('core.settings.manage');
    }

    public function view(User $user, SystemSetting $systemSetting): bool
    {
        return $user->hasPermissionTo('core.settings.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('core.settings.manage');
    }

    public function update(User $user, SystemSetting $systemSetting): bool
    {
        return $user->hasPermissionTo('core.settings.manage');
    }

    public function manageMultiSchool(User $user): bool
    {
        return $user->hasPermissionTo('core.multi_school.manage');
    }

    public function delete(User $user, SystemSetting $systemSetting): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SystemSetting $systemSetting): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SystemSetting $systemSetting): bool
    {
        return false;
    }
}
