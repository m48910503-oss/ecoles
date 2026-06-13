<?php

namespace App\Services\Academic;

use App\Models\AcademicCalendar;
use App\Models\AcademicClassroom;
use App\Models\AcademicLevel;
use App\Models\AcademicSeries;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Core\AuditLogService;
use App\Services\Core\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AcademicClassroomService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): AcademicClassroom
    {
        return DB::transaction(function () use ($validated, $actor): AcademicClassroom {
            $school = $this->resolveSchool($validated, $actor);
            $payload = $this->payload($validated, $school->id, $actor);

            $classroom = AcademicClassroom::query()->create($payload);

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_classroom.created',
                auditable: $classroom,
                newValues: $classroom->fresh()->toArray(),
                description: "Création de la classe {$classroom->name}.",
                user: $actor,
            );

            return $classroom;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(AcademicClassroom $classroom, array $validated, User $actor): AcademicClassroom
    {
        return DB::transaction(function () use ($classroom, $validated, $actor): AcademicClassroom {
            $oldValues = $classroom->toArray();
            $school = $this->resolveSchool($validated, $actor);
            $payload = $this->payload($validated, $school->id, $actor, $classroom);

            $classroom->update($payload);

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_classroom.updated',
                auditable: $classroom,
                oldValues: $oldValues,
                newValues: $classroom->fresh()->toArray(),
                description: "Mise à jour de la classe {$classroom->name}.",
                user: $actor,
            );

            return $classroom->refresh();
        });
    }

    public function toggleActive(AcademicClassroom $classroom, User $actor): AcademicClassroom
    {
        return DB::transaction(function () use ($classroom, $actor): AcademicClassroom {
            $oldValues = $classroom->toArray();

            if ($classroom->is_active && $classroom->calendars()->where('is_active', true)->exists()) {
                throw ValidationException::withMessages([
                    'classroom' => __('academic.classes.validation.deactivation_blocked_active_calendars'),
                ]);
            }

            $classroom->update([
                'is_active' => ! $classroom->is_active,
                'updated_by' => $actor->id,
            ]);

            $this->auditLogService->log(
                module: 'academic',
                action: $classroom->is_active ? 'academic_classroom.enabled' : 'academic_classroom.disabled',
                auditable: $classroom,
                oldValues: $oldValues,
                newValues: $classroom->fresh()->toArray(),
                description: ($classroom->is_active ? 'Activation' : 'Désactivation')." de la classe {$classroom->name}.",
                user: $actor,
            );

            return $classroom->refresh();
        });
    }

    public function delete(AcademicClassroom $classroom, User $actor): void
    {
        if (AcademicCalendar::query()->where('class_id', $classroom->id)->exists()) {
            throw ValidationException::withMessages([
                'classroom' => __('academic.classes.validation.delete_blocked_linked_records'),
            ]);
        }

        DB::transaction(function () use ($classroom, $actor): void {
            $oldValues = $classroom->toArray();
            $label = $classroom->name;

            $classroom->delete();

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_classroom.deleted',
                auditable: $classroom,
                oldValues: $oldValues,
                description: "Suppression de la classe {$label}.",
                user: $actor,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated, int $schoolId, User $actor, ?AcademicClassroom $existingClassroom = null): array
    {
        $level = AcademicLevel::query()->with('series')->findOrFail($validated['level_id']);
        $academicYear = $existingClassroom
            ? AcademicYear::query()->with(['cycles:id', 'levels:id', 'series:id'])->findOrFail($existingClassroom->academic_year_id)
            : $this->resolveAcademicYear($validated);
        $series = isset($validated['series_id']) ? AcademicSeries::query()->find($validated['series_id']) : null;
        $allowedSeriesIds = $level->series
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($seriesId) => (int) $seriesId)
            ->all();

        if (! $academicYear->cycles->contains('id', $level->cycle_id)) {
            throw ValidationException::withMessages([
                'cycle_id' => __('academic.classes.validation.cycle_not_available_for_year'),
            ]);
        }

        if (! $academicYear->levels->contains('id', $level->id)) {
            throw ValidationException::withMessages([
                'level_id' => __('academic.classes.validation.level_not_available_for_year'),
            ]);
        }

        if ($level->uses_series && ! $series) {
            throw ValidationException::withMessages([
                'series_id' => __('academic.classes.validation.series_required_for_level'),
            ]);
        }

        if (! $level->uses_series && $series) {
            throw ValidationException::withMessages([
                'series_id' => __('academic.classes.validation.series_not_allowed_for_level'),
            ]);
        }

        if ($series && ! in_array($series->id, $allowedSeriesIds, true)) {
            throw ValidationException::withMessages([
                'series_id' => __('academic.classes.validation.series_cycle_mismatch'),
            ]);
        }

        if ($series && ! $academicYear->series->contains('id', $series->id)) {
            throw ValidationException::withMessages([
                'series_id' => __('academic.classes.validation.series_not_available_for_year'),
            ]);
        }

        return [
            'school_id' => $schoolId,
            'academic_year_id' => $academicYear->id,
            'level_id' => $level->id,
            'series_id' => $series?->id,
            'name' => $validated['name'],
            'code' => $existingClassroom?->code ?: $this->generatedCodeFor((string) $validated['name'], $schoolId, $academicYear->id),
            'capacity' => $validated['capacity'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => (bool) $validated['is_active'],
            'created_by' => $existingClassroom?->created_by ?: $actor->id,
            'updated_by' => $actor->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveSchool(array $validated, User $actor): School
    {
        $selectedSchoolId = isset($validated['school_id']) ? (int) $validated['school_id'] : null;

        if ($selectedSchoolId && $actor->can('manageMultiSchool', SystemSetting::class)) {
            return School::query()->active()->findOrFail($selectedSchoolId);
        }

        return $this->schoolContext->current();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveAcademicYear(array $validated): AcademicYear
    {
        if (isset($validated['academic_year_id'])) {
            return AcademicYear::query()
                ->with(['cycles:id', 'levels:id', 'series:id'])
                ->findOrFail($validated['academic_year_id']);
        }

        return $this->resolveActiveAcademicYear();
    }

    private function resolveActiveAcademicYear(): AcademicYear
    {
        $academicYear = AcademicYear::query()->active()->first();

        if ($academicYear) {
            return $academicYear->loadMissing(['cycles:id', 'levels:id', 'series:id']);
        }

        throw ValidationException::withMessages([
            'academic_year_id' => __('academic.classes.validation.academic_year_required'),
        ]);
    }

    private function generatedCodeFor(string $name, int $schoolId, int $academicYearId): string
    {
        $baseCode = Str::upper(Str::substr(Str::slug($name, '-'), 0, 80));
        $baseCode = $baseCode !== '' ? $baseCode : 'CLASSE';

        $candidate = $baseCode;
        $suffix = 2;

        while (AcademicClassroom::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('code', $candidate)
            ->exists()) {
            $candidate = Str::limit("{$baseCode}-{$suffix}", 100, '');
            $suffix++;
        }

        return $candidate;
    }
}
