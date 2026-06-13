<?php

namespace App\Services\People;

use App\Models\AcademicClassroom;
use App\Models\AcademicCycle;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Teacher;
use App\Models\TeacherInvitation;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use App\Services\Core\AuditLogService;
use App\Services\Core\SchoolContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function indexQuery(int $schoolId, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $contract = trim((string) ($filters['contract'] ?? ''));
        $account = trim((string) ($filters['account'] ?? ''));
        $track = trim((string) ($filters['track'] ?? ''));
        $invitation = trim((string) ($filters['invitation'] ?? ''));
        $cycleId = (int) ($filters['cycle_id'] ?? 0) ?: null;

        return Teacher::query()
            ->forSchool($schoolId)
            ->with(['user', 'academicCycle', 'mainClassroom.level.cycle', 'mainSubject', 'latestInvitation'])
            ->withCount('assignments')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('matricule', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('first_names', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('specialty', 'like', "%{$search}%")
                        ->orWhere('diploma', 'like', "%{$search}%")
                        ->orWhereHas('academicCycle', function ($cycleQuery) use ($search): void {
                            $cycleQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('mainClassroom', function ($classroomQuery) use ($search): void {
                            $classroomQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('mainSubject', function ($subjectQuery) use ($search): void {
                            $subjectQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($contract !== '', fn ($query) => $query->where('contract_type', $contract))
            ->when($account === 'linked', fn ($query) => $query->whereNotNull('user_id'))
            ->when($account === 'unlinked', fn ($query) => $query->whereNull('user_id'))
            ->when($track === 'primary', fn ($query) => $query->primaryTrack())
            ->when($track === 'secondary', fn ($query) => $query->secondaryTrack())
            ->when($track === 'unclassified', fn ($query) => $query->whereNull('academic_cycle_id'))
            ->when($invitation !== '', function ($query) use ($invitation): void {
                match ($invitation) {
                    'pending' => $query->whereHas('latestInvitation', function ($invitationQuery): void {
                        $invitationQuery
                            ->where('status', TeacherInvitation::STATUS_PENDING)
                            ->where(function ($nestedQuery): void {
                                $nestedQuery
                                    ->whereNull('expires_at')
                                    ->orWhere('expires_at', '>', now());
                            });
                    }),
                    'completed' => $query->whereHas('latestInvitation', fn ($invitationQuery) => $invitationQuery->where('status', TeacherInvitation::STATUS_COMPLETED)),
                    'validated' => $query->whereHas('latestInvitation', fn ($invitationQuery) => $invitationQuery->where('status', TeacherInvitation::STATUS_VALIDATED)),
                    'revoked' => $query->whereHas('latestInvitation', fn ($invitationQuery) => $invitationQuery->where('status', TeacherInvitation::STATUS_REVOKED)),
                    'expired' => $query->whereHas('latestInvitation', function ($invitationQuery): void {
                        $invitationQuery
                            ->where('status', TeacherInvitation::STATUS_PENDING)
                            ->whereNotNull('expires_at')
                            ->where('expires_at', '<=', now());
                    }),
                    'none' => $query->whereDoesntHave('latestInvitation'),
                    default => null,
                };
            })
            ->when($cycleId !== null, fn ($query) => $query->where('academic_cycle_id', $cycleId));
    }

    public function stats(int $schoolId): array
    {
        $query = Teacher::query()->forSchool($schoolId);

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->active()->count(),
            'primary' => (clone $query)->primaryTrack()->count(),
            'secondary' => (clone $query)->secondaryTrack()->count(),
            'unclassified' => (clone $query)->whereNull('academic_cycle_id')->count(),
            'linked_account' => (clone $query)->whereNotNull('user_id')->count(),
            'without_assignments' => (clone $query)->withoutPedagogicPlacement()->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): Teacher
    {
        return DB::transaction(function () use ($validated, $actor): Teacher {
            $school = $this->schoolContext->current();

            $this->ensureNoDuplicateContact(
                schoolId: (int) $school->id,
                email: Arr::get($validated, 'email'),
                phone: Arr::get($validated, 'phone'),
            );

            $teacher = Teacher::query()->create($this->teacherPayload($validated, $actor, (int) $school->id));

            $teacher->refresh()->load([
                'user',
                'academicCycle',
                'mainClassroom.level.cycle',
                'mainSubject',
                'assignments.subject',
                'assignments.classroom.level.cycle',
                'assignments.academicYear',
            ]);

            $this->auditLogService->log(
                module: 'people',
                action: 'teacher.created',
                auditable: $teacher,
                newValues: $teacher->toArray(),
                description: "Création du dossier enseignant {$teacher->fullName()}.",
                user: $actor,
            );

            return $teacher;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(Teacher $teacher, array $validated, User $actor): Teacher
    {
        return DB::transaction(function () use ($teacher, $validated, $actor): Teacher {
            $oldValues = $teacher->load([
                'user',
                'academicCycle',
                'mainClassroom.level.cycle',
                'mainSubject',
                'assignments.subject',
                'assignments.classroom.level.cycle',
                'assignments.academicYear',
            ])->toArray();

            $this->ensureNoDuplicateContact(
                schoolId: (int) $teacher->school_id,
                email: Arr::get($validated, 'email'),
                phone: Arr::get($validated, 'phone'),
                exceptId: (int) $teacher->id,
            );

            $teacher->update($this->teacherPayload($validated, $actor, (int) $teacher->school_id, $teacher));

            $teacher->refresh()->load([
                'user',
                'academicCycle',
                'mainClassroom.level.cycle',
                'mainSubject',
                'assignments.subject',
                'assignments.classroom.level.cycle',
                'assignments.academicYear',
            ]);

            $this->auditLogService->log(
                module: 'people',
                action: 'teacher.updated',
                auditable: $teacher,
                oldValues: $oldValues,
                newValues: $teacher->toArray(),
                description: "Mise à jour du dossier enseignant {$teacher->fullName()}.",
                user: $actor,
            );

            return $teacher;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function assignSubject(Teacher $teacher, array $validated, User $actor): TeacherSubjectAssignment
    {
        return DB::transaction(function () use ($teacher, $validated, $actor): TeacherSubjectAssignment {
            $schoolId = (int) $teacher->school_id;
            $subject = AcademicSubject::query()
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->findOrFail((int) Arr::get($validated, 'academic_subject_id'));
            $classroom = AcademicClassroom::query()
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->findOrFail((int) Arr::get($validated, 'academic_classroom_id'));
            $academicYear = AcademicYear::query()->findOrFail((int) Arr::get($validated, 'academic_year_id'));
            $notes = $this->nullableString(Arr::get($validated, 'notes'));

            $existingAssignment = $teacher->assignments()
                ->where('academic_subject_id', $subject->id)
                ->where('academic_classroom_id', $classroom->id)
                ->where('academic_year_id', $academicYear->id)
                ->first();

            if ($existingAssignment && $this->nullableString($existingAssignment->notes) === $notes) {
                return $existingAssignment->load(['subject', 'classroom.level.cycle', 'academicYear']);
            }

            if ($existingAssignment) {
                $oldValues = $this->assignmentSnapshot($existingAssignment);

                $existingAssignment->update([
                    'notes' => $notes,
                    'updated_by' => $actor->id,
                ]);

                $assignment = $existingAssignment->fresh()->load(['subject', 'classroom.level.cycle', 'academicYear']);

                $this->auditLogService->log(
                    module: 'people',
                    action: 'teacher.assignment.updated',
                    auditable: $teacher,
                    oldValues: $oldValues,
                    newValues: $this->assignmentSnapshot($assignment),
                    description: 'Mise à jour de l’affectation '.$assignment->summary().' pour '.$teacher->fullName().'.',
                    user: $actor,
                );

                return $assignment;
            }

            $assignment = $teacher->assignments()->create([
                'school_id' => $schoolId,
                'academic_subject_id' => $subject->id,
                'academic_classroom_id' => $classroom->id,
                'academic_year_id' => $academicYear->id,
                'notes' => $notes,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $assignment->load(['subject', 'classroom.level.cycle', 'academicYear']);

            $this->auditLogService->log(
                module: 'people',
                action: 'teacher.assignment.created',
                auditable: $teacher,
                newValues: $this->assignmentSnapshot($assignment),
                description: 'Ajout de l’affectation '.$assignment->summary().' pour '.$teacher->fullName().'.',
                user: $actor,
            );

            return $assignment;
        });
    }

    public function removeAssignment(Teacher $teacher, TeacherSubjectAssignment $assignment, User $actor): void
    {
        $this->ensureAssignmentBelongsToTeacher($teacher, $assignment);

        DB::transaction(function () use ($teacher, $assignment, $actor): void {
            $oldValues = $this->assignmentSnapshot($assignment->loadMissing(['subject', 'classroom.level.cycle', 'academicYear']));

            $assignment->delete();

            $this->auditLogService->log(
                module: 'people',
                action: 'teacher.assignment.removed',
                auditable: $teacher,
                oldValues: $oldValues,
                newValues: [
                    'assignment_deleted' => true,
                ],
                description: 'Retrait de l’affectation '.$oldValues['summary'].' pour '.$teacher->fullName().'.',
                user: $actor,
            );
        });
    }

    /**
     * @return Collection<int, AuditLog>
     */
    public function historyFor(Teacher $teacher, int $limit = 10): Collection
    {
        return AuditLog::query()
            ->with('user:id,name')
            ->where('module', 'people')
            ->where('auditable_type', $teacher->getMorphClass())
            ->where('auditable_id', $teacher->id)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function nextMatricule(int $schoolId): string
    {
        $year = (string) now()->year;
        $prefix = "ENS-{$year}-";

        $lastMatricule = Teacher::query()
            ->forSchool($schoolId)
            ->where('matricule', 'like', $prefix.'%')
            ->orderByDesc('matricule')
            ->value('matricule');

        $nextNumber = 1;

        if (is_string($lastMatricule) && preg_match('/^ENS-'.$year.'-(\d{4})$/', $lastMatricule, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function teacherPayload(array $validated, User $actor, int $schoolId, ?Teacher $teacher = null): array
    {
        $cycleId = $this->nullableInteger(Arr::get($validated, 'academic_cycle_id'));
        $track = $this->pedagogicTrackForCycleId($cycleId);

        return [
            'school_id' => $schoolId,
            'user_id' => $teacher?->user_id,
            'academic_cycle_id' => $cycleId,
            'main_classroom_id' => $track === 'primary'
                ? $this->nullableInteger(Arr::get($validated, 'main_classroom_id'))
                : null,
            'main_subject_id' => $track === 'secondary'
                ? $this->nullableInteger(Arr::get($validated, 'main_subject_id'))
                : null,
            'matricule' => $teacher?->matricule ?? $this->nextMatricule($schoolId),
            'last_name' => trim((string) $validated['last_name']),
            'first_names' => trim((string) $validated['first_names']),
            'gender' => (string) $validated['gender'],
            'phone' => $this->nullableString(Arr::get($validated, 'phone')),
            'email' => $this->nullableString(Arr::get($validated, 'email')),
            'address' => $this->nullableString(Arr::get($validated, 'address')),
            'specialty' => $this->nullableString(Arr::get($validated, 'specialty')),
            'diploma' => $this->nullableString(Arr::get($validated, 'diploma')),
            'contract_type' => (string) Arr::get($validated, 'contract_type', 'contractuel'),
            'status' => (string) Arr::get($validated, 'status', 'actif'),
            'notes' => $this->nullableString(Arr::get($validated, 'notes')),
            'created_by' => $teacher?->created_by ?? $actor->id,
            'updated_by' => $actor->id,
        ];
    }

    /**
     * @param  int  $schoolId
     * @param  mixed  $email
     * @param  mixed  $phone
     * @param  int|null  $exceptId
     */
    private function ensureNoDuplicateContact(int $schoolId, mixed $email, mixed $phone, ?int $exceptId = null): void
    {
        $normalizedEmail = $this->nullableString($email);
        $normalizedPhone = $this->nullableString($phone);

        if ($normalizedEmail === null && $normalizedPhone === null) {
            return;
        }

        $query = Teacher::query()->forSchool($schoolId);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $query->where(function ($nestedQuery) use ($normalizedEmail, $normalizedPhone): void {
            if ($normalizedEmail !== null) {
                $nestedQuery->orWhereRaw('LOWER(email) = ?', [mb_strtolower($normalizedEmail)]);
            }

            if ($normalizedPhone !== null) {
                $nestedQuery->orWhereRaw('TRIM(phone) = ?', [trim($normalizedPhone)]);
            }
        });

        $duplicate = $query->first();

        if (! $duplicate) {
            return;
        }

        $errors = [];

        if ($normalizedEmail !== null && $duplicate->email !== null && mb_strtolower($duplicate->email) === mb_strtolower($normalizedEmail)) {
            $errors['email'] = __('people.teachers.validation.duplicate_contact');
        }

        if ($normalizedPhone !== null && $duplicate->phone !== null && trim($duplicate->phone) === trim($normalizedPhone)) {
            $errors['phone'] = __('people.teachers.validation.duplicate_contact');
        }

        if ($errors === []) {
            $errors['phone'] = __('people.teachers.validation.duplicate_contact');
        }

        throw ValidationException::withMessages($errors);
    }

    private function ensureAssignmentBelongsToTeacher(Teacher $teacher, TeacherSubjectAssignment $assignment): void
    {
        abort_unless(
            (int) $assignment->teacher_id === (int) $teacher->id
                && (int) $assignment->school_id === (int) $teacher->school_id,
            404
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return $value === null ? null : trim((string) $value);
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function pedagogicTrackForCycleId(?int $cycleId): ?string
    {
        if ($cycleId === null) {
            return null;
        }

        return AcademicCycle::query()
            ->find($cycleId)
            ?->category?->teacherTrack();
    }

    /**
     * @return array<string, mixed>
     */
    private function assignmentSnapshot(TeacherSubjectAssignment $assignment): array
    {
        return [
            'assignment_id' => $assignment->id,
            'school_id' => $assignment->school_id,
            'teacher_id' => $assignment->teacher_id,
            'academic_subject_id' => $assignment->academic_subject_id,
            'academic_subject' => $assignment->subject?->name,
            'academic_classroom_id' => $assignment->academic_classroom_id,
            'academic_classroom' => $assignment->classroom?->name,
            'academic_year_id' => $assignment->academic_year_id,
            'academic_year' => $assignment->academicYear?->name,
            'notes' => $assignment->notes,
            'summary' => $assignment->summary(),
        ];
    }
}
