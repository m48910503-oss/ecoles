<?php

namespace App\Http\Requests\Academic;

use App\Enums\Academic\CalendarScopeType;
use App\Enums\Academic\SchoolDayType;
use App\Models\AcademicYear;
use App\Models\SchoolDayConfiguration;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSchoolDayConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dayConfiguration = $this->route('day_configuration');

        return $dayConfiguration ? ($this->user()?->can('update', $dayConfiguration) ?? false) : false;
    }

    protected function prepareForValidation(): void
    {
        $scopeType = $this->input('scope_type') ?: CalendarScopeType::Global->value;

        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'scope_type' => $scopeType,
            'cycle_id' => $scopeType === CalendarScopeType::Cycle->value && $this->filled('cycle_id')
                ? $this->input('cycle_id')
                : null,
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
            'day_type' => ['required', Rule::in(SchoolDayType::values())],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'morning_start_time' => ['nullable', 'date_format:H:i'],
            'morning_end_time' => ['nullable', 'date_format:H:i'],
            'afternoon_start_time' => ['nullable', 'date_format:H:i'],
            'afternoon_end_time' => ['nullable', 'date_format:H:i'],
            'break_start_time' => ['nullable', 'date_format:H:i'],
            'break_end_time' => ['nullable', 'date_format:H:i'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validateTimes($validator));
    }

    private function validateTimes(Validator $validator): void
    {
        $scopeType = $this->string('scope_type')->toString();
        $dayType = $this->string('day_type')->toString();

        if ($scopeType === CalendarScopeType::Cycle->value && ! $this->filled('cycle_id')) {
            $validator->errors()->add('cycle_id', __('academic.day_config.validation.cycle_required'));
        }

        if ($scopeType === CalendarScopeType::Cycle->value && $this->filled('cycle_id')) {
            $academicYear = AcademicYear::query()
                ->with('cycles:id')
                ->find($this->integer('academic_year_id'));

            if (! $academicYear || ! $academicYear->cycles->contains('id', $this->integer('cycle_id'))) {
                $validator->errors()->add('cycle_id', __('academic.day_config.validation.cycle_not_in_year'));
            }
        }

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
            $requiredFields = ['morning_start_time', 'morning_end_time', 'afternoon_start_time', 'afternoon_end_time'];

            foreach ($requiredFields as $field) {
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
