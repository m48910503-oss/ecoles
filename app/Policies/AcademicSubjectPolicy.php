<?php

namespace App\Policies;

use App\Models\AcademicSubject;
use App\Models\User;

class AcademicSubjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['academic.subjects.view', 'academic.subjects.manage']);
    }

    public function view(User $user, AcademicSubject $academicSubject): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['academic.subjects.create', 'academic.subjects.manage']);
    }

    public function update(User $user, AcademicSubject $academicSubject): bool
    {
        return $user->hasAnyPermission(['academic.subjects.update', 'academic.subjects.manage']);
    }

    public function disable(User $user, AcademicSubject $academicSubject): bool
    {
        return $user->hasAnyPermission(['academic.subjects.disable', 'academic.subjects.manage']);
    }
}
