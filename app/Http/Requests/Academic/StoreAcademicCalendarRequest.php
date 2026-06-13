<?php

namespace App\Http\Requests\Academic;

use App\Enums\Academic\AcademicPeriodType;
use App\Enums\Academic\CalendarScopeType;
use App\Models\AcademicCalendar;
use App\Models\AcademicClassroom;
use App\Models\AcademicLevel;
use App\Models\AcademicYear;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAcademicCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AcademicCalendar::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $periodType = $this->input('period_type');
        $scopeType = $this->input('scope_type') ?: CalendarScopeType::Global->value;

        $periodCount = filled($periodType) && in_array($periodType, AcademicPeriodType::values(), true)
            ? AcademicPeriodType::from($periodType)->defaultCount()
            : $this->input('period_count');

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'scope_type' => $scopeType,
            'cycle_id' => $scopeType === CalendarScopeType::Cycle->value && $this->filled('cycle_id')
                ? $this->input('cycle_id')
                : null,
            'period_count' => $periodCount,
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'scope_type' => ['required', Rule::in(CalendarScopeType::values())],
            'cycle_id' => ['nullable', 'integer', 'exists:academic_cycles,id'],
            'level_id' => ['nullable', 'integer', 'exists:academic_levels,id'],
            'class_id' => ['nullable', 'integer', 'exists:academic_classrooms,id'],
            'period_type' => ['nullable', Rule::in(AcademicPeriodType::values())],
            'period_count' => ['nullable', 'integer', 'min:1', 'max:6'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $scope = $this->string('scope_type')->toString();

            if ($scope === CalendarScopeType::Global->value) {
                return;
            }

            if ($scope === CalendarScopeType::Cycle->value && ! $this->filled('cycle_id')) {
                $validator->errors()->add('cycle_id', __('academic.calendars.validation.cycle_required'));
            }

            if ($scope === CalendarScopeType::Cycle->value && $this->filled('cycle_id')) {
                $academicYear = AcademicYear::query()
                    ->with('cycles:id')
                    ->find($this->integer('academic_year_id'));

                if (! $academicYear || ! $academicYear->cycles->contains('id', $this->integer('cycle_id'))) {
                    $validator->errors()->add('cycle_id', __('academic.calendars.validation.cycle_not_in_year'));
                }
            }

            if ($scope === CalendarScopeType::Level->value && ! $this->filled('level_id')) {
                $validator->errors()->add('level_id', __('academic.calendars.validation.level_required'));
            }

            if ($scope === CalendarScopeType::Level->value && $this->filled('level_id')) {
                $academicYear = AcademicYear::query()
                    ->with('levels:id')
                    ->find($this->integer('academic_year_id'));
                $level = AcademicLevel::query()->find($this->integer('level_id'));

                if ($level && (! $academicYear || ! $academicYear->levels->contains('id', $level->id))) {
                    $validator->errors()->add('level_id', __('academic.calendars.validation.level_not_in_year'));
                }
            }

            if ($scope === CalendarScopeType::ClassRoom->value && ! $this->filled('class_id')) {
                $validator->errors()->add('class_id', __('academic.calendars.validation.class_required'));
            }

            if ($scope === CalendarScopeType::ClassRoom->value && $this->filled('class_id')) {
                $classroom = AcademicClassroom::query()->find($this->integer('class_id'));

                if ($classroom && $classroom->academic_year_id !== $this->integer('academic_year_id')) {
                    $validator->errors()->add('class_id', __('academic.calendars.validation.class_year_mismatch'));
                }
            }

            if ($this->filled('period_type')) {
                $expectedCount = AcademicPeriodType::from($this->string('period_type')->toString())->defaultCount();

                if ($this->filled('period_count') && $this->integer('period_count') !== $expectedCount) {
                    $validator->errors()->add('period_count', __('academic.calendars.validation.period_count_mismatch'));
                }
            }
        });
    }
}
