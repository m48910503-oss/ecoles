<?php

namespace App\Support\Navigation;

use App\Models\Guardian;
use App\Models\User;

class NavigationBuilder
{
    /**
     * @return array<int, array{key: string, title: string, items: array<int, array<string, string|null>>}>
     */
    public function forUser(User $user): array
    {
        if ($user->isParentPortalUser()) {
            return $this->forParentPortal($user);
        }

        if ($user->isTeacherPortalUser()) {
            return $this->forTeacherPortal($user);
        }

        if ($user->isStudentPortalUser()) {
            return $this->forStudentPortal($user);
        }

        $sections = [
            [
                'key' => 'general',
                'title' => __('ui.nav.general'),
                'items' => [
                    ['label' => __('ui.nav.dashboard'), 'route' => 'dashboard', 'pattern' => 'dashboard', 'permission' => 'core.dashboard.view', 'icon' => 'fa-table-cells-large'],
                    ['label' => __('ui.nav.profile'), 'route' => 'profile.edit', 'pattern' => 'profile.*', 'permission' => null, 'icon' => 'fa-user'],
                ],
            ],
            [
                'key' => 'people_management',
                'title' => __('ui.nav.people_management'),
                'items' => [
                    ['label' => __('ui.nav.people_dashboard'), 'route' => 'people.dashboard', 'pattern' => 'people.dashboard', 'permission' => 'people.dashboard.view', 'icon' => 'fa-people-group'],
                    ['label' => __('ui.nav.students'), 'route' => 'people.students.index', 'pattern' => 'people.students.*', 'permission' => 'people.students.view', 'icon' => 'fa-user-graduate'],
                    ['label' => __('ui.nav.parents'), 'route' => 'people.parents.index', 'pattern' => 'people.parents.*', 'permission' => 'people.parents.view', 'icon' => 'fa-people-roof'],
                    ['label' => __('ui.nav.staff'), 'route' => 'people.staff.index', 'pattern' => 'people.staff.*', 'permission' => 'people.staff.view', 'icon' => 'fa-people-carry-box'],
                    ['label' => __('ui.nav.teachers'), 'route' => 'people.teachers.index', 'pattern' => 'people.teachers.*', 'permission' => 'people.teachers.view', 'icon' => 'fa-chalkboard-user'],
                ],
            ],
            [
                'key' => 'academic_reference',
                'title' => __('ui.nav.academic_reference'),
                'items' => [
                    ['label' => __('ui.nav.academic_dashboard'), 'route' => 'academic.dashboard', 'pattern' => 'academic.dashboard', 'permission' => 'academic.cycles.view|academic.cycles.manage|academic.levels.view|academic.levels.manage|academic.series.view|academic.series.manage|academic.classes.view|academic.classes.manage|academic.subjects.view|academic.subjects.manage|academic.coefficients.view|academic.coefficients.manage|academic.conduct.view|academic.conduct.manage|academic.config.view|academic.config.manage|academic.periods.view|academic.periods.manage|academic.day_config.view|academic.day_config.manage|academic.calendars.view|academic.calendars.manage|academic.events.view|academic.events.view_public|academic.events.view_internal|academic.events.manage_all|core.academic_years.view', 'icon' => 'fa-compass-drafting'],
                    ['label' => __('ui.nav.academic_cycles'), 'route' => 'academic.cycles.index', 'pattern' => 'academic.cycles.*', 'permission' => 'academic.cycles.view|academic.cycles.manage', 'icon' => 'fa-diagram-project'],
                    ['label' => __('ui.nav.academic_levels'), 'route' => 'academic.levels.index', 'pattern' => 'academic.levels.*', 'permission' => 'academic.levels.view|academic.levels.manage', 'icon' => 'fa-layer-group'],
                    ['label' => __('ui.nav.academic_series'), 'route' => 'academic.series.index', 'pattern' => 'academic.series.*', 'permission' => 'academic.series.view|academic.series.manage', 'icon' => 'fa-sitemap'],
                    ['label' => __('ui.nav.academic_subjects'), 'route' => 'academic.subjects.index', 'pattern' => 'academic.subjects.*', 'permission' => 'academic.subjects.view|academic.subjects.manage', 'icon' => 'fa-book-open'],
                ],
            ],
            [
                'key' => 'academic_years',
                'title' => __('ui.nav.academic_years_section'),
                'items' => [
                    ['label' => __('ui.nav.academic_years_list'), 'route' => 'core.academic-years.index', 'pattern' => 'core.academic-years.*', 'permission' => 'core.academic_years.view', 'icon' => 'fa-calendar-days'],
                    ['label' => __('ui.nav.academic_conduct'), 'route' => 'academic.conduct.index', 'pattern' => 'academic.conduct.*', 'permission' => 'academic.conduct.view|academic.conduct.manage', 'icon' => 'fa-scale-balanced'],
                    ['label' => __('ui.nav.academic_configuration'), 'route' => 'academic.configuration.index', 'pattern' => 'academic.configuration.*', 'permission' => 'academic.config.view|academic.config.manage', 'icon' => 'fa-sliders'],
                    ['label' => __('ui.nav.academic_periods'), 'route' => 'academic.periods.index', 'pattern' => 'academic.periods.*', 'permission' => 'academic.periods.view|academic.periods.manage', 'icon' => 'fa-layer-group'],
                    ['label' => __('ui.nav.school_day_configurations'), 'route' => 'academic.day-configurations.index', 'pattern' => 'academic.day-configurations.*', 'permission' => 'academic.day_config.view|academic.day_config.manage', 'icon' => 'fa-clock'],
                    ['label' => __('ui.nav.academic_classes'), 'route' => 'academic.classes.index', 'pattern' => 'academic.classes.*', 'permission' => 'academic.classes.view|academic.classes.manage', 'icon' => 'fa-chalkboard'],
                    ['label' => __('ui.nav.academic_coefficients'), 'route' => 'academic.coefficients.index', 'pattern' => 'academic.coefficients.*', 'permission' => 'academic.coefficients.view|academic.coefficients.manage', 'icon' => 'fa-square-root-variable'],
                    ['label' => __('ui.nav.academic_calendars'), 'route' => 'academic.calendars.index', 'pattern' => 'academic.calendars.*', 'permission' => 'academic.calendars.view', 'icon' => 'fa-calendar-week'],
                    ['label' => __('ui.nav.calendar_events'), 'route' => 'academic.events.index', 'pattern' => 'academic.events.*', 'permission' => 'academic.events.view|academic.events.view_public|academic.events.view_internal|academic.events.manage_all', 'icon' => 'fa-calendar-check'],
                ],
            ],
            [
                'key' => 'core_system',
                'title' => __('ui.nav.core_system'),
                'items' => [
                    ['label' => __('ui.nav.users'), 'route' => 'core.users.index', 'pattern' => 'core.users.*', 'permission' => 'core.users.view', 'icon' => 'fa-users'],
                    ['label' => __('ui.nav.roles'), 'route' => 'core.roles.index', 'pattern' => 'core.roles.*', 'permission' => 'core.roles.view', 'icon' => 'fa-user-shield'],
                    ['label' => __('ui.nav.permissions'), 'route' => 'core.permissions.index', 'pattern' => 'core.permissions.*', 'permission' => 'core.roles.manage', 'icon' => 'fa-key'],
                    ['label' => __('ui.nav.schools'), 'route' => 'core.schools.index', 'pattern' => 'core.schools.*', 'permission' => 'core.schools.view', 'icon' => 'fa-building-columns'],
                    ['label' => __('ui.nav.settings'), 'route' => 'core.settings.edit', 'pattern' => 'core.settings.*', 'permission' => 'core.settings.manage', 'icon' => 'fa-sliders'],
                    ['label' => __('ui.nav.audit_logs'), 'route' => 'core.audit-logs.index', 'pattern' => 'core.audit-logs.*', 'permission' => 'core.audit.view', 'icon' => 'fa-file-shield'],
                ],
            ],
        ];

        return collect($sections)
            ->map(function (array $section) use ($user): array {
                $items = collect($section['items'])
                    ->filter(function (array $item) use ($user): bool {
                        if ($item['permission'] === null) {
                            return true;
                        }

                        $permissions = collect(explode('|', $item['permission']))
                            ->map(fn (string $permission): string => trim($permission))
                            ->filter()
                            ->values()
                            ->all();

                        return $user->hasAnyPermission($permissions);
                    })
                    ->values()
                    ->all();

                return [
                    'key' => $section['key'],
                    'title' => $section['title'],
                    'items' => $items,
                ];
            })
            ->filter(fn (array $section): bool => $section['items'] !== [])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{key: string, title: string, items: array<int, array<string, string|null>>}>
     */
    private function forParentPortal(User $user): array
    {
        $user->loadMissing('guardian');

        $sections = [
            [
                'key' => 'general',
                'title' => __('ui.nav.general'),
                'items' => $this->portalGeneralItems('ui.nav.parent_dashboard'),
            ],
            [
                'key' => 'parent_space',
                'title' => __('ui.nav.parent_space'),
                'items' => $this->parentPortalItems($user),
            ],
        ];

        return collect($sections)
            ->map(function (array $section) use ($user): array {
                $items = collect($section['items'])
                    ->filter(function (array $item) use ($user): bool {
                        if ($item['permission'] === null) {
                            return true;
                        }

                        $permissions = collect(explode('|', $item['permission']))
                            ->map(fn (string $permission): string => trim($permission))
                            ->filter()
                            ->values()
                            ->all();

                        return $user->hasAnyPermission($permissions);
                    })
                    ->values()
                    ->all();

                return [
                    'key' => $section['key'],
                    'title' => $section['title'],
                    'items' => $items,
                ];
            })
            ->filter(fn (array $section): bool => $section['items'] !== [])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{key: string, title: string, items: array<int, array<string, string|null>>}>
     */
    private function forTeacherPortal(User $user): array
    {
        $user->loadMissing('teacher');

        $sections = [
            [
                'key' => 'general',
                'title' => __('ui.nav.general'),
                'items' => $this->portalGeneralItems('ui.nav.teacher_dashboard'),
            ],
            [
                'key' => 'teacher_space',
                'title' => __('ui.nav.teacher_space'),
                'items' => $this->teacherPortalItems($user),
            ],
        ];

        return collect($sections)
            ->map(function (array $section) use ($user): array {
                $items = collect($section['items'])
                    ->filter(function (array $item) use ($user): bool {
                        if ($item['permission'] === null) {
                            return true;
                        }

                        $permissions = collect(explode('|', $item['permission']))
                            ->map(fn (string $permission): string => trim($permission))
                            ->filter()
                            ->values()
                            ->all();

                        return $user->hasAnyPermission($permissions);
                    })
                    ->values()
                    ->all();

                return [
                    'key' => $section['key'],
                    'title' => $section['title'],
                    'items' => $items,
                ];
            })
            ->filter(fn (array $section): bool => $section['items'] !== [])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{key: string, title: string, items: array<int, array<string, string|null>>}>
     */
    private function forStudentPortal(User $user): array
    {
        $user->loadMissing('student');

        $sections = [
            [
                'key' => 'general',
                'title' => __('ui.nav.general'),
                'items' => $this->portalGeneralItems('ui.nav.student_dashboard'),
            ],
            [
                'key' => 'student_space',
                'title' => __('ui.nav.student_space'),
                'items' => $this->studentPortalItems($user),
            ],
        ];

        return collect($sections)
            ->map(function (array $section) use ($user): array {
                $items = collect($section['items'])
                    ->filter(function (array $item) use ($user): bool {
                        if ($item['permission'] === null) {
                            return true;
                        }

                        $permissions = collect(explode('|', $item['permission']))
                            ->map(fn (string $permission): string => trim($permission))
                            ->filter()
                            ->values()
                            ->all();

                        return $user->hasAnyPermission($permissions);
                    })
                    ->values()
                    ->all();

                return [
                    'key' => $section['key'],
                    'title' => $section['title'],
                    'items' => $items,
                ];
            })
            ->filter(fn (array $section): bool => $section['items'] !== [])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function parentPortalItems(User $user): array
    {
        $items = [];

        if ($user->guardian instanceof Guardian) {
            $items[] = [
                'label' => __('ui.nav.parent_record'),
                'url' => route('people.parents.show', $user->guardian),
                'pattern' => 'people.parents.show',
                'permission' => null,
                'icon' => 'fa-id-card',
            ];
        }

        if ($user->guardian instanceof Guardian) {
            $items[] = [
                'label' => __('ui.nav.parent_documents'),
                'url' => route('people.parents.show', $user->guardian).'#documents',
                'pattern' => 'people.parents.show',
                'permission' => null,
                'icon' => 'fa-folder-open',
            ];
        }

        $items[] = [
            'label' => __('ui.nav.calendar_events'),
            'route' => 'academic.events.index',
            'pattern' => 'academic.events.*',
            'permission' => 'academic.events.view_public',
            'icon' => 'fa-calendar-check',
        ];

        return $items;
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function portalGeneralItems(string $dashboardLabelKey): array
    {
        return [
            [
                'label' => __($dashboardLabelKey),
                'route' => 'dashboard',
                'pattern' => 'dashboard',
                'permission' => 'core.dashboard.view',
                'icon' => 'fa-table-cells-large',
            ],
            [
                'label' => __('ui.nav.profile'),
                'route' => 'profile.edit',
                'pattern' => 'profile.*',
                'permission' => null,
                'icon' => 'fa-user',
            ],
        ];
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function teacherPortalItems(User $user): array
    {
        $items = [];

        if ($user->teacher) {
            $items[] = [
                'label' => __('ui.nav.teacher_record'),
                'url' => route('people.teachers.show', $user->teacher),
                'pattern' => 'people.teachers.show',
                'permission' => null,
                'icon' => 'fa-chalkboard-user',
            ];

            $items[] = [
                'label' => __('ui.nav.teacher_assignments'),
                'url' => route('people.teachers.show', $user->teacher).'#assignments',
                'pattern' => 'people.teachers.show',
                'permission' => null,
                'icon' => 'fa-diagram-project',
            ];

            $items[] = [
                'label' => __('ui.nav.teacher_documents'),
                'url' => route('people.teachers.show', $user->teacher).'#documents',
                'pattern' => 'people.teachers.show',
                'permission' => null,
                'icon' => 'fa-folder-open',
            ];
        }

        $items[] = [
            'label' => __('ui.nav.calendar_events'),
            'route' => 'academic.events.index',
            'pattern' => 'academic.events.*',
            'permission' => 'academic.events.view_public',
            'icon' => 'fa-calendar-check',
        ];

        return $items;
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function studentPortalItems(User $user): array
    {
        $items = [];

        if ($user->student) {
            $items[] = [
                'label' => __('ui.nav.student_record'),
                'url' => route('people.students.show', $user->student),
                'pattern' => 'people.students.show',
                'permission' => null,
                'icon' => 'fa-user-graduate',
            ];

            $items[] = [
                'label' => __('ui.nav.student_parents'),
                'url' => route('people.students.show', $user->student).'#parents',
                'pattern' => 'people.students.show',
                'permission' => null,
                'icon' => 'fa-people-roof',
            ];

            $items[] = [
                'label' => __('ui.nav.student_documents'),
                'url' => route('people.students.show', $user->student).'#documents',
                'pattern' => 'people.students.show',
                'permission' => null,
                'icon' => 'fa-folder-open',
            ];
        }

        $items[] = [
            'label' => __('ui.nav.calendar_events'),
            'route' => 'academic.events.index',
            'pattern' => 'academic.events.*',
            'permission' => 'academic.events.view_public',
            'icon' => 'fa-calendar-check',
        ];

        return $items;
    }
}
