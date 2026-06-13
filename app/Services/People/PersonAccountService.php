<?php

namespace App\Services\People;

use App\Models\Guardian;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Core\AuditLogService;
use App\Services\Core\SchoolContext;
use App\Services\Core\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PersonAccountService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly UserService $userService,
        private readonly SchoolContext $schoolContext,
        private readonly TeacherInvitationService $teacherInvitationService,
    ) {
    }

    /**
     * @return array{user: User, temporary_password: string|null, mode: 'created'|'linked'}
     */
    public function createForStudent(Student $student, User $actor): array
    {
        return $this->createOrLink($student, 'eleve', $actor);
    }

    /**
     * @return array{user: User, temporary_password: string|null, mode: 'created'|'linked'}
     */
    public function createForParent(Guardian $parent, User $actor): array
    {
        return $this->createOrLink($parent, 'parent', $actor);
    }

    /**
     * @return array{user: User, temporary_password: string|null, mode: 'created'|'linked'}
     */
    public function createForTeacher(Teacher $teacher, User $actor): array
    {
        $result = $this->createOrLink($teacher, 'enseignant', $actor);

        $this->teacherInvitationService->revokePendingInvitations($teacher, $actor);

        return $result;
    }

    /**
     * @return array{user: User, temporary_password: string|null, mode: 'created'|'linked'}
     */
    public function createForStaff(Staff $staff, User $actor): array
    {
        return $this->createOrLink($staff, $staff->accountRoleSlug(), $actor);
    }

    /**
     * @return array{user: User, temporary_password: string|null, mode: 'temporary'|'email'}
     */
    public function resetForStudent(Student $student, User $actor): array
    {
        return $this->reset($student, $actor);
    }

    /**
     * @return array{user: User, temporary_password: string|null, mode: 'temporary'|'email'}
     */
    public function resetForParent(Guardian $parent, User $actor): array
    {
        return $this->reset($parent, $actor);
    }

    /**
     * @return array{user: User, temporary_password: string|null, mode: 'temporary'|'email'}
     */
    public function resetForTeacher(Teacher $teacher, User $actor): array
    {
        return $this->reset($teacher, $actor);
    }

    /**
     * @return array{user: User, temporary_password: string|null, mode: 'temporary'|'email'}
     */
    public function resetForStaff(Staff $staff, User $actor): array
    {
        return $this->reset($staff, $actor);
    }

    public function toggleForStudent(Student $student, User $actor): User
    {
        return $this->toggle($student, $actor);
    }

    public function toggleForParent(Guardian $parent, User $actor): User
    {
        return $this->toggle($parent, $actor);
    }

    public function toggleForTeacher(Teacher $teacher, User $actor): User
    {
        return $this->toggle($teacher, $actor);
    }

    public function toggleForStaff(Staff $staff, User $actor): User
    {
        return $this->toggle($staff, $actor);
    }

    public function unlinkForStudent(Student $student, User $actor): void
    {
        $this->unlink($student, $actor);
    }

    public function unlinkForParent(Guardian $parent, User $actor): void
    {
        $this->unlink($parent, $actor);
    }

    public function unlinkForTeacher(Teacher $teacher, User $actor): void
    {
        $this->unlink($teacher, $actor);
    }

    public function unlinkForStaff(Staff $staff, User $actor): void
    {
        $this->unlink($staff, $actor);
    }

    /**
     * @return array{user: User, temporary_password: string|null, mode: 'created'|'linked'}
     */
    private function createOrLink(Student|Guardian|Staff|Teacher $person, ?string $baseRoleSlug, User $actor): array
    {
        $this->ensureCurrentSchool($person);

        return DB::transaction(function () use ($person, $baseRoleSlug, $actor): array {
            $person->loadMissing(['school', 'user.roles', 'user.student', 'user.guardian', 'user.staff', 'user.teacher']);

            if ($person->user_id !== null) {
                throw ValidationException::withMessages([
                    'account' => __('people.accounts.validation.already_linked'),
                ]);
            }

            $loginEmail = $this->loginEmailFor($person);
            $existingUser = User::query()
                ->with(['roles', 'student', 'guardian', 'teacher'])
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($loginEmail)])
                ->first();

            if ($existingUser) {
                $this->ensureUserIsAvailable($existingUser, $person);
                if ($baseRoleSlug !== null) {
                    $this->ensureRole($existingUser, $baseRoleSlug, $actor);
                }
                $this->linkPerson($person, $existingUser, $actor);

                $this->auditLogService->log(
                    module: 'people',
                    action: 'person.account.linked',
                    auditable: $person,
                    oldValues: $this->accountSnapshot($person, null, $loginEmail),
                    newValues: $this->accountSnapshot($person, $existingUser, $loginEmail),
                    description: 'Liaison du compte existant au dossier '.$person->fullName().'.',
                    user: $actor,
                );

                return [
                    'user' => $existingUser->fresh()->load('roles'),
                    'temporary_password' => null,
                    'mode' => 'linked',
                ];
            }

            $temporaryPassword = $this->temporaryPassword();
            $roleIds = [];

            if ($baseRoleSlug !== null) {
                $roleId = Role::query()->where('slug', $baseRoleSlug)->value('id');

                if (! $roleId) {
                    throw ValidationException::withMessages([
                        'account' => __('people.accounts.validation.role_missing'),
                    ]);
                }

                $roleIds[] = $roleId;
            }

            $user = $this->userService->create([
                'name' => $person->fullName(),
                'email' => $loginEmail,
                'password' => $temporaryPassword,
                'role_ids' => $roleIds,
                'is_active' => true,
            ], $actor);

            $this->linkPerson($person, $user, $actor);

            $this->auditLogService->log(
                module: 'people',
                action: 'person.account.created',
                auditable: $person,
                oldValues: $this->accountSnapshot($person, null, $loginEmail),
                newValues: $this->accountSnapshot($person, $user, $loginEmail),
                description: 'Création du compte lié au dossier '.$person->fullName().'.',
                user: $actor,
            );

            return [
                'user' => $user->fresh()->load('roles'),
                'temporary_password' => $temporaryPassword,
                'mode' => 'created',
            ];
        });
    }

    /**
     * @return array{user: User, temporary_password: string|null, mode: 'temporary'|'email'}
     */
    private function reset(Student|Guardian|Staff|Teacher $person, User $actor): array
    {
        $this->ensureCurrentSchool($person);

        return DB::transaction(function () use ($person, $actor): array {
            $person->loadMissing(['school', 'user.roles', 'user.student', 'user.guardian', 'user.staff', 'user.teacher']);

            $user = $person->user;

            if (! $user) {
                throw ValidationException::withMessages([
                    'account' => __('people.accounts.validation.account_missing'),
                ]);
            }

            $oldValues = $this->accountSnapshot($person, $user, $this->loginEmailFor($person));

            if ($this->isInternalEmail($user->email)) {
                $temporaryPassword = $this->temporaryPassword();

                $this->updateUserPassword($user, $temporaryPassword, $actor);

                $person->loadMissing('user.roles');

                $this->auditLogService->log(
                    module: 'people',
                    action: 'person.account.reset',
                    auditable: $person,
                    oldValues: $oldValues,
                    newValues: $this->accountSnapshot($person, $person->user, $this->loginEmailFor($person), [
                        'password_reset' => true,
                    ]),
                    description: 'Réinitialisation de l’accès pour le dossier '.$person->fullName().'.',
                    user: $actor,
                );

                return [
                    'user' => $person->user->fresh()->load('roles'),
                    'temporary_password' => $temporaryPassword,
                    'mode' => 'temporary',
                ];
            }

            $status = Password::sendResetLink([
                'email' => $user->email,
            ]);

            if ($status !== Password::RESET_LINK_SENT) {
                throw ValidationException::withMessages([
                    'account' => __('people.accounts.validation.reset_failed'),
                ]);
            }

            $this->auditLogService->log(
                module: 'people',
                action: 'person.account.reset_requested',
                auditable: $person,
                oldValues: $oldValues,
                newValues: $this->accountSnapshot($person, $user, $this->loginEmailFor($person), [
                    'password_reset_link_sent' => true,
                ]),
                description: 'Envoi d’un lien de réinitialisation pour le dossier '.$person->fullName().'.',
                user: $actor,
            );

            return [
                'user' => $user,
                'temporary_password' => null,
                'mode' => 'email',
            ];
        });
    }

    private function toggle(Student|Guardian|Staff|Teacher $person, User $actor): User
    {
        $this->ensureCurrentSchool($person);

        return DB::transaction(function () use ($person, $actor): User {
            $person->loadMissing(['school', 'user.roles', 'user.student', 'user.guardian', 'user.staff', 'user.teacher']);

            $user = $person->user;

            if (! $user) {
                throw ValidationException::withMessages([
                    'account' => __('people.accounts.validation.account_missing'),
                ]);
            }

            $oldValues = $this->accountSnapshot($person, $user, $this->loginEmailFor($person));
            $nextState = ! $user->is_active;
            $suspendedReason = $nextState
                ? null
                : __('people.accounts.messages.quick_suspend_reason', ['user' => $actor->name]);

            $updated = $this->updateUserState($user, $nextState, $actor, $suspendedReason);

            $person->loadMissing('user.roles');

            $this->auditLogService->log(
                module: 'people',
                action: $updated->is_active ? 'person.account.reactivated' : 'person.account.suspended',
                auditable: $person,
                oldValues: $oldValues,
                newValues: $this->accountSnapshot($person, $updated, $this->loginEmailFor($person), [
                    'status_changed' => true,
                ]),
                description: $updated->is_active
                    ? 'Réactivation du compte lié au dossier '.$person->fullName().'.'
                    : 'Suspension du compte lié au dossier '.$person->fullName().'.',
                user: $actor,
            );

            return $updated->fresh()->load('roles');
        });
    }

    private function unlink(Student|Guardian|Staff|Teacher $person, User $actor): void
    {
        $this->ensureCurrentSchool($person);

        DB::transaction(function () use ($person, $actor): void {
            $person->loadMissing(['school', 'user.roles', 'user.student', 'user.guardian', 'user.teacher']);

            $user = $person->user;

            if (! $user) {
                throw ValidationException::withMessages([
                    'account' => __('people.accounts.validation.account_missing'),
                ]);
            }

            $oldValues = $this->accountSnapshot($person, $user, $this->loginEmailFor($person));

            $person->forceFill([
                'user_id' => null,
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogService->log(
                module: 'people',
                action: 'person.account.unlinked',
                auditable: $person,
                oldValues: $oldValues,
                newValues: $this->accountSnapshot($person, null, $this->loginEmailFor($person), [
                    'account_unlinked' => true,
                ]),
                description: 'Dissociation du compte lié au dossier '.$person->fullName().'.',
                user: $actor,
            );
        });
    }

    private function ensureCurrentSchool(Student|Guardian|Staff|Teacher $person): void
    {
        abort_unless((int) $person->school_id === (int) $this->schoolContext->current()->id, 404);
    }

    private function linkPerson(Student|Guardian|Staff|Teacher $person, User $user, User $actor): void
    {
        $person->forceFill([
            'user_id' => $user->id,
            'updated_by' => $actor->id,
        ])->save();
    }

    private function updateUserPassword(User $user, string $password, User $actor): User
    {
        return $this->userService->update($user, $this->updatePayloadFor($user, [
            'password' => $password,
        ]), $actor);
    }

    private function updateUserState(User $user, bool $isActive, User $actor, ?string $suspendedReason = null): User
    {
        return $this->userService->update($user, $this->updatePayloadFor($user, [
            'is_active' => $isActive,
            'suspended_reason' => $suspendedReason,
        ]), $actor);
    }

    /**
     * @return array<string, mixed>
     */
    private function updatePayloadFor(User $user, array $overrides = []): array
    {
        $payload = [
            'name' => $user->name,
            'email' => $user->email,
            'role_ids' => $user->roles->pluck('id')->all(),
            'is_active' => $user->is_active,
        ];

        if (! $user->is_active) {
            $payload['suspended_reason'] = $user->suspended_reason;
        }

        return array_merge($payload, $overrides);
    }

    private function ensureUserIsAvailable(User $user, Student|Guardian|Staff|Teacher $person): void
    {
        $student = $user->student;
        $guardian = $user->guardian;
        $staff = $user->staff;
        $teacher = $user->teacher;

        $linkedToSameStudent = $person instanceof Student && $student?->is($person);
        $linkedToSameGuardian = $person instanceof Guardian && $guardian?->is($person);
        $linkedToSameStaff = $person instanceof Staff && $staff?->is($person);
        $linkedToSameTeacher = $person instanceof Teacher && $teacher?->is($person);

        if (($student && ! $linkedToSameStudent) || ($guardian && ! $linkedToSameGuardian) || ($staff && ! $linkedToSameStaff) || ($teacher && ! $linkedToSameTeacher)) {
            throw ValidationException::withMessages([
                'account' => __('people.accounts.validation.user_already_linked'),
            ]);
        }
    }

    private function ensureRole(User $user, string $roleSlug, User $actor): void
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

        if ($user->roles->contains('id', $role->id)) {
            return;
        }

        $user->roles()->attach($role->id, [
            'assigned_by' => $actor->id,
            'assigned_at' => now(),
        ]);

        $user->load('roles');
    }

    private function loginEmailFor(Student|Guardian|Staff|Teacher $person): string
    {
        if ($person instanceof Guardian && filled($person->email)) {
            return mb_strtolower(trim((string) $person->email));
        }

        if ($person instanceof Staff && filled($person->email)) {
            return mb_strtolower(trim((string) $person->email));
        }

        if ($person instanceof Teacher && filled($person->email)) {
            return mb_strtolower(trim((string) $person->email));
        }

        $schoolCode = Str::slug($person->school?->code ?: 'ecole', '-');

        if ($person instanceof Student) {
            $localPart = 'eleve-'.Str::slug($person->matricule ?: (string) $person->id, '-');
        } elseif ($person instanceof Staff) {
            $localPart = 'personnel-'.Str::slug($person->matricule ?: (string) $person->id, '-');
        } elseif ($person instanceof Teacher) {
            $localPart = 'enseignant-'.Str::slug($person->matricule ?: (string) $person->id, '-');
        } else {
            $localPart = 'parent-'.$person->id;
        }

        return Str::lower($localPart.'@'.$schoolCode.'.local');
    }

    private function isInternalEmail(string $email): bool
    {
        return str_ends_with(mb_strtolower(trim($email)), '.local');
    }

    private function temporaryPassword(): string
    {
        return 'Aa1!'.Str::random(12);
    }

    /**
     * @return array<string, mixed>
     */
    private function accountSnapshot(Student|Guardian|Staff|Teacher $person, ?User $user, string $loginEmail, array $extra = []): array
    {
        $roles = $user?->roles?->pluck('slug')->all() ?? [];

        return array_merge([
            'person_type' => $person::class,
            'person_id' => $person->id,
            'user_id' => $user?->id,
            'email' => $loginEmail,
            'status' => $user?->is_active ? 'actif' : ($user ? 'suspendu' : null),
            'roles' => $roles,
            'last_login_at' => $user?->last_login_at?->toDateTimeString(),
        ], $extra);
    }
}
