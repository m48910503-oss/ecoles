<?php

namespace App\Http\Controllers\Core;

use App\Enums\Academic\CalendarEventStatus;
use App\Enums\Academic\CalendarScopeType;
use App\Http\Controllers\Controller;
use App\Models\AcademicCalendar;
use App\Models\AcademicClassroom;
use App\Models\AcademicConfiguration;
use App\Models\AcademicCycle;
use App\Models\AcademicLevel;
use App\Models\AcademicSeries;
use App\Models\AcademicYear;
use App\Models\CalendarEvent;
use App\Services\Core\AcademicYearCycleConfigurationService;
use App\Services\Core\AcademicYearService;
use App\Services\Core\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicYearCycleController extends Controller
{
    public function __construct(
        private readonly AcademicYearCycleConfigurationService $academicYearCycleConfigurationService,
        private readonly AcademicYearService $academicYearService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    public function show(Request $request, AcademicYear $academicYear, AcademicCycle $cycle): View
    {
        $this->authorize('view', $academicYear);
        abort_unless($academicYear->cycles()->whereKey($cycle->id)->exists(), 404);
        $this->academicYearService->ensureDefaultGlobalCalendar($academicYear, $request->user());

        $currentSchool = $this->schoolContext->current();
        $activeLevelIds = $academicYear->levels()
            ->where('academic_levels.cycle_id', $cycle->id)
            ->pluck('academic_levels.id')
            ->map(fn ($levelId) => (int) $levelId)
            ->all();

        $activeSeriesIds = $academicYear->series()
            ->where('academic_series.cycle_id', $cycle->id)
            ->pluck('academic_series.id')
            ->map(fn ($seriesId) => (int) $seriesId)
            ->all();

        $levels = AcademicLevel::query()
            ->withCount([
                'classrooms as year_classrooms_count' => fn ($query) => $query
                    ->where('academic_year_id', $academicYear->id)
                    ->where('school_id', $currentSchool->id),
                'coefficients as year_coefficients_count' => fn ($query) => $query
                    ->where('academic_year_id', $academicYear->id)
                    ->where('school_id', $currentSchool->id),
            ])
            ->where('cycle_id', $cycle->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $series = AcademicSeries::query()
            ->withCount([
                'classrooms as year_classrooms_count' => fn ($query) => $query
                    ->where('academic_year_id', $academicYear->id)
                    ->where('school_id', $currentSchool->id),
                'coefficients as year_coefficients_count' => fn ($query) => $query
                    ->where('academic_year_id', $academicYear->id)
                    ->where('school_id', $currentSchool->id),
                'levels',
            ])
            ->where('cycle_id', $cycle->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $classrooms = AcademicClassroom::query()
            ->with(['level', 'series'])
            ->where('school_id', $currentSchool->id)
            ->where('academic_year_id', $academicYear->id)
            ->whereHas('level', fn ($query) => $query->where('cycle_id', $cycle->id))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $cycleCalendar = AcademicCalendar::query()
            ->with(['cycle', 'coveredCycles', 'validator'])
            ->withCount([
                'periods',
                'events',
                'events as published_events_count' => fn ($query) => $query
                    ->where('status', CalendarEventStatus::Published->value),
                'events as upcoming_events_count' => fn ($query) => $query
                    ->where('status', CalendarEventStatus::Published->value)
                    ->where('start_datetime', '>=', now()),
            ])
            ->where('school_id', $currentSchool->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('scope_type', CalendarScopeType::Cycle->value)
            ->where(function ($query) use ($cycle): void {
                $query
                    ->whereHas('coveredCycles', fn ($cycleQuery) => $cycleQuery->whereKey($cycle->id))
                    ->orWhere('cycle_id', $cycle->id);
            })
            ->orderByRaw('academic_configuration_id IS NOT NULL DESC')
            ->orderByDesc('validated_at')
            ->orderByDesc('is_active')
            ->first();

        if (! $cycleCalendar) {
            $cycleCalendar = AcademicCalendar::query()
                ->with(['cycle', 'coveredCycles', 'validator'])
                ->withCount([
                    'periods',
                    'events',
                    'events as published_events_count' => fn ($query) => $query
                        ->where('status', CalendarEventStatus::Published->value),
                    'events as upcoming_events_count' => fn ($query) => $query
                        ->where('status', CalendarEventStatus::Published->value)
                        ->where('start_datetime', '>=', now()),
                ])
                ->where('school_id', $currentSchool->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('scope_type', CalendarScopeType::Global->value)
                ->whereNull('cycle_id')
                ->orderByRaw('academic_configuration_id IS NOT NULL DESC')
                ->orderByDesc('validated_at')
                ->orderByDesc('is_active')
                ->first();
        }

        $user = $request->user();

        return view('core.academic-years.cycle', [
            'academicYear' => $academicYear,
            'cycle' => $cycle,
            'currentSchool' => $currentSchool,
            'levels' => $levels,
            'series' => $series,
            'classrooms' => $classrooms,
            'activeLevelIds' => $activeLevelIds,
            'activeSeriesIds' => $activeSeriesIds,
            'cycleCalendar' => $cycleCalendar,
            'stats' => [
                'available_levels' => $levels->count(),
                'active_levels' => count($activeLevelIds),
                'available_series' => $series->count(),
                'active_series' => count($activeSeriesIds),
                'classrooms' => $classrooms->count(),
            ],
            'canViewCycleReference' => $user?->hasAnyPermission(['academic.cycles.view', 'academic.cycles.manage']) ?? false,
            'canManageCalendars' => $user?->hasAnyPermission(['academic.calendars.view', 'academic.calendars.manage']) ?? false,
            'canCreateCycleCalendar' => $user?->hasAnyPermission(['academic.config.update', 'academic.config.manage']) ?? false,
            'canCreateEvents' => $user?->hasAnyPermission(['academic.events.create', 'academic.events.manage_all']) ?? false,
            'canToggleLevels' => $user?->hasAnyPermission(['academic.levels.disable', 'academic.levels.manage']) ?? false,
            'canToggleSeries' => $user?->hasAnyPermission(['academic.series.disable', 'academic.series.manage']) ?? false,
            'canCreateClassroom' => $user?->hasAnyPermission(['academic.classes.create', 'academic.classes.manage']) ?? false,
            'canUpdateClassroom' => $user?->hasAnyPermission(['academic.classes.update', 'academic.classes.manage']) ?? false,
            'canToggleClassroom' => $user?->hasAnyPermission(['academic.classes.disable', 'academic.classes.manage']) ?? false,
        ]);
    }

    public function toggleLevel(Request $request, AcademicYear $academicYear, AcademicCycle $cycle, AcademicLevel $level): RedirectResponse
    {
        $this->authorize('view', $academicYear);
        abort_unless($request->user()?->hasAnyPermission(['academic.levels.disable', 'academic.levels.manage']) ?? false, 403);
        abort_unless($academicYear->cycles()->whereKey($cycle->id)->exists() && $level->cycle_id === $cycle->id, 404);

        $enabled = $this->academicYearCycleConfigurationService->toggleLevel($academicYear, $cycle, $level, $request->user());

        return back()->with('success', $enabled
            ? __('core.academic_years.messages.level_enabled_for_year')
            : __('core.academic_years.messages.level_disabled_for_year'));
    }

    public function toggleSeries(Request $request, AcademicYear $academicYear, AcademicCycle $cycle, AcademicSeries $series): RedirectResponse
    {
        $this->authorize('view', $academicYear);
        abort_unless($request->user()?->hasAnyPermission(['academic.series.disable', 'academic.series.manage']) ?? false, 403);
        abort_unless($academicYear->cycles()->whereKey($cycle->id)->exists() && $series->cycle_id === $cycle->id, 404);

        $enabled = $this->academicYearCycleConfigurationService->toggleSeries($academicYear, $cycle, $series, $request->user());

        return back()->with('success', $enabled
            ? __('core.academic_years.messages.series_enabled_for_year')
            : __('core.academic_years.messages.series_disabled_for_year'));
    }
}
