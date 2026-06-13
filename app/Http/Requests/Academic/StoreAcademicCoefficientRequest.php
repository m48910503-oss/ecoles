<?php

namespace App\Http\Requests\Academic;

use App\Models\AcademicCoefficient;
use App\Models\AcademicLevel;
use App\Models\AcademicSeries;
use App\Models\AcademicSubject;
use App\Services\Core\SchoolContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAcademicCoefficientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AcademicCoefficient::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_optional' => $this->boolean('is_optional'),
            'is_active' => $this->boolean('is_active'),
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
            'level_id' => ['required', 'integer', 'exists:academic_levels,id'],
            'series_id' => ['nullable', 'integer', 'exists:academic_series,id'],
            'academic_subject_id' => ['required', 'integer', 'exists:academic_subjects,id'],
            'coefficient' => ['required', 'numeric', 'min:0.25', 'max:99.99'],
            'is_optional' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $level = AcademicLevel::query()->with('cycle')->find($this->integer('level_id'));
            $series = $this->filled('series_id')
                ? AcademicSeries::query()->find($this->integer('series_id'))
                : null;
            $subject = AcademicSubject::query()->find($this->integer('academic_subject_id'));

            if (! $level || ! $subject) {
                return;
            }

            if (! $level->uses_series && $series) {
                $validator->errors()->add('series_id', __('academic.coefficients.validation.series_not_allowed_for_level'));
            }

            if ($series && $series->cycle_id !== $level->cycle_id) {
                $validator->errors()->add('series_id', __('academic.coefficients.validation.series_cycle_mismatch'));
            }

            $schoolId = $this->resolvedSchoolId();

            if ($subject->school_id !== $schoolId) {
                $validator->errors()->add('academic_subject_id', __('academic.coefficients.validation.subject_school_mismatch'));
            }

            $exists = AcademicCoefficient::query()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $this->integer('academic_year_id'))
                ->where('level_id', $level->id)
                ->where('academic_subject_id', $subject->id)
                ->when($series, fn ($query) => $query->where('series_id', $series->id), fn ($query) => $query->whereNull('series_id'))
                ->exists();

            if ($exists) {
                $validator->errors()->add('academic_subject_id', __('academic.coefficients.validation.duplicate_assignment'));
            }
        });
    }

    private function resolvedSchoolId(): int
    {
        if ($this->filled('school_id')) {
            return $this->integer('school_id');
        }

        return app(SchoolContext::class)->current()->id;
    }
}
