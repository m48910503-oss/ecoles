<?php

namespace App\Http\Requests\People;

use App\Models\Student;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkParentToStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof Student
            ? ($this->user()?->can('linkParent', $student) ?? false)
            : false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'parent_id' => ['required', 'integer', 'exists:parents,id'],
            'relationship_type' => ['required', Rule::in(array_keys(Student::relationshipOptions()))],
            'is_primary_contact' => ['sometimes', 'boolean'],
            'is_financial_responsible' => ['sometimes', 'boolean'],
            'can_receive_notifications' => ['sometimes', 'boolean'],
            'can_pickup_child' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.required' => __('people.students.validation.parent_required'),
            'relationship_type.required' => __('people.students.validation.relationship_required'),
        ];
    }
}
