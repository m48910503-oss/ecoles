<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$teacher->fullName()" :description="__('people.teachers.show.description')">
            <x-slot:actions>
                <x-ui.button :href="$backUrl" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>

                @can('update', $teacher)
                    <x-ui.button :href="route('people.teachers.edit', $teacher)" variant="warning">
                        {{ __('core.common.actions.edit') }}
                    </x-ui.button>
                @endcan

                @can('viewAccount', $teacher)
                    <x-ui.button :href="'#account'" variant="info">
                        {{ __('people.accounts.actions.view_section') }}
                    </x-ui.button>
                @endcan

                <x-ui.button :href="'#pedagogic-profile'" variant="info">
                    {{ __('people.teachers.show.pedagogic_title') }}
                </x-ui.button>

                @can('assignSubjects', $teacher)
                    <x-ui.button :href="'#assignments'" variant="info">
                        {{ __('people.teachers.show.assignments_title') }}
                    </x-ui.button>
                @endcan

                @can('viewDocuments', $teacher)
                    <x-ui.button :href="'#documents'" variant="info">
                        {{ __('people.documents.show_action') }}
                    </x-ui.button>
                @endcan

                @can('history', $teacher)
                    <x-ui.button :href="'#history'" variant="info">
                        {{ __('people.teachers.show.history_title') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    @php
        $genderLabels = \App\Models\Teacher::genderOptions();
        $statusLabels = \App\Models\Teacher::statusOptions();
        $contractLabels = \App\Models\Teacher::contractOptions();
        $pedagogicLoadCount = $teacher->assignments->count() + (($teacher->mainClassroom || $teacher->mainSubject) ? 1 : 0);
    @endphp

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('people.teachers.form.matricule')" :value="$teacher->matricule" icon="fa-hashtag" tone="sky" />
            <x-ui.stats-card :label="__('people.teachers.form.specialty')" :value="$teacher->specialty ?: '—'" icon="fa-book-open" tone="emerald" />
            <x-ui.stats-card :label="__('ui.dashboard.teacher.stats.assignments')" :value="$pedagogicLoadCount" icon="fa-diagram-project" tone="amber" />
            <x-ui.stats-card :label="__('people.teachers.form.contract_type')" :value="$contractLabels[$teacher->contract_type] ?? $teacher->contract_type" icon="fa-file-contract" tone="violet" />
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <x-ui.card class="space-y-6">
                <div class="flex items-start gap-4 border-b border-gray-100 pb-5 dark:border-gray-800">
                    <div class="flex h-20 w-20 items-center justify-center rounded-3xl bg-sky-100 text-lg font-bold text-sky-700 dark:bg-sky-500/15 dark:text-sky-200">
                        {{ $teacher->initials() }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ui.badge :tone="\App\Models\Teacher::statusTone($teacher->status)">
                                {{ $statusLabels[$teacher->status] ?? $teacher->status }}
                            </x-ui.badge>

                            @if ($teacher->latestInvitation)
                                <x-ui.badge :tone="$teacher->latestInvitation->statusToneValue()">
                                    {{ $teacher->latestInvitation->statusLabel() }}
                                </x-ui.badge>
                            @endif

                            @if ($teacher->user)
                                <x-ui.badge :tone="$teacher->user->is_active ? 'emerald' : 'rose'">
                                    {{ $teacher->user->is_active ? __('people.teachers.show.linked_account') : __('core.common.states.suspended') }}
                                </x-ui.badge>
                            @else
                                <x-ui.badge tone="slate">{{ __('people.teachers.show.no_account') }}</x-ui.badge>
                            @endif
                        </div>

                        <h2 class="mt-3 text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $teacher->fullName() }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $teacher->matricule }}</p>
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.teachers.form.gender') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $genderLabels[$teacher->gender] ?? $teacher->gender }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.teachers.form.contract_type') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $contractLabels[$teacher->contract_type] ?? $teacher->contract_type }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.teachers.form.phone') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $teacher->phone ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.teachers.form.email') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $teacher->email ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.teachers.form.address') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $teacher->address ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.teachers.form.diploma') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $teacher->diploma ?: '—' }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.teachers.form.specialty') }}</p>
                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $teacher->specialty ?: '—' }}</p>
                </div>

                <div id="pedagogic-profile" class="rounded-2xl border border-sky-200 bg-sky-50/70 p-4 dark:border-sky-900/40 dark:bg-sky-950/20">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-500 dark:text-sky-300">{{ __('people.teachers.show.pedagogic_title') }}</p>
                            <p class="mt-1 text-sm text-sky-700/90 dark:text-sky-200/90">{{ __('people.teachers.show.pedagogic_description') }}</p>
                        </div>

                        <x-ui.badge :tone="$teacher->pedagogicTrackTone()">
                            {{ $teacher->pedagogicTrackLabel() }}
                        </x-ui.badge>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border border-sky-100 bg-white/80 p-4 dark:border-sky-900/30 dark:bg-slate-950/40">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.teachers.show.cycle') }}</p>
                            <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $teacher->academicCycle?->name ?: '—' }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $teacher->academicCycle?->code ?: '—' }}</p>
                        </div>

                        <div class="rounded-2xl border border-sky-100 bg-white/80 p-4 dark:border-sky-900/30 dark:bg-slate-950/40">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.teachers.show.track') }}</p>
                            <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $teacher->pedagogicTrackLabel() }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $teacher->pedagogicPlacementTypeLabel() ?: __('people.teachers.show.placement') }}</p>
                        </div>

                        <div class="rounded-2xl border border-sky-100 bg-white/80 p-4 md:col-span-2 dark:border-sky-900/30 dark:bg-slate-950/40">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">
                                {{ $teacher->pedagogicPlacementTypeLabel() ?: __('people.teachers.show.placement') }}
                            </p>
                            <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">
                                {{ $teacher->pedagogicPlacementLabel() ?: '—' }}
                            </p>
                        </div>
                    </div>
                </div>

                @if ($teacher->notes)
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.teachers.form.notes') }}</p>
                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-200">{{ $teacher->notes }}</p>
                    </div>
                @endif
            </x-ui.card>

            <div class="space-y-6">
                @include('people.accounts.section', [
                    'documentable' => $teacher,
                ])

                <x-ui.card id="assignments" class="space-y-6">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.teachers.show.assignments_title') }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.teachers.show.assignments_description') }}</p>
                        </div>

                        <x-ui.badge tone="sky">{{ $pedagogicLoadCount }} {{ __('people.teachers.index.table.assignments') }}</x-ui.badge>
                    </div>

                    @can('assignSubjects', $teacher)
                        <div class="rounded-3xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('people.teachers.show.assignment_form_title') }}</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.teachers.show.assignment_form_description') }}</p>
                                </div>
                            </div>

                            <x-ui.alert tone="info" class="mt-4">
                                {{ __('people.teachers.show.assignments_description') }}
                            </x-ui.alert>

                            <form method="POST" action="{{ route('people.teachers.assignments.store', $teacher) }}" class="mt-4 space-y-4">
                                @csrf

                                <div class="grid gap-4 md:grid-cols-3">
                                    <x-form.select
                                        name="academic_subject_id"
                                        :label="__('people.teachers.show.subject')"
                                        icon="fa-book-open"
                                        required
                                        :placeholder="__('people.teachers.show.subject_placeholder')"
                                    >
                                        @foreach ($availableSubjects as $subject)
                                            <option value="{{ $subject->id }}" @selected((string) old('academic_subject_id') === (string) $subject->id)>
                                                {{ $subject->name }}{{ $subject->code ? ' · '.$subject->code : '' }}
                                            </option>
                                        @endforeach
                                    </x-form.select>

                                    <x-form.select
                                        name="academic_classroom_id"
                                        :label="__('people.teachers.show.classroom')"
                                        icon="fa-chalkboard"
                                        required
                                        :placeholder="__('people.teachers.show.classroom_placeholder')"
                                    >
                                        @foreach ($availableClassrooms as $classroom)
                                            <option value="{{ $classroom->id }}" @selected((string) old('academic_classroom_id') === (string) $classroom->id)>
                                                {{ $classroom->name }}
                                                @if ($classroom->level?->cycle)
                                                    · {{ $classroom->level->cycle->name }} / {{ $classroom->level->name }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </x-form.select>

                                    <x-form.select
                                        name="academic_year_id"
                                        :label="__('people.teachers.show.academic_year')"
                                        icon="fa-calendar-days"
                                        required
                                        :value="$activeYearId"
                                        :placeholder="__('people.teachers.show.academic_year_placeholder')"
                                    >
                                        @foreach ($academicYears as $academicYear)
                                            <option value="{{ $academicYear->id }}">{{ $academicYear->name }}</option>
                                        @endforeach
                                    </x-form.select>
                                </div>

                                <x-form.textarea name="notes" :label="__('people.teachers.show.notes')" icon="fa-note-sticky" :value="old('notes')" />

                                <div class="flex flex-wrap gap-3">
                                    <x-ui.button type="submit">
                                        {{ __('people.teachers.show.attach_assignment_action') }}
                                    </x-ui.button>
                                </div>
                            </form>
                        </div>
                    @endcan

                    <div class="space-y-4 border-t border-gray-100 pt-5 dark:border-gray-800">
                        @if ($teacher->assignments->count() > 0)
                            <x-ui.table>
                                <thead>
                                    <tr>
                                        <th class="table-head-cell">{{ __('people.teachers.show.subject') }}</th>
                                        <th class="table-head-cell">{{ __('people.teachers.show.classroom') }}</th>
                                        <th class="table-head-cell">{{ __('people.teachers.show.academic_year') }}</th>
                                        <th class="table-head-cell">{{ __('people.teachers.show.notes') }}</th>
                                        <th class="table-head-cell text-right">{{ __('people.teachers.index.table.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($teacher->assignments as $assignment)
                                        <tr class="table-body-row">
                                            <td class="table-body-cell">
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ $assignment->subject?->name ?? '—' }}</p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $assignment->subject?->code ?: '—' }}</p>
                                            </td>
                                            <td class="table-body-cell text-gray-600 dark:text-gray-300">
                                                {{ $assignment->classroom?->name ?? '—' }}
                                            </td>
                                            <td class="table-body-cell text-gray-600 dark:text-gray-300">
                                                {{ $assignment->academicYear?->name ?? '—' }}
                                            </td>
                                            <td class="table-body-cell text-sm text-gray-600 dark:text-gray-300">
                                                {{ $assignment->notes ?: '—' }}
                                            </td>
                                            <td class="table-body-cell text-right">
                                                <div class="flex flex-wrap justify-end gap-2">
                                                    @can('assignSubjects', $teacher)
                                                        <x-ui.confirm-modal
                                                            :name="'remove-assignment-'.$assignment->id"
                                                            :action="route('people.teachers.assignments.destroy', [$teacher, $assignment])"
                                                            method="DELETE"
                                                            :title="__('people.teachers.show.remove_assignment_title')"
                                                            :description="__('people.teachers.show.remove_assignment_confirm')"
                                                            :warning="__('people.teachers.show.remove_assignment_warning')"
                                                            :confirmLabel="__('people.teachers.show.remove_assignment_action')"
                                                            :triggerLabel="__('people.teachers.show.remove_assignment_action')"
                                                            trigger-variant="warning"
                                                            icon="fa-link-slash"
                                                        />
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </x-ui.table>
                        @else
                            <x-ui.empty-state :title="__('people.teachers.show.assignments_empty_title')" :description="__('people.teachers.show.assignments_empty_description')" icon="fa-diagram-project" />
                        @endif
                    </div>
                </x-ui.card>
            </div>
        </div>

        @include('people.documents.section', [
            'documentable' => $teacher,
            'documents' => $documents,
        ])

        @if ($canViewHistory)
            <x-ui.card id="history" class="space-y-4">
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.teachers.show.history_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.teachers.show.history_empty_description') }}</p>
                    </div>
                </div>

                @if ($historyLogs->count() > 0)
                    <div class="space-y-3">
                        @foreach ($historyLogs as $log)
                            <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $log->description ?? $log->action }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $log->user?->name ?? __('ui.roles.user') }}</p>
                                    </div>
                                    <x-ui.badge tone="slate">{{ $log->created_at?->translatedFormat('d M Y H:i') }}</x-ui.badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state :title="__('people.teachers.show.history_empty_title')" :description="__('people.teachers.show.history_empty_description')" icon="fa-file-shield" />
                @endif
            </x-ui.card>
        @endif
    </div>
</x-app-layout>
