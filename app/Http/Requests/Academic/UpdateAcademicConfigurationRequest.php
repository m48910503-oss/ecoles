<?php

namespace App\Http\Requests\Academic;

use App\Enums\Academic\AcademicPeriodType;
use App\Enums\Academic\CalendarScopeType;
use App\Enums\Academic\SchoolDayType;
use App\Models\AcademicYear;
use App\Models\AcademicConfiguration;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAcademicConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $configuration = $this->route('configuration');

        return $configuration ? ($this->user()?->can('update', $configuration) ?? false) : false;
    }

    protected function prepareForValidation(): void
    {
        $periodType = $this->input('period_type');
        $scopeType = $this->input('scope_type') ?: CalendarScopeType::Global->value;
        $cycleIds = collect($this->input('cycle_ids', []));

        if ($cycleIds->isEmpty() && $this->filled('cycle_id')) {
            $cycleIds = collect([$this->input('cycle_id')]);
        }

        $cycleIds = $cycleIds
            ->map(fn ($cycleId) => (int) $cycleId)
            ->filter(fn (int $cycleId) => $cycleId > 0)
            ->unique()
            ->sort()
            ->values();

        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'is_locked' => $this->boolean('is_locked'),
            'scope_type' => $scopeType,
            'cycle_ids' => $scopeType === CalendarScopeType::Cycle->value ? $cycleIds->all() : [],
            'cycle_id' => $scopeType === CalendarScopeType::Cycle->value && $cycleIds->count() === 1
                ? $cycleIds->first()
                : null,
            'period_count' => filled($periodType) && in_array($periodType, AcademicPeriodType::values(), true)
                ? AcademicPeriodType::from($periodType)->defaultCount()
                : $this->input('period_count'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'school_id' => ['nullable', 'integer', 'exists:schools,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'scope_type' => ['required', Rule::in([CalendarScopeType::Global->value, CalendarScopeType::Cycle->value])],
            'cycle_id' => ['nullable', 'integer', 'exists:academic_cycles,id'],
            'cycle_ids' => ['nullable', 'array'],
            'cycle_ids.*' => ['integer', 'exists:academic_cycles,id'],
            'period_type' => ['required', Rule::in(AcademicPeriodType::values())],
            'period_count' => ['required', 'integer', 'min:1', 'max:6'],
            'school_day_type' => ['required', Rule::in(SchoolDayType::values())],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'morning_start_time' => ['nullable', 'date_format:H:i'],
            'morning_end_time' => ['nullable', 'date_format:H:i'],
            'afternoon_start_time' => ['nullable', 'date_format:H:i'],
            'afternoon_end_time' => ['nullable', 'date_format:H:i'],
            'break_start_time' => ['nullable', 'date_format:H:i'],
            'break_end_time' => ['nullable', 'date_format:H:i'],
            'is_active' => ['required', 'boolean'],
            'is_locked' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $scopeType = $this->string('scope_type')->toString();
            $periodType = $this->string('period_type')->toString();
            $cycleIds = collect($this->input('cycle_ids', []))
                ->map(fn ($cycleId) => (int) $cycleId)
                ->filter(fn (int $cycleId) => $cycleId > 0)
                ->unique()
                ->values();

            if ($scopeType === CalendarScopeType::Cycle->value && $cycleIds->isEmpty()) {
                $validator->errors()->add('cycle_ids', __('academic.configuration.validation.cycle_required'));
            }

            if ($scopeType === CalendarScopeType::Cycle->value && $cycleIds->isNotEmpty()) {
                $academicYear = AcademicYear::query()
                    ->with('cycles:id')
                    ->find($this->integer('academic_year_id'));

                if (! $academicYear || $cycleIds->diff($academicYear->cycles->pluck('id')->map(fn ($cycleId) => (int) $cycleId))->isNotEmpty()) {
                    $validator->errors()->add('cycle_ids', __('academic.configuration.validation.cycle_not_in_year'));
                }
            }

            if ($periodType === '') {
                return;
            }

            $expectedCount = AcademicPeriodType::from($periodType)->defaultCount();

            if ($this->integer('period_count') !== $expectedCount) {
                $validator->errors()->add('period_count', __('academic.configuration.validation.period_count_mismatch'));
            }

            $this->validateDayType($validator);
        });
    }

    private function validateDayType(Validator $validator): void
    {
        $dayType = $this->string('school_day_type')->toString();

        if ($dayType === SchoolDayType::Continuous->value) {
            if (! $this->filled('start_time') || ! $this->filled('end_time')) {
                $validator->errors()->add('start_time', __('academic.day_config.validation.continuous_times_required'));

                return;
            }

            if ($this->toMinutes((string) $this->input('end_time')) <= $this->toMinutes((string) $this->input('start_time'))) {
                $validator->errors()->add('end_time', __('academic.day_config.validation.end_must_be_after_start'));
            }
        }

        if ($dayType === SchoolDayType::Discontinuous->value) {
            foreach (['morning_start_time', 'morning_end_time', 'afternoon_start_time', 'afternoon_end_time'] as $field) {
                if (! $this->filled($field)) {
                    $validator->errors()->add($field, __('academic.day_config.validation.discontinuous_times_required'));
                }
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->toMinutes((string) $this->input('morning_end_time')) <= $this->toMinutes((string) $this->input('morning_start_time'))) {
                $validator->errors()->add('morning_end_time', __('academic.day_config.validation.morning_end_after_start'));
            }

            if ($this->toMinutes((string) $this->input('afternoon_end_time')) <= $this->toMinutes((string) $this->input('afternoon_start_time'))) {
                $validator->errors()->add('afternoon_end_time', __('academic.day_config.validation.afternoon_end_after_start'));
            }

            if ($this->toMinutes((string) $this->input('afternoon_start_time')) <= $this->toMinutes((string) $this->input('morning_end_time'))) {
                $validator->errors()->add('afternoon_start_time', __('academic.day_config.validation.afternoon_after_morning'));
            }
        }

        if ($this->filled('break_start_time') xor $this->filled('break_end_time')) {
            $validator->errors()->add('break_end_time', __('academic.day_config.validation.break_pair_required'));
        }

        if ($this->filled('break_start_time') && $this->filled('break_end_time')) {
            if ($this->toMinutes((string) $this->input('break_end_time')) <= $this->toMinutes((string) $this->input('break_start_time'))) {
                $validator->errors()->add('break_end_time', __('academic.day_config.validation.break_end_after_start'));
            }
        }
    }

    private function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }
}
