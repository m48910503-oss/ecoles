<?php

namespace App\Http\Requests\People;

use App\Models\Guardian;
use App\Models\Student;
use App\Services\Core\SchoolContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LinkStudentToParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $parent = $this->route('parent');

        return $parent instanceof Guardian
            ? ($this->user()?->can('linkStudent', $parent) ?? false)
            : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'student_id' => $this->filled('student_id') ? $this->integer('student_id') : null,
            'relationship_type' => $this->filled('relationship_type') ? trim((string) $this->input('relationship_type')) : null,
            'is_primary_contact' => $this->boolean('is_primary_contact', true),
            'is_financial_responsible' => $this->boolean('is_financial_responsible'),
            'can_receive_notifications' => $this->boolean('can_receive_notifications', true),
            'can_pickup_child' => $this->boolean('can_pickup_child', true),
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'relationship_type' => ['required', Rule::in(array_keys(Student::relationshipOptions()))],
            'is_primary_contact' => ['sometimes', 'boolean'],
            'is_financial_responsible' => ['sometimes', 'boolean'],
            'can_receive_notifications' => ['sometimes', 'boolean'],
            'can_pickup_child' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $parent = $this->route('parent');

            if (! $parent instanceof Guardian) {
                return;
            }

            if (! $this->filled('student_id')) {
                $validator->errors()->add('student_id', __('people.parents.validation.student_required'));
                return;
            }

            $student = Student::query()
                ->where('school_id', $parent->school_id)
                ->find($this->integer('student_id'));

            if (! $student) {
                $validator->errors()->add('student_id', __('people.parents.validation.student_invalid'));
            }
        });
    }
}
