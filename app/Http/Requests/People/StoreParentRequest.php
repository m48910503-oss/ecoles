<?php

namespace App\Http\Requests\People;

use App\Models\Guardian;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Guardian::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->filled('status') ? trim((string) $this->input('status')) : 'actif',
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'email' => $this->filled('email') ? trim((string) $this->input('email')) : null,
            'address' => $this->filled('address') ? trim((string) $this->input('address')) : null,
            'profession' => $this->filled('profession') ? trim((string) $this->input('profession')) : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
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
            'gender' => ['required', Rule::in(array_keys(Guardian::genderOptions()))],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(Guardian::statusOptions()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('phone') && ! $this->filled('email')) {
                $validator->errors()->add('phone', __('people.parents.validation.contact_required'));
            }
        });
    }
}
