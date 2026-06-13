<?php

namespace App\Services\Academic;

use App\Enums\Academic\CalendarEventStatus;
use App\Enums\Academic\CalendarTargetType;
use App\Enums\Academic\CalendarEventVisibility;
use App\Events\CalendarEventCancelled;
use App\Events\CalendarEventPublished;
use App\Events\CalendarEventUpdated;
use App\Models\AcademicCalendar;
use App\Models\AcademicClassroom;
use App\Models\AcademicPeriod;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\Core\AuditLogService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalendarEventService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): CalendarEvent
    {
        return DB::transaction(function () use ($validated, $actor): CalendarEvent {
            $calendar = $this->resolveCalendar((int) $validated['academic_calendar_id']);

            $event = CalendarEvent::query()->create($this->payload($validated, $calendar, $actor));

            $this->auditLogService->log(
                module: 'academic',
                action: 'calendar_event.created',
                auditable: $event,
                newValues: $event->fresh()->toArray(),
                description: "Création de l'événement {$event->title}.",
                user: $actor,
            );

            return $event;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(CalendarEvent $event, array $validated, User $actor): CalendarEvent
    {
        return DB::transaction(function () use ($event, $validated, $actor): CalendarEvent {
            $oldValues = $event->toArray();
            $calendar = $this->resolveCalendar((int) $validated['academic_calendar_id']);

            $event->update($this->payload($validated, $calendar, $actor, $event));

            $freshEvent = $event->fresh();

            $this->auditLogService->log(
                module: 'academic',
                action: 'calendar_event.updated',
                auditable: $event,
                oldValues: $oldValues,
                newValues: $freshEvent?->toArray(),
                description: "Mise à jour de l'événement {$event->title}.",
                user: $actor,
            );

            event(new CalendarEventUpdated($freshEvent));

            return $event->refresh();
        });
    }

    public function publish(CalendarEvent $event, User $actor): CalendarEvent
    {
        return DB::transaction(function () use ($event, $actor): CalendarEvent {
            if ($event->status === CalendarEventStatus::Cancelled || $event->status === CalendarEventStatus::Archived) {
                throw ValidationException::withMessages([
                    'status' => __('academic.events.messages.publish_forbidden'),
                ]);
            }

            $oldValues = $event->toArray();

            $event->update([
                'status' => CalendarEventStatus::Published,
                'published_by' => $actor->id,
                'published_at' => now(),
                'validated_by' => $event->validated_by ?: $actor->id,
                'validated_at' => $event->validated_at ?: now(),
            ]);

            $freshEvent = $event->fresh();

            $this->auditLogService->log(
                module: 'academic',
                action: 'calendar_event.published',
                auditable: $event,
                oldValues: $oldValues,
                newValues: $freshEvent?->toArray(),
                description: "Publication de l'événement {$event->title}.",
                user: $actor,
            );

            event(new CalendarEventPublished($freshEvent));

            return $event->refresh();
        });
    }

    public function cancel(CalendarEvent $event, string $reason, User $actor): CalendarEvent
    {
        return DB::transaction(function () use ($event, $reason, $actor): CalendarEvent {
            if ($event->status === CalendarEventStatus::Archived) {
                throw ValidationException::withMessages([
                    'status' => __('academic.events.messages.cancel_archived_forbidden'),
                ]);
            }

            $oldValues = $event->toArray();

            $event->update([
                'status' => CalendarEventStatus::Cancelled,
                'cancelled_by' => $actor->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $freshEvent = $event->fresh();

            $this->auditLogService->log(
                module: 'academic',
                action: 'calendar_event.cancelled',
                auditable: $event,
                oldValues: $oldValues,
                newValues: $freshEvent?->toArray(),
                description: "Annulation de l'événement {$event->title}.",
                user: $actor,
            );

            event(new CalendarEventCancelled($freshEvent));

            return $event->refresh();
        });
    }

    public function archive(CalendarEvent $event, User $actor): CalendarEvent
    {
        return DB::transaction(function () use ($event, $actor): CalendarEvent {
            $oldValues = $event->toArray();

            $event->update([
                'status' => CalendarEventStatus::Archived,
                'archived_by' => $actor->id,
                'archived_at' => now(),
            ]);

            $this->auditLogService->log(
                module: 'academic',
                action: 'calendar_event.archived',
                auditable: $event,
                oldValues: $oldValues,
                newValues: $event->fresh()->toArray(),
                description: "Archivage de l'événement {$event->title}.",
                user: $actor,
            );

            return $event->refresh();
        });
    }

    public function delete(CalendarEvent $event, User $actor): void
    {
        DB::transaction(function () use ($event, $actor): void {
            if ($event->status === CalendarEventStatus::Published) {
                throw ValidationException::withMessages([
                    'event' => __('academic.events.messages.published_delete_forbidden'),
                ]);
            }

            $oldValues = $event->toArray();
            $title = $event->title;

            $event->delete();

            $this->auditLogService->log(
                module: 'academic',
                action: 'calendar_event.deleted',
                auditable: $event,
                oldValues: $oldValues,
                description: "Suppression logique de l'événement {$title}.",
                user: $actor,
            );
        });
    }

    public function applyVisibilityScope(Builder $query, User $user): Builder
    {
        if ($user->hasPermissionTo('academic.events.manage_all')) {
            return $query;
        }

        if ($user->hasAnyPermission(['academic.events.view', 'academic.events.view_internal'])) {
            return $query;
        }

        if ($user->hasPermissionTo('academic.events.view_public')) {
            $targetTypes = [CalendarTargetType::Global->value];

            if ($user->hasRole('parent')) {
                $targetTypes[] = CalendarTargetType::Parents->value;
            }

            if ($user->hasRole('eleve')) {
                $targetTypes[] = CalendarTargetType::Students->value;
            }

            if ($user->hasRole('enseignant')) {
                $targetTypes[] = CalendarTargetType::Teachers->value;
            }

            return $query
                ->where('status', CalendarEventStatus::Published->value)
                ->where('visibility', CalendarEventVisibility::Public->value)
                ->whereIn('target_type', $targetTypes);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated, AcademicCalendar $calendar, User $actor, ?CalendarEvent $existingEvent = null): array
    {
        $status = CalendarEventStatus::from((string) $validated['status']);
        $eventDates = $this->resolveEventDates($validated);
        $this->assertEventDatesWithinAcademicYear($eventDates, $calendar);
        $period = $this->resolveEventPeriod(
            isset($validated['academic_period_id']) ? (int) $validated['academic_period_id'] : null,
            $calendar,
            $eventDates,
        );

        if ($status === CalendarEventStatus::Validated && ! $actor->hasAnyPermission(['academic.events.validate', 'academic.events.manage_all'])) {
            throw ValidationException::withMessages([
                'status' => __('academic.events.validation.validation_permission_required'),
            ]);
        }

        $targetType = $validated['target_type'];
        $targetId = in_array($targetType, [
            CalendarTargetType::Global->value,
            CalendarTargetType::Staff->value,
            CalendarTargetType::Teachers->value,
            CalendarTargetType::Students->value,
            CalendarTargetType::Parents->value,
        ], true) ? null : ($validated['target_id'] ?? null);

        if ($targetType === CalendarTargetType::ClassRoom->value && $targetId) {
            $classroom = AcademicClassroom::query()->findOrFail($targetId);

            if ($classroom->school_id !== $calendar->school_id) {
                throw ValidationException::withMessages([
                    'target_id' => __('academic.events.validation.class_target_school_mismatch'),
                ]);
            }

            if ($classroom->academic_year_id !== $calendar->academic_year_id) {
                throw ValidationException::withMessages([
                    'target_id' => __('academic.events.validation.class_target_year_mismatch'),
                ]);
            }
        }

        return [
            'school_id' => $calendar->school_id,
            'academic_calendar_id' => $calendar->id,
            'academic_period_id' => $period?->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'event_type' => $validated['event_type'],
            'start_datetime' => $eventDates['start_datetime'],
            'end_datetime' => $eventDates['end_datetime'],
            'is_full_day' => (bool) $validated['is_full_day'],
            'location' => isset($validated['location']) && trim((string) $validated['location']) !== '' ? trim((string) $validated['location']) : null,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'status' => $status,
            'visibility' => $validated['visibility'],
            'requires_validation' => (bool) $validated['requires_validation'],
            'created_by' => $existingEvent?->created_by ?: $actor->id,
            'validated_by' => $status === CalendarEventStatus::Validated ? $actor->id : $existingEvent?->validated_by,
            'validated_at' => $status === CalendarEventStatus::Validated ? now() : $existingEvent?->validated_at,
            'cancellation_reason' => $existingEvent?->status === CalendarEventStatus::Cancelled ? $existingEvent->cancellation_reason : null,
            'cancelled_by' => $existingEvent?->status === CalendarEventStatus::Cancelled ? $existingEvent->cancelled_by : null,
            'cancelled_at' => $existingEvent?->status === CalendarEventStatus::Cancelled ? $existingEvent->cancelled_at : null,
            'published_by' => $existingEvent?->published_by,
            'published_at' => $existingEvent?->published_at,
            'archived_by' => $existingEvent?->archived_by,
            'archived_at' => $existingEvent?->archived_at,
        ];
    }

    private function resolveCalendar(int $calendarId): AcademicCalendar
    {
        $calendar = AcademicCalendar::query()->findOrFail($calendarId);

        if (! $calendar->is_active) {
            throw ValidationException::withMessages([
                'academic_calendar_id' => __('academic.events.validation.inactive_calendar'),
            ]);
        }

        return $calendar;
    }

    /**
     * @param  array{start_datetime: Carbon, end_datetime: Carbon}  $eventDates
     */
    private function assertEventDatesWithinAcademicYear(array $eventDates, AcademicCalendar $calendar): void
    {
        $calendar->loadMissing('academicYear');
        $yearStart = $calendar->academicYear?->start_date?->copy()->startOfDay();
        $yearEnd = $calendar->academicYear?->end_date?->copy()->endOfDay();

        if (! $yearStart || ! $yearEnd) {
            return;
        }

        if ($eventDates['start_datetime']->lt($yearStart) || $eventDates['end_datetime']->gt($yearEnd)) {
            throw ValidationException::withMessages([
                'event_date' => __('academic.events.validation.outside_academic_year'),
            ]);
        }
    }

    /**
     * @param  array{start_datetime: Carbon, end_datetime: Carbon}  $eventDates
     */
    private function resolveEventPeriod(?int $periodId, AcademicCalendar $calendar, array $eventDates): ?AcademicPeriod
    {
        if (! $periodId) {
            return null;
        }

        $period = AcademicPeriod::query()->find($periodId);

        if (! $period) {
            throw ValidationException::withMessages([
                'academic_period_id' => __('academic.events.validation.period_invalid'),
            ]);
        }

        if ((int) $period->academic_calendar_id !== (int) $calendar->id) {
            throw ValidationException::withMessages([
                'academic_period_id' => __('academic.events.validation.period_calendar_mismatch'),
            ]);
        }

        if (! $period->start_date || ! $period->end_date) {
            throw ValidationException::withMessages([
                'academic_period_id' => __('academic.events.validation.period_dates_missing'),
            ]);
        }

        $periodStart = $period->start_date->copy()->startOfDay();
        $periodEnd = $period->end_date->copy()->endOfDay();

        if ($eventDates['start_datetime']->lt($periodStart) || $eventDates['end_datetime']->gt($periodEnd)) {
            throw ValidationException::withMessages([
                'academic_period_id' => __('academic.events.validation.period_date_mismatch'),
            ]);
        }

        return $period;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{start_datetime: Carbon, end_datetime: Carbon}
     */
    private function resolveEventDates(array $validated): array
    {
        if (! empty($validated['is_full_day'])) {
            $eventDate = Carbon::parse((string) $validated['event_date']);

            return [
                'start_datetime' => $eventDate->copy()->startOfDay(),
                'end_datetime' => $eventDate->copy()->endOfDay(),
            ];
        }

        return [
            'start_datetime' => Carbon::parse((string) $validated['start_datetime']),
            'end_datetime' => Carbon::parse((string) $validated['end_datetime']),
        ];
    }
}
