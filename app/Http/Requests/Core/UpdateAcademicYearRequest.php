<?php

namespace App\Http\Requests\Core;

use App\Enums\Academic\AcademicPeriodType;
use App\Enums\Academic\SchoolDayType;
use App\Models\AcademicCycle;
use App\Models\AcademicYear;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        $academicYear = $this->route('academic_year');

        return $academicYear ? ($this->user()?->can('update', $academicYear) ?? false) : false;
    }

    protected function prepareForValidation(): void
    {
        $startDate = $this->input('start_date');
        $endDate = $this->input('end_date');

        $payload = [
            'is_active' => $this->boolean('is_active'),
            'enrollment_open' => $this->boolean('enrollment_open'),
            'configure_now' => $this->boolean('configure_now'),
        ];

        if (filled($startDate) && filled($endDate)) {
            $payload['name'] = $this->yearLabel($startDate, $endDate);
            $payload['code'] = $payload['name'];
        }

        if ($this->has('multi_school_enabled')) {
            $payload['multi_school_enabled'] = $this->boolean('multi_school_enabled');
        }

        if ($this->filled('period_type') && in_array($this->input('period_type'), AcademicPeriodType::values(), true)) {
            $payload['period_count'] = AcademicPeriodType::from((string) $this->input('period_type'))->defaultCount();
        }

        $this->merge($payload);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var AcademicYear $academicYear */
        $academicYear = $this->route('academic_year');
        $hasCycles = AcademicCycle::query()->exists();

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('academic_years', 'code')->ignore($academicYear)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['required', 'boolean'],
            'enrollment_open' => ['required', 'boolean'],
            'multi_school_enabled' => ['sometimes', 'boolean'],
            'configure_now' => ['required', 'boolean'],
            'cycle_ids' => $hasCycles ? ['required', 'array', 'min:1'] : ['sometimes', 'array'],
            'cycle_ids.*' => ['integer', Rule::exists('academic_cycles', 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],
            'period_type' => [Rule::requiredIf($this->boolean('configure_now')), Rule::in(AcademicPeriodType::values())],
            'period_count' => ['nullable', 'integer', 'min:1', 'max:6'],
            'school_day_type' => [Rule::requiredIf($this->boolean('configure_now')), Rule::in(SchoolDayType::values())],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'morning_start_time' => ['nullable', 'date_format:H:i'],
            'morning_end_time' => ['nullable', 'date_format:H:i'],
            'afternoon_start_time' => ['nullable', 'date_format:H:i'],
            'afternoon_end_time' => ['nullable', 'date_format:H:i'],
            'break_start_time' => ['nullable', 'date_format:H:i'],
            'break_end_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cycle_ids.required' => __('core.academic_years.validation.cycles_required'),
            'cycle_ids.min' => __('core.academic_years.validation.cycles_required'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('configure_now')) {
                return;
            }

            if (! $this->filled('period_type')) {
                $validator->errors()->add('period_type', __('validation.required', ['attribute' => __('academic.configuration.form.period_type')]));
            }

            if (! $this->filled('school_day_type')) {
                $validator->errors()->add('school_day_type', __('validation.required', ['attribute' => __('academic.configuration.form.school_day_type')]));
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validateDayType($validator);
        });
    }

    private function yearLabel(string $startDate, string $endDate): string
    {
        return date('Y', strtotime($startDate)).'-'.date('Y', strtotime($endDate));
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
