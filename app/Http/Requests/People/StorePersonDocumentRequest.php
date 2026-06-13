<?php

namespace App\Http\Requests\People;

use App\Models\Guardian;
use App\Models\PersonDocument;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $person = $this->person();

        return match (true) {
            $person instanceof Student => $this->user()?->can('attachDocument', $person) ?? false,
            $person instanceof Guardian => $this->user()?->can('attachDocument', $person) ?? false,
            $person instanceof Staff => $this->user()?->can('attachDocument', $person) ?? false,
            $person instanceof Teacher => $this->user()?->can('attachDocument', $person) ?? false,
            default => false,
        };
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'document_type' => $this->filled('document_type') ? trim((string) $this->input('document_type')) : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
            'is_sensitive' => $this->boolean('is_sensitive', true),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $person = $this->person();
        $typeOptions = $person ? array_keys(PersonDocument::typeOptionsFor($person)) : [];

        return [
            'document_type' => ['required', Rule::in($typeOptions)],
            'document_file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_sensitive' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'document_type.required' => __('people.documents.validation.type_required'),
            'document_type.in' => __('people.documents.validation.type_invalid'),
            'document_file.required' => __('people.documents.validation.file_required'),
            'document_file.file' => __('people.documents.validation.file_invalid'),
            'document_file.mimes' => __('people.documents.validation.file_type_invalid'),
            'document_file.max' => __('people.documents.validation.file_too_large'),
        ];
    }

    private function person(): Student|Guardian|Staff|Teacher|null
    {
        return $this->route('student') instanceof Student
            ? $this->route('student')
            : ($this->route('parent') instanceof Guardian
                ? $this->route('parent')
                : ($this->route('staff') instanceof Staff
                    ? $this->route('staff')
                    : ($this->route('teacher') instanceof Teacher ? $this->route('teacher') : null)));
    }
}
