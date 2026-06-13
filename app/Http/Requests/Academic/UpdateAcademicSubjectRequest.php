<?php

namespace App\Http\Requests\Academic;

use App\Enums\Academic\AcademicSubjectCategory;
use App\Models\AcademicSubject;
use App\Services\Core\SchoolContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAcademicSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $subject = $this->route('subject');

        return $subject instanceof AcademicSubject
            ? ($this->user()?->can('update', $subject) ?? false)
            : false;
    }

    protected function prepareForValidation(): void
    {
        $payload = [
            'category' => filled($this->input('category')) ? trim((string) $this->input('category')) : null,
            'is_conduct' => $this->boolean('is_conduct'),
        ];

        if ($this->exists('is_optional_by_default')) {
            $payload['is_optional_by_default'] = $this->boolean('is_optional_by_default');
        }

        if ($this->exists('is_active')) {
            $payload['is_active'] = $this->boolean('is_active');
        }

        $this->merge($payload);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'school_id' => ['nullable', 'integer', 'exists:schools,id'],
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', Rule::in(AcademicSubjectCategory::values())],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_optional_by_default' => ['sometimes', 'boolean'],
            'is_conduct' => ['required', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $subject = $this->route('subject');

            if (! $subject instanceof AcademicSubject) {
                return;
            }

            $schoolId = $this->resolvedSchoolId();

            $nameExists = AcademicSubject::query()
                ->where('school_id', $schoolId)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $this->input('name'))])
                ->whereKeyNot($subject->id)
                ->exists();

            if ($nameExists) {
                $validator->errors()->add('name', __('academic.subjects.validation.name_taken'));
            }

            if ($this->boolean('is_conduct')) {
                $conductExists = AcademicSubject::query()
                    ->where('school_id', $schoolId)
                    ->where('is_conduct', true)
                    ->whereKeyNot($subject->id)
                    ->exists();

                if ($conductExists) {
                    $validator->errors()->add('is_conduct', __('academic.subjects.validation.single_conduct_subject'));
                }
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
