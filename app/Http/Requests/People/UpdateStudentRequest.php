<?php

namespace App\Http\Requests\People;

use App\Models\AcademicClassroom;
use App\Models\Student;
use App\Services\Core\SchoolContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof Student
            ? ($this->user()?->can('update', $student) ?? false)
            : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'academic_classroom_id' => $this->filled('academic_classroom_id') ? $this->integer('academic_classroom_id') : null,
            'status_reason' => trim((string) $this->input('status_reason')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:255'],
            'first_names' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(array_keys(Student::genderOptions()))],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'academic_classroom_id' => ['nullable', 'integer', 'exists:academic_classrooms,id'],
            'entry_academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'status' => ['required', Rule::in(array_keys(Student::statusOptions()))],
            'status_reason' => ['nullable', 'string', 'max:1000', 'required_if:status,suspendu,transfere,radie'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('academic_classroom_id')) {
                $classroom = AcademicClassroom::query()
                    ->where('school_id', app(SchoolContext::class)->current()->id)
                    ->where('is_active', true)
                    ->find($this->integer('academic_classroom_id'));

                if (! $classroom) {
                    $validator->errors()->add('academic_classroom_id', __('people.students.validation.classroom_invalid'));
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'status_reason.required_if' => __('people.students.validation.status_reason_required'),
            'entry_academic_year_id.exists' => __('people.students.validation.academic_year_invalid'),
        ];
    }
}
