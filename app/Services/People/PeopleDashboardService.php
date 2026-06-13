<?php

namespace App\Services\People;

use App\Models\Guardian;
use App\Models\Staff;
use App\Models\Student;
use App\Models\TeacherInvitation;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Core\SchoolContext;
use Illuminate\Support\Facades\Gate;

class PeopleDashboardService
{
    public function __construct(
        private readonly SchoolContext $schoolContext,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $schoolId = $this->schoolContext->current()->id;
        $gate = Gate::forUser($user);

        $studentsQuery = Student::query()->forSchool($schoolId);
        $parentsQuery = Guardian::query()->forSchool($schoolId);
        $teachersQuery = Teacher::query()->forSchool($schoolId);
        $staffQuery = Staff::query()->forSchool($schoolId);

        $canViewStudents = $gate->allows('viewAny', Student::class);
        $canViewParents = $gate->allows('viewAny', Guardian::class);
        $canViewTeachers = $gate->allows('viewAny', Teacher::class);
        $canViewStaff = $gate->allows('viewAny', Staff::class);
        $canViewAccounts = $gate->allows('viewAny', User::class);

        $studentsActive = $canViewStudents ? (clone $studentsQuery)->active()->count() : 0;
        $parentsCount = $canViewParents ? (clone $parentsQuery)->count() : 0;
        $teachersCount = $canViewTeachers ? (clone $teachersQuery)->active()->count() : 0;
        $staffCount = $canViewStaff ? (clone $staffQuery)->active()->count() : 0;
        $studentsWithoutParents = $canViewStudents ? (clone $studentsQuery)->withoutParents()->count() : 0;
        $parentsWithoutChildren = $canViewParents ? (clone $parentsQuery)->doesntHave('students')->count() : 0;
        $teachersWithoutAssignments = $canViewTeachers ? (clone $teachersQuery)->withoutPedagogicPlacement()->count() : 0;
        $staffWithoutAccount = $canViewStaff ? (clone $staffQuery)->withoutAccount()->count() : 0;
        $inactiveAccounts = $canViewAccounts ? $this->inactiveLinkedAccountsCount($schoolId) : 0;
        $primaryTeachers = $canViewTeachers ? (clone $teachersQuery)->primaryTrack()->count() : 0;
        $secondaryTeachers = $canViewTeachers ? (clone $teachersQuery)->secondaryTrack()->count() : 0;
        $unclassifiedTeachers = $canViewTeachers ? (clone $teachersQuery)->whereNull('academic_cycle_id')->count() : 0;
        $teacherInvitationsQuery = TeacherInvitation::query()->forSchool($schoolId);
        $teacherInvitationsTotal = $canViewTeachers ? (clone $teacherInvitationsQuery)->count() : 0;
        $teacherInvitationsPending = $canViewTeachers
            ? (clone $teacherInvitationsQuery)
                ->where('status', TeacherInvitation::STATUS_PENDING)
                ->where(function ($query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->count()
            : 0;
        $teacherInvitationsToValidate = $canViewTeachers
            ? (clone $teacherInvitationsQuery)
                ->where('status', TeacherInvitation::STATUS_COMPLETED)
                ->count()
            : 0;
        $teacherInvitationsValidated = $canViewTeachers
            ? (clone $teacherInvitationsQuery)
                ->where('status', TeacherInvitation::STATUS_VALIDATED)
                ->count()
            : 0;
        $teacherInvitationsExpired = $canViewTeachers
            ? (clone $teacherInvitationsQuery)
                ->where('status', TeacherInvitation::STATUS_PENDING)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->count()
            : 0;
        $recentTeacherInvitations = $canViewTeachers
            ? (clone $teacherInvitationsQuery)
                ->with(['teacher.user', 'teacher.academicCycle', 'teacher.mainClassroom.level.cycle', 'teacher.mainSubject'])
                ->orderByDesc('sent_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get()
            : collect();

        $stats = array_values(array_filter([
            $canViewStudents ? [
                'label' => __('people.dashboard.stats.students_active'),
                'value' => (string) $studentsActive,
                'icon' => 'fa-user-graduate',
                'tone' => 'sky',
            ] : null,
            $canViewParents ? [
                'label' => __('people.dashboard.stats.parents'),
                'value' => (string) $parentsCount,
                'icon' => 'fa-people-roof',
                'tone' => 'emerald',
            ] : null,
            $canViewTeachers ? [
                'label' => __('people.dashboard.stats.teachers'),
                'value' => (string) $teachersCount,
                'icon' => 'fa-chalkboard-user',
                'tone' => 'amber',
            ] : null,
            $canViewStaff ? [
                'label' => __('people.dashboard.stats.staff'),
                'value' => (string) $staffCount,
                'icon' => 'fa-people-carry-box',
                'tone' => 'violet',
            ] : null,
            $canViewStudents ? [
                'label' => __('people.dashboard.stats.without_parents'),
                'value' => (string) $studentsWithoutParents,
                'icon' => 'fa-user-xmark',
                'tone' => 'rose',
            ] : null,
            $canViewAccounts ? [
                'label' => __('people.dashboard.stats.inactive_accounts'),
                'value' => (string) $inactiveAccounts,
                'icon' => 'fa-user-clock',
                'tone' => 'rose',
            ] : null,
        ]));

        $alerts = array_values(array_filter([
            $canViewStudents && $studentsWithoutParents > 0 ? [
                'tone' => 'warning',
                'icon' => 'fa-user-xmark',
                'title' => __('people.dashboard.alerts.without_parents.title'),
                'description' => __('people.dashboard.alerts.without_parents.description', ['count' => $studentsWithoutParents]),
                'action' => [
                    'label' => __('people.dashboard.quick_actions.view_students'),
                    'route' => route('people.students.index'),
                ],
            ] : null,
            $canViewParents && $parentsWithoutChildren > 0 ? [
                'tone' => 'info',
                'icon' => 'fa-people-roof',
                'title' => __('people.dashboard.alerts.parents_without_children.title'),
                'description' => __('people.dashboard.alerts.parents_without_children.description', ['count' => $parentsWithoutChildren]),
                'action' => [
                    'label' => __('people.dashboard.quick_actions.view_parents'),
                    'route' => route('people.parents.index'),
                ],
            ] : null,
            $canViewTeachers && $teachersWithoutAssignments > 0 ? [
                'tone' => 'warning',
                'icon' => 'fa-chalkboard-user',
                'title' => __('people.dashboard.alerts.teachers_without_assignments.title'),
                'description' => __('people.dashboard.alerts.teachers_without_assignments.description', ['count' => $teachersWithoutAssignments]),
                'action' => [
                    'label' => __('people.dashboard.quick_actions.view_teachers'),
                    'route' => route('people.teachers.index'),
                ],
            ] : null,
            $canViewStaff && $staffWithoutAccount > 0 ? [
                'tone' => 'info',
                'icon' => 'fa-user-lock',
                'title' => __('people.dashboard.alerts.staff_without_account.title'),
                'description' => __('people.dashboard.alerts.staff_without_account.description', ['count' => $staffWithoutAccount]),
                'action' => [
                    'label' => __('people.dashboard.quick_actions.view_staff'),
                    'route' => route('people.staff.index'),
                ],
            ] : null,
            $canViewAccounts && $inactiveAccounts > 0 ? [
                'tone' => 'error',
                'icon' => 'fa-user-clock',
                'title' => __('people.dashboard.alerts.inactive_accounts.title'),
                'description' => __('people.dashboard.alerts.inactive_accounts.description', ['count' => $inactiveAccounts]),
                'action' => [
                    'label' => __('people.dashboard.quick_actions.view_accounts'),
                    'route' => route('core.users.index'),
                ],
            ] : null,
        ]));

        if ($alerts === []) {
            $alerts[] = [
                'tone' => 'success',
                'icon' => 'fa-circle-check',
                'title' => __('people.dashboard.alerts_none_title'),
                'description' => __('people.dashboard.alerts_none_description'),
                'action' => null,
            ];
        }

        $recentStudents = $canViewStudents
            ? (clone $studentsQuery)
                ->with(['currentClassroom.level.cycle', 'parents'])
                ->withCount('parents')
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        $recentParents = $canViewParents
            ? (clone $parentsQuery)
                ->withCount('students')
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        $recentTeachers = $canViewTeachers
            ? (clone $teachersQuery)
                ->with(['academicCycle', 'mainClassroom.level.cycle', 'mainSubject', 'latestInvitation'])
                ->withCount('assignments')
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        $recentStaff = $canViewStaff
            ? (clone $staffQuery)
                ->with('user')
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        return [
            'title' => __('people.dashboard.title'),
            'description' => __('people.dashboard.description'),
            'stats' => $stats,
            'alerts' => $alerts,
            'recent_students' => $recentStudents,
            'recent_parents' => $recentParents,
            'recent_teachers' => $recentTeachers,
            'recent_staff' => $recentStaff,
            'teacher_cards' => array_values(array_filter([
                $canViewTeachers ? [
                    'label' => __('people.dashboard.teachers.primary.label'),
                    'value' => (string) $primaryTeachers,
                    'icon' => 'fa-chalkboard',
                    'tone' => 'emerald',
                    'route' => route('people.teachers.index', ['track' => 'primary']),
                    'button_label' => __('people.dashboard.teachers.primary.button'),
                ] : null,
                $canViewTeachers ? [
                    'label' => __('people.dashboard.teachers.secondary.label'),
                    'value' => (string) $secondaryTeachers,
                    'icon' => 'fa-book-open',
                    'tone' => 'sky',
                    'route' => route('people.teachers.index', ['track' => 'secondary']),
                    'button_label' => __('people.dashboard.teachers.secondary.button'),
                ] : null,
                $canViewTeachers ? [
                    'label' => __('people.dashboard.teachers.unclassified.label'),
                    'value' => (string) $unclassifiedTeachers,
                    'icon' => 'fa-circle-question',
                    'tone' => 'amber',
                    'route' => route('people.teachers.index', ['track' => 'unclassified']),
                    'button_label' => __('people.dashboard.teachers.unclassified.button'),
                ] : null,
            ])),
            'teacher_invitations_stats' => [
                'total' => $teacherInvitationsTotal,
                'pending' => $teacherInvitationsPending,
                'to_validate' => $teacherInvitationsToValidate,
                'validated' => $teacherInvitationsValidated,
                'expired' => $teacherInvitationsExpired,
            ],
            'teacher_invitation_cards' => array_values(array_filter([
                $canViewTeachers ? [
                    'label' => __('people.dashboard.teacher_invitations.stats.sent.label'),
                    'subtitle' => __('people.dashboard.teacher_invitations.stats.sent.subtitle'),
                    'value' => (string) $teacherInvitationsTotal,
                    'icon' => 'fa-paper-plane',
                    'tone' => 'sky',
                    'trend' => __('people.dashboard.teacher_invitations.trends.sent'),
                ] : null,
                $canViewTeachers ? [
                    'label' => __('people.dashboard.teacher_invitations.stats.pending.label'),
                    'subtitle' => __('people.dashboard.teacher_invitations.stats.pending.subtitle'),
                    'value' => (string) $teacherInvitationsPending,
                    'icon' => 'fa-hourglass-half',
                    'tone' => 'amber',
                    'trend' => __('people.dashboard.teacher_invitations.trends.pending'),
                ] : null,
                $canViewTeachers ? [
                    'label' => __('people.dashboard.teacher_invitations.stats.to_validate.label'),
                    'subtitle' => __('people.dashboard.teacher_invitations.stats.to_validate.subtitle'),
                    'value' => (string) $teacherInvitationsToValidate,
                    'icon' => 'fa-pen-to-square',
                    'tone' => 'violet',
                    'trend' => __('people.dashboard.teacher_invitations.trends.to_validate'),
                ] : null,
                $canViewTeachers ? [
                    'label' => __('people.dashboard.teacher_invitations.stats.validated.label'),
                    'subtitle' => __('people.dashboard.teacher_invitations.stats.validated.subtitle'),
                    'value' => (string) $teacherInvitationsValidated,
                    'icon' => 'fa-circle-check',
                    'tone' => 'emerald',
                    'trend' => __('people.dashboard.teacher_invitations.trends.validated'),
                ] : null,
                $canViewTeachers ? [
                    'label' => __('people.dashboard.teacher_invitations.stats.expired.label'),
                    'subtitle' => __('people.dashboard.teacher_invitations.stats.expired.subtitle'),
                    'value' => (string) $teacherInvitationsExpired,
                    'icon' => 'fa-hourglass-end',
                    'tone' => 'rose',
                    'trend' => __('people.dashboard.teacher_invitations.trends.expired'),
                ] : null,
            ])),
            'recent_teacher_invitations' => $recentTeacherInvitations,
            'quick_actions' => array_values(array_filter([
                $gate->allows('create', Student::class) ? [
                    'label' => __('people.dashboard.quick_actions.create_student'),
                    'route' => route('people.students.create'),
                    'icon' => 'fa-user-plus',
                    'variant' => 'primary',
                ] : null,
                $canViewStudents ? [
                    'label' => __('people.dashboard.quick_actions.view_students'),
                    'route' => route('people.students.index'),
                    'icon' => 'fa-list',
                    'variant' => 'secondary',
                ] : null,
                $gate->allows('create', Guardian::class) ? [
                    'label' => __('people.dashboard.quick_actions.create_parent'),
                    'route' => route('people.parents.create'),
                    'icon' => 'fa-user-plus',
                    'variant' => 'primary',
                ] : null,
                $canViewParents ? [
                    'label' => __('people.dashboard.quick_actions.view_parents'),
                    'route' => route('people.parents.index'),
                    'icon' => 'fa-people-roof',
                    'variant' => 'secondary',
                ] : null,
                $gate->allows('create', Teacher::class) ? [
                    'label' => __('people.dashboard.quick_actions.create_teacher'),
                    'route' => route('people.teachers.create'),
                    'icon' => 'fa-user-plus',
                    'variant' => 'primary',
                ] : null,
                $canViewTeachers ? [
                    'label' => __('people.dashboard.quick_actions.view_teachers'),
                    'route' => route('people.teachers.index'),
                    'icon' => 'fa-chalkboard-user',
                    'variant' => 'secondary',
                ] : null,
                $gate->allows('create', Staff::class) ? [
                    'label' => __('people.dashboard.quick_actions.create_staff'),
                    'route' => route('people.staff.create'),
                    'icon' => 'fa-user-plus',
                    'variant' => 'primary',
                ] : null,
                $canViewStaff ? [
                    'label' => __('people.dashboard.quick_actions.view_staff'),
                    'route' => route('people.staff.index'),
                    'icon' => 'fa-people-carry-box',
                    'variant' => 'secondary',
                ] : null,
                $canViewAccounts ? [
                    'label' => __('people.dashboard.quick_actions.view_accounts'),
                    'route' => route('core.users.index'),
                    'icon' => 'fa-user-gear',
                    'variant' => 'secondary',
                ] : null,
            ])),
        ];
    }

    private function inactiveLinkedAccountsCount(int $schoolId): int
    {
        $linkedUserIds = collect()
            ->merge(Student::query()->forSchool($schoolId)->whereNotNull('user_id')->pluck('user_id'))
            ->merge(Guardian::query()->forSchool($schoolId)->whereNotNull('user_id')->pluck('user_id'))
            ->merge(Teacher::query()->forSchool($schoolId)->whereNotNull('user_id')->pluck('user_id'))
            ->merge(Staff::query()->forSchool($schoolId)->whereNotNull('user_id')->pluck('user_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($linkedUserIds === []) {
            return 0;
        }

        return User::query()
            ->whereIn('id', $linkedUserIds)
            ->where('is_active', false)
            ->count();
    }
}
