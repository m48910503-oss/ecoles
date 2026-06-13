<?php

namespace App\Policies;

use App\Models\AcademicCalendar;
use App\Models\User;

class AcademicCalendarPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['academic.calendars.view', 'academic.calendars.manage']);
    }

    public function view(User $user, AcademicCalendar $academicCalendar): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['academic.calendars.create', 'academic.calendars.manage']);
    }

    public function update(User $user, AcademicCalendar $academicCalendar): bool
    {
        return $user->hasAnyPermission(['academic.calendars.update', 'academic.calendars.manage']);
    }

    public function disable(User $user, AcademicCalendar $academicCalendar): bool
    {
        return $user->hasAnyPermission(['academic.calendars.disable', 'academic.calendars.manage']);
    }

    public function delete(User $user, AcademicCalendar $academicCalendar): bool
    {
        return false;
    }

    public function restore(User $user, AcademicCalendar $academicCalendar): bool
    {
        return false;
    }

    public function forceDelete(User $user, AcademicCalendar $academicCalendar): bool
    {
        return false;
    }
}
