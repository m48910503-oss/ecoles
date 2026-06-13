<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$parent->fullName()" :description="__('people.parents.show.description')">
            <x-slot:actions>
                <x-ui.button :href="route('people.parents.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>

                @can('update', $parent)
                    <x-ui.button :href="route('people.parents.edit', $parent)" variant="warning">
                        {{ __('core.common.actions.edit') }}
                    </x-ui.button>
                @endcan

                @can('viewAccount', $parent)
                    <x-ui.button :href="'#account'" variant="info">
                        {{ __('people.accounts.actions.view_section') }}
                    </x-ui.button>
                @endcan

                @can('linkStudent', $parent)
                    <x-ui.button href="#child-link-form" variant="info">
                        {{ __('people.parents.show.attach_child_action') }}
                    </x-ui.button>
                @endcan

                @can('viewDocuments', $parent)
                    <x-ui.button :href="'#documents'" variant="info">
                        {{ __('people.documents.show_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    @php
        $genderLabels = \App\Models\Guardian::genderOptions();
        $statusLabels = \App\Models\Guardian::statusOptions();
    @endphp

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('people.parents.index.table.children')" :value="$children->count()" icon="fa-child" tone="sky" />
            <x-ui.stats-card :label="__('people.parents.form.phone')" :value="$parent->phone ?: '—'" icon="fa-phone" tone="emerald" />
            <x-ui.stats-card :label="__('people.parents.form.email')" :value="$parent->email ?: '—'" icon="fa-envelope" tone="amber" />
            <x-ui.stats-card :label="__('people.parents.form.status')" :value="$statusLabels[$parent->status] ?? $parent->status" icon="fa-toggle-on" tone="violet" />
        </section>

        <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.parents.show.identity_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.parents.show.description') }}</p>
                </div>

                <div class="flex items-start gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 text-lg font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                        {{ $parent->initials() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $parent->fullName() }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $genderLabels[$parent->gender] ?? $parent->gender }}</p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-ui.badge :tone="\App\Models\Guardian::statusTone($parent->status)">
                                {{ $statusLabels[$parent->status] ?? $parent->status }}
                            </x-ui.badge>

                            @if ($parent->user)
                                <x-ui.badge tone="{{ $parent->user->is_active ? 'emerald' : 'rose' }}">
                                    {{ $parent->user->is_active ? __('people.parents.show.linked_account') : __('core.common.states.suspended') }}
                                </x-ui.badge>
                            @else
                                <x-ui.badge tone="slate">{{ __('people.parents.show.no_account') }}</x-ui.badge>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.parents.form.phone') }}</p>
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $parent->phone ?: '—' }}</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.parents.form.email') }}</p>
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $parent->email ?: '—' }}</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.parents.form.address') }}</p>
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $parent->address ?: '—' }}</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.parents.form.profession') }}</p>
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $parent->profession ?: '—' }}</p>
                    </div>
                </div>

                @if ($parent->notes)
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.parents.form.notes') }}</p>
                        <p class="mt-2 text-sm text-gray-700 dark:text-gray-200">{{ $parent->notes }}</p>
                    </div>
                @endif
            </x-ui.card>

            <div class="space-y-6">
                @include('people.accounts.section', [
                    'documentable' => $parent,
                ])

                <x-ui.card class="space-y-4">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.parents.show.contact_title') }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.parents.form.contact_description') }}</p>
                        </div>
                    </div>

                    @if ($parent->user)
                        <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.parents.show.account_title') }}</p>
                            <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $parent->user->name }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $parent->user->email }}</p>
                        </div>
                    @else
                        <x-ui.empty-state :title="__('people.parents.show.no_account')" :description="__('people.parents.form.contact_hint')" icon="fa-user-lock" />
                    @endif
                </x-ui.card>

                <x-ui.card class="space-y-6" id="child-link-form">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.parents.show.relation_title') }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.parents.show.relation_description') }}</p>
                        </div>

                        @can('linkStudent', $parent)
                            <x-ui.badge tone="sky">{{ $children->count() }} {{ __('people.parents.index.table.children') }}</x-ui.badge>
                        @endcan
                    </div>

                    @php
                        $selectedFormStudentId = old('student_id', $selectedStudentId);
                        $selectedFormStudent = $selectedStudent;

                        if (! $selectedFormStudent && filled($selectedFormStudentId)) {
                            $selectedFormStudent = $linkedChildren->firstWhere('id', (int) $selectedFormStudentId)
                                ?? $availableStudents->firstWhere('id', (int) $selectedFormStudentId);
                        }

                        $selectedFormRelation = $selectedFormStudent
                            ? $linkedChildren->firstWhere('id', $selectedFormStudent->id)?->pivot
                            : null;

                        $editingRelation = $selectedFormStudent !== null && $selectedFormRelation !== null;
                    @endphp

                    @can('linkStudent', $parent)
                        <div class="space-y-4 rounded-3xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                        {{ $editingRelation ? __('people.parents.show.edit_relation_title') : __('people.parents.show.attach_child_action') }}
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $editingRelation ? __('people.parents.show.edit_relation_hint') : __('people.parents.show.relation_hint') }}
                                    </p>
                                </div>

                                @if ($selectedFormStudent)
                                    <x-ui.badge :tone="$editingRelation ? 'amber' : 'sky'">
                                        {{ $selectedFormStudent->matricule }}
                                    </x-ui.badge>
                                @endif
                            </div>

                            @if ($selectedFormStudent)
                                <x-ui.alert tone="info" :title="__('people.parents.show.selected_student_title')">
                                    <div class="space-y-2">
                                        <p class="text-sm font-medium text-sky-900 dark:text-sky-100">
                                            {{ $selectedFormStudent->fullName() }}
                                            <span class="text-sky-700 dark:text-sky-200">· {{ $selectedFormStudent->matricule }}</span>
                                        </p>
                                        <p class="text-sm text-sky-800 dark:text-sky-200">
                                            {{ $selectedFormStudent->currentClassroom?->name ?? '—' }}
                                            @if ($selectedFormRelation)
                                                · {{ __('people.parents.show.current_relation') }}
                                            @endif
                                        </p>

                                        @if ($selectedFormRelation)
                                            <div class="flex flex-wrap gap-2">
                                                <x-ui.badge tone="sky">
                                                    {{ $relationshipOptions[$selectedFormRelation->relationship_type] ?? $selectedFormRelation->relationship_type }}
                                                </x-ui.badge>

                                                @if ($selectedFormRelation->is_primary_contact)
                                                    <x-ui.badge tone="emerald">{{ __('people.parents.show.primary_contact') }}</x-ui.badge>
                                                @endif

                                                @if ($selectedFormRelation->is_financial_responsible)
                                                    <x-ui.badge tone="amber">{{ __('people.parents.show.financial_responsible') }}</x-ui.badge>
                                                @endif

                                                @if ($selectedFormRelation->can_receive_notifications)
                                                    <x-ui.badge tone="violet">{{ __('people.parents.show.notifications') }}</x-ui.badge>
                                                @endif

                                                @if ($selectedFormRelation->can_pickup_child)
                                                    <x-ui.badge tone="amber">{{ __('people.parents.show.pickup') }}</x-ui.badge>
                                                @endif
                                            </div>
                                        @else
                                            <p class="text-sm text-sky-800 dark:text-sky-200">{{ __('people.parents.show.selected_student_description') }}</p>
                                        @endif
                                    </div>
                                </x-ui.alert>
                            @endif

                            <form method="POST" action="{{ route('people.parents.students.store', $parent) }}" class="space-y-4">
                                @csrf

                                <div class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
                                    <x-form.select
                                        name="student_id"
                                        :label="__('people.parents.show.student_field')"
                                        icon="fa-user-graduate"
                                        required
                                        :value="$selectedFormStudent?->id"
                                        :placeholder="__('people.parents.show.student_placeholder')"
                                    >
                                        <optgroup label="{{ __('people.parents.show.linked_children_group') }}">
                                            @forelse ($linkedChildren as $linkedChild)
                                                <option value="{{ $linkedChild->id }}">
                                                    {{ $linkedChild->fullName() }} - {{ $linkedChild->matricule }}{{ $linkedChild->currentClassroom?->name ? ' · '.$linkedChild->currentClassroom->name : '' }}
                                                </option>
                                            @empty
                                                <option value="" disabled>{{ __('people.parents.show.children_empty_title') }}</option>
                                            @endforelse
                                        </optgroup>

                                        @if ($availableStudents->count() > 0)
                                            <optgroup label="{{ __('people.parents.show.available_children_group') }}">
                                                @foreach ($availableStudents as $availableStudent)
                                                    <option value="{{ $availableStudent->id }}">
                                                        {{ $availableStudent->fullName() }} - {{ $availableStudent->matricule }}{{ $availableStudent->currentClassroom?->name ? ' · '.$availableStudent->currentClassroom->name : '' }}
                                                    </option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                    </x-form.select>

                                    <x-form.select
                                        name="relationship_type"
                                        :label="__('people.parents.show.relationship_type')"
                                        icon="fa-link"
                                        required
                                        :value="$selectedFormRelation?->relationship_type ?? 'autre'"
                                    >
                                        <option value="">{{ __('people.parents.show.relationship_placeholder') }}</option>
                                        @foreach ($relationshipOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </x-form.select>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-white/80 p-4 dark:border-gray-800 dark:bg-gray-900/60">
                                        <input type="checkbox" name="is_primary_contact" value="1" class="form-checkbox" @checked(old('is_primary_contact', $selectedFormRelation?->is_primary_contact ?? true))>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('people.parents.show.primary_contact') }}</span>
                                    </label>

                                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-white/80 p-4 dark:border-gray-800 dark:bg-gray-900/60">
                                        <input type="checkbox" name="is_financial_responsible" value="1" class="form-checkbox" @checked(old('is_financial_responsible', $selectedFormRelation?->is_financial_responsible ?? false))>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('people.parents.show.financial_responsible') }}</span>
                                    </label>

                                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-white/80 p-4 dark:border-gray-800 dark:bg-gray-900/60">
                                        <input type="checkbox" name="can_receive_notifications" value="1" class="form-checkbox" @checked(old('can_receive_notifications', $selectedFormRelation?->can_receive_notifications ?? true))>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('people.parents.show.notifications') }}</span>
                                    </label>

                                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-white/80 p-4 dark:border-gray-800 dark:bg-gray-900/60">
                                        <input type="checkbox" name="can_pickup_child" value="1" class="form-checkbox" @checked(old('can_pickup_child', $selectedFormRelation?->can_pickup_child ?? true))>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('people.parents.show.pickup') }}</span>
                                    </label>
                                </div>

                                <x-form.textarea
                                    name="notes"
                                    :label="__('people.parents.form.notes')"
                                    icon="fa-note-sticky"
                                    :value="$selectedFormRelation?->notes"
                                />

                                <div class="flex flex-wrap gap-3">
                                    <x-ui.button type="submit">
                                        {{ $editingRelation ? __('people.parents.show.update_relation_action') : __('people.parents.show.attach_relation_action') }}
                                    </x-ui.button>

                                    @if ($selectedFormStudentId)
                                        <x-ui.button :href="route('people.parents.show', $parent).'#child-link-form'" variant="secondary">
                                            {{ __('people.parents.show.reset_selection') }}
                                        </x-ui.button>
                                    @endif
                                </div>
                            </form>
                        </div>
                    @endcan

                    <div class="space-y-4 border-t border-gray-100 pt-5 dark:border-gray-800">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('people.parents.show.children_title') }}</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.parents.show.children_description') }}</p>
                        </div>

                        @if ($children->count() > 0)
                            <x-ui.table>
                                <thead>
                                    <tr>
                                        <th class="table-head-cell">{{ __('people.students.index.table.student') }}</th>
                                        <th class="table-head-cell">{{ __('people.students.index.table.classroom') }}</th>
                                        <th class="table-head-cell">{{ __('people.parents.index.table.contact') }}</th>
                                        <th class="table-head-cell">{{ __('people.parents.index.table.status') }}</th>
                                        <th class="table-head-cell text-right">{{ __('people.parents.index.table.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($children as $student)
                                        <tr @class([
                                            'table-body-row',
                                            'bg-sky-50/70 dark:bg-sky-500/10' => (int) $selectedFormStudentId === (int) $student->id,
                                        ])>
                                            <td class="table-body-cell">
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ $student->fullName() }}</p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $student->matricule }}</p>
                                            </td>
                                            <td class="table-body-cell text-gray-600 dark:text-gray-300">
                                                {{ $student->currentClassroom?->name ?? '—' }}
                                            </td>
                                            <td class="table-body-cell">
                                                <div class="flex flex-wrap gap-2">
                                                    <x-ui.badge tone="sky">
                                                        {{ $relationshipOptions[$student->pivot->relationship_type] ?? $student->pivot->relationship_type }}
                                                    </x-ui.badge>

                                                    @if ($student->pivot->is_primary_contact)
                                                        <x-ui.badge tone="emerald">{{ __('people.parents.show.primary_contact') }}</x-ui.badge>
                                                    @endif

                                                    @if ($student->pivot->is_financial_responsible)
                                                        <x-ui.badge tone="amber">{{ __('people.parents.show.financial_responsible') }}</x-ui.badge>
                                                    @endif

                                                    @if ($student->pivot->can_receive_notifications)
                                                        <x-ui.badge tone="violet">{{ __('people.parents.show.notifications') }}</x-ui.badge>
                                                    @endif

                                                    @if ($student->pivot->can_pickup_child)
                                                        <x-ui.badge tone="amber">{{ __('people.parents.show.pickup') }}</x-ui.badge>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="table-body-cell">
                                                <x-ui.badge :tone="\App\Models\Student::statusTone($student->status)">
                                                    {{ \App\Models\Student::statusOptions()[$student->status] ?? $student->status }}
                                                </x-ui.badge>
                                            </td>
                                            <td class="table-body-cell text-right">
                                                <div class="flex flex-wrap justify-end gap-2">
                                                    @can('view', $student)
                                                        <x-ui.button :href="route('people.students.show', $student)" variant="ghost" size="sm">
                                                            {{ __('people.parents.show.view_student') }}
                                                        </x-ui.button>
                                                    @endcan

                                                    @can('linkStudent', $parent)
                                                        <x-ui.button :href="route('people.parents.show', ['parent' => $parent, 'student_id' => $student->id]).'#child-link-form'" variant="info" size="sm">
                                                            {{ __('people.parents.show.edit_relation_action') }}
                                                        </x-ui.button>

                                                        <x-ui.confirm-modal
                                                            :name="'unlink-child-'.$parent->id.'-'.$student->id"
                                                            :action="route('people.parents.students.destroy', [$parent, $student])"
                                                            method="DELETE"
                                                            :title="__('people.parents.show.detach_title')"
                                                            :description="__('people.parents.show.detach_confirm')"
                                                            :warning="__('people.parents.show.detach_warning')"
                                                            :confirmLabel="__('people.parents.show.detach_action')"
                                                            :triggerLabel="__('people.parents.show.detach_action')"
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
                            <x-ui.empty-state :title="__('people.parents.show.children_empty_title')" :description="__('people.parents.show.children_empty_description')" icon="fa-user-graduate" />
                        @endif
                    </div>
                </x-ui.card>
            </div>
        </section>

        @include('people.documents.section', [
            'documentable' => $parent,
            'documents' => $documents,
        ])

        @if ($canViewHistory)
            <x-ui.card class="space-y-4">
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.parents.show.history_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.parents.show.history_empty_description') }}</p>
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
                    <x-ui.empty-state :title="__('people.parents.show.history_empty_title')" :description="__('people.parents.show.history_empty_description')" icon="fa-file-shield" />
                @endif
            </x-ui.card>
        @endif
    </div>
</x-app-layout>
