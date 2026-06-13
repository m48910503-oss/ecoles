<?php

namespace App\Services\People;

use App\Models\AcademicClassroom;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;
use App\Services\Core\AuditLogService;
use App\Services\Core\SchoolContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): Student
    {
        return DB::transaction(function () use ($validated, $actor): Student {
            $school = $this->schoolContext->current();
            $student = Student::query()->create($this->studentPayload($validated, $actor, $school->id));

            $guardian = $this->resolveGuardianForCreation($validated, $school->id, $actor);
            $this->attachGuardian($student, $guardian, $this->parentPivotPayload($validated, $student, $actor, true), $actor);

            $student->refresh()->load(['parents', 'currentClassroom.level.cycle', 'entryAcademicYear', 'school']);

            $this->auditLogService->log(
                module: 'people',
                action: 'student.created',
                auditable: $student,
                newValues: $student->toArray(),
                description: "Création du dossier élève {$student->fullName()}.",
                user: $actor,
            );

            return $student;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(Student $student, array $validated, User $actor): Student
    {
        return DB::transaction(function () use ($student, $validated, $actor): Student {
            $oldValues = $student->load(['parents', 'currentClassroom.level.cycle', 'entryAcademicYear'])->toArray();
            $student->update($this->studentPayload($validated, $actor, (int) $student->school_id, $student));

            $student->refresh()->load(['parents', 'currentClassroom.level.cycle', 'entryAcademicYear', 'school']);

            $this->auditLogService->log(
                module: 'people',
                action: 'student.updated',
                auditable: $student,
                oldValues: $oldValues,
                newValues: $student->toArray(),
                description: "Mise à jour du dossier élève {$student->fullName()}.",
                user: $actor,
            );

            return $student;
        });
    }

    public function toggleStatus(Student $student, User $actor): Student
    {
        return DB::transaction(function () use ($student, $actor): Student {
            $oldValues = $student->load(['parents', 'currentClassroom.level.cycle', 'entryAcademicYear'])->toArray();

            $newStatus = $student->status === 'actif' ? 'suspendu' : 'actif';
            $statusReason = $newStatus === 'actif'
                ? null
                : __('people.students.messages.quick_suspend_reason', ['user' => $actor->name]);

            $student->update([
                'status' => $newStatus,
                'status_reason' => $statusReason,
                'updated_by' => $actor->id,
            ]);

            $student->refresh()->load(['parents', 'currentClassroom.level.cycle', 'entryAcademicYear', 'school']);

            $this->auditLogService->log(
                module: 'people',
                action: $newStatus === 'actif' ? 'student.reactivated' : 'student.suspended',
                auditable: $student,
                oldValues: $oldValues,
                newValues: $student->toArray(),
                description: ($newStatus === 'actif' ? 'Réactivation' : 'Suspension')." du dossier élève {$student->fullName()}.",
                user: $actor,
            );

            return $student;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function linkParent(Student $student, array $validated, User $actor, string $errorField = 'parent_id'): Student
    {
        return DB::transaction(function () use ($student, $validated, $actor, $errorField): Student {
            $student->load('parents');
            $guardian = Guardian::query()
                ->forSchool((int) $student->school_id)
                ->findOrFail((int) $validated['parent_id']);

            $pivotPayload = $this->parentPivotPayload($validated, $student, $actor);
            $currentPrimaryParents = $student->parents->filter(
                fn (Guardian $candidate) => (bool) $candidate->pivot->is_primary_contact
            );
            $existingParent = $student->parents()->whereKey($guardian->id)->first();
            $existingPivot = $existingParent?->pivot ? $this->normalizedPivot($existingParent->pivot->getAttributes()) : null;
            $normalizedPayload = $this->normalizedPivot($pivotPayload);
            $remainingParents = $student->parents
                ->reject(fn (Guardian $candidate) => (int) $candidate->id === (int) $guardian->id)
                ->values();

            if ($existingPivot !== null && $existingPivot === $normalizedPayload) {
                return $student->refresh()->load(['parents', 'currentClassroom.level.cycle', 'entryAcademicYear', 'school']);
            }

            if ($existingPivot !== null
                && $existingPivot['is_primary_contact']
                && ! $pivotPayload['is_primary_contact']
                && ! $remainingParents->contains(fn (Guardian $candidate) => (bool) $candidate->pivot->is_primary_contact)
            ) {
                throw ValidationException::withMessages([
                    $errorField => __('people.students.validation.primary_parent_required'),
                ]);
            }

            if (! $pivotPayload['is_primary_contact']
                && $currentPrimaryParents->count() === 1
                && (int) $currentPrimaryParents->first()->id === (int) $guardian->id
            ) {
                throw ValidationException::withMessages([
                    $errorField => __('people.students.validation.primary_parent_required'),
                ]);
            }

            if ($pivotPayload['is_primary_contact']) {
                $this->demoteOtherPrimaryContacts($student, $guardian->id, $actor);
            } elseif (! $student->parents()->wherePivot('is_primary_contact', true)->exists()) {
                $pivotPayload['is_primary_contact'] = true;
            }

            if ($pivotPayload['is_financial_responsible']) {
                $this->demoteOtherFinancialResponsibleParents($student, $guardian->id, $actor);
            }

            if ($existingParent) {
                $student->parents()->updateExistingPivot($guardian->id, array_merge($pivotPayload, [
                    'updated_by' => $actor->id,
                ]));
            } else {
                $student->parents()->attach($guardian->id, array_merge($pivotPayload, [
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]));
            }

            $student->refresh()->load(['parents', 'currentClassroom.level.cycle', 'entryAcademicYear']);

            $this->auditLogService->log(
                module: 'people',
                action: $existingParent ? 'student.parent.updated' : 'student.parent.linked',
                auditable: $student,
                oldValues: $existingPivot,
                newValues: [
                    'parent_id' => $guardian->id,
                    'relationship_type' => $pivotPayload['relationship_type'],
                    'is_primary_contact' => $pivotPayload['is_primary_contact'],
                    'is_financial_responsible' => $pivotPayload['is_financial_responsible'],
                    'can_receive_notifications' => $pivotPayload['can_receive_notifications'],
                    'can_pickup_child' => $pivotPayload['can_pickup_child'],
                ],
                description: ($existingParent ? 'Mise à jour' : 'Association')." du parent {$guardian->fullName()} au dossier de {$student->fullName()}.",
                user: $actor,
            );

            return $student;
        });
    }

    public function unlinkParent(Student $student, Guardian $parent, User $actor, string $errorField = 'parent_id'): Student
    {
        return DB::transaction(function () use ($student, $parent, $actor, $errorField): Student {
            $student->load('parents');
            $linkedParent = $student->parents()->whereKey($parent->id)->first();

            if (! $linkedParent) {
                throw ValidationException::withMessages([
                    $errorField => __('people.students.validation.parent_not_linked'),
                ]);
            }

            $pivot = $this->normalizedPivot($linkedParent->pivot->getAttributes());
            $remainingParents = $student->parents->reject(fn (Guardian $candidate) => (int) $candidate->id === (int) $parent->id)->values();

            if ($pivot['is_primary_contact'] && $remainingParents->isEmpty()) {
                throw ValidationException::withMessages([
                    $errorField => __('people.students.validation.primary_parent_required'),
                ]);
            }

            $student->parents()->detach($parent->id);

            if ($pivot['is_primary_contact']
                && $remainingParents->isNotEmpty()
                && ! $remainingParents->contains(fn (Guardian $candidate) => (bool) $candidate->pivot->is_primary_contact)
            ) {
                $fallbackParent = $remainingParents->first();
                $student->parents()->updateExistingPivot($fallbackParent->id, [
                    'is_primary_contact' => true,
                    'updated_by' => $actor->id,
                ]);
            }

            $student->refresh()->load(['parents', 'currentClassroom.level.cycle', 'entryAcademicYear']);

            $this->auditLogService->log(
                module: 'people',
                action: 'student.parent.unlinked',
                auditable: $student,
                oldValues: [
                    'parent_id' => $parent->id,
                    'relationship_type' => $pivot['relationship_type'],
                    'is_primary_contact' => $pivot['is_primary_contact'],
                    'is_financial_responsible' => $pivot['is_financial_responsible'],
                    'can_receive_notifications' => $pivot['can_receive_notifications'],
                    'can_pickup_child' => $pivot['can_pickup_child'],
                ],
                newValues: [
                    'remaining_parents' => $student->parents->pluck('id')->all(),
                ],
                description: "Retrait du lien entre {$parent->fullName()} et le dossier de {$student->fullName()}.",
                user: $actor,
            );

            return $student;
        });
    }

    /**
     * @return Collection<int, AuditLog>
     */
    public function historyFor(Student $student, int $limit = 10): Collection
    {
        return AuditLog::query()
            ->with('user:id,name')
            ->where('module', 'people')
            ->where('auditable_type', $student->getMorphClass())
            ->where('auditable_id', $student->id)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function studentPayload(array $validated, User $actor, int $schoolId, ?Student $student = null): array
    {
        $photoPath = $this->storePhoto(Arr::get($validated, 'photo'), $student?->photo_path);
        $entryYearId = (int) Arr::get($validated, 'entry_academic_year_id');
        $classroomId = $this->resolveClassroomId(Arr::get($validated, 'academic_classroom_id'), $schoolId);
        $status = (string) Arr::get($validated, 'status', 'preinscrit');

        if ($entryYearId <= 0) {
            throw ValidationException::withMessages([
                'entry_academic_year_id' => __('people.students.validation.academic_year_invalid'),
            ]);
        }

        return [
            'school_id' => $schoolId,
            'user_id' => $student?->user_id,
            'matricule' => $student?->matricule ?? $this->nextMatricule($schoolId),
            'last_name' => trim((string) $validated['last_name']),
            'first_names' => trim((string) $validated['first_names']),
            'gender' => (string) $validated['gender'],
            'date_of_birth' => Arr::get($validated, 'date_of_birth') ?: null,
            'place_of_birth' => Arr::get($validated, 'place_of_birth') ?: null,
            'nationality' => Arr::get($validated, 'nationality') ?: null,
            'photo_path' => $photoPath,
            'academic_classroom_id' => $classroomId,
            'entry_academic_year_id' => $entryYearId,
            'status' => $status,
            'status_reason' => $status === 'actif' ? null : (Arr::get($validated, 'status_reason') ?: null),
            'notes' => Arr::get($validated, 'notes') ?: null,
            'created_by' => $student?->created_by ?? $actor->id,
            'updated_by' => $actor->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveGuardianForCreation(array $validated, int $schoolId, User $actor): Guardian
    {
        if (! empty($validated['existing_guardian_id'])) {
            return Guardian::query()
                ->forSchool($schoolId)
                ->findOrFail((int) $validated['existing_guardian_id']);
        }

        $guardianPayload = $this->guardianPayload($validated, $actor, $schoolId);
        $existingGuardian = $this->findGuardianByContact($schoolId, $guardianPayload['email'], $guardianPayload['phone']);

        if ($existingGuardian) {
            $existingGuardian->fill(array_filter($guardianPayload, fn ($value) => $value !== null && $value !== ''));
            $existingGuardian->updated_by = $actor->id;
            $existingGuardian->save();

            return $existingGuardian;
        }

        return Guardian::query()->create($guardianPayload);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function guardianPayload(array $validated, User $actor, int $schoolId): array
    {
        return [
            'school_id' => $schoolId,
            'user_id' => null,
            'last_name' => trim((string) Arr::get($validated, 'guardian_last_name')),
            'first_names' => trim((string) Arr::get($validated, 'guardian_first_names')),
            'gender' => (string) Arr::get($validated, 'guardian_gender'),
            'phone' => Arr::get($validated, 'guardian_phone') ?: null,
            'email' => Arr::get($validated, 'guardian_email') ?: null,
            'address' => Arr::get($validated, 'guardian_address') ?: null,
            'profession' => Arr::get($validated, 'guardian_profession') ?: null,
            'status' => 'actif',
            'notes' => Arr::get($validated, 'guardian_notes') ?: null,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function parentPivotPayload(array $validated, Student $student, User $actor, bool $forcePrimary = false): array
    {
        $relationshipType = (string) Arr::get(
            $validated,
            'relationship_type',
            Arr::get($validated, 'guardian_relationship_type', 'autre')
        );
        $primaryRequested = $this->booleanValue(Arr::get(
            $validated,
            'is_primary_contact',
            Arr::get($validated, 'guardian_is_primary_contact', true)
        ));
        $financialResponsible = $this->booleanValue(Arr::get(
            $validated,
            'is_financial_responsible',
            Arr::get($validated, 'guardian_is_financial_responsible', false)
        ));
        $receiveNotifications = $this->booleanValue(Arr::get(
            $validated,
            'can_receive_notifications',
            Arr::get($validated, 'guardian_can_receive_notifications', true)
        ));
        $canPickupChild = $this->booleanValue(Arr::get(
            $validated,
            'can_pickup_child',
            Arr::get($validated, 'guardian_can_pickup_child', true)
        ));
        $notes = Arr::get($validated, 'notes', Arr::get($validated, 'guardian_notes'));
        $isPrimary = $forcePrimary || $primaryRequested || ! $student->parents()->wherePivot('is_primary_contact', true)->exists();

        return [
            'relationship_type' => $relationshipType,
            'is_primary_contact' => $isPrimary,
            'is_financial_responsible' => $financialResponsible,
            'can_receive_notifications' => $receiveNotifications,
            'can_pickup_child' => $canPickupChild,
            'notes' => $notes ?: null,
        ];
    }

    private function attachGuardian(Student $student, Guardian $guardian, array $pivotPayload, User $actor): void
    {
        if ($pivotPayload['is_primary_contact']) {
            $this->demoteOtherPrimaryContacts($student, $guardian->id, $actor);
        }

        if ($pivotPayload['is_financial_responsible']) {
            $this->demoteOtherFinancialResponsibleParents($student, $guardian->id, $actor);
        }

        $student->parents()->attach($guardian->id, array_merge($pivotPayload, [
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]));
    }

    private function demoteOtherPrimaryContacts(Student $student, int $guardianId, User $actor): void
    {
        $student->parents()
            ->get()
            ->filter(fn (Guardian $candidate) => (int) $candidate->id !== $guardianId && (bool) $candidate->pivot->is_primary_contact)
            ->each(function (Guardian $candidate) use ($student, $actor): void {
                $student->parents()->updateExistingPivot($candidate->id, [
                    'is_primary_contact' => false,
                    'updated_by' => $actor->id,
                ]);
            });
    }

    private function demoteOtherFinancialResponsibleParents(Student $student, int $guardianId, User $actor): void
    {
        $student->parents()
            ->get()
            ->filter(fn (Guardian $candidate) => (int) $candidate->id !== $guardianId && (bool) $candidate->pivot->is_financial_responsible)
            ->each(function (Guardian $candidate) use ($student, $actor): void {
                $student->parents()->updateExistingPivot($candidate->id, [
                    'is_financial_responsible' => false,
                    'updated_by' => $actor->id,
                ]);
            });
    }

    private function findGuardianByContact(int $schoolId, mixed $email, mixed $phone): ?Guardian
    {
        if (! $email && ! $phone) {
            return null;
        }

        return Guardian::query()
            ->forSchool($schoolId)
            ->where(function ($query) use ($email, $phone): void {
                if ($email) {
                    $query->orWhereRaw('LOWER(email) = ?', [mb_strtolower((string) $email)]);
                }

                if ($phone) {
                    $query->orWhere('phone', trim((string) $phone));
                }
            })
            ->first();
    }

    private function storePhoto(mixed $photo, ?string $currentPath = null): ?string
    {
        if (! $photo instanceof UploadedFile) {
            return $currentPath;
        }

        $path = $photo->store('people/students', 'public');

        if ($currentPath) {
            Storage::disk('public')->delete($currentPath);
        }

        return $path;
    }

    private function resolveClassroomId(mixed $classroomId, int $schoolId): ?int
    {
        if (! $classroomId) {
            return null;
        }

        $classroom = AcademicClassroom::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->find((int) $classroomId);

        if (! $classroom) {
            throw ValidationException::withMessages([
                'academic_classroom_id' => __('people.students.validation.classroom_invalid'),
            ]);
        }

        return $classroom->id;
    }

    public function nextMatricule(int $schoolId): string
    {
        $year = (string) now()->year;
        $prefix = "ELV-{$year}-";

        $lastMatricule = Student::query()
            ->forSchool($schoolId)
            ->where('matricule', 'like', $prefix.'%')
            ->orderByDesc('matricule')
            ->value('matricule');

        $nextNumber = 1;

        if (is_string($lastMatricule) && preg_match('/^ELV-'.$year.'-(\d{4})$/', $lastMatricule, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $pivot
     * @return array<string, mixed>
     */
    private function normalizedPivot(array $pivot): array
    {
        return [
            'relationship_type' => (string) ($pivot['relationship_type'] ?? 'autre'),
            'is_primary_contact' => $this->booleanValue($pivot['is_primary_contact'] ?? false),
            'is_financial_responsible' => $this->booleanValue($pivot['is_financial_responsible'] ?? false),
            'can_receive_notifications' => $this->booleanValue($pivot['can_receive_notifications'] ?? true),
            'can_pickup_child' => $this->booleanValue($pivot['can_pickup_child'] ?? true),
            'notes' => $pivot['notes'] ?? null,
        ];
    }

    private function booleanValue(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on'], true);
    }
}
