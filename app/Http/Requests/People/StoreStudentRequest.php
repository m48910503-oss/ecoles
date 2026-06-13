<?php

namespace App\Http\Requests\People;

use App\Models\AcademicClassroom;
use App\Models\AcademicYear;
use App\Models\Guardian;
use App\Models\Student;
use App\Services\Core\SchoolContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Student::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->filled('status') ? trim((string) $this->input('status')) : 'preinscrit',
            'entry_academic_year_id' => $this->filled('entry_academic_year_id')
                ? $this->integer('entry_academic_year_id')
                : AcademicYear::query()->active()->value('id'),
            'academic_classroom_id' => $this->filled('academic_classroom_id') ? $this->integer('academic_classroom_id') : null,
            'guardian_is_primary_contact' => $this->boolean('guardian_is_primary_contact', true),
            'guardian_is_financial_responsible' => $this->boolean('guardian_is_financial_responsible'),
            'guardian_can_receive_notifications' => $this->boolean('guardian_can_receive_notifications', true),
            'guardian_can_pickup_child' => $this->boolean('guardian_can_pickup_child', true),
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
            'existing_guardian_id' => ['nullable', 'integer', 'exists:parents,id'],
            'guardian_last_name' => ['required_without:existing_guardian_id', 'nullable', 'string', 'max:255'],
            'guardian_first_names' => ['required_without:existing_guardian_id', 'nullable', 'string', 'max:255'],
            'guardian_gender' => ['required_without:existing_guardian_id', 'nullable', Rule::in(array_keys(Guardian::genderOptions()))],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'guardian_address' => ['nullable', 'string', 'max:255'],
            'guardian_profession' => ['nullable', 'string', 'max:255'],
            'guardian_relationship_type' => ['required_without:existing_guardian_id', 'nullable', Rule::in(array_keys(Student::relationshipOptions()))],
            'guardian_is_primary_contact' => ['sometimes', 'boolean'],
            'guardian_is_financial_responsible' => ['sometimes', 'boolean'],
            'guardian_can_receive_notifications' => ['sometimes', 'boolean'],
            'guardian_can_pickup_child' => ['sometimes', 'boolean'],
            'guardian_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! AcademicYear::query()->active()->exists()) {
                $validator->errors()->add('entry_academic_year_id', __('people.students.validation.active_year_required'));
            }

            if ($this->filled('academic_classroom_id')) {
                $classroom = AcademicClassroom::query()
                    ->where('school_id', app(SchoolContext::class)->current()->id)
                    ->where('is_active', true)
                    ->find($this->integer('academic_classroom_id'));

                if (! $classroom) {
                    $validator->errors()->add('academic_classroom_id', __('people.students.validation.classroom_invalid'));
                }
            }

            $hasExistingGuardian = $this->filled('existing_guardian_id');
            $hasNewGuardian = $this->filled('guardian_last_name')
                || $this->filled('guardian_first_names')
                || $this->filled('guardian_gender');

            if (! $hasExistingGuardian && ! $hasNewGuardian) {
                $validator->errors()->add('guardian_last_name', __('people.students.validation.guardian_required'));
            }
        });
    }

    public function messages(): array
    {
        return [
            'status_reason.required_if' => __('people.students.validation.status_reason_required'),
            'guardian_last_name.required_without' => __('people.students.validation.guardian_required'),
            'guardian_first_names.required_without' => __('people.students.validation.guardian_required'),
            'guardian_gender.required_without' => __('people.students.validation.guardian_required'),
            'guardian_relationship_type.required_without' => __('people.students.validation.guardian_required'),
            'entry_academic_year_id.exists' => __('people.students.validation.academic_year_invalid'),
        ];
    }
}
