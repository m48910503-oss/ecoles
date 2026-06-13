<?php

namespace App\Policies;

use App\Models\AcademicClassroom;
use App\Models\User;

class AcademicClassroomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['academic.classes.view', 'academic.classes.manage']);
    }

    public function view(User $user, AcademicClassroom $academicClassroom): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['academic.classes.create', 'academic.classes.manage']);
    }

    public function update(User $user, AcademicClassroom $academicClassroom): bool
    {
        return $user->hasAnyPermission(['academic.classes.update', 'academic.classes.manage']);
    }

    public function disable(User $user, AcademicClassroom $academicClassroom): bool
    {
        return $user->hasAnyPermission(['academic.classes.disable', 'academic.classes.manage']);
    }

    public function delete(User $user, AcademicClassroom $academicClassroom): bool
    {
        return $user->hasAnyPermission(['academic.classes.manage']);
    }
}
