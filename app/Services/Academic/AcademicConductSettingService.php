<?php

namespace App\Services\Academic;

use App\Models\AcademicConductSetting;
use App\Models\AcademicSubject;
use App\Models\School;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Core\AuditLogService;
use App\Services\Core\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicConductSettingService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): AcademicConductSetting
    {
        return DB::transaction(function () use ($validated, $actor): AcademicConductSetting {
            $school = $this->resolveSchool($validated, $actor);
            $payload = $this->payload($validated, $school->id, $actor);

            $setting = AcademicConductSetting::query()->create($payload);

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_conduct_setting.created',
                auditable: $setting,
                newValues: $setting->fresh()->toArray(),
                description: 'Création d’un réglage de conduite.',
                user: $actor,
            );

            return $setting;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(AcademicConductSetting $setting, array $validated, User $actor): AcademicConductSetting
    {
        return DB::transaction(function () use ($setting, $validated, $actor): AcademicConductSetting {
            $oldValues = $setting->toArray();
            $school = $this->resolveSchool($validated, $actor);
            $payload = $this->payload($validated, $school->id, $actor, $setting);

            $setting->update($payload);

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_conduct_setting.updated',
                auditable: $setting,
                oldValues: $oldValues,
                newValues: $setting->fresh()->toArray(),
                description: 'Mise à jour d’un réglage de conduite.',
                user: $actor,
            );

            return $setting->refresh();
        });
    }

    public function toggleActive(AcademicConductSetting $setting, User $actor): AcademicConductSetting
    {
        return DB::transaction(function () use ($setting, $actor): AcademicConductSetting {
            $oldValues = $setting->toArray();

            $setting->update([
                'is_active' => ! $setting->is_active,
                'updated_by' => $actor->id,
            ]);

            $this->auditLogService->log(
                module: 'academic',
                action: $setting->is_active ? 'academic_conduct_setting.enabled' : 'academic_conduct_setting.disabled',
                auditable: $setting,
                oldValues: $oldValues,
                newValues: $setting->fresh()->toArray(),
                description: ($setting->is_active ? 'Activation' : 'Désactivation').' d’un réglage de conduite.',
                user: $actor,
            );

            return $setting->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated, int $schoolId, User $actor, ?AcademicConductSetting $existingSetting = null): array
    {
        $subject = AcademicSubject::query()->findOrFail($validated['academic_subject_id']);

        if (! $subject->is_conduct) {
            throw ValidationException::withMessages([
                'academic_subject_id' => __('academic.conduct.validation.subject_must_be_conduct'),
            ]);
        }

        if ($subject->school_id !== $schoolId) {
            throw ValidationException::withMessages([
                'academic_subject_id' => __('academic.conduct.validation.subject_school_mismatch'),
            ]);
        }

        $exists = AcademicConductSetting::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $validated['academic_year_id'])
            ->where('level_id', $validated['level_id'])
            ->when($existingSetting, fn ($query) => $query->whereKeyNot($existingSetting->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'level_id' => __('academic.conduct.validation.duplicate_level_setting'),
            ]);
        }

        return [
            'school_id' => $schoolId,
            'academic_year_id' => $validated['academic_year_id'],
            'level_id' => $validated['level_id'],
            'academic_subject_id' => $subject->id,
            'base_score' => $validated['base_score'],
            'deductions_from_sanctions_only' => (bool) $validated['deductions_from_sanctions_only'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => (bool) $validated['is_active'],
            'created_by' => $existingSetting?->created_by ?: $actor->id,
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
