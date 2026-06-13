@php
    $sections = app(\App\Support\Navigation\NavigationBuilder::class)->forUser(Auth::user());
    $generalSection = collect($sections)->firstWhere('key', 'general');
    $navigationSections = collect($sections)->reject(fn (array $section): bool => $section['key'] === 'general')->values();
    $isPortalMode = Auth::user()?->isSchoolPortalUser() ?? false;
    $sidebarRoleName = Auth::user()?->portalSpaceLabel() ?? ($shell['user_role'] ?? __('ui.roles.user'));
    $footerRoleName = $sidebarRoleName;
    $activeAcademicYearId = $shell['active_academic_year_id'] ?? null;
    $previewSections = [
        [
            'title' => __('ui.preview.schooling'),
            'items' => [
                ['label' => __('ui.preview.buildings'), 'icon' => 'fa-building'],
                ['label' => __('ui.preview.rooms'), 'icon' => 'fa-door-open'],
                ['label' => __('ui.preview.subjects'), 'icon' => 'fa-book-open'],
                ['label' => __('ui.preview.classes'), 'icon' => 'fa-school'],
                ['label' => __('ui.preview.courses'), 'icon' => 'fa-chalkboard-user'],
                ['label' => __('ui.preview.assessments'), 'icon' => 'fa-clipboard-check'],
                ['label' => __('ui.preview.report_cards'), 'icon' => 'fa-file-lines'],
                ['label' => __('ui.preview.students'), 'icon' => 'fa-user-graduate'],
                ['label' => __('ui.preview.parents'), 'icon' => 'fa-people-roof'],
                ['label' => __('ui.preview.pending_registrations'), 'icon' => 'fa-hourglass-half'],
            ],
        ],
        [
            'title' => __('ui.preview.staff'),
            'items' => [
                ['label' => __('ui.preview.teachers'), 'icon' => 'fa-user-tie'],
            ],
        ],
    ];
@endphp

<div
    x-show="sidebarOpen"
    x-transition.opacity.duration.200ms
    class="fixed inset-0 z-30 bg-gray-950/40 backdrop-blur-sm lg:hidden"
    @click="sidebarOpen = false"
    x-cloak
></div>

<aside
    class="soft-scrollbar fixed bottom-0 left-0 top-16 z-40 w-60 -translate-x-full overflow-x-hidden overflow-y-auto border-r border-gray-100 bg-white shadow-sm shadow-slate-900/5 transition-all duration-300 dark:border-gray-800 dark:bg-gray-900 dark:shadow-none lg:translate-x-0"
    :class="{ 'translate-x-0': sidebarOpen, 'lg:w-20': sidebarCollapsed, 'lg:w-60': ! sidebarCollapsed }"
>
    <div class="p-4" :class="sidebarCollapsed ? 'lg:px-2' : ''">
        <div class="mb-5 px-1" :class="sidebarCollapsed ? 'lg:hidden' : ''">
            <span class="inline-flex items-center rounded-full bg-sky-100/90 px-2.5 py-1 text-[11px] font-semibold tracking-[0.06em] text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">
                {{ $sidebarRoleName }}
            </span>
        </div>

        @if ($generalSection)
            @php($dashboardItem = collect($generalSection['items'])->firstWhere('route', 'dashboard'))

            @if ($dashboardItem)
                <a
                    href="{{ route($dashboardItem['route']) }}"
                    class="mb-1 flex items-center gap-3 rounded-lg bg-gradient-to-r from-sky-500 to-blue-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition-all duration-200 hover:shadow-md"
                    :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                >
                    <i class="fa-solid {{ $dashboardItem['icon'] ?? 'fa-table-cells-large' }} text-sm"></i>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $dashboardItem['label'] }}</span>
                </a>
            @endif
        @endif

        @unless ($isPortalMode)
            @foreach ($previewSections as $section)
                <p class="mb-2 mt-4 px-3 text-[10px] font-bold uppercase tracking-[0.3em] text-gray-400" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $section['title'] }}</p>
                <div class="space-y-0.5">
                    @foreach ($section['items'] as $item)
                        <div class="flex cursor-default items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition-all duration-200 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800" :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''">
                            <i class="fa-solid {{ $item['icon'] }} w-5 text-gray-400 dark:text-gray-500"></i>
                            <span :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $item['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endforeach
        @endunless

        @foreach ($navigationSections as $section)
            <p class="mb-2 mt-5 px-3 text-[10px] font-bold uppercase tracking-[0.3em] text-gray-400" :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $section['title'] }}</p>

            @if ($section['key'] === 'academic_years' && $activeAcademicYearId && Auth::user()?->hasPermissionTo('core.academic_years.view'))
                <a
                    href="{{ route('core.academic-years.show', $activeAcademicYearId) }}"
                    class="mb-2 flex items-center gap-3 rounded-xl border border-sky-100 bg-sky-50/90 px-3 py-2 text-sm font-medium text-sky-700 transition-all duration-200 hover:border-sky-200 hover:bg-sky-100 dark:border-sky-900/40 dark:bg-sky-950/40 dark:text-sky-300 dark:hover:border-sky-800 dark:hover:bg-sky-950/70"
                    :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                >
                    <i class="fa-solid fa-calendar-days w-5 text-sky-500 dark:text-sky-300"></i>
                    <span :class="sidebarCollapsed ? 'lg:hidden' : ''">
                        {{ __('ui.nav.academic_year_hub') }}
                    </span>
                </a>
            @endif

            <div class="space-y-0.5">
                @foreach ($section['items'] as $item)
                    @php($active = request()->routeIs($item['pattern'] ?? $item['route']))
                    <a
                        href="{{ $item['url'] ?? route($item['route']) }}"
                        class="{{ $active ? 'bg-gray-100 text-sky-700 dark:bg-gray-800 dark:text-sky-300' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800' }} flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-200"
                        :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''"
                    >
                        <i class="fa-solid {{ $item['icon'] ?? 'fa-circle' }} w-5 {{ $active ? 'text-sky-600 dark:text-sky-300' : 'text-gray-400 dark:text-gray-500' }}"></i>
                        <span :class="sidebarCollapsed ? 'lg:hidden' : ''">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="border-t border-gray-100 p-4 dark:border-gray-800">
        <div class="rounded-2xl bg-gray-50 px-4 py-4 transition-colors duration-300 dark:bg-gray-800" :class="sidebarCollapsed ? 'lg:px-0 lg:py-3' : ''">
            <div class="flex items-center gap-3" :class="sidebarCollapsed ? 'lg:justify-center' : ''">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-sky-100 text-sm font-bold uppercase tracking-[0.16em] text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">
                    {{ $shell['user_initials'] }}
                </span>
                <div :class="sidebarCollapsed ? 'lg:hidden' : ''">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $shell['user_name'] }}</p>
                    <p class="mt-1 text-xs tracking-[0.08em] text-gray-500 dark:text-gray-400">{{ $footerRoleName }}</p>
                </div>
            </div>
        </div>
    </div>
</aside>
