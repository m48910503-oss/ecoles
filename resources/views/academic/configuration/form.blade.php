@php
    use App\Enums\Academic\AcademicPeriodType;
    use App\Enums\Academic\CalendarScopeType;
    use App\Enums\Academic\SchoolDayType;

    $configurationModel = $configurationModel ?? null;
    $dayConfigurationModel = $dayConfigurationModel ?? null;
    $periodTypes = $periodTypes ?? AcademicPeriodType::cases();
    $schoolDayTypes = $schoolDayTypes ?? SchoolDayType::cases();
    $scopeOptions = $scopeOptions ?? [CalendarScopeType::Global, CalendarScopeType::Cycle];
    $academicYears = $academicYears ?? collect();
    $selectedAcademicYearId = (string) ($selectedAcademicYearId ?? old('academic_year_id', $configurationModel?->academic_year_id ?? ''));
    $selectedScopeType = (string) ($selectedScopeType ?? old('scope_type', $configurationModel?->scope_type?->value ?? CalendarScopeType::Global->value));
    $selectedCycleIds = collect($selectedCycleIds ?? old('cycle_ids', []))
        ->map(fn ($cycleId) => (string) $cycleId)
        ->values()
        ->all();
    $selectedPeriodType = old('period_type', $configurationModel?->period_type?->value ?? AcademicPeriodType::Trimester->value);
    $selectedDayType = old('school_day_type', $dayConfigurationModel?->day_type?->value ?? $configurationModel?->school_day_type?->value ?? SchoolDayType::Continuous->value);
    $yearCycles = $academicYears->mapWithKeys(fn ($academicYear) => [
        $academicYear->id => $academicYear->cycles->map(fn ($cycle) => [
            'id' => $cycle->id,
            'name' => $cycle->name,
        ])->values(),
    ]);
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('academic.configuration.form.edit_title') : __('academic.configuration.form.create_title')"
            :description="__('academic.configuration.form.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="route('academic.configuration.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                <x-ui.button type="submit" form="configuration-form">
                    {{ $isEdit ? __('academic.configuration.form.submit_edit') : __('academic.configuration.form.submit_create') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <form
        id="configuration-form"
        method="POST"
        action="{{ $isEdit ? route('academic.configuration.update', $configuration) : route('academic.configuration.store') }}"
        class="space-y-6"
    >
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <div
                x-data="{
                    scopeType: @js($selectedScopeType),
                    academicYearId: @js($selectedAcademicYearId),
                    selectedCycleIds: @js($selectedCycleIds),
                    yearCycles: @js($yearCycles),
                    get availableCycles() {
                        return this.yearCycles[this.academicYearId] ?? [];
                    },
                    hasSelectedCycles() {
                        return this.selectedCycleIds.length > 0;
                    },
                    syncScope() {
                        if (this.scopeType !== 'cycle') {
                            this.selectedCycleIds = [];
                            return;
                        }

                        const allowedIds = this.availableCycles.map(cycle => String(cycle.id));

                        this.selectedCycleIds = this.selectedCycleIds.filter(cycleId => allowedIds.includes(String(cycleId)));
                    },
                    dayType: @js($selectedDayType),
                }"
                x-init="syncScope()"
                x-effect="syncScope()"
                class="space-y-6"
            >
                <x-ui.card class="space-y-5">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.configuration.form.context_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.configuration.form.context_description') }}</p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        @if ($canChooseSchool)
                            <x-form.select name="school_id" :label="__('academic.configuration.form.school')" icon="fa-school">
                                @foreach ($schools as $school)
                                    <option value="{{ $school->id }}" @selected((int) old('school_id', $currentSchoolId) === $school->id)>{{ $school->name }}</option>
                                @endforeach
                            </x-form.select>
                        @endif

                        <x-form.select name="academic_year_id" :label="__('academic.configuration.form.academic_year')" icon="fa-calendar-days" x-model="academicYearId">
                            @foreach ($academicYears as $academicYear)
                                <option value="{{ $academicYear->id }}" @selected((int) $selectedAcademicYearId === $academicYear->id)>{{ $academicYear->name }}</option>
                            @endforeach
                        </x-form.select>

                        <x-form.select name="scope_type" :label="__('academic.configuration.form.scope_type')" icon="fa-diagram-project" x-model="scopeType">
                            @foreach ($scopeOptions as $scopeOption)
                                <option value="{{ $scopeOption->value }}" @selected($selectedScopeType === $scopeOption->value)>{{ $scopeOption->label() }}</option>
                            @endforeach
                        </x-form.select>
                    </div>

                    <div x-show="scopeType === 'cycle'" x-cloak class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('academic.configuration.form.cycle') }}</label>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('academic.configuration.form.cycle_hint') }}</p>
                        </div>

                        <div x-show="availableCycles.length > 0" x-cloak class="grid gap-3 sm:grid-cols-2">
                            <template x-for="cycle in availableCycles" :key="cycle.id">
                                <label
                                    class="flex cursor-pointer items-start gap-3 rounded-2xl border px-4 py-3 transition"
                                    :class="selectedCycleIds.includes(String(cycle.id))
                                        ? 'border-sky-300 bg-sky-50/80 text-sky-900 dark:border-sky-500/40 dark:bg-sky-950/30 dark:text-sky-100'
                                        : 'border-gray-200 bg-white/80 text-gray-700 dark:border-gray-800 dark:bg-gray-950/40 dark:text-gray-200'"
                                >
                                    <input type="checkbox" name="cycle_ids[]" class="mt-1 form-checkbox" :value="String(cycle.id)" x-model="selectedCycleIds">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold" x-text="cycle.name"></span>
                                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ __('academic.configuration.form.cycle_card_help') }}</span>
                                    </span>
                                </label>
                            </template>
                        </div>

                        <x-ui.alert x-show="availableCycles.length === 0" x-cloak tone="info">
                            {{ __('academic.configuration.form.cycle_empty') }}
                        </x-ui.alert>

                        <div x-show="hasSelectedCycles()" x-cloak class="rounded-2xl border border-emerald-100 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-200">
                            <span x-text="selectedCycleIds.length"></span>
                            {{ __('academic.configuration.form.cycle_selected_count') }}
                        </div>
                    </div>
                </x-ui.card>

                @include('academic.configuration.partials.structure-editor', [
                    'configurationModel' => $configuration,
                    'dayConfigurationModel' => $dayConfigurationModel,
                    'periodTypes' => $periodTypes,
                    'schoolDayTypes' => $schoolDayTypes,
                ])
            </div>

            <div class="space-y-6">
                <x-ui.card class="space-y-5">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.configuration.form.governance_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.configuration.form.governance_description') }}</p>
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-checkbox" @checked(old('is_active', $configuration->is_active ?? true))>
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-toggle-on text-emerald-500"></i>
                            {{ __('academic.configuration.form.active_label') }}
                        </span>
                    </label>

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="hidden" name="is_locked" value="0">
                        <input type="checkbox" name="is_locked" value="1" class="form-checkbox" @checked(old('is_locked', $configuration->is_locked))>
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-lock text-rose-500"></i>
                            {{ __('academic.configuration.form.locked_label') }}
                        </span>
                    </label>

                    <x-ui.alert tone="info">{{ __('academic.configuration.form.lock_hint') }}</x-ui.alert>
                </x-ui.card>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit">
                        {{ $isEdit ? __('academic.configuration.form.submit_edit') : __('academic.configuration.form.submit_create') }}
                    </x-ui.button>
                    <x-ui.button :href="route('academic.configuration.index')" variant="secondary">
                        {{ __('core.common.actions.back') }}
                    </x-ui.button>
                </div>
            </div>
        </section>
    </form>
</x-app-layout>
