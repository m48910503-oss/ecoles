<?php

namespace App\Services\Academic;

use App\Enums\Academic\CalendarEventStatus;
use App\Enums\Academic\CalendarScopeType;
use App\Models\AcademicCalendar;
use App\Models\AcademicConfiguration;
use App\Models\AcademicCycle;
use App\Models\AcademicLevel;
use App\Models\AcademicSeries;
use App\Models\AcademicClassroom;
use App\Models\AcademicSubject;
use App\Models\AcademicCoefficient;
use App\Models\AcademicConductSetting;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\CalendarEvent;
use App\Models\SchoolDayConfiguration;
use App\Models\User;
use App\Services\Core\SchoolContext;

class AcademicDashboardService
{
    public function __construct(
        private readonly SchoolContext $schoolContext,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $school = $this->schoolContext->current();
        $activeYear = AcademicYear::query()->active()->first();

        $activeConfiguration = AcademicConfiguration::query()
            ->with(['periods', 'cycle'])
            ->where('school_id', $school->id)
            ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
            ->where('is_active', true)
            ->orderByRaw("CASE scope_type WHEN 'global' THEN 0 WHEN 'cycle' THEN 1 ELSE 2 END")
            ->orderBy('id')
            ->first();

        $activeCalendar = AcademicCalendar::query()
            ->where('school_id', $school->id)
            ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
            ->where('is_active', true)
            ->orderByRaw("CASE scope_type WHEN 'global' THEN 0 WHEN 'cycle' THEN 1 WHEN 'level' THEN 2 WHEN 'class' THEN 3 ELSE 4 END")
            ->orderBy('id')
            ->with(['periods' => fn ($query) => $query->orderBy('position')])
            ->first();

        $activeDayConfiguration = SchoolDayConfiguration::query()
            ->where('school_id', $school->id)
            ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
            ->where('is_active', true)
            ->orderByRaw("CASE scope_type WHEN 'global' THEN 0 WHEN 'cycle' THEN 1 ELSE 2 END")
            ->orderBy('id')
            ->with('cycle')
            ->first();

        $baseEvents = CalendarEvent::query()
            ->where('school_id', $school->id)
            ->when($activeYear, fn ($query) => $query->whereHas('calendar', fn ($calendarQuery) => $calendarQuery->where('academic_year_id', $activeYear->id)));

        $activePeriods = $activeCalendar?->periods ?? collect();

        return [
            'activeYear' => $activeYear,
            'activeConfiguration' => $activeConfiguration,
            'activeCalendar' => $activeCalendar,
            'activeDayConfiguration' => $activeDayConfiguration,
            'activePeriods' => $activePeriods,
            'stats' => [
                'period_type' => $activeConfiguration?->period_type?->label() ?? __('academic.dashboard.not_configured'),
                'period_count' => $activeConfiguration?->period_count ?? 0,
                'school_day_type' => $activeConfiguration?->school_day_type?->label() ?? __('academic.dashboard.not_configured'),
                'cycles' => AcademicCycle::query()->where('is_active', true)->count(),
                'levels' => AcademicLevel::query()->where('is_active', true)->count(),
                'series' => AcademicSeries::query()->where('is_active', true)->count(),
                'classes' => AcademicClassroom::query()
                    ->where('school_id', $school->id)
                    ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
                    ->where('is_active', true)
                    ->count(),
                'subjects' => AcademicSubject::query()
                    ->where('school_id', $school->id)
                    ->where('is_active', true)
                    ->count(),
                'coefficients' => AcademicCoefficient::query()
                    ->where('school_id', $school->id)
                    ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
                    ->where('is_active', true)
                    ->count(),
                'conduct' => AcademicConductSetting::query()
                    ->where('school_id', $school->id)
                    ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
                    ->where('is_active', true)
                    ->count(),
                'active_calendars' => AcademicCalendar::query()
                    ->where('school_id', $school->id)
                    ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
                    ->where('is_active', true)
                    ->count(),
                'upcoming_events' => (clone $baseEvents)
                    ->where('status', CalendarEventStatus::Published->value)
                    ->where('start_datetime', '>=', now())
                    ->count(),
                'pending_events' => (clone $baseEvents)
                    ->where('status', CalendarEventStatus::PendingValidation->value)
                    ->count(),
                'published_events' => (clone $baseEvents)
                    ->where('status', CalendarEventStatus::Published->value)
                    ->count(),
            ],
            'upcomingEvents' => (clone $baseEvents)
                ->with('calendar')
                ->where('status', CalendarEventStatus::Published->value)
                ->where('start_datetime', '>=', now())
                ->orderBy('start_datetime')
                ->limit(5)
                ->get(),
        ];
    }
}
