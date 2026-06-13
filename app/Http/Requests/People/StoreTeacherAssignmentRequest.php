<?php

namespace App\Http\Requests\People;

use App\Models\AcademicClassroom;
use App\Models\AcademicSubject;
use App\Models\Teacher;
use App\Services\Core\SchoolContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTeacherAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $teacher = $this->route('teacher');

        return $teacher instanceof Teacher
            ? ($this->user()?->can('assignSubjects', $teacher) ?? false)
            : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'academic_subject_id' => ['required', 'integer', 'exists:academic_subjects,id'],
            'academic_classroom_id' => ['required', 'integer', 'exists:academic_classrooms,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $schoolId = app(SchoolContext::class)->current()->id;

            $subject = AcademicSubject::query()
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->find($this->integer('academic_subject_id'));

            if (! $subject) {
                $validator->errors()->add('academic_subject_id', __('people.teachers.validation.subject_invalid'));
            }

            $classroom = AcademicClassroom::query()
                ->where('school_id', $schoolId)
                ->where('is_active', true)
                ->find($this->integer('academic_classroom_id'));

            if (! $classroom) {
                $validator->errors()->add('academic_classroom_id', __('people.teachers.validation.classroom_invalid'));
            }
        });
    }
}
