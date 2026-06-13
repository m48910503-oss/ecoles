<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('academic.events.form.edit_title') : __('academic.events.form.create_title')"
            :description="__('academic.events.form.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="route('academic.events.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                <x-ui.button type="submit" form="event-form">
                    {{ $isEdit ? __('academic.events.form.submit_edit') : __('academic.events.form.submit_create') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <form
        id="event-form"
        method="POST"
        action="{{ $isEdit ? route('academic.events.update', $eventModel) : route('academic.events.store') }}"
        class="space-y-6"
        x-data="{
            calendarId: '{{ old('academic_calendar_id', $eventModel->academic_calendar_id) }}',
            periodId: '{{ old('academic_period_id', $eventModel->academic_period_id) }}',
            targetType: '{{ old('target_type', $eventModel->target_type?->value ?? \App\Enums\Academic\CalendarTargetType::Global->value) }}',
            targetCycleId: '{{ $initialTargetCycleId }}',
            eventType: '{{ old('event_type', $eventModel->event_type?->value ?? \App\Enums\Academic\CalendarEventType::Reunion->value) }}',
            isFullDay: {{ old('is_full_day', $eventModel->is_full_day) ? 'true' : 'false' }},
            eventDate: '{{ old('event_date', optional($eventModel->start_datetime)->format('Y-m-d')) }}',
            location: @js(old('location', $eventModel->location)),
            startDateTime: '{{ old('start_datetime', optional($eventModel->start_datetime)->format('Y-m-d\\TH:i')) }}',
            endDateTime: '{{ old('end_datetime', optional($eventModel->end_datetime)->format('Y-m-d\\TH:i')) }}',
            calendars: @js($calendars->map(fn ($calendar) => [
                'id' => $calendar->id,
                'name' => $calendar->name,
                'academic_year_id' => $calendar->academic_year_id,
                'academic_year_name' => $calendar->academicYear->name,
                'school_id' => $calendar->school_id,
                'scope_label' => $calendar->scope_type->label(),
                'reference_label' => $calendar->scopeReferenceLabel(),
                'covered_cycle_ids' => $calendar->resolvedCoveredCycleIds(),
            ])->values()),
            cycles: @js($cycles->map(fn ($cycle) => ['id' => $cycle->id, 'name' => $cycle->name])->values()),
            yearCycleMap: @js($yearCycleMap),
            yearLevelMap: @js($yearLevelMap),
            calendarCycleMap: @js($calendarCycleMap),
            periodsByCalendar: @js($periodsByCalendar),
            levels: @js($levels->map(fn ($level) => ['id' => $level->id, 'name' => $level->short_name ?: $level->name, 'cycle_id' => $level->cycle_id, 'cycle_name' => $level->cycle->name])->values()),
            classrooms: @js($classrooms->map(fn ($classroom) => ['id' => $classroom->id, 'school_id' => $classroom->school_id, 'academic_year_id' => $classroom->academic_year_id, 'name' => $classroom->name, 'cycle_id' => $classroom->level->cycle_id, 'level_name' => $classroom->level->name, 'series_name' => $classroom->series?->short_name ?: $classroom->series?->name])->values()),
            locationRequiredTypes: @js($locationRequiredTypes),
            selectedCalendar() {
                return this.calendars.find(calendar => String(calendar.id) === String(this.calendarId));
            },
            calendarAcademicYearId() {
                return this.selectedCalendar()?.academic_year_id ?? '';
            },
            calendarSchoolId() {
                return this.selectedCalendar()?.school_id ?? '';
            },
            filteredPeriods() {
                if (! this.calendarId) {
                    return [];
                }

                return this.periodsByCalendar[String(this.calendarId)] ?? [];
            },
            requiresLocation() {
                return this.locationRequiredTypes.includes(this.eventType);
            },
            filteredCycles() {
                const selectedCalendar = this.selectedCalendar();

                if (! selectedCalendar) {
                    return this.cycles;
                }

                const coveredCycleIds = this.calendarCycleMap[String(selectedCalendar.id)] ?? [];
                const allowedCycleIds = coveredCycleIds.length > 0
                    ? coveredCycleIds
                    : (this.yearCycleMap[String(selectedCalendar.academic_year_id)] ?? []);

                return this.cycles.filter(cycle => allowedCycleIds.includes(String(cycle.id)));
            },
            filteredLevels() {
                const calendarYearId = String(this.calendarAcademicYearId() || '');

                if (! calendarYearId) {
                    return this.levels.filter(level => String(level.cycle_id) === String(this.targetCycleId));
                }

                const allowedLevelIds = this.yearLevelMap[calendarYearId] ?? [];

                return this.levels.filter(level => String(level.cycle_id) === String(this.targetCycleId)
                    && allowedLevelIds.includes(String(level.id)));
            },
            filteredClasses() {
                const calendarYearId = String(this.calendarAcademicYearId() || '');
                const calendarSchoolId = String(this.calendarSchoolId() || '');

                return this.classrooms.filter(classroom => String(classroom.cycle_id) === String(this.targetCycleId)
                    && (! calendarYearId || String(classroom.academic_year_id) === calendarYearId)
                    && (! calendarSchoolId || String(classroom.school_id) === calendarSchoolId));
            },
        }"
        x-effect="
            if (targetCycleId && !filteredCycles().some(cycle => String(cycle.id) === String(targetCycleId))) {
                targetCycleId = '';
            }
            if (periodId && !filteredPeriods().some(period => String(period.id) === String(periodId))) {
                periodId = '';
            }
        "
    >
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif
        @if ($selectedGroup)
            <input type="hidden" name="group" value="{{ $selectedGroup }}">
        @endif

        <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.events.form.details_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.events.form.details_description') }}</p>
                </div>

                @if ($calendars->isEmpty())
                    <x-ui.alert tone="info">
                        {{ __('academic.calendars.index.empty.description') }}
                    </x-ui.alert>
                @endif

                @if ($selectedGroup)
                    <x-ui.alert tone="info">
                        {{ __('academic.events.form.group_context', ['group' => $selectedGroupLabel]) }}
                    </x-ui.alert>
                @endif

                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.select name="academic_calendar_id" :label="__('academic.events.form.calendar')" icon="fa-calendar-week" x-model="calendarId">
                        <option value="">--</option>
                        @foreach ($calendars as $calendar)
                            <option value="{{ $calendar->id }}" @selected((int) old('academic_calendar_id', $eventModel->academic_calendar_id) === $calendar->id)>
                                {{ $calendar->name }} - {{ $calendar->academicYear->name }}
                            </option>
                        @endforeach
                    </x-form.select>

                    <x-form.select
                        name="academic_period_id"
                        :label="__('academic.events.form.period')"
                        icon="fa-layer-group"
                        x-model="periodId"
                        x-bind:disabled="!calendarId"
                    >
                        <option value="">{{ __('academic.events.form.period_optional') }}</option>
                        <template x-for="period in filteredPeriods()" :key="period.id">
                            <option :value="period.id" x-text="`${period.name} (${period.start_date} -> ${period.end_date})`"></option>
                        </template>
                    </x-form.select>

                    <div x-show="selectedCalendar()" x-cloak class="md:col-span-2 rounded-2xl border border-sky-100 bg-sky-50/80 p-4 dark:border-sky-900/40 dark:bg-sky-950/20">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-200">
                                <i class="fa-solid fa-calendar-week text-sm"></i>
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('academic.events.form.calendar_context_title') }}</p>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.events.form.calendar_context_description') }}</p>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span class="inline-flex items-center rounded-full border border-white/80 bg-white/90 px-3 py-1 text-xs font-semibold text-slate-700 dark:border-slate-800 dark:bg-slate-900/70 dark:text-slate-200" x-text="selectedCalendar()?.name"></span>
                                    <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200" x-text="selectedCalendar()?.academic_year_name"></span>
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200" x-text="selectedCalendar()?.scope_label"></span>
                                    <template x-if="selectedCalendar()?.reference_label">
                                        <span class="inline-flex items-center rounded-full border border-violet-200 bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-700 dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-violet-200" x-text="selectedCalendar()?.reference_label"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <x-form.input name="title" :label="__('academic.events.form.title')" :value="$eventModel->title" icon="fa-bullhorn" required />

                    <x-form.select name="event_type" :label="__('academic.events.form.event_type')" icon="fa-tags" x-model="eventType">
                        @foreach ($eventTypes as $eventType)
                            <option value="{{ $eventType->value }}" @selected(old('event_type', $eventModel->event_type?->value) === $eventType->value)>{{ $eventType->label() }}</option>
                        @endforeach
                    </x-form.select>

                    <div x-show="requiresLocation()" x-cloak class="md:col-span-2">
                        <x-form.input name="location" :label="__('academic.events.form.location')" :value="$eventModel->location" icon="fa-location-dot" x-bind:disabled="!requiresLocation()" />
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('academic.events.form.location_help') }}</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                            <input type="hidden" name="is_full_day" value="0">
                            <input type="checkbox" name="is_full_day" value="1" class="form-checkbox" x-model="isFullDay">
                            <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                                <i class="fa-solid fa-sun text-amber-500"></i>
                                {{ __('academic.events.form.is_full_day') }}
                            </span>
                        </label>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('academic.events.form.is_full_day_help') }}</p>
                    </div>

                    <div x-show="!isFullDay" x-cloak class="contents">
                        <x-form.input name="start_datetime" type="datetime-local" :label="__('academic.events.form.start_datetime')" :value="optional($eventModel->start_datetime)->format('Y-m-d\\TH:i')" icon="fa-clock" x-model="startDateTime" x-bind:required="!isFullDay" x-bind:disabled="isFullDay" />
                        <x-form.input name="end_datetime" type="datetime-local" :label="__('academic.events.form.end_datetime')" :value="optional($eventModel->end_datetime)->format('Y-m-d\\TH:i')" icon="fa-clock" x-model="endDateTime" x-bind:required="!isFullDay" x-bind:disabled="isFullDay" />
                    </div>

                    <div x-show="isFullDay" x-cloak class="contents">
                        <x-form.input name="event_date" type="date" :label="__('academic.events.form.event_date')" :value="optional($eventModel->start_datetime)->format('Y-m-d')" icon="fa-calendar-day" x-model="eventDate" x-bind:required="isFullDay" x-bind:disabled="!isFullDay" />
                        <p class="-mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('academic.events.form.event_date_hint') }}</p>
                    </div>

                    <x-form.textarea name="description" :label="__('academic.events.form.description_label')" :value="$eventModel->description" icon="fa-align-left" class="md:col-span-2" />
                </div>
            </x-ui.card>

            <div class="space-y-6">
                <x-ui.card class="space-y-5">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.events.form.publication_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.events.form.publication_description') }}</p>
                    </div>

                    <x-form.select name="visibility" :label="__('academic.events.form.visibility')" icon="fa-eye">
                        @foreach ($visibilityOptions as $visibilityOption)
                            <option value="{{ $visibilityOption->value }}" @selected(old('visibility', $eventModel->visibility?->value ?? \App\Enums\Academic\CalendarEventVisibility::Internal->value) === $visibilityOption->value)>{{ $visibilityOption->label() }}</option>
                        @endforeach
                    </x-form.select>
                    <p class="-mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('academic.events.form.visibility_help') }}</p>

                    <x-form.select name="target_type" :label="__('academic.events.form.target_type')" icon="fa-crosshairs" x-model="targetType">
                        @foreach ($targetOptions as $targetOption)
                            <option value="{{ $targetOption->value }}">{{ $targetOption->label() }}</option>
                        @endforeach
                    </x-form.select>

                    <div x-show="targetType === 'cycle'" x-cloak>
                        <x-form.select name="target_id" :label="__('academic.events.form.target_reference')" icon="fa-diagram-project">
                            <option value="">--</option>
                            <template x-for="cycle in filteredCycles()" :key="cycle.id">
                                <option
                                    :value="cycle.id"
                                    x-text="cycle.name"
                                    :selected="String(cycle.id) === String('{{ old('target_id', $eventModel->target_id) }}')"
                                ></option>
                            </template>
                        </x-form.select>
                    </div>

                    <div x-show="targetType === 'level' || targetType === 'class'" x-cloak class="space-y-2">
                        <x-form.select name="target_cycle_id" :label="__('academic.events.form.target_cycle')" icon="fa-diagram-project" x-model="targetCycleId">
                            <option value="">--</option>
                            <template x-for="cycle in filteredCycles()" :key="cycle.id">
                                <option :value="cycle.id" x-text="cycle.name"></option>
                            </template>
                        </x-form.select>

                        <template x-if="targetType === 'level'">
                            <div>
                                <x-form.select name="target_id" :label="__('academic.events.form.target_reference')" icon="fa-layer-group">
                                    <option value="">--</option>
                                    <template x-for="level in filteredLevels()" :key="level.id">
                                        <option
                                            :value="level.id"
                                            x-text="`${level.name} - ${level.cycle_name}`"
                                            :selected="String(level.id) === String('{{ old('target_id', $eventModel->target_id) }}')"
                                        ></option>
                                    </template>
                                </x-form.select>
                            </div>
                        </template>

                        <template x-if="targetType === 'class'">
                            <div class="space-y-2">
                                <x-form.select name="target_id" :label="__('academic.events.form.target_reference')" icon="fa-chalkboard">
                                    <option value="">--</option>
                                    <template x-for="classroom in filteredClasses()" :key="classroom.id">
                                        <option
                                            :value="classroom.id"
                                            x-text="`${classroom.name} - ${classroom.level_name}${classroom.series_name ? ' - ' + classroom.series_name : ''}`"
                                            :selected="String(classroom.id) === String('{{ old('target_id', $eventModel->target_id) }}')"
                                        ></option>
                                    </template>
                                </x-form.select>
                            </div>
                        </template>
                    </div>

                    <div x-show="['global', 'staff', 'teachers', 'students', 'parents'].includes(targetType)" x-cloak>
                        <x-ui.alert tone="info">{{ __('academic.events.form.target_group_hint') }}</x-ui.alert>
                    </div>

                    <x-form.select name="status" :label="__('academic.events.form.status')" icon="fa-signal">
                        @php
                            $statusFieldValue = old('status', $eventModel->status?->value ?? \App\Enums\Academic\CalendarEventStatus::PendingValidation->value);
                            $knownStatusValues = collect($statusOptions)->map(fn ($statusOption) => $statusOption->value)->all();
                        @endphp

                        @if (! in_array($statusFieldValue, $knownStatusValues, true) && $eventModel->status)
                            <option value="{{ $eventModel->status->value }}" selected>{{ $eventModel->status->label() }}</option>
                        @endif

                        @foreach ($statusOptions as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected(old('status', $eventModel->status?->value ?? \App\Enums\Academic\CalendarEventStatus::PendingValidation->value) === $statusOption->value)>{{ $statusOption->label() }}</option>
                        @endforeach
                    </x-form.select>

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="hidden" name="requires_validation" value="0">
                        <input type="checkbox" name="requires_validation" value="1" class="form-checkbox" @checked(old('requires_validation', $eventModel->requires_validation))>
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-shield-check text-emerald-500"></i>
                            {{ __('academic.events.form.requires_validation') }}
                        </span>
                    </label>
                </x-ui.card>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit">
                        {{ $isEdit ? __('academic.events.form.submit_edit') : __('academic.events.form.submit_create') }}
                    </x-ui.button>
                    <x-ui.button :href="route('academic.events.index')" variant="secondary">
                        {{ __('core.common.actions.back') }}
                    </x-ui.button>
                </div>
            </div>
        </section>
    </form>
</x-app-layout>
