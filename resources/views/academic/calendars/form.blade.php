<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('academic.calendars.form.edit_title') : __('academic.calendars.form.create_title')"
            :description="__('academic.calendars.form.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="route('academic.calendars.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                <x-ui.button type="submit" form="calendar-form">
                    {{ $isEdit ? __('academic.calendars.form.submit_edit') : __('academic.calendars.form.submit_create') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <form
        id="calendar-form"
        method="POST"
        action="{{ $isEdit ? route('academic.calendars.update', $calendar) : route('academic.calendars.store') }}"
        class="space-y-6"
        x-data="{
            scopeType: '{{ old('scope_type', $initialScopeType) }}',
            academicYearId: '{{ old('academic_year_id', $calendar->academic_year_id) }}',
            schoolId: '{{ old('school_id', $currentSchoolId) }}',
            cycleId: '{{ old('cycle_id', $calendar->cycle_id) }}',
            cycleFilter: '{{ old('scope_cycle_id', $calendar->cycle_id ?? $calendar->level?->cycle_id ?? $calendar->classroom?->level?->cycle_id) }}',
            cycles: @js($cycles->map(fn ($cycle) => ['id' => $cycle->id, 'name' => $cycle->name])->values()),
            yearCycleMap: @js($yearCycleMap),
            yearLevelMap: @js($yearLevelMap),
            levels: @js($levels->map(fn ($level) => ['id' => $level->id, 'name' => $level->short_name ?: $level->name, 'cycle_id' => $level->cycle_id, 'cycle_name' => $level->cycle->name])->values()),
            classrooms: @js($classrooms->map(fn ($classroom) => ['id' => $classroom->id, 'school_id' => $classroom->school_id, 'academic_year_id' => $classroom->academic_year_id, 'name' => $classroom->name, 'cycle_id' => $classroom->level->cycle_id, 'level_name' => $classroom->level->name, 'series_name' => $classroom->series?->short_name ?: $classroom->series?->name])->values()),
            filteredCycles() {
                const calendarYearId = String(this.academicYearId || '');

                if (! calendarYearId) {
                    return this.cycles;
                }

                const allowedCycleIds = this.yearCycleMap[calendarYearId] ?? [];

                return this.cycles.filter(cycle => allowedCycleIds.includes(String(cycle.id)));
            },
            filteredLevels() {
                const allowedLevelIds = this.yearLevelMap[String(this.academicYearId)] ?? [];

                return this.levels.filter(level => String(level.cycle_id) === String(this.cycleFilter)
                    && allowedLevelIds.includes(String(level.id)));
            },
            filteredClasses() {
                return this.classrooms.filter(classroom => String(classroom.cycle_id) === String(this.cycleFilter)
                    && String(classroom.academic_year_id) === String(this.academicYearId)
                    && String(classroom.school_id) === String(this.schoolId));
            },
        }"
        x-effect="
            if (cycleId && !filteredCycles().some(cycle => String(cycle.id) === String(cycleId))) {
                cycleId = '';
            }

            if (cycleFilter && !filteredCycles().some(cycle => String(cycle.id) === String(cycleFilter))) {
                cycleFilter = '';
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
        @if ($fromPeriods)
            <input type="hidden" name="from_periods" value="1">
        @endif

        <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.calendars.form.details_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.calendars.form.details_description') }}</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    @if ($canChooseSchool)
                        <x-form.select name="school_id" :label="__('academic.calendars.form.school')" icon="fa-school" x-model="schoolId">
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}" @selected((int) old('school_id', $currentSchoolId) === $school->id)>{{ $school->name }}</option>
                            @endforeach
                        </x-form.select>
                    @endif

                    <x-form.input name="name" :label="__('academic.calendars.form.name')" :value="$calendar->name" icon="fa-calendar-week" required />

                    <x-form.select name="academic_year_id" :label="__('academic.calendars.form.academic_year')" icon="fa-calendar-days" x-model="academicYearId">
                        @foreach ($academicYears as $academicYear)
                            <option value="{{ $academicYear->id }}" @selected((int) old('academic_year_id', $calendar->academic_year_id) === $academicYear->id)>{{ $academicYear->name }}</option>
                        @endforeach
                    </x-form.select>

                    <x-form.select name="period_type" :label="__('academic.configuration.form.period_type')" icon="fa-layer-group">
                        <option value="">{{ __('academic.common.not_configured') }}</option>
                        @foreach ($periodTypes as $periodType)
                            <option value="{{ $periodType->value }}" @selected(old('period_type', $calendar->period_type?->value) === $periodType->value)>{{ $periodType->label() }}</option>
                        @endforeach
                    </x-form.select>

                    <x-form.textarea name="description" :label="__('academic.calendars.form.description_label')" :value="$calendar->description" icon="fa-align-left" class="md:col-span-2" />

                    <x-ui.alert tone="info" class="md:col-span-2">
                        {{ __('academic.configuration.form.period_count_hint') }}
                    </x-ui.alert>

                    @if ($selectedGroup)
                        <x-ui.alert tone="info" class="md:col-span-2">
                            {{ __('academic.calendars.form.group_context', ['group' => $selectedGroupLabel]) }}
                        </x-ui.alert>
                    @endif
                </div>
            </x-ui.card>

            <div class="space-y-6">
                <x-ui.card class="space-y-5">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.calendars.form.scope_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.calendars.form.scope_description') }}</p>
                    </div>

                    <x-form.select name="scope_type" :label="__('academic.calendars.form.scope_type')" icon="fa-crosshairs" x-model="scopeType">
                        @foreach ($scopeOptions as $scopeOption)
                            <option value="{{ $scopeOption->value }}">{{ $scopeOption->label() }}</option>
                        @endforeach
                    </x-form.select>

                    <div x-show="scopeType === 'cycle'" x-cloak>
                        <x-form.select name="cycle_id" :label="__('academic.calendars.form.cycle')" icon="fa-diagram-project" x-model="cycleId">
                            <option value="">--</option>
                            <template x-for="cycle in filteredCycles()" :key="cycle.id">
                                <option
                                    :value="cycle.id"
                                    x-text="cycle.name"
                                    :selected="String(cycle.id) === String('{{ old('cycle_id', $calendar->cycle_id) }}')"
                                ></option>
                            </template>
                        </x-form.select>
                    </div>

                    <div x-show="scopeType === 'level'" x-cloak class="space-y-2">
                        <x-form.select name="scope_cycle_id" :label="__('academic.calendars.form.scope_cycle')" icon="fa-diagram-project" x-model="cycleFilter">
                            <option value="">--</option>
                            <template x-for="cycle in filteredCycles()" :key="cycle.id">
                                <option :value="cycle.id" x-text="cycle.name"></option>
                            </template>
                        </x-form.select>

                        <x-form.select name="level_id" :label="__('academic.calendars.form.level_id')" icon="fa-layer-group">
                            <option value="">--</option>
                            <template x-for="level in filteredLevels()" :key="level.id">
                                <option
                                    :value="level.id"
                                    x-text="`${level.name} - ${level.cycle_name}`"
                                    :selected="String(level.id) === String('{{ old('level_id', $calendar->level_id) }}')"
                                ></option>
                            </template>
                        </x-form.select>
                    </div>

                    <div x-show="scopeType === 'class'" x-cloak class="space-y-2">
                        <x-form.select name="scope_cycle_id" :label="__('academic.calendars.form.scope_cycle')" icon="fa-diagram-project" x-model="cycleFilter">
                            <option value="">--</option>
                            <template x-for="cycle in filteredCycles()" :key="cycle.id">
                                <option :value="cycle.id" x-text="cycle.name"></option>
                            </template>
                        </x-form.select>

                        <x-form.select name="class_id" :label="__('academic.calendars.form.class_id')" icon="fa-chalkboard">
                            <option value="">--</option>
                            <template x-for="classroom in filteredClasses()" :key="classroom.id">
                                <option
                                    :value="classroom.id"
                                    x-text="`${classroom.name} - ${classroom.level_name}${classroom.series_name ? ' - ' + classroom.series_name : ''}`"
                                    :selected="String(classroom.id) === String('{{ old('class_id', $calendar->class_id) }}')"
                                ></option>
                            </template>
                        </x-form.select>
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-checkbox" @checked(old('is_active', $calendar->is_active ?? true))>
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-toggle-on text-sky-500"></i>
                            {{ __('academic.calendars.form.active_label') }}
                        </span>
                    </label>
                </x-ui.card>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit">
                        {{ $isEdit ? __('academic.calendars.form.submit_edit') : __('academic.calendars.form.submit_create') }}
                    </x-ui.button>
                    <x-ui.button :href="route('academic.calendars.index')" variant="secondary">
                        {{ __('core.common.actions.back') }}
                    </x-ui.button>
                </div>
            </div>
        </section>
    </form>
</x-app-layout>
