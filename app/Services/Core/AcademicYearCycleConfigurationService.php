<?php

namespace App\Services\Core;

use App\Models\AcademicCalendar;
use App\Models\AcademicCoefficient;
use App\Models\AcademicConductSetting;
use App\Models\AcademicCycle;
use App\Models\AcademicLevel;
use App\Models\AcademicSeries;
use App\Models\AcademicYear;
use App\Models\AcademicClassroom;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicYearCycleConfigurationService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function toggleLevel(AcademicYear $academicYear, AcademicCycle $cycle, AcademicLevel $level, User $actor): bool
    {
        return DB::transaction(function () use ($academicYear, $cycle, $level, $actor): bool {
            $isActiveForYear = $academicYear->levels()->whereKey($level->id)->exists();

            if ($isActiveForYear) {
                $this->guardLevelDeactivation($academicYear, $level);
                $academicYear->levels()->detach($level->id);
            } else {
                $this->ensureCycleIsAvailableForYear($academicYear, $cycle);

                if (! $level->is_active) {
                    throw ValidationException::withMessages([
                        'level' => __('core.academic_years.validation.level_reference_inactive'),
                    ]);
                }

                $academicYear->levels()->syncWithoutDetaching([$level->id]);
            }

            $enabledAfterToggle = ! $isActiveForYear;

            $this->auditLogService->log(
                module: 'core',
                action: $enabledAfterToggle ? 'academic_year.level.enabled' : 'academic_year.level.disabled',
                auditable: $academicYear,
                newValues: [
                    'academic_year_id' => $academicYear->id,
                    'cycle_id' => $cycle->id,
                    'level_id' => $level->id,
                    'enabled' => $enabledAfterToggle,
                ],
                description: ($enabledAfterToggle ? 'Activation' : 'Désactivation')." annuelle du niveau {$level->name} pour {$academicYear->name}.",
                user: $actor,
            );

            return $enabledAfterToggle;
        });
    }

    public function toggleSeries(AcademicYear $academicYear, AcademicCycle $cycle, AcademicSeries $series, User $actor): bool
    {
        return DB::transaction(function () use ($academicYear, $cycle, $series, $actor): bool {
            $isActiveForYear = $academicYear->series()->whereKey($series->id)->exists();

            if ($isActiveForYear) {
                $this->guardSeriesDeactivation($academicYear, $series);
                $academicYear->series()->detach($series->id);
            } else {
                $this->ensureCycleIsAvailableForYear($academicYear, $cycle);

                if (! $series->is_active) {
                    throw ValidationException::withMessages([
                        'series' => __('core.academic_years.validation.series_reference_inactive'),
                    ]);
                }

                $academicYear->series()->syncWithoutDetaching([$series->id]);
            }

            $enabledAfterToggle = ! $isActiveForYear;

            $this->auditLogService->log(
                module: 'core',
                action: $enabledAfterToggle ? 'academic_year.series.enabled' : 'academic_year.series.disabled',
                auditable: $academicYear,
                newValues: [
                    'academic_year_id' => $academicYear->id,
                    'cycle_id' => $cycle->id,
                    'series_id' => $series->id,
                    'enabled' => $enabledAfterToggle,
                ],
                description: ($enabledAfterToggle ? 'Activation' : 'Désactivation')." annuelle de la série {$series->name} pour {$academicYear->name}.",
                user: $actor,
            );

            return $enabledAfterToggle;
        });
    }

    private function ensureCycleIsAvailableForYear(AcademicYear $academicYear, AcademicCycle $cycle): void
    {
        $exists = $academicYear->cycles()->whereKey($cycle->id)->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'cycle' => __('core.academic_years.validation.cycle_not_linked'),
            ]);
        }
    }

    private function guardLevelDeactivation(AcademicYear $academicYear, AcademicLevel $level): void
    {
        $hasClasses = AcademicClassroom::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('level_id', $level->id)
            ->exists();

        $hasCalendars = AcademicCalendar::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('level_id', $level->id)
            ->exists();

        $hasCoefficients = AcademicCoefficient::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('level_id', $level->id)
            ->exists();

        $hasConduct = AcademicConductSetting::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('level_id', $level->id)
            ->exists();

        if ($hasClasses || $hasCalendars || $hasCoefficients || $hasConduct) {
            throw ValidationException::withMessages([
                'level' => __('core.academic_years.validation.level_deactivation_blocked'),
            ]);
        }
    }

    private function guardSeriesDeactivation(AcademicYear $academicYear, AcademicSeries $series): void
    {
        $hasClasses = AcademicClassroom::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('series_id', $series->id)
            ->exists();

        $hasCoefficients = AcademicCoefficient::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('series_id', $series->id)
            ->exists();

        if ($hasClasses || $hasCoefficients) {
            throw ValidationException::withMessages([
                'series' => __('core.academic_years.validation.series_deactivation_blocked'),
            ]);
        }
    }
}
