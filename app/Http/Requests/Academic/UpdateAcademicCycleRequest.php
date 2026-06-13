<?php

namespace App\Http\Requests\Academic;

use App\Models\AcademicCycle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAcademicCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $cycle = $this->route('cycle');

        return $cycle ? ($this->user()?->can('update', $cycle) ?? false) : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var AcademicCycle $cycle */
        $cycle = $this->route('cycle');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('academic_cycles', 'name')->ignore($cycle)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
