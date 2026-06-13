<?php

namespace App\Http\Requests\Academic;

use App\Models\AcademicCalendar;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GenerateAcademicCalendarPeriodsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AcademicCalendar::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'academic_calendar_id' => ['nullable', 'integer', 'exists:academic_calendars,id'],
            'school_id' => ['nullable', 'integer', 'exists:schools,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('academic_calendar_id')) {
                return;
            }

            $calendar = AcademicCalendar::query()->find($this->integer('academic_calendar_id'));

            if (! $calendar) {
                return;
            }

            if ($calendar->academic_year_id !== $this->integer('academic_year_id')) {
                $validator->errors()->add('academic_calendar_id', __('academic.calendars.validation.calendar_year_mismatch'));
            }

            if ($this->filled('school_id') && $calendar->school_id !== $this->integer('school_id')) {
                $validator->errors()->add('academic_calendar_id', __('academic.calendars.validation.calendar_school_mismatch'));
            }
        });
    }
}
