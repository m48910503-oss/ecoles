<?php

namespace App\Services\Academic;

use App\Models\AcademicCoefficient;
use App\Models\AcademicLevel;
use App\Models\AcademicSeries;
use App\Models\AcademicSubject;
use App\Models\School;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Core\AuditLogService;
use App\Services\Core\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicCoefficientService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): AcademicCoefficient
    {
        return DB::transaction(function () use ($validated, $actor): AcademicCoefficient {
            $school = $this->resolveSchool($validated, $actor);
            $payload = $this->payload($validated, $school->id, $actor);

            $coefficient = AcademicCoefficient::query()->create($payload);

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_coefficient.created',
                auditable: $coefficient,
                newValues: $coefficient->fresh()->toArray(),
                description: "Création du coefficient {$coefficient->coefficient}.",
                user: $actor,
            );

            return $coefficient;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(AcademicCoefficient $coefficient, array $validated, User $actor): AcademicCoefficient
    {
        return DB::transaction(function () use ($coefficient, $validated, $actor): AcademicCoefficient {
            $oldValues = $coefficient->toArray();
            $school = $this->resolveSchool($validated, $actor);
            $payload = $this->payload($validated, $school->id, $actor, $coefficient);

            $coefficient->update($payload);

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_coefficient.updated',
                auditable: $coefficient,
                oldValues: $oldValues,
                newValues: $coefficient->fresh()->toArray(),
                description: 'Mise à jour d’un coefficient académique.',
                user: $actor,
            );

            return $coefficient->refresh();
        });
    }

    public function toggleActive(AcademicCoefficient $coefficient, User $actor): AcademicCoefficient
    {
        return DB::transaction(function () use ($coefficient, $actor): AcademicCoefficient {
            $oldValues = $coefficient->toArray();

            $coefficient->update([
                'is_active' => ! $coefficient->is_active,
                'updated_by' => $actor->id,
            ]);

            $this->auditLogService->log(
                module: 'academic',
                action: $coefficient->is_active ? 'academic_coefficient.enabled' : 'academic_coefficient.disabled',
                auditable: $coefficient,
                oldValues: $oldValues,
                newValues: $coefficient->fresh()->toArray(),
                description: ($coefficient->is_active ? 'Activation' : 'Désactivation').' d’un coefficient académique.',
                user: $actor,
            );

            return $coefficient->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated, int $schoolId, User $actor, ?AcademicCoefficient $existingCoefficient = null): array
    {
        $level = AcademicLevel::query()->findOrFail($validated['level_id']);
        $series = isset($validated['series_id']) ? AcademicSeries::query()->find($validated['series_id']) : null;
        $subject = AcademicSubject::query()->findOrFail($validated['academic_subject_id']);

        if (! $level->uses_series && $series) {
            throw ValidationException::withMessages([
                'series_id' => __('academic.coefficients.validation.series_not_allowed_for_level'),
            ]);
        }

        if ($series && $series->cycle_id !== $level->cycle_id) {
            throw ValidationException::withMessages([
                'series_id' => __('academic.coefficients.validation.series_cycle_mismatch'),
            ]);
        }

        if ($subject->school_id !== $schoolId) {
            throw ValidationException::withMessages([
                'academic_subject_id' => __('academic.coefficients.validation.subject_school_mismatch'),
            ]);
        }

        $duplicateExists = AcademicCoefficient::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('level_id', $level->id)
            ->where('academic_subject_id', $subject->id)
            ->when($series, fn ($query) => $query->where('series_id', $series->id), fn ($query) => $query->whereNull('series_id'))
            ->when($existingCoefficient, fn ($query) => $query->whereKeyNot($existingCoefficient->id))
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'academic_subject_id' => __('academic.coefficients.validation.duplicate_assignment'),
            ]);
        }

        return [
            'school_id' => $schoolId,
            'academic_year_id' => $validated['academic_year_id'],
            'level_id' => $level->id,
            'series_id' => $series?->id,
            'academic_subject_id' => $subject->id,
            'coefficient' => $validated['coefficient'],
            'is_optional' => (bool) $validated['is_optional'],
            'is_active' => (bool) $validated['is_active'],
            'created_by' => $existingCoefficient?->created_by ?: $actor->id,
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
}
