<?php

namespace App\Services\People;

use App\Mail\TeacherInvitationMail;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\TeacherInvitation;
use App\Models\User;
use App\Services\Core\AuditLogService;
use App\Services\Core\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeacherInvitationService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    /**
     * @return array{invitation: TeacherInvitation, url: string}
     */
    public function sendInvitation(Teacher $teacher, User $actor): array
    {
        $this->ensureCurrentSchool($teacher);

        $result = DB::transaction(function () use ($teacher, $actor): array {
            $teacher->loadMissing(['user', 'latestInvitation']);

            if ($teacher->user_id !== null) {
                throw ValidationException::withMessages([
                    'account' => __('people.teachers.invitations.validation.already_has_account'),
                ]);
            }

            $this->revokePendingInvitations($teacher, $actor);

            $plainToken = Str::random(64);
            $invitation = TeacherInvitation::query()->create([
                'school_id' => (int) $teacher->school_id,
                'teacher_id' => (int) $teacher->id,
                'recipient_email' => $this->nullableString($teacher->email),
                'token_hash' => hash('sha256', $plainToken),
                'status' => TeacherInvitation::STATUS_PENDING,
                'sent_at' => now(),
                'expires_at' => now()->addDays(7),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $invitation->loadMissing('teacher');

            $url = route('teacher.invitations.show', ['token' => $plainToken]);

            $this->auditLogService->log(
                module: 'people',
                action: 'teacher.invitation.created',
                auditable: $teacher,
                newValues: [
                    'teacher_invitation_id' => $invitation->id,
                    'recipient_email' => $invitation->recipient_email,
                    'status' => $invitation->status,
                    'sent_at' => $invitation->sent_at?->toDateTimeString(),
                    'expires_at' => $invitation->expires_at?->toDateTimeString(),
                ],
                description: 'Génération du lien d’invitation pour '.$teacher->fullName().'.',
                user: $actor,
            );

            return [
                'invitation' => $invitation,
                'url' => $url,
            ];
        });

        if ($result['invitation']->recipient_email !== null) {
            try {
                Mail::to($result['invitation']->recipient_email)->send(
                    new TeacherInvitationMail($result['invitation'], $result['url'])
                );
            } catch (\Throwable $throwable) {
                report($throwable);
            }
        }

        return $result;
    }

    /**
     * @return array{invitation: TeacherInvitation, user: User, mode: 'created'|'linked'}
     */
    public function completeInvitation(string $token, array $validated): array
    {
        $invitation = $this->findByToken($token);

        if ($invitation === null) {
            throw ValidationException::withMessages([
                'token' => __('people.teachers.invitations.validation.invalid_token'),
            ]);
        }

        if ($invitation->isExpired()) {
            throw ValidationException::withMessages([
                'token' => __('people.teachers.invitations.validation.expired'),
            ]);
        }

        if (! $invitation->isPending()) {
            throw ValidationException::withMessages([
                'token' => match ($invitation->displayStatus()) {
                    TeacherInvitation::STATUS_COMPLETED => __('people.teachers.invitations.validation.completed'),
                    TeacherInvitation::STATUS_VALIDATED => __('people.teachers.invitations.validation.validated'),
                    TeacherInvitation::STATUS_REVOKED => __('people.teachers.invitations.validation.revoked'),
                    default => __('people.teachers.invitations.validation.invalid_token'),
                },
            ]);
        }

        $teacher = $invitation->teacher()->with(['school', 'user.roles', 'user.student', 'user.guardian', 'user.teacher', 'user.staff'])->first();

        if ($teacher === null) {
            throw ValidationException::withMessages([
                'token' => __('people.teachers.invitations.validation.teacher_missing'),
            ]);
        }

        if ($teacher->user_id !== null) {
            throw ValidationException::withMessages([
                'email' => __('people.teachers.invitations.validation.already_has_account'),
            ]);
        }

        return DB::transaction(function () use ($invitation, $teacher, $validated): array {
            $email = mb_strtolower(trim((string) $validated['email']));
            $password = (string) $validated['password'];
            if (User::query()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                throw ValidationException::withMessages([
                    'email' => __('people.teachers.invitations.validation.email_taken'),
                ]);
            }

            $user = User::query()->create([
                'name' => $teacher->fullName(),
                'email' => $email,
                'password' => $password,
                'is_active' => false,
                'suspended_at' => now(),
                'suspended_reason' => __('people.teachers.invitations.suspension_reason'),
            ]);

            $user->roles()->attach($this->teacherRoleId(), [
                'assigned_by' => null,
                'assigned_at' => now(),
            ]);

            $user = $user->fresh()->load('roles');
            $mode = 'created';

            $teacher->forceFill([
                'user_id' => $user->id,
                'updated_by' => $user->id,
            ])->save();

            $invitation->forceFill([
                'status' => TeacherInvitation::STATUS_COMPLETED,
                'completed_at' => now(),
                'updated_by' => $user->id,
            ])->save();

            $this->auditLogService->log(
                module: 'people',
                action: 'teacher.invitation.completed',
                auditable: $teacher,
                oldValues: [
                    'teacher_id' => $teacher->id,
                    'user_id' => null,
                    'invitation_id' => $invitation->id,
                    'status' => TeacherInvitation::STATUS_PENDING,
                ],
                newValues: [
                    'teacher_id' => $teacher->id,
                    'user_id' => $user->id,
                    'invitation_id' => $invitation->id,
                    'status' => TeacherInvitation::STATUS_COMPLETED,
                ],
                description: 'Complétion de l’inscription de '.$teacher->fullName().'.',
                user: $user,
            );

            return [
                'invitation' => $invitation->fresh(['teacher', 'teacher.user.roles']),
                'user' => $user,
                'mode' => $mode,
            ];
        });
    }

    /**
     * @return TeacherInvitation|null
     */
    public function findByToken(string $token): ?TeacherInvitation
    {
        $hash = hash('sha256', $token);

        return TeacherInvitation::query()
            ->with(['teacher.school', 'teacher.academicCycle', 'teacher.mainClassroom.level.cycle', 'teacher.mainSubject', 'teacher.user.roles'])
            ->where('token_hash', $hash)
            ->first();
    }

    public function validateInvitation(Teacher $teacher, User $actor): TeacherInvitation
    {
        $this->ensureCurrentSchool($teacher);

        return DB::transaction(function () use ($teacher, $actor): TeacherInvitation {
            $teacher->loadMissing(['user.roles', 'latestInvitation']);

            if ($teacher->user === null) {
                throw ValidationException::withMessages([
                    'account' => __('people.teachers.invitations.validation.account_missing'),
                ]);
            }

            $invitation = $teacher->latestInvitation;

            if (! $invitation) {
                throw ValidationException::withMessages([
                    'invitation' => __('people.teachers.invitations.validation.no_invitation'),
                ]);
            }

            if (! in_array($invitation->displayStatus(), [TeacherInvitation::STATUS_COMPLETED, TeacherInvitation::STATUS_VALIDATED], true)) {
                throw ValidationException::withMessages([
                    'invitation' => __('people.teachers.invitations.validation.incomplete'),
                ]);
            }

            $oldUserValues = $teacher->user->toArray();

            $teacher->user->forceFill([
                'is_active' => true,
                'suspended_at' => null,
                'suspended_reason' => null,
                'email_verified_at' => now(),
            ])->save();

            $invitation->forceFill([
                'status' => TeacherInvitation::STATUS_VALIDATED,
                'validated_at' => now(),
                'updated_by' => $actor->id,
            ])->save();

            $this->auditLogService->log(
                module: 'people',
                action: 'teacher.invitation.validated',
                auditable: $teacher,
                oldValues: $oldUserValues,
                newValues: $teacher->user->fresh()->toArray(),
                description: 'Validation de l’inscription de '.$teacher->fullName().'.',
                user: $actor,
            );

            return $invitation->fresh(['teacher.user.roles']);
        });
    }

    /**
     * @return int Number of revoked invitations.
     */
    public function revokePendingInvitations(Teacher $teacher, User $actor): int
    {
        $this->ensureCurrentSchool($teacher);

        $revokedCount = TeacherInvitation::query()
            ->forSchool((int) $teacher->school_id)
            ->forTeacher((int) $teacher->id)
            ->where('status', TeacherInvitation::STATUS_PENDING)
            ->update([
                'status' => TeacherInvitation::STATUS_REVOKED,
                'revoked_at' => now(),
                'updated_by' => $actor->id,
            ]);

        if ($revokedCount > 0) {
            $this->auditLogService->log(
                module: 'people',
                action: 'teacher.invitation.revoked',
                auditable: $teacher,
                newValues: [
                    'revoked_count' => $revokedCount,
                ],
                description: 'Révocation de '.$revokedCount.' invitation(s) en attente pour '.$teacher->fullName().'.',
                user: $actor,
            );
        }

        return $revokedCount;
    }

    private function ensureCurrentSchool(Teacher $teacher): void
    {
        $currentSchoolId = (int) $this->schoolContext->current()->id;

        abort_unless((int) $teacher->school_id === $currentSchoolId, 404);
    }

    private function teacherRoleId(): int
    {
        $roleId = Role::query()->where('slug', 'enseignant')->value('id');

        if (! $roleId) {
            throw ValidationException::withMessages([
                'role' => __('people.teachers.invitations.validation.role_missing'),
            ]);
        }

        return (int) $roleId;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return $value === null ? null : trim((string) $value);
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
