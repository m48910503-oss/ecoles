<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('academic.classes.form.edit_title') : __('academic.classes.form.create_title')"
            :description="__('academic.classes.form.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="$backRoute" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                <x-ui.button type="submit" form="classroom-form">
                    {{ $isEdit ? __('academic.classes.form.submit_edit') : __('academic.classes.form.submit_create') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    @php
        $selectedCycleId = (string) old('cycle_id', $selectedCycle?->id ?? $classroom->level?->cycle_id ?? '');
        $selectedLevelId = (string) old('level_id', $classroom->level_id ?? '');
        $selectedSeriesId = (string) old('series_id', $classroom->series_id ?? '');
        $isCustomName = (bool) old('custom_name', $isEdit);
        $selectedSchoolId = (string) old('school_id', $classroom->school_id ?: $currentSchoolId);
        $selectedAcademicYearId = (int) old('academic_year_id', $selectedAcademicYear?->id ?? $namingAcademicYearId);
    @endphp

    <form
        id="classroom-form"
        method="POST"
        action="{{ $isEdit ? route('academic.classes.update', $classroom) : route('academic.classes.store') }}"
        class="space-y-6"
        x-data="{
            cycleId: @js($selectedCycleId),
            levelId: @js($selectedLevelId),
            seriesId: @js($selectedSeriesId),
            schoolId: @js($selectedSchoolId),
            academicYearId: @js($selectedAcademicYearId),
            namingAcademicYearId: @js((int) $namingAcademicYearId),
            ignoreClassroomId: @js($isEdit ? $classroom->id : null),
            customName: @js($isCustomName),
            nameValue: @js(old('name', $classroom->name)),
            sectionNumber: @js((int) old('section_number', 1)),
            classrooms: @js($classrooms->map(fn ($classroomItem) => [
                'id' => $classroomItem->id,
                'school_id' => $classroomItem->school_id,
                'academic_year_id' => $classroomItem->academic_year_id,
                'level_id' => $classroomItem->level_id,
                'series_id' => $classroomItem->series_id,
                'name' => $classroomItem->name,
            ])->values()),
            cycles: @js($cycles->map(fn ($cycle) => [
                'id' => $cycle->id,
                'name' => $cycle->name,
                'category' => $cycle->category?->value,
            ])->values()),
            levels: @js($levels->map(fn ($level) => [
                'id' => $level->id,
                'name' => $level->short_name ?: $level->name,
                'short_name' => $level->short_name ?: $level->name,
                'cycle_id' => $level->cycle_id,
                'cycle_name' => $level->cycle->name,
                'cycle_category' => $level->cycle->category?->value,
                'uses_series' => $level->uses_series,
                'series_ids' => $level->series->pluck('id')->map(fn ($seriesId) => (string) $seriesId)->values(),
            ])->values()),
            seriesOptions: @js($seriesOptions->map(fn ($series) => [
                'id' => $series->id,
                'name' => $series->short_name ?: $series->name,
                'short_name' => $series->short_name ?: $series->name,
                'cycle_id' => $series->cycle_id,
                'cycle_name' => $series->cycle->name,
                'code' => $series->code,
            ])->values()),
            selectedCycle() {
                return this.cycles.find(cycle => String(cycle.id) === String(this.cycleId));
            },
            selectedLevel() {
                return this.levels.find(level => String(level.id) === String(this.levelId));
            },
            selectedSeries() {
                return this.seriesOptions.find(series => String(series.id) === String(this.seriesId));
            },
            escapeRegExp(value) {
                return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            },
            extractSectionLetter(name, baseLabel) {
                const pattern = new RegExp(`^${this.escapeRegExp(baseLabel)}\\s*-\\s*([A-Z]+)$`, 'i');
                const match = String(name || '').match(pattern);

                return match ? String(match[1]).toUpperCase() : '';
            },
            extractSectionNumber(name, baseLabel) {
                const pattern = new RegExp(`^${this.escapeRegExp(baseLabel)}\\s*(\\d+)$`, 'i');
                const match = String(name || '').match(pattern);

                return match ? Number(match[1]) : null;
            },
            indexToLetters(index) {
                let label = '';

                while (index > 0) {
                    index -= 1;
                    label = String.fromCharCode(65 + (index % 26)) + label;
                    index = Math.floor(index / 26);
                }

                return label;
            },
            selectedBaseName() {
                const level = this.selectedLevel();

                if (! level) {
                    return '';
                }

                const baseLabel = level.short_name || level.name;

                if (! this.isUpperSecondary()) {
                    return baseLabel;
                }

                if (level.uses_series && ! this.selectedSeries()) {
                    return '';
                }

                const series = this.selectedSeries();
                const seriesLabel = series ? (series.short_name || series.name) : '';

                return seriesLabel ? `${baseLabel} ${seriesLabel}` : baseLabel;
            },
            classroomsForSelectedStructure() {
                const level = this.selectedLevel();

                if (! level || ! this.schoolId || ! this.namingAcademicYearId) {
                    return [];
                }

                return this.classrooms.filter(classroom => String(classroom.school_id) === String(this.schoolId)
                    && String(classroom.academic_year_id) === String(this.namingAcademicYearId)
                    && String(classroom.level_id) === String(level.id)
                    && String(classroom.id) !== String(this.ignoreClassroomId ?? '')
                    && (! this.isUpperSecondary() || String(classroom.series_id ?? '') === String(this.seriesId ?? '')));
            },
            nextSectionLetter() {
                const level = this.selectedLevel();

                if (! level || this.isUpperSecondary()) {
                    return '';
                }

                const baseLabel = this.selectedBaseName();

                if (! baseLabel || ! this.schoolId || ! this.namingAcademicYearId) {
                    return '';
                }

                const classroomNames = this.classroomsForSelectedStructure().map(classroom => String(classroom.name || '').trim()).filter(name => Boolean(name));

                if (! classroomNames.length) {
                    return '';
                }

                const usedLetters = classroomNames
                    .map(name => this.extractSectionLetter(name, baseLabel))
                    .filter(letter => Boolean(letter));
                const uniqueLetters = [...new Set(usedLetters)];

                if (classroomNames.includes(baseLabel)) {
                    uniqueLetters.push('A');
                }

                for (let index = 1; index <= 1000; index += 1) {
                    const candidate = this.indexToLetters(index);

                    if (! uniqueLetters.includes(candidate)) {
                        return candidate;
                    }
                }

                return 'B';
            },
            nextSectionNumber() {
                if (! this.isUpperSecondary()) {
                    return 1;
                }

                const baseLabel = this.selectedBaseName();

                if (! baseLabel) {
                    return 1;
                }

                const classroomNames = this.classroomsForSelectedStructure().map(classroom => String(classroom.name || '').trim()).filter(name => Boolean(name));

                if (! classroomNames.length) {
                    return 1;
                }

                const usedNumbers = classroomNames
                    .map(name => this.extractSectionNumber(name, baseLabel))
                    .filter(number => Number.isInteger(number));
                const uniqueNumbers = [...new Set(usedNumbers)];

                if (classroomNames.includes(baseLabel)) {
                    uniqueNumbers.push(1);
                }

                for (let candidate = 1; candidate <= 99; candidate += 1) {
                    if (! uniqueNumbers.includes(candidate)) {
                        return candidate;
                    }
                }

                return Math.max(...uniqueNumbers) + 1;
            },
            availableLevels() {
                if (! this.cycleId) {
                    return [];
                }

                return this.levels.filter(level => String(level.cycle_id) === String(this.cycleId));
            },
            requiresSeries() {
                return Boolean(this.selectedLevel() && this.selectedLevel().uses_series);
            },
            availableSeries() {
                const level = this.selectedLevel();

                if (! level) {
                    return [];
                }

                return this.seriesOptions.filter(series => level.series_ids.includes(String(series.id)));
            },
            isUpperSecondary() {
                return Boolean(this.selectedLevel() && this.selectedLevel().cycle_category === 'upper_secondary');
            },
            generatedName() {
                const level = this.selectedLevel();
                const baseLabel = this.selectedBaseName();

                if (! level || ! baseLabel) {
                    return '';
                }

                if (this.isUpperSecondary()) {
                    const number = Math.max(Number(this.sectionNumber || 0), this.nextSectionNumber(), 1);

                    return number <= 1 ? baseLabel : `${baseLabel}${number}`;
                }

                const letter = this.nextSectionLetter();

                return letter ? `${baseLabel} - ${letter}` : baseLabel;
            },
            syncGeneratedName() {
                if (! this.customName) {
                    if (this.isUpperSecondary()) {
                        this.sectionNumber = Math.max(Number(this.sectionNumber || 0), this.nextSectionNumber(), 1);
                    } else {
                        this.sectionNumber = 1;
                    }

                    this.nameValue = this.generatedName();
                }
            },
        }"
        x-effect="
            if (!cycleId && (levelId || seriesId)) {
                levelId = '';
                seriesId = '';
            }

            if (cycleId && levelId && !availableLevels().some(level => String(level.id) === String(levelId))) {
                levelId = '';
                seriesId = '';
            }

            if (seriesId && !availableSeries().some(series => String(series.id) === String(seriesId))) {
                seriesId = '';
            }

            if (!customName) {
                syncGeneratedName();
            }
        "
    >
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif
        @if ($selectedAcademicYearId > 0)
            <input type="hidden" name="academic_year_id" value="{{ $selectedAcademicYearId }}">
        @endif
        @if ($returnToYearCycle)
            <input type="hidden" name="return_to_year_cycle" value="1">
        @endif
        @if ($returnToYearCycle && $selectedCycle)
            <input type="hidden" name="cycle_id" value="{{ $selectedCycle->id }}">
        @endif

        @if ($errors->has('academic_year_id'))
            <x-ui.alert tone="error">
                {{ $errors->first('academic_year_id') }}
            </x-ui.alert>
        @endif

        <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.classes.form.details_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.classes.form.details_description') }}</p>
                </div>

                <div class="space-y-5">
                    @if ($canChooseSchool)
                        <x-form.select name="school_id" :label="__('academic.classes.form.school')" icon="fa-school" x-model="schoolId">
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}" @selected((int) old('school_id', $currentSchoolId) === $school->id)>{{ $school->name }}</option>
                            @endforeach
                        </x-form.select>
                    @endif

                    @if ($selectedAcademicYear)
                        <div class="rounded-2xl border border-sky-100 bg-sky-50/40 p-4 dark:border-sky-900/40 dark:bg-sky-950/15">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700 dark:text-sky-300">{{ __('academic.classes.form.academic_year') }}</p>
                            <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">{{ $selectedAcademicYear->name }}</p>
                        </div>
                    @endif

                    @if ($returnToYearCycle && $selectedCycle)
                        <div class="rounded-2xl border border-violet-100 bg-violet-50/40 p-4 dark:border-violet-900/40 dark:bg-violet-950/15">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-violet-700 dark:text-violet-300">{{ __('academic.classes.form.cycle') }}</p>
                            <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">{{ $selectedCycle->name }}</p>
                        </div>
                    @else
                        <x-form.select
                            name="cycle_id"
                            :label="__('academic.classes.form.cycle')"
                            icon="fa-arrows-rotate"
                            x-model="cycleId"
                            :placeholder="__('academic.classes.form.cycle_placeholder')"
                            required
                        >
                            @foreach ($cycles as $cycle)
                                <option value="{{ $cycle->id }}" @selected((string) $selectedCycleId === (string) $cycle->id)>{{ $cycle->name }}</option>
                            @endforeach
                        </x-form.select>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('academic.classes.form.cycle_hint') }}</p>
                    @endif

                    <div x-show="!cycleId" x-cloak>
                        <x-ui.alert tone="info">
                            {{ __('academic.classes.form.level_cycle_prompt') }}
                        </x-ui.alert>
                    </div>

                    <div x-show="cycleId" x-cloak class="space-y-5">
                        <div x-show="availableLevels().length > 0" x-cloak class="space-y-2">
                            <x-form.select
                                name="level_id"
                                :label="__('academic.classes.form.level')"
                                icon="fa-layer-group"
                                x-model="levelId"
                                :placeholder="__('academic.classes.form.level_placeholder')"
                                x-bind:disabled="!cycleId || availableLevels().length === 0"
                                required
                            >
                                <template x-for="level in availableLevels()" :key="level.id">
                                    <option :value="String(level.id)" x-text="level.name"></option>
                                </template>
                            </x-form.select>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('academic.classes.form.level_hint') }}</p>
                        </div>

                        <div x-show="availableLevels().length === 0" x-cloak>
                            <x-ui.alert tone="warning">
                                {{ __('academic.classes.form.level_empty') }}
                            </x-ui.alert>
                        </div>

                        <div x-show="requiresSeries()" x-cloak class="space-y-2">
                            <x-form.select
                                name="series_id"
                                :label="__('academic.classes.form.series')"
                                icon="fa-sitemap"
                                x-model="seriesId"
                                :placeholder="__('academic.classes.form.series_placeholder')"
                                x-bind:disabled="!requiresSeries() || !availableSeries().length"
                            >
                                <template x-for="serie in availableSeries()" :key="serie.id">
                                    <option :value="String(serie.id)" x-text="serie.name"></option>
                                </template>
                            </x-form.select>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('academic.classes.form.series_hint') }}</p>
                        </div>

                        <div x-show="requiresSeries() && availableSeries().length === 0" x-cloak>
                            <x-ui.alert tone="warning">
                                {{ __('academic.classes.form.series_empty') }}
                            </x-ui.alert>
                        </div>

                        <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                            <input type="hidden" name="custom_name" value="0">
                            <input type="checkbox" name="custom_name" value="1" class="form-checkbox" x-model="customName" @checked($isCustomName)>
                            <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                                <i class="fa-solid fa-pen-to-square text-sky-500"></i>
                                {{ __('academic.classes.form.custom_name_label') }}
                            </span>
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('academic.classes.form.custom_name_hint') }}</p>

                        <div x-show="!customName && selectedLevel()" x-cloak class="space-y-4 rounded-2xl border border-sky-100 bg-sky-50/40 p-4 dark:border-sky-900/40 dark:bg-sky-950/15">
                            <div x-show="!isUpperSecondary()" x-cloak>
                                <p class="text-sm font-medium text-sky-700 dark:text-sky-300">{{ __('academic.classes.form.section_auto_hint') }}</p>
                            </div>

                            <div x-show="isUpperSecondary()" x-cloak class="space-y-2">
                                <x-form.input
                                    name="section_number"
                                    type="number"
                                    :label="__('academic.classes.form.section_number')"
                                    icon="fa-hashtag"
                                    x-model.number="sectionNumber"
                                    x-bind:disabled="customName || !isUpperSecondary() || !selectedLevel()"
                                    min="1"
                                    max="99"
                                    placeholder="1"
                                    required
                                />
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('academic.classes.form.number_hint') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            <div class="space-y-6">
                <x-ui.card class="space-y-5">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.classes.form.governance_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.classes.form.governance_description') }}</p>
                    </div>

                    <div class="space-y-2">
                        <x-form.input
                            name="name"
                            :label="__('academic.classes.form.name')"
                            :value="$classroom->name"
                            icon="fa-chalkboard"
                            x-model="nameValue"
                            x-bind:readonly="!customName"
                            required
                        />
                        <p x-show="customName" x-cloak class="text-xs text-gray-500 dark:text-gray-400">{{ __('academic.classes.form.custom_name_entry_hint') }}</p>
                        <p x-show="!customName && selectedLevel() && (!requiresSeries() || selectedSeries())" x-cloak class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('academic.classes.form.generated_name_label') }}:
                            <span class="font-semibold text-gray-900 dark:text-white" x-text="generatedName()"></span>
                        </p>
                        <p x-show="!customName && selectedLevel() && requiresSeries() && !selectedSeries()" x-cloak class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('academic.classes.form.series_name_prompt') }}
                        </p>
                        <p x-show="!customName && !selectedLevel()" x-cloak class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('academic.classes.form.generate_name_prompt') }}
                        </p>
                    </div>

                    <x-form.input name="capacity" type="number" :label="__('academic.classes.form.capacity')" :value="$classroom->capacity" icon="fa-users" min="1" max="999" />

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-checkbox" @checked(old('is_active', $classroom->is_active ?? true))>
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-toggle-on text-sky-500"></i>
                            {{ __('academic.classes.form.active_label') }}
                        </span>
                    </label>
                </x-ui.card>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit">
                        {{ $isEdit ? __('academic.classes.form.submit_edit') : __('academic.classes.form.submit_create') }}
                    </x-ui.button>
                    <x-ui.button :href="$backRoute" variant="secondary">
                        {{ __('core.common.actions.back') }}
                    </x-ui.button>
                </div>
            </div>
        </section>
    </form>
</x-app-layout>
