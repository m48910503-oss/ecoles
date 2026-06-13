<?php

namespace App\Http\Requests\Core;

use App\Models\AcademicCycle;
use App\Models\AcademicYear;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicYearStepTwoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AcademicYear::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $hasCycles = AcademicCycle::query()->exists();

        return [
            'cycle_ids' => $hasCycles ? ['required', 'array', 'min:1'] : ['sometimes', 'array'],
            'cycle_ids.*' => ['integer', Rule::exists('academic_cycles', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cycle_ids.required' => __('core.academic_years.validation.cycles_required'),
            'cycle_ids.min' => __('core.academic_years.validation.cycles_required'),
        ];
    }
}
