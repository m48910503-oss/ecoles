<?php

namespace App\Policies;

use App\Enums\Academic\CalendarEventVisibility;
use App\Enums\Academic\CalendarEventStatus;
use App\Enums\Academic\CalendarTargetType;
use App\Models\CalendarEvent;
use App\Models\User;

class CalendarEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'academic.events.view',
            'academic.events.view_internal',
            'academic.events.view_public',
            'academic.events.manage_all',
        ]);
    }

    public function view(User $user, CalendarEvent $calendarEvent): bool
    {
        if ($user->hasPermissionTo('academic.events.manage_all')) {
            return true;
        }

        if ($user->hasAnyPermission(['academic.events.view', 'academic.events.view_internal'])) {
            if ($calendarEvent->visibility === CalendarEventVisibility::Internal) {
                return true;
            }

            return $calendarEvent->status === CalendarEventStatus::Published
                || $user->hasPermissionTo('academic.events.view');
        }

        if (! $user->hasPermissionTo('academic.events.view_public')) {
            return false;
        }

        return $calendarEvent->status === CalendarEventStatus::Published
            && $calendarEvent->visibility === CalendarEventVisibility::Public
            && in_array($calendarEvent->target_type, $this->publicTargetsFor($user), true);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermission(['academic.events.create', 'academic.events.manage_all']);
    }

    public function update(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->hasAnyPermission(['academic.events.update', 'academic.events.manage_all']);
    }

    public function publish(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->hasAnyPermission(['academic.events.publish', 'academic.events.manage_all']);
    }

    public function validate(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->hasAnyPermission(['academic.events.validate', 'academic.events.manage_all']);
    }

    public function cancel(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->hasAnyPermission(['academic.events.cancel', 'academic.events.manage_all']);
    }

    public function archive(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->hasAnyPermission(['academic.events.archive', 'academic.events.manage_all']);
    }

    public function delete(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->hasAnyPermission(['academic.events.delete', 'academic.events.manage_all'])
            && $calendarEvent->status !== CalendarEventStatus::Published;
    }

    public function restore(User $user, CalendarEvent $calendarEvent): bool
    {
        return false;
    }

    public function forceDelete(User $user, CalendarEvent $calendarEvent): bool
    {
        return false;
    }

    /**
     * @return array<int, CalendarTargetType>
     */
    private function publicTargetsFor(User $user): array
    {
        $targets = [CalendarTargetType::Global];

        if ($user->hasRole('parent')) {
            $targets[] = CalendarTargetType::Parents;
        }

        if ($user->hasRole('eleve')) {
            $targets[] = CalendarTargetType::Students;
        }

        if ($user->hasRole('enseignant')) {
            $targets[] = CalendarTargetType::Teachers;
        }

        return $targets;
    }
}
