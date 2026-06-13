<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$student->fullName()" :description="__('people.students.show.description')">
            <x-slot:actions>
                <x-ui.button :href="route('people.students.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>

                @can('update', $student)
                    <x-ui.button :href="route('people.students.edit', $student)" variant="warning">
                        {{ __('core.common.actions.edit') }}
                    </x-ui.button>
                @endcan

                @can('viewAccount', $student)
                    <x-ui.button :href="'#account'" variant="info">
                        {{ __('people.accounts.actions.view_section') }}
                    </x-ui.button>
                @endcan

                @can('disable', $student)
                    @php
                        $toggleAction = route('people.students.toggle-status', $student);
                    @endphp
                    @if ($student->status === 'actif')
                        <x-ui.confirm-modal
                            :name="'suspend-student-'.$student->id"
                            :action="$toggleAction"
                            method="PATCH"
                            :title="__('people.students.show.toggle_suspend')"
                            :description="__('people.students.show.toggle_suspend_confirm')"
                            :warning="__('people.students.show.toggle_suspend_warning')"
                            :confirmLabel="__('people.students.show.toggle_suspend')"
                            :triggerLabel="__('people.students.show.toggle_suspend')"
                            trigger-variant="warning"
                            icon="fa-user-slash"
                        />
                    @elseif ($student->status === 'suspendu')
                        <x-ui.confirm-modal
                            :name="'reactivate-student-'.$student->id"
                            :action="$toggleAction"
                            method="PATCH"
                            :title="__('people.students.show.toggle_reactivate')"
                            :description="__('people.students.show.toggle_reactivate_confirm')"
                            :warning="__('people.students.show.toggle_reactivate_warning')"
                            :confirmLabel="__('people.students.show.toggle_reactivate')"
                            :triggerLabel="__('people.students.show.toggle_reactivate')"
                            trigger-variant="primary"
                            icon="fa-circle-check"
                        />
                    @endif
                @endcan

                @can('linkParent', $student)
                    <x-ui.button :href="'#parents'" variant="info">
                        {{ __('people.students.show.attach_action') }}
                    </x-ui.button>
                @endcan

                @can('viewDocuments', $student)
                    <x-ui.button :href="'#documents'" variant="info">
                        {{ __('people.documents.show_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    @php
        $statusLabels = \App\Models\Student::statusOptions();
        $relationshipLabels = \App\Models\Student::relationshipOptions();
        $genderLabels = \App\Models\Student::genderOptions();
    @endphp

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('people.students.form.matricule')" :value="$student->matricule" icon="fa-hashtag" tone="sky" />
            <x-ui.stats-card :label="__('people.students.form.classroom')" :value="$student->currentClassroom?->name ?? '—'" icon="fa-school" tone="emerald" />
            <x-ui.stats-card :label="__('people.students.index.table.parents')" :value="$parents->count()" icon="fa-people-roof" tone="amber" />
            <x-ui.stats-card :label="__('people.students.form.status')" :value="$statusLabels[$student->status] ?? $student->status" icon="fa-toggle-on" tone="violet" />
        </section>

        <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <x-ui.card class="space-y-6">
                <div class="flex items-start gap-4 border-b border-gray-100 pb-5 dark:border-gray-800">
                    <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-3xl bg-sky-100 text-lg font-bold text-sky-700 dark:bg-sky-500/15 dark:text-sky-200">
                        @if ($student->photo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($student->photo_path) }}" alt="{{ $student->fullName() }}" class="h-full w-full object-cover">
                        @else
                            {{ $student->initials() }}
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-ui.badge :tone="\App\Models\Student::statusTone($student->status)">
                                {{ $statusLabels[$student->status] ?? $student->status }}
                            </x-ui.badge>
                            @if ($student->user)
                                <x-ui.badge tone="{{ $student->user->is_active ? 'emerald' : 'rose' }}">
                                    {{ $student->user->is_active ? __('people.students.show.linked_account') : __('core.common.states.suspended') }}
                                </x-ui.badge>
                            @endif
                        </div>

                        <h2 class="mt-3 text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $student->fullName() }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $student->matricule }}</p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $student->currentClassroom?->level?->cycle?->name ?? '—' }} · {{ $student->currentClassroom?->level?->name ?? '—' }} · {{ $student->currentClassroom?->name ?? '—' }}</p>
                    </div>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.students.form.gender') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $genderLabels[$student->gender] ?? $student->gender }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.students.form.date_of_birth') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $student->date_of_birth?->translatedFormat('d M Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.students.form.place_of_birth') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $student->place_of_birth ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.students.form.nationality') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $student->nationality ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.students.form.academic_year') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $student->entryAcademicYear?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.students.form.classroom') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $student->currentClassroom?->name ?? '—' }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.students.show.sensitive_title') }}</p>

                    @if ($canViewSensitive)
                        <div class="mt-4 grid gap-4 md:grid-cols-3">
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('people.students.form.date_of_birth') }}</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $student->date_of_birth?->translatedFormat('d M Y') ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('people.students.form.place_of_birth') }}</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $student->place_of_birth ?: '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('people.students.form.nationality') }}</p>
                                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $student->nationality ?: '—' }}</p>
                            </div>
                        </div>
                    @else
                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('people.students.show.sensitive_description') }}</p>
                    @endif
                </div>
            </x-ui.card>

            <div class="space-y-6">
                @include('people.accounts.section', [
                    'documentable' => $student,
                ])

                <x-ui.card class="space-y-4">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white" id="parents">{{ __('people.students.show.guardians_title') }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.students.form.primary_hint') }}</p>
                        </div>
                    </div>

                    @if ($parents->count() > 0)
                        <div class="space-y-3">
                            @foreach ($parents as $parent)
                                <div class="rounded-2xl border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $parent->fullName() }}</h3>
                                                @if ($parent->pivot->is_primary_contact)
                                                    <x-ui.badge tone="emerald">{{ __('people.students.show.primary_contact') }}</x-ui.badge>
                                                @endif
                                                @if ($parent->pivot->is_financial_responsible)
                                                    <x-ui.badge tone="amber">{{ __('people.students.show.financial_responsible') }}</x-ui.badge>
                                                @endif
                                            </div>
                                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $relationshipLabels[$parent->pivot->relationship_type] ?? $parent->pivot->relationship_type }}</p>
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                <x-ui.badge tone="sky">{{ $parent->phone ?: '—' }}</x-ui.badge>
                                                <x-ui.badge tone="slate">{{ $parent->email ?: '—' }}</x-ui.badge>
                                                <x-ui.badge :tone="$parent->status === 'actif' ? 'emerald' : 'slate'">
                                                    {{ \App\Models\Guardian::statusOptions()[$parent->status] ?? $parent->status }}
                                                </x-ui.badge>
                                                @if ($parent->pivot->can_receive_notifications)
                                                    <x-ui.badge tone="violet">{{ __('people.students.show.notifications') }}</x-ui.badge>
                                                @endif
                                                @if ($parent->pivot->can_pickup_child)
                                                    <x-ui.badge tone="amber">{{ __('people.students.show.pickup') }}</x-ui.badge>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            @can('linkParent', $student)
                                                <x-ui.confirm-modal
                                                    :name="'unlink-parent-'.$student->id.'-'.$parent->id"
                                                    :action="route('people.students.parents.destroy', [$student, $parent])"
                                                    method="DELETE"
                                                    :title="__('people.students.show.detach_action')"
                                                    :description="__('people.students.show.detach_confirm')"
                                                    :warning="__('people.students.show.detach_warning')"
                                                    :confirmLabel="__('people.students.show.detach_action')"
                                                    :triggerLabel="__('people.students.show.detach_action')"
                                                    trigger-variant="warning"
                                                    icon="fa-link-slash"
                                                />
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty-state :title="__('people.students.show.parents_empty')" :description="__('people.students.form.primary_hint')" icon="fa-people-roof" />
                    @endif

                    @can('linkParent', $student)
                        <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                            <form method="POST" action="{{ route('people.students.parents.store', $student) }}" class="space-y-4">
                                @csrf

                                <div class="grid gap-4 md:grid-cols-2">
                                    <x-form.select name="parent_id" :label="__('people.students.form.existing_guardian')" icon="fa-people-roof" required>
                                        <option value="">{{ __('people.students.form.existing_guardian') }}</option>
                                        @foreach ($availableGuardians as $guardian)
                                            <option value="{{ $guardian->id }}" @selected((string) old('parent_id') === (string) $guardian->id)>
                                                {{ $guardian->fullName() }} @if($guardian->phone) - {{ $guardian->phone }} @endif
                                            </option>
                                        @endforeach
                                    </x-form.select>

                                    <x-form.select name="relationship_type" :label="__('people.students.form.relationship_type')" icon="fa-link" required>
                                        <option value="">{{ __('people.students.form.relationship_type') }}</option>
                                        @foreach ($relationshipLabels as $value => $label)
                                            <option value="{{ $value }}" @selected(old('relationship_type') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </x-form.select>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-white/80 p-4 dark:border-gray-800 dark:bg-gray-900/60">
                                        <input type="checkbox" name="is_primary_contact" value="1" class="form-checkbox" @checked(old('is_primary_contact', true))>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('people.students.form.primary_contact') }}</span>
                                    </label>

                                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-white/80 p-4 dark:border-gray-800 dark:bg-gray-900/60">
                                        <input type="checkbox" name="is_financial_responsible" value="1" class="form-checkbox" @checked(old('is_financial_responsible'))>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('people.students.form.financial_responsible') }}</span>
                                    </label>

                                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-white/80 p-4 dark:border-gray-800 dark:bg-gray-900/60">
                                        <input type="checkbox" name="can_receive_notifications" value="1" class="form-checkbox" @checked(old('can_receive_notifications', true))>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('people.students.form.notifications') }}</span>
                                    </label>

                                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-white/80 p-4 dark:border-gray-800 dark:bg-gray-900/60">
                                        <input type="checkbox" name="can_pickup_child" value="1" class="form-checkbox" @checked(old('can_pickup_child', true))>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('people.students.form.pickup') }}</span>
                                    </label>
                                </div>

                                <x-form.textarea name="notes" :label="__('people.students.form.notes')" icon="fa-note-sticky" />

                                <x-ui.button type="submit">
                                    {{ __('people.students.show.attach_action') }}
                                </x-ui.button>
                            </form>
                        </div>
                    @endcan
                </x-ui.card>

                @include('people.documents.section', [
                    'documentable' => $student,
                    'documents' => $documents,
                ])

                <x-ui.card class="space-y-4">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.students.show.history_title') }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.students.show.description') }}</p>
                        </div>
                    </div>

                    @php
                        $canViewHistory = request()->user() ? request()->user()->can('history', $student) : false;
                    @endphp

                    @if ($canViewHistory)
                        @if ($historyLogs->count() > 0)
                            <div class="space-y-3">
                                @foreach ($historyLogs as $log)
                                    <div class="rounded-2xl border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $log->description }}</p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $log->user?->name ?? __('core.audit_logs.index.system') }}</p>
                                            </div>
                                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ $log->created_at?->translatedFormat('d M Y H:i') }}</p>
                                        </div>

                                        @if ($log->old_values || $log->new_values)
                                            <details class="mt-3 rounded-xl border border-gray-200 bg-white/80 p-3 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900/70 dark:text-gray-300">
                                                <summary class="cursor-pointer font-medium text-gray-900 dark:text-white">{{ __('core.audit_logs.index.details') }}</summary>
                                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                                    <div>
                                                        <p class="mb-2 font-semibold text-gray-900 dark:text-white">{{ __('core.audit_logs.index.old_values') }}</p>
                                                        <pre class="soft-scrollbar overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                    </div>
                                                    <div>
                                                        <p class="mb-2 font-semibold text-gray-900 dark:text-white">{{ __('core.audit_logs.index.new_values') }}</p>
                                                        <pre class="soft-scrollbar overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                    </div>
                                                </div>
                                            </details>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <x-ui.empty-state :title="__('people.students.show.history_empty_title')" :description="__('people.students.show.history_empty_description')" icon="fa-file-shield" />
                        @endif
                    @else
                        <x-ui.alert tone="info" :title="__('people.students.show.history_title')">
                            {{ __('people.students.show.history_empty') }}
                        </x-ui.alert>
                    @endif
                </x-ui.card>
            </div>
        </div>
    </div>
</x-app-layout>
