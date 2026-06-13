<?php

namespace App\Services\Core;

use App\Models\AcademicClassroom;
use App\Models\AcademicCycle;
use App\Models\AcademicLevel;
use App\Models\AcademicSeries;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\CalendarEvent;
use App\Models\Guardian;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DashboardService
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
        if ($user->isParentPortalUser()) {
            return $this->buildParentPortal($user);
        }

        if ($user->isTeacherPortalUser()) {
            return $this->buildTeacherPortal($user);
        }

        if ($user->isStudentPortalUser()) {
            return $this->buildStudentPortal($user);
        }

        $activeAcademicYear = AcademicYear::query()->active()->first();
        $activeYear = $activeAcademicYear?->name ?? __('ui.dashboard.not_configured');
        $school = $this->schoolContext->current();
        $schoolName = $school->name;
        $schoolId = $school->id;
        $recentActivity = $this->recentActivityFor($user);
        $welcomePanel = $this->welcomePanelFor($user, $schoolName);

        return [
            'headline' => __('ui.dashboard.title'),
            'school_name' => $schoolName,
            'active_academic_year' => $activeYear,
            'welcome_panel' => $welcomePanel,
            'financial_cards' => [
                [
                    'label' => __('ui.dashboard.financial_cards.total_revenue.label'),
                    'subtitle' => __('ui.dashboard.financial_cards.total_revenue.subtitle'),
                    'value' => '0 XOF',
                    'trend' => '+100%',
                    'icon' => 'fa-comments-dollar',
                    'tone' => 'sky',
                ],
                [
                    'label' => __('ui.dashboard.financial_cards.pending.label'),
                    'subtitle' => __('ui.dashboard.financial_cards.pending.subtitle'),
                    'value' => '0 XOF',
                    'trend' => null,
                    'icon' => 'fa-sack-dollar',
                    'tone' => 'amber',
                ],
                [
                    'label' => __('ui.dashboard.financial_cards.overdue.label'),
                    'subtitle' => __('ui.dashboard.financial_cards.overdue.subtitle'),
                    'value' => '0 XOF',
                    'trend' => null,
                    'icon' => 'fa-money-bill-transfer',
                    'tone' => 'cyan',
                ],
                [
                    'label' => __('ui.dashboard.financial_cards.unpaid.label'),
                    'subtitle' => __('ui.dashboard.financial_cards.unpaid.subtitle'),
                    'value' => '0 XOF',
                    'trend' => null,
                    'icon' => 'fa-file-invoice-dollar',
                    'tone' => 'pink',
                ],
            ],
            'module_cards' => $this->adminModuleCards($user, $schoolId, $activeAcademicYear),
            'guide_steps' => [
                [
                    'title' => __('ui.dashboard.guide_steps.configure_core.title'),
                    'description' => __('ui.dashboard.guide_steps.configure_core.description'),
                    'icon' => 'fa-gear',
                ],
                [
                    'title' => __('ui.dashboard.guide_steps.activate_year.title'),
                    'description' => __('ui.dashboard.guide_steps.activate_year.description'),
                    'icon' => 'fa-calendar-check',
                ],
                [
                    'title' => __('ui.dashboard.guide_steps.deploy_modules.title'),
                    'description' => __('ui.dashboard.guide_steps.deploy_modules.description'),
                    'icon' => 'fa-layer-group',
                ],
            ],
            'core_snapshot' => [
                [
                    'label' => __('ui.dashboard.core_snapshot.active_users'),
                    'value' => (string) User::query()->where('is_active', true)->count(),
                ],
                [
                    'label' => __('ui.dashboard.core_snapshot.active_roles'),
                    'value' => (string) Role::query()->where('is_active', true)->count(),
                ],
                [
                    'label' => __('ui.dashboard.core_snapshot.logs_last_7_days'),
                    'value' => (string) AuditLog::query()->where('created_at', '>=', now()->subDays(7))->count(),
                ],
            ],
            'recent_activity' => $recentActivity,
            'has_core_access' => $user->hasAnyPermission([
                'core.users.view',
                'core.roles.view',
                'core.academic_years.view',
                'core.settings.manage',
                'core.audit.view',
            ]),
            'mode' => 'admin',
        ];
    }

    /**
     * @return array<int, array{label: string, value: string, icon: string, tone: string, route: string|null}>
     */
    private function adminModuleCards(User $user, int $schoolId, ?AcademicYear $activeAcademicYear): array
    {
        $classroomCount = AcademicClassroom::query()
            ->where('school_id', $schoolId)
            ->when($activeAcademicYear, fn ($query) => $query->where('academic_year_id', $activeAcademicYear->id))
            ->where('is_active', true)
            ->count();

        $eventCount = CalendarEvent::query()
            ->where('school_id', $schoolId)
            ->when(
                $activeAcademicYear,
                fn ($query) => $query->whereHas(
                    'calendar',
                    fn ($calendarQuery) => $calendarQuery->where('academic_year_id', $activeAcademicYear->id)
                )
            )
            ->count();

        return [
            [
                'label' => __('ui.preview.students'),
                'value' => (string) Student::query()->forSchool($schoolId)->count(),
                'icon' => 'fa-user-group',
                'tone' => 'cyan',
                'route' => $user->hasAnyPermission([
                    'people.students.view',
                    'people.students.create',
                    'people.students.update',
                ]) ? route('people.students.index') : null,
            ],
            [
                'label' => __('ui.nav.teachers'),
                'value' => (string) Teacher::query()->forSchool($schoolId)->count(),
                'icon' => 'fa-graduation-cap',
                'tone' => 'amber',
                'route' => $user->hasAnyPermission([
                    'people.teachers.view',
                    'people.teachers.create',
                    'people.teachers.update',
                    'people.teachers.assign_subjects',
                ]) ? route('people.teachers.index') : null,
            ],
            [
                'label' => __('ui.nav.academic_years'),
                'value' => (string) AcademicYear::query()->count(),
                'icon' => 'fa-calendar-days',
                'tone' => 'sky',
                'route' => $user->hasPermissionTo('core.academic_years.view')
                    ? route('core.academic-years.index')
                    : null,
            ],
            [
                'label' => __('ui.nav.academic_cycles'),
                'value' => (string) AcademicCycle::query()->where('is_active', true)->count(),
                'icon' => 'fa-diagram-project',
                'tone' => 'orange',
                'route' => $user->hasAnyPermission(['academic.cycles.view', 'academic.cycles.manage'])
                    ? route('academic.cycles.index')
                    : null,
            ],
            [
                'label' => __('ui.nav.academic_levels'),
                'value' => (string) AcademicLevel::query()->where('is_active', true)->count(),
                'icon' => 'fa-layer-group',
                'tone' => 'blue',
                'route' => $user->hasAnyPermission(['academic.levels.view', 'academic.levels.manage'])
                    ? route('academic.levels.index')
                    : null,
            ],
            [
                'label' => __('ui.nav.academic_series'),
                'value' => (string) AcademicSeries::query()->where('is_active', true)->count(),
                'icon' => 'fa-sitemap',
                'tone' => 'green',
                'route' => $user->hasAnyPermission(['academic.series.view', 'academic.series.manage'])
                    ? route('academic.series.index')
                    : null,
            ],
            [
                'label' => __('ui.nav.academic_classes'),
                'value' => (string) $classroomCount,
                'icon' => 'fa-school',
                'tone' => 'pink',
                'route' => $user->hasAnyPermission(['academic.classes.view', 'academic.classes.manage'])
                    ? route('academic.classes.index')
                    : null,
            ],
            [
                'label' => __('ui.nav.calendar_events'),
                'value' => (string) $eventCount,
                'icon' => 'fa-calendar-check',
                'tone' => 'emerald',
                'route' => $user->hasAnyPermission([
                    'academic.events.view',
                    'academic.events.view_public',
                    'academic.events.view_internal',
                    'academic.events.manage_all',
                ]) ? route('academic.events.index') : null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTeacherPortal(User $user): array
    {
        $activeYear = AcademicYear::query()->active()->value('name') ?? __('ui.dashboard.not_configured');
        $school = $this->schoolContext->current();

        $user->loadMissing([
            'teacher.academicCycle',
            'teacher.mainClassroom.level.cycle',
            'teacher.mainSubject',
            'teacher.assignments.subject',
            'teacher.assignments.classroom.level.cycle',
            'teacher.documents',
            'roles',
        ]);

        $teacher = $user->teacher;
        $track = $teacher?->pedagogicTrack();

        $mainPlacementItem = null;

        if ($teacher && in_array($track, ['primary', 'secondary'], true)) {
            $placementTypeLabel = $teacher->pedagogicPlacementTypeLabel() ?? __('ui.dashboard.not_configured');
            $placementLabel = $teacher->pedagogicPlacementLabel() ?? __('ui.dashboard.not_configured');
            $cycleLabel = $teacher->academicCycle?->name ?? __('ui.dashboard.not_configured');

            $mainPlacementItem = [
                'title' => $placementLabel,
                'subtitle' => trim($placementTypeLabel.' · '.$cycleLabel),
                'badges' => array_values(array_filter([
                    [
                        'label' => $teacher->pedagogicTrackLabel(),
                        'tone' => $teacher->pedagogicTrackTone(),
                    ],
                    $teacher->academicCycle?->name ? [
                        'label' => $teacher->academicCycle->name,
                        'tone' => 'violet',
                    ] : null,
                    [
                        'label' => $placementTypeLabel,
                        'tone' => 'sky',
                    ],
                ])),
                'route' => route('people.teachers.show', $teacher).'#pedagogic-profile',
                'action_label' => __('ui.dashboard.teacher.actions.open_profile'),
            ];
        }

        $assignmentItems = $teacher
            ? $teacher->assignments
                ->sortByDesc(fn ($assignment): int => (int) ($assignment->academic_year_id ?? 0))
                ->values()
                ->map(function ($assignment): array {
                    $subject = $assignment->subject;
                    $classroom = $assignment->classroom;
                    $academicYear = $assignment->academicYear;

                    return [
                        'title' => $subject?->name ?? __('ui.dashboard.teacher.no_subject'),
                        'subtitle' => trim(
                            ($classroom?->name ?? __('ui.dashboard.parent.no_class'))
                            .' · '
                            .($academicYear?->name ?? __('ui.dashboard.not_configured'))
                        ),
                        'badges' => array_values(array_filter([
                            $subject?->code ? [
                                'label' => $subject->code,
                                'tone' => 'sky',
                            ] : null,
                            $classroom?->name ? [
                                'label' => $classroom->name,
                                'tone' => 'emerald',
                            ] : null,
                            $academicYear?->name ? [
                                'label' => $academicYear->name,
                                'tone' => 'violet',
                            ] : null,
                        ])),
                        'route' => null,
                        'action_label' => null,
                    ];
                })
            : collect();

        $relatedItems = collect([$mainPlacementItem])
            ->filter()
            ->merge($assignmentItems)
            ->values();

        $classroomIds = $teacher
            ? $teacher->assignments->pluck('academic_classroom_id')->filter()->unique()
            : collect();

        if ($teacher?->mainClassroom) {
            $classroomIds->push($teacher->mainClassroom->id);
        }

        $classesCount = $classroomIds->filter()->unique()->count();
        $teachingLoadCount = $relatedItems->count();
        $documentsCount = (int) ($teacher?->documents?->count() ?? 0);

        $alerts = [];

        if (! $teacher) {
            $alerts[] = [
                'tone' => 'warning',
                'icon' => 'fa-chalkboard-user',
                'title' => __('ui.dashboard.teacher.alerts.profile_missing.title'),
                'description' => __('ui.dashboard.teacher.alerts.profile_missing.description'),
            ];
        } elseif ($relatedItems->isEmpty()) {
            $alerts[] = [
                'tone' => 'warning',
                'icon' => 'fa-diagram-project',
                'title' => __('ui.dashboard.teacher.alerts.no_assignments.title'),
                'description' => __('ui.dashboard.teacher.alerts.no_assignments.description'),
            ];
        } else {
            if ($documentsCount === 0) {
                $alerts[] = [
                    'tone' => 'info',
                    'icon' => 'fa-folder-open',
                    'title' => __('ui.dashboard.teacher.alerts.no_documents.title'),
                    'description' => __('ui.dashboard.teacher.alerts.no_documents.description'),
                ];
            }

            if ($teacher->status !== 'actif') {
                $alerts[] = [
                    'tone' => 'warning',
                    'icon' => 'fa-user-slash',
                    'title' => __('ui.dashboard.teacher.alerts.inactive_teacher.title'),
                    'description' => __('ui.dashboard.teacher.alerts.inactive_teacher.description'),
                ];
            }
        }

        if ($alerts === []) {
            $alerts[] = [
                'tone' => 'success',
                'icon' => 'fa-circle-check',
                'title' => __('ui.dashboard.teacher.alerts.all_good.title'),
                'description' => __('ui.dashboard.teacher.alerts.all_good.description'),
            ];
        }

        return [
            'mode' => 'teacher',
            'headline' => __('ui.dashboard.teacher.title'),
            'school_name' => $school->name,
            'active_academic_year' => $activeYear,
            'welcome_panel' => [
                'title' => __('ui.dashboard.teacher.welcome.title', [
                    'name' => $this->firstName($user),
                ]),
                'subtitle' => $teacher
                    ? __('ui.dashboard.teacher.welcome.subtitle')
                    : __('ui.dashboard.teacher.welcome.no_profile'),
                'role' => __('ui.roles.enseignant'),
                'school_name' => Str::upper($school->name),
                'icon' => 'fa-chalkboard-user',
                'secondary_label' => $teacher
                    ? __('ui.dashboard.teacher.actions.open_profile')
                    : __('ui.layout.profile'),
                'secondary_route' => $teacher
                    ? route('people.teachers.show', $teacher)
                    : route('profile.edit'),
            ],
            'stats' => [
                [
                    'label' => __('ui.dashboard.teacher.stats.assignments'),
                    'value' => (string) $teachingLoadCount,
                    'icon' => 'fa-diagram-project',
                    'tone' => 'sky',
                ],
                [
                    'label' => __('ui.dashboard.teacher.stats.classes'),
                    'value' => (string) $classesCount,
                    'icon' => 'fa-school',
                    'tone' => 'emerald',
                ],
                [
                    'label' => __('ui.dashboard.teacher.stats.documents'),
                    'value' => (string) $documentsCount,
                    'icon' => 'fa-folder-open',
                    'tone' => 'violet',
                ],
            ],
            'alerts' => $alerts,
            'portal_profile' => $teacher ? [
                'section_label' => __('ui.dashboard.teacher.sections.profile'),
                'title' => $teacher->fullName(),
                'description' => __('people.teachers.show.description'),
                'status_label' => Teacher::statusOptions()[$teacher->status] ?? $teacher->status,
                'status_tone' => Teacher::statusTone($teacher->status),
                'fields' => [
                    [
                        'label' => __('people.teachers.form.academic_cycle'),
                        'value' => $teacher->academicCycle?->name ?: __('ui.dashboard.parent.not_provided'),
                    ],
                    [
                        'label' => __('people.teachers.show.track'),
                        'value' => $teacher->pedagogicTrackLabel(),
                    ],
                    [
                        'label' => $track === 'primary'
                            ? __('people.teachers.form.main_classroom')
                            : ($track === 'secondary'
                                ? __('people.teachers.form.main_subject')
                                : __('people.teachers.show.placement')),
                        'value' => $track === 'primary'
                            ? ($teacher->mainClassroom?->label() ?? $teacher->mainClassroom?->name ?? __('ui.dashboard.parent.not_provided'))
                            : ($track === 'secondary'
                                ? ($teacher->mainSubject?->name ?: __('ui.dashboard.parent.not_provided'))
                                : ($teacher->specialty ?: __('ui.dashboard.parent.not_provided'))),
                    ],
                    [
                        'label' => __('people.teachers.form.phone'),
                        'value' => $teacher->phone ?: __('ui.dashboard.parent.not_provided'),
                    ],
                    [
                        'label' => __('people.teachers.form.email'),
                        'value' => $teacher->email ?: __('ui.dashboard.parent.not_provided'),
                    ],
                    [
                        'label' => __('people.teachers.form.address'),
                        'value' => $teacher->address ?: __('ui.dashboard.parent.not_provided'),
                    ],
                    [
                        'label' => __('people.teachers.form.specialty'),
                        'value' => $teacher->specialty ?: __('ui.dashboard.parent.not_provided'),
                    ],
                    [
                        'label' => __('people.teachers.form.diploma'),
                        'value' => $teacher->diploma ?: __('ui.dashboard.parent.not_provided'),
                    ],
                    [
                        'label' => __('people.teachers.form.contract_type'),
                        'value' => Teacher::contractOptions()[$teacher->contract_type] ?? $teacher->contract_type,
                    ],
                ],
                'route' => route('people.teachers.show', $teacher).'#pedagogic-profile',
                'action_label' => __('ui.dashboard.teacher.actions.open_profile'),
                'empty_title' => __('ui.dashboard.teacher.empty_profile_title'),
                'empty_description' => __('ui.dashboard.teacher.empty_profile_description'),
                'empty_icon' => 'fa-chalkboard-user',
            ] : [
                'section_label' => __('ui.dashboard.teacher.sections.profile'),
                'title' => __('ui.dashboard.teacher.empty_profile_title'),
                'description' => __('ui.dashboard.teacher.empty_profile_description'),
                'status_label' => null,
                'status_tone' => null,
                'fields' => [],
                'route' => route('profile.edit'),
                'action_label' => __('ui.layout.profile'),
                'empty_title' => __('ui.dashboard.teacher.empty_profile_title'),
                'empty_description' => __('ui.dashboard.teacher.empty_profile_description'),
                'empty_icon' => 'fa-chalkboard-user',
            ],
            'portal_related' => [
                'section_label' => __('ui.dashboard.teacher.sections.related'),
                'title' => __('ui.dashboard.teacher.assignments_title'),
                'description' => __('ui.dashboard.teacher.assignments_description'),
                'action_label' => $teacher ? __('ui.dashboard.teacher.actions.open_assignments') : null,
                'action_route' => $teacher ? route('people.teachers.show', $teacher).'#assignments' : null,
                'items' => $relatedItems->all(),
                'empty_title' => __('ui.dashboard.teacher.empty_related_title'),
                'empty_description' => __('ui.dashboard.teacher.empty_related_description'),
                'empty_icon' => 'fa-diagram-project',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStudentPortal(User $user): array
    {
        $activeYear = AcademicYear::query()->active()->value('name') ?? __('ui.dashboard.not_configured');
        $school = $this->schoolContext->current();

        $user->loadMissing([
            'student.currentClassroom.level.cycle',
            'student.parents.currentClassroom.level.cycle',
            'student.documents',
            'roles',
        ]);

        $student = $user->student;
        $parents = $student
            ? $student->parents
                ->sortByDesc(fn (Guardian $guardian): bool => (bool) $guardian->pivot?->is_primary_contact)
                ->values()
            : collect();
        $documentsCount = (int) ($student?->documents?->count() ?? 0);
        $classroomLabel = $student?->currentClassroom?->name ?: __('ui.dashboard.parent.no_class');

        $alerts = [];

        if (! $student) {
            $alerts[] = [
                'tone' => 'warning',
                'icon' => 'fa-user-graduate',
                'title' => __('ui.dashboard.student.alerts.profile_missing.title'),
                'description' => __('ui.dashboard.student.alerts.profile_missing.description'),
            ];
        } elseif ($parents->isEmpty()) {
            $alerts[] = [
                'tone' => 'warning',
                'icon' => 'fa-people-roof',
                'title' => __('ui.dashboard.student.alerts.no_parents.title'),
                'description' => __('ui.dashboard.student.alerts.no_parents.description'),
            ];
        } else {
            if ($student->currentClassroom === null) {
                $alerts[] = [
                    'tone' => 'info',
                    'icon' => 'fa-school',
                    'title' => __('ui.dashboard.student.alerts.without_class.title'),
                    'description' => __('ui.dashboard.student.alerts.without_class.description'),
                ];
            }

            if (! $student->isActiveStatus()) {
                $alerts[] = [
                    'tone' => 'warning',
                    'icon' => 'fa-user-slash',
                    'title' => __('ui.dashboard.student.alerts.inactive_student.title'),
                    'description' => __('ui.dashboard.student.alerts.inactive_student.description'),
                ];
            }
        }

        if ($alerts === []) {
            $alerts[] = [
                'tone' => 'success',
                'icon' => 'fa-circle-check',
                'title' => __('ui.dashboard.student.alerts.all_good.title'),
                'description' => __('ui.dashboard.student.alerts.all_good.description'),
            ];
        }

        return [
            'mode' => 'student',
            'headline' => __('ui.dashboard.student.title'),
            'school_name' => $school->name,
            'active_academic_year' => $activeYear,
            'welcome_panel' => [
                'title' => __('ui.dashboard.student.welcome.title', [
                    'name' => $this->firstName($user),
                ]),
                'subtitle' => $student
                    ? __('ui.dashboard.student.welcome.subtitle')
                    : __('ui.dashboard.student.welcome.no_profile'),
                'role' => __('ui.roles.eleve'),
                'school_name' => Str::upper($school->name),
                'icon' => 'fa-user-graduate',
                'secondary_label' => $student
                    ? __('ui.dashboard.student.actions.open_profile')
                    : __('ui.layout.profile'),
                'secondary_route' => $student
                    ? route('people.students.show', $student)
                    : route('profile.edit'),
            ],
            'stats' => [
                [
                    'label' => __('ui.dashboard.student.stats.parents'),
                    'value' => (string) $parents->count(),
                    'icon' => 'fa-people-roof',
                    'tone' => 'sky',
                ],
                [
                    'label' => __('ui.dashboard.student.stats.documents'),
                    'value' => (string) $documentsCount,
                    'icon' => 'fa-folder-open',
                    'tone' => 'violet',
                ],
                [
                    'label' => __('ui.dashboard.student.stats.classroom'),
                    'value' => $classroomLabel,
                    'icon' => 'fa-school',
                    'tone' => 'emerald',
                ],
            ],
            'alerts' => $alerts,
            'portal_profile' => $student ? [
                'section_label' => __('ui.dashboard.student.sections.profile'),
                'title' => $student->fullName(),
                'description' => __('people.students.show.description'),
                'status_label' => Student::statusOptions()[$student->status] ?? $student->status,
                'status_tone' => Student::statusTone($student->status),
                'fields' => [
                    [
                        'label' => __('people.students.form.matricule'),
                        'value' => $student->matricule,
                    ],
                    [
                        'label' => __('people.students.form.gender'),
                        'value' => Student::genderOptions()[$student->gender] ?? $student->gender,
                    ],
                    [
                        'label' => __('people.students.form.date_of_birth'),
                        'value' => $student->date_of_birth?->translatedFormat('d M Y') ?? __('ui.dashboard.parent.not_provided'),
                    ],
                    [
                        'label' => __('people.students.form.place_of_birth'),
                        'value' => $student->place_of_birth ?: __('ui.dashboard.parent.not_provided'),
                    ],
                    [
                        'label' => __('people.students.form.nationality'),
                        'value' => $student->nationality ?: __('ui.dashboard.parent.not_provided'),
                    ],
                    [
                        'label' => __('people.students.form.classroom'),
                        'value' => $classroomLabel,
                    ],
                    [
                        'label' => __('people.students.form.academic_year'),
                        'value' => $student->entryAcademicYear?->name ?: __('ui.dashboard.not_configured'),
                    ],
                    [
                        'label' => __('ui.dashboard.student.stats.parents'),
                        'value' => (string) $parents->count(),
                    ],
                    [
                        'label' => __('ui.dashboard.student.stats.documents'),
                        'value' => (string) $documentsCount,
                    ],
                ],
                'route' => route('people.students.show', $student),
                'action_label' => __('ui.dashboard.student.actions.open_profile'),
                'empty_title' => __('ui.dashboard.student.empty_profile_title'),
                'empty_description' => __('ui.dashboard.student.empty_profile_description'),
                'empty_icon' => 'fa-user-graduate',
            ] : [
                'section_label' => __('ui.dashboard.student.sections.profile'),
                'title' => __('ui.dashboard.student.empty_profile_title'),
                'description' => __('ui.dashboard.student.empty_profile_description'),
                'status_label' => null,
                'status_tone' => null,
                'fields' => [],
                'route' => route('profile.edit'),
                'action_label' => __('ui.layout.profile'),
                'empty_title' => __('ui.dashboard.student.empty_profile_title'),
                'empty_description' => __('ui.dashboard.student.empty_profile_description'),
                'empty_icon' => 'fa-user-graduate',
            ],
            'portal_related' => [
                'section_label' => __('ui.dashboard.student.sections.related'),
                'title' => __('ui.dashboard.student.parents_title'),
                'description' => __('ui.dashboard.student.parents_description'),
                'action_label' => $student ? __('ui.dashboard.student.actions.open_parents') : null,
                'action_route' => $student ? route('people.students.show', $student).'#parents' : null,
                'items' => $parents
                    ->map(function (Guardian $guardian): array {
                        $relationshipLabels = Student::relationshipOptions();

                        return [
                            'title' => $guardian->fullName(),
                            'subtitle' => $guardian->phone ?: $guardian->email ?: __('ui.dashboard.parent.not_provided'),
                            'badges' => array_values(array_filter([
                                [
                                    'label' => $relationshipLabels[$guardian->pivot?->relationship_type ?? 'autre'] ?? ($guardian->pivot?->relationship_type ?? __('ui.roles.user')),
                                    'tone' => 'sky',
                                ],
                                $guardian->pivot?->is_primary_contact ? [
                                    'label' => __('people.students.show.primary_contact'),
                                    'tone' => 'emerald',
                                ] : null,
                                $guardian->pivot?->is_financial_responsible ? [
                                    'label' => __('people.students.show.financial_responsible'),
                                    'tone' => 'amber',
                                ] : null,
                                $guardian->pivot?->can_receive_notifications ? [
                                    'label' => __('people.students.show.notifications'),
                                    'tone' => 'violet',
                                ] : null,
                                $guardian->pivot?->can_pickup_child ? [
                                    'label' => __('people.students.show.pickup'),
                                    'tone' => 'amber',
                                ] : null,
                                [
                                    'label' => \App\Models\Guardian::statusOptions()[$guardian->status] ?? $guardian->status,
                                    'tone' => \App\Models\Guardian::statusTone($guardian->status),
                                ],
                            ])),
                            'route' => null,
                            'action_label' => null,
                        ];
                    })
                    ->all(),
                'empty_title' => __('ui.dashboard.student.empty_related_title'),
                'empty_description' => __('ui.dashboard.student.empty_related_description'),
                'empty_icon' => 'fa-people-roof',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildParentPortal(User $user): array
    {
        $activeYear = AcademicYear::query()->active()->value('name') ?? __('ui.dashboard.not_configured');
        $school = $this->schoolContext->current();

        $user->loadMissing([
            'guardian.students.currentClassroom.level.cycle',
            'guardian.documents',
            'roles',
        ]);

        $guardian = $user->guardian;
        $children = $guardian
            ? $guardian->students
                ->sortByDesc(fn (Student $student): bool => (bool) $student->pivot?->is_primary_contact)
                ->values()
            : collect();

        $childrenWithoutClass = $children->filter(fn (Student $student): bool => $student->currentClassroom === null)->count();
        $inactiveChildren = $children->reject(fn (Student $student): bool => $student->isActiveStatus())->count();

        $alerts = [];

        if (! $guardian) {
            $alerts[] = [
                'tone' => 'warning',
                'icon' => 'fa-user-shield',
                'title' => __('ui.dashboard.parent.alerts.profile_missing.title'),
                'description' => __('ui.dashboard.parent.alerts.profile_missing.description'),
            ];
        } elseif ($children->isEmpty()) {
            $alerts[] = [
                'tone' => 'warning',
                'icon' => 'fa-user-graduate',
                'title' => __('ui.dashboard.parent.alerts.no_children.title'),
                'description' => __('ui.dashboard.parent.alerts.no_children.description'),
            ];
        } else {
            if ($childrenWithoutClass > 0) {
                $alerts[] = [
                    'tone' => 'info',
                    'icon' => 'fa-school',
                    'title' => __('ui.dashboard.parent.alerts.without_class.title'),
                    'description' => __('ui.dashboard.parent.alerts.without_class.description', [
                        'count' => $childrenWithoutClass,
                    ]),
                ];
            }

            if ($inactiveChildren > 0) {
                $alerts[] = [
                    'tone' => 'warning',
                    'icon' => 'fa-circle-exclamation',
                    'title' => __('ui.dashboard.parent.alerts.inactive_children.title'),
                    'description' => __('ui.dashboard.parent.alerts.inactive_children.description', [
                        'count' => $inactiveChildren,
                    ]),
                ];
            }
        }

        if ($alerts === []) {
            $alerts[] = [
                'tone' => 'success',
                'icon' => 'fa-circle-check',
                'title' => __('ui.dashboard.parent.alerts.all_good.title'),
                'description' => __('ui.dashboard.parent.alerts.all_good.description'),
            ];
        }

        return [
            'mode' => 'parent',
            'headline' => __('ui.dashboard.parent.title'),
            'school_name' => $school->name,
            'active_academic_year' => $activeYear,
            'welcome_panel' => [
                'title' => __('ui.dashboard.parent.welcome.title', [
                    'name' => $this->firstName($user),
                ]),
                'subtitle' => $guardian
                    ? __('ui.dashboard.parent.welcome.subtitle')
                    : __('ui.dashboard.parent.welcome.no_profile'),
                'role' => __('ui.roles.parent'),
                'school_name' => Str::upper($school->name),
                'icon' => 'fa-people-roof',
                'secondary_label' => $guardian
                    ? __('ui.dashboard.parent.actions.open_profile')
                    : __('ui.layout.profile'),
                'secondary_route' => $guardian
                    ? route('people.parents.show', $guardian)
                    : route('profile.edit'),
            ],
            'stats' => [
                [
                    'label' => __('ui.dashboard.parent.stats.children'),
                    'value' => (string) $children->count(),
                    'icon' => 'fa-user-graduate',
                    'tone' => 'sky',
                ],
                [
                    'label' => __('ui.dashboard.parent.stats.active_children'),
                    'value' => (string) $children->filter(fn (Student $student): bool => $student->isActiveStatus())->count(),
                    'icon' => 'fa-circle-check',
                    'tone' => 'emerald',
                ],
                [
                    'label' => __('ui.dashboard.parent.stats.documents'),
                    'value' => (string) ($guardian?->documents?->count() ?? 0),
                    'icon' => 'fa-file-shield',
                    'tone' => 'violet',
                ],
            ],
            'alerts' => $alerts,
            'parent_profile' => $guardian ? [
                'full_name' => $guardian->fullName(),
                'phone' => $guardian->phone ?: __('ui.dashboard.parent.not_provided'),
                'email' => $guardian->email ?: __('ui.dashboard.parent.not_provided'),
                'address' => $guardian->address ?: __('ui.dashboard.parent.not_provided'),
                'profession' => $guardian->profession ?: __('ui.dashboard.parent.not_provided'),
                'status_label' => Guardian::statusOptions()[$guardian->status] ?? $guardian->status,
                'status_tone' => Guardian::statusTone($guardian->status),
                'children_count' => $children->count(),
                'documents_count' => $guardian->documents->count(),
                'route' => route('people.parents.show', $guardian),
            ] : null,
            'children' => $children
                ->map(function (Student $student): array {
                    $relationshipLabels = Student::relationshipOptions();

                    return [
                        'id' => $student->id,
                        'name' => $student->fullName(),
                        'matricule' => $student->matricule,
                        'classroom' => $student->currentClassroom?->name ?: __('ui.dashboard.parent.no_class'),
                        'cycle' => $student->currentClassroom?->level?->cycle?->name,
                        'status_label' => Student::statusOptions()[$student->status] ?? $student->status,
                        'status_tone' => Student::statusTone($student->status),
                        'relationship_label' => $relationshipLabels[$student->pivot?->relationship_type ?? 'autre'] ?? ($student->pivot?->relationship_type ?? __('ui.roles.user')),
                        'primary_contact' => (bool) ($student->pivot?->is_primary_contact ?? false),
                        'financial_responsible' => (bool) ($student->pivot?->is_financial_responsible ?? false),
                        'notifications' => (bool) ($student->pivot?->can_receive_notifications ?? false),
                        'pickup' => (bool) ($student->pivot?->can_pickup_child ?? false),
                        'route' => route('people.students.show', $student),
                    ];
                })
                ->all(),
            'portal_profile' => $guardian ? [
                'section_label' => __('ui.dashboard.parent.sections.profile'),
                'title' => $guardian->fullName(),
                'description' => __('people.parents.show.description'),
                'status_label' => Guardian::statusOptions()[$guardian->status] ?? $guardian->status,
                'status_tone' => Guardian::statusTone($guardian->status),
                'fields' => [
                    [
                        'label' => __('people.parents.form.phone'),
                        'value' => $guardian->phone ?: __('ui.dashboard.parent.not_provided'),
                    ],
                    [
                        'label' => __('people.parents.form.email'),
                        'value' => $guardian->email ?: __('ui.dashboard.parent.not_provided'),
                    ],
                    [
                        'label' => __('people.parents.form.address'),
                        'value' => $guardian->address ?: __('ui.dashboard.parent.not_provided'),
                    ],
                    [
                        'label' => __('people.parents.form.profession'),
                        'value' => $guardian->profession ?: __('ui.dashboard.parent.not_provided'),
                    ],
                    [
                        'label' => __('people.parents.index.table.children'),
                        'value' => (string) $children->count(),
                    ],
                    [
                        'label' => __('people.documents.meta.count'),
                        'value' => (string) $guardian->documents->count(),
                    ],
                ],
                'route' => route('people.parents.show', $guardian),
                'action_label' => __('ui.dashboard.parent.actions.open_profile'),
                'empty_title' => __('ui.dashboard.parent.empty_profile_title'),
                'empty_description' => __('ui.dashboard.parent.empty_profile_description'),
                'empty_icon' => 'fa-people-roof',
            ] : [
                'section_label' => __('ui.dashboard.parent.sections.profile'),
                'title' => __('ui.dashboard.parent.empty_profile_title'),
                'description' => __('ui.dashboard.parent.empty_profile_description'),
                'status_label' => null,
                'status_tone' => null,
                'fields' => [],
                'route' => route('profile.edit'),
                'action_label' => __('ui.layout.profile'),
                'empty_title' => __('ui.dashboard.parent.empty_profile_title'),
                'empty_description' => __('ui.dashboard.parent.empty_profile_description'),
                'empty_icon' => 'fa-people-roof',
            ],
            'portal_related' => [
                'section_label' => __('ui.dashboard.parent.sections.children'),
                'title' => __('ui.dashboard.parent.children_title'),
                'description' => __('ui.dashboard.parent.children_description'),
                'action_label' => $guardian ? __('people.parents.show.attach_child_action') : null,
                'action_route' => $guardian ? route('people.parents.show', $guardian).'#child-link-form' : null,
                'items' => $children
                    ->map(function (Student $student): array {
                        $relationshipLabels = Student::relationshipOptions();

                        return [
                            'title' => $student->fullName(),
                            'subtitle' => $student->matricule.' · '.($student->currentClassroom?->name ?: __('ui.dashboard.parent.no_class')),
                            'badges' => array_values(array_filter([
                                [
                                    'label' => $relationshipLabels[$student->pivot?->relationship_type ?? 'autre'] ?? ($student->pivot?->relationship_type ?? __('ui.roles.user')),
                                    'tone' => 'sky',
                                ],
                                $student->pivot?->is_primary_contact ? [
                                    'label' => __('people.parents.show.primary_contact'),
                                    'tone' => 'emerald',
                                ] : null,
                                $student->pivot?->is_financial_responsible ? [
                                    'label' => __('people.parents.show.financial_responsible'),
                                    'tone' => 'amber',
                                ] : null,
                                $student->pivot?->can_receive_notifications ? [
                                    'label' => __('people.parents.show.notifications'),
                                    'tone' => 'violet',
                                ] : null,
                                $student->pivot?->can_pickup_child ? [
                                    'label' => __('people.parents.show.pickup'),
                                    'tone' => 'amber',
                                ] : null,
                                [
                                    'label' => Student::statusOptions()[$student->status] ?? $student->status,
                                    'tone' => Student::statusTone($student->status),
                                ],
                            ])),
                            'route' => route('people.students.show', $student),
                            'action_label' => __('people.parents.show.view_student'),
                        ];
                    })
                    ->all(),
                'empty_title' => __('ui.dashboard.parent.empty_children_title'),
                'empty_description' => __('ui.dashboard.parent.empty_children_description'),
                'empty_icon' => 'fa-user-graduate',
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, meta: string, actor: string, action: string}>
     */
    private function recentActivityFor(User $user): array
    {
        $query = AuditLog::query()
            ->with('user:id,name')
            ->latest('created_at')
            ->limit(4);

        if (! $user->hasPermissionTo('core.audit.view')) {
            $query->where('user_id', $user->id);
        }

        return $query
            ->get()
            ->map(function (AuditLog $log): array {
                $title = $log->description
                    ?: Str::headline(str_replace(['.', '_', '-'], ' ', $log->action));

                return [
                    'title' => $title,
                    'meta' => $this->formatAuditModule($log->module).' · '.$log->created_at?->diffForHumans(),
                    'actor' => $log->user?->name ?? __('ui.roles.user'),
                    'action' => Str::upper(Str::substr($title, 0, 1)),
                ];
            })
            ->all();
    }

    private function formatAuditModule(?string $module): string
    {
        if (! $module) {
            return __('ui.dashboard.recent_activity.system');
        }

        return Str::headline(str_replace(['.', '_', '-'], ' ', $module));
    }

    /**
     * @return array{title: string, subtitle: string, role: string, icon: string, secondary_label: string, secondary_route: string}
     */
    private function welcomePanelFor(User $user, string $schoolName): array
    {
        $role = $user->roles->sortBy('name')->first();
        $roleSlug = $role?->slug ?? 'user';
        $roleLabel = $this->localizedRoleLabel($roleSlug, $role?->name);
        $secondaryAction = $this->welcomeSecondaryActionFor($user);

        return [
            'title' => __('ui.dashboard.welcome.title', [
                'name' => $this->firstName($user),
            ]),
            'subtitle' => $this->welcomeSubtitleForRole($roleSlug),
            'role' => $roleLabel,
            'school_name' => Str::upper($schoolName),
            'icon' => 'fa-hand',
            'secondary_label' => $secondaryAction['label'],
            'secondary_route' => $secondaryAction['route'],
        ];
    }

    private function firstName(User $user): string
    {
        return Str::of($user->name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->first() ?? $user->name;
    }

    private function localizedRoleLabel(string $roleSlug, ?string $fallback = null): string
    {
        $translationKey = "ui.roles.{$roleSlug}";

        if (Lang::has($translationKey)) {
            return __($translationKey);
        }

        return $fallback ?: __('ui.roles.user');
    }

    private function welcomeSubtitleForRole(string $roleSlug): string
    {
        $translationKey = "ui.dashboard.welcome.descriptions.{$roleSlug}";

        if (Lang::has($translationKey)) {
            return __($translationKey);
        }

        return __('ui.dashboard.welcome.descriptions.user');
    }

    /**
     * @return array{label: string, route: string}
     */
    private function welcomeSecondaryActionFor(User $user): array
    {
        if ($user->hasPermissionTo('core.settings.manage')) {
            return [
                'label' => __('ui.dashboard.welcome.actions.settings'),
                'route' => route('core.settings.edit'),
            ];
        }

        if ($user->hasPermissionTo('core.users.view')) {
            return [
                'label' => __('ui.dashboard.welcome.actions.users'),
                'route' => route('core.users.index'),
            ];
        }

        if ($user->hasPermissionTo('core.academic_years.view')) {
            return [
                'label' => __('ui.dashboard.welcome.actions.years'),
                'route' => route('core.academic-years.index'),
            ];
        }

        return [
            'label' => __('ui.dashboard.welcome.actions.profile'),
            'route' => route('profile.edit'),
        ];
    }

    private function peopleCountForSchool(int $schoolId): int
    {
        $total = 0;

        if (Schema::hasTable((new Student())->getTable())) {
            $total += Student::query()->forSchool($schoolId)->count();
        }

        if (Schema::hasTable((new Guardian())->getTable())) {
            $total += Guardian::query()->forSchool($schoolId)->count();
        }

        return $total;
    }
}
