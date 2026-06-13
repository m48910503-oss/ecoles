<?php

namespace App\Http\Requests\Academic;

use App\Models\AcademicConductSetting;
use App\Models\AcademicSubject;
use App\Services\Core\SchoolContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAcademicConductSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AcademicConductSetting::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'deductions_from_sanctions_only' => $this->boolean('deductions_from_sanctions_only'),
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
            'academic_subject_id' => ['required', 'integer', 'exists:academic_subjects,id'],
            'base_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'deductions_from_sanctions_only' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $subject = AcademicSubject::query()->find($this->integer('academic_subject_id'));

            if (! $subject) {
                return;
            }

            $schoolId = $this->resolvedSchoolId();

            if (! $subject->is_conduct) {
                $validator->errors()->add('academic_subject_id', __('academic.conduct.validation.subject_must_be_conduct'));
            }

            if ($subject->school_id !== $schoolId) {
                $validator->errors()->add('academic_subject_id', __('academic.conduct.validation.subject_school_mismatch'));
            }

            $exists = AcademicConductSetting::query()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $this->integer('academic_year_id'))
                ->where('level_id', $this->integer('level_id'))
                ->exists();

            if ($exists) {
                $validator->errors()->add('level_id', __('academic.conduct.validation.duplicate_level_setting'));
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
