<?php

namespace App\Services\Academic;

use App\Models\AcademicSubject;
use App\Models\School;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Core\AuditLogService;
use App\Services\Core\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AcademicSubjectService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): AcademicSubject
    {
        return DB::transaction(function () use ($validated, $actor): AcademicSubject {
            $school = $this->resolveSchool($validated, $actor);
            $payload = $this->payload($validated, $school->id, $actor);

            $subject = AcademicSubject::query()->create($payload);

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_subject.created',
                auditable: $subject,
                newValues: $subject->fresh()->toArray(),
                description: "Création de la matière {$subject->name}.",
                user: $actor,
            );

            return $subject;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(AcademicSubject $subject, array $validated, User $actor): AcademicSubject
    {
        return DB::transaction(function () use ($subject, $validated, $actor): AcademicSubject {
            $oldValues = $subject->toArray();
            $school = $this->resolveSchool($validated, $actor);
            $payload = $this->payload($validated, $school->id, $actor, $subject);

            $subject->update($payload);

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_subject.updated',
                auditable: $subject,
                oldValues: $oldValues,
                newValues: $subject->fresh()->toArray(),
                description: "Mise à jour de la matière {$subject->name}.",
                user: $actor,
            );

            return $subject->refresh();
        });
    }

    public function toggleActive(AcademicSubject $subject, User $actor): AcademicSubject
    {
        return DB::transaction(function () use ($subject, $actor): AcademicSubject {
            $oldValues = $subject->toArray();

            if ($subject->is_active && $subject->coefficients()->where('is_active', true)->exists()) {
                throw ValidationException::withMessages([
                    'subject' => __('academic.subjects.validation.deactivation_blocked_active_coefficients'),
                ]);
            }

            if ($subject->is_active && $subject->conductSettings()->where('is_active', true)->exists()) {
                throw ValidationException::withMessages([
                    'subject' => __('academic.subjects.validation.deactivation_blocked_active_conduct'),
                ]);
            }

            $subject->update([
                'is_active' => ! $subject->is_active,
                'updated_by' => $actor->id,
            ]);

            $this->auditLogService->log(
                module: 'academic',
                action: $subject->is_active ? 'academic_subject.enabled' : 'academic_subject.disabled',
                auditable: $subject,
                oldValues: $oldValues,
                newValues: $subject->fresh()->toArray(),
                description: ($subject->is_active ? 'Activation' : 'Désactivation')." de la matière {$subject->name}.",
                user: $actor,
            );

            return $subject->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated, int $schoolId, User $actor, ?AcademicSubject $existingSubject = null): array
    {
        $generated = $this->generatedAttributesFor((string) ($validated['name'] ?? $existingSubject?->name ?? 'Matiere'), $schoolId);
        $preserveExistingStructure = $existingSubject instanceof AcademicSubject
            && (int) $existingSubject->school_id === $schoolId;
        $isOptionalByDefault = array_key_exists('is_optional_by_default', $validated)
            ? (bool) $validated['is_optional_by_default']
            : (bool) ($existingSubject?->is_optional_by_default ?? false);
        $isConduct = array_key_exists('is_conduct', $validated)
            ? (bool) $validated['is_conduct']
            : (bool) ($existingSubject?->is_conduct ?? false);
        $isActive = array_key_exists('is_active', $validated)
            ? (bool) $validated['is_active']
            : (bool) ($existingSubject?->is_active ?? true);

        if ($isConduct && AcademicSubject::query()
            ->where('school_id', $schoolId)
            ->where('is_conduct', true)
            ->when($existingSubject, fn ($query) => $query->whereKeyNot($existingSubject->id))
            ->exists()) {
            throw ValidationException::withMessages([
                'is_conduct' => __('academic.subjects.validation.single_conduct_subject'),
            ]);
        }

        return [
            'school_id' => $schoolId,
            'name' => $validated['name'],
            'code' => $preserveExistingStructure ? $existingSubject->code : $generated['code'],
            'short_name' => $validated['short_name'] ?? null,
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_optional_by_default' => $isOptionalByDefault,
            'is_conduct' => $isConduct,
            'is_active' => $isActive,
            'sort_order' => $preserveExistingStructure ? $existingSubject->sort_order : $generated['sort_order'],
            'created_by' => $existingSubject?->created_by ?: $actor->id,
            'updated_by' => $actor->id,
        ];
    }

    /**
     * @return array{code: string, sort_order: int}
     */
    private function generatedAttributesFor(string $name, int $schoolId): array
    {
        return [
            'code' => $this->generatedCodeFor($name, $schoolId),
            'sort_order' => $this->generatedSortOrder($schoolId),
        ];
    }

    private function generatedCodeFor(string $name, int $schoolId): string
    {
        $baseCode = Str::upper(Str::substr(Str::slug($name, '-'), 0, 80));
        $baseCode = $baseCode !== '' ? $baseCode : 'MATIERE';

        $candidate = $baseCode;
        $suffix = 2;

        while (AcademicSubject::query()
            ->where('school_id', $schoolId)
            ->where('code', $candidate)
            ->exists()) {
            $candidate = Str::limit("{$baseCode}-{$suffix}", 100, '');
            $suffix++;
        }

        return $candidate;
    }

    private function generatedSortOrder(int $schoolId): int
    {
        return ((int) AcademicSubject::query()
            ->where('school_id', $schoolId)
            ->max('sort_order')) + 1;
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
