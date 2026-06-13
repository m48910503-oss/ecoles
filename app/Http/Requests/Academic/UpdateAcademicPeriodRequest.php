<?php

namespace App\Http\Requests\Academic;

use App\Models\AcademicCalendar;
use App\Models\AcademicConfiguration;
use App\Models\AcademicPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAcademicPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        $period = $this->route('period');

        return $period ? ($this->user()?->can('update', $period) ?? false) : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'academic_calendar_id' => ['nullable', 'integer', 'exists:academic_calendars,id'],
            'academic_configuration_id' => ['nullable', 'integer', 'exists:academic_configurations,id'],
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'integer', 'min:1', 'max:12'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('academic_calendar_id') && ! $this->filled('academic_configuration_id')) {
                $validator->errors()->add('academic_calendar_id', __('academic.periods.validation.calendar_required'));

                return;
            }

            $calendar = $this->filled('academic_calendar_id')
                ? AcademicCalendar::query()->find($this->integer('academic_calendar_id'))
                : null;

            $configuration = $this->filled('academic_configuration_id')
                ? AcademicConfiguration::query()->find($this->integer('academic_configuration_id'))
                : null;

            if (! $calendar && ! $configuration) {
                return;
            }

            $expectedCount = $calendar?->period_count ?? $configuration?->period_count;

            if ($expectedCount !== null && $this->integer('position') > $expectedCount) {
                $validator->errors()->add('position', __('academic.periods.validation.position_out_of_range'));
            }
        });
    }
}
