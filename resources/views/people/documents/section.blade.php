@php
    $documents = collect($documents ?? []);
    $isStudent = $documentable instanceof \App\Models\Student;
    $isParent = $documentable instanceof \App\Models\Guardian;
    $isStaff = $documentable instanceof \App\Models\Staff;
    $documentTitle = $isStudent
        ? __('people.documents.student_title')
        : ($isParent ? __('people.documents.parent_title') : ($isStaff ? __('people.documents.staff_title') : __('people.documents.teacher_title')));
    $documentOptions = \App\Models\PersonDocument::typeOptionsFor($documentable);
    $storeRoute = $isStudent
        ? route('people.students.documents.store', $documentable)
        : ($isParent ? route('people.parents.documents.store', $documentable) : ($isStaff ? route('people.staff.documents.store', $documentable) : route('people.teachers.documents.store', $documentable)));
@endphp

@can('viewDocuments', $documentable)
    <x-ui.card id="documents" class="space-y-6">
        <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $documentTitle }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.documents.description') }}</p>
            </div>

            <x-ui.badge tone="sky">
                {{ $documents->count() }} {{ __('people.documents.meta.count') }}
            </x-ui.badge>
        </div>

        @can('attachDocument', $documentable)
            <div class="rounded-3xl border border-gray-200 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('people.documents.form.title') }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.documents.form.description') }}</p>
                    </div>
                </div>

                <x-ui.alert tone="info" class="mt-4">
                    {{ __('people.documents.form.audit_hint') }}
                </x-ui.alert>

                <form method="POST" action="{{ $storeRoute }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-form.select
                            name="document_type"
                            :label="__('people.documents.fields.type')"
                            icon="fa-folder-tree"
                            :placeholder="__('people.documents.placeholders.type')"
                            required
                        >
                            @foreach ($documentOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('document_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </x-form.select>

                        <x-form.input
                            type="file"
                            name="document_file"
                            :label="__('people.documents.fields.file')"
                            icon="fa-paperclip"
                            hint="{{ __('people.documents.hints.file') }}"
                            accept=".jpg,.jpeg,.png,.webp,.pdf"
                            required
                        />
                    </div>

                    <x-form.textarea
                        name="notes"
                        :label="__('people.documents.fields.notes')"
                        icon="fa-note-sticky"
                        :hint="__('people.documents.hints.notes')"
                    />

                    <label class="flex items-start gap-3 rounded-2xl border border-gray-200 bg-white/80 p-4 dark:border-gray-800 dark:bg-gray-900/60">
                        <input type="checkbox" name="is_sensitive" value="1" class="form-checkbox mt-1" @checked(old('is_sensitive', true))>
                        <span class="space-y-1">
                            <span class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('people.documents.fields.sensitive') }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('people.documents.hints.sensitive') }}</span>
                        </span>
                    </label>

                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.button type="submit">
                            {{ __('people.documents.actions.attach') }}
                        </x-ui.button>
                    </div>
                </form>
            </div>
        @endcan

        @if ($documents->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('people.documents.table.type') }}</th>
                        <th class="table-head-cell">{{ __('people.documents.table.file') }}</th>
                        <th class="table-head-cell">{{ __('people.documents.table.visibility') }}</th>
                        <th class="table-head-cell">{{ __('people.documents.table.meta') }}</th>
                        <th class="table-head-cell">{{ __('people.documents.table.uploaded_by') }}</th>
                        <th class="table-head-cell">{{ __('people.documents.table.uploaded_at') }}</th>
                        <th class="table-head-cell text-right">{{ __('people.documents.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($documents as $document)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <x-ui.badge tone="sky">{{ $document->documentTypeLabel() }}</x-ui.badge>
                            </td>
                            <td class="table-body-cell">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-gray-900 dark:text-white">{{ $document->original_name }}</p>
                                    @if ($document->notes)
                                        <p class="mt-1 max-h-12 overflow-hidden text-xs text-gray-500 dark:text-gray-400">{{ $document->notes }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.badge :tone="$document->is_sensitive ? 'rose' : 'emerald'">
                                        {{ $document->is_sensitive ? __('people.documents.badges.sensitive') : __('people.documents.badges.visible') }}
                                    </x-ui.badge>
                                </div>
                            </td>
                            <td class="table-body-cell text-sm text-gray-600 dark:text-gray-300">
                                <div class="space-y-1">
                                    <p>{{ $document->humanReadableSize() }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $document->mime_type ?: '—' }}</p>
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $document->uploader?->name ?? '—' }}</p>
                            </td>
                            <td class="table-body-cell text-sm text-gray-600 dark:text-gray-300">
                                {{ $document->created_at?->translatedFormat('d M Y H:i') ?? '—' }}
                            </td>
                            <td class="table-body-cell text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <x-ui.button :href="route('people.documents.download', $document)" variant="secondary" size="sm">
                                        {{ __('people.documents.actions.download') }}
                                    </x-ui.button>

                                    @can('removeDocument', $documentable)
                                        <x-ui.confirm-modal
                                            :name="'remove-document-'.$document->id"
                                            :action="route('people.documents.destroy', $document)"
                                            method="DELETE"
                                            :title="__('people.documents.confirmations.remove_title')"
                                            :description="__('people.documents.confirmations.remove_description')"
                                            :warning="__('people.documents.confirmations.remove_warning')"
                                            :confirmLabel="__('people.documents.actions.remove')"
                                            :triggerLabel="__('people.documents.actions.remove')"
                                            trigger-variant="danger"
                                            icon="fa-trash-can"
                                        />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @else
            <x-ui.empty-state
                :title="__('people.documents.empty.title')"
                :description="__('people.documents.empty.description')"
                icon="fa-folder-open"
            />
        @endif
    </x-ui.card>
@endcan
