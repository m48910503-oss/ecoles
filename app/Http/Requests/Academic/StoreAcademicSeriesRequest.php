<?php

namespace App\Http\Requests\Academic;

use App\Enums\Academic\AcademicCycleCategory;
use App\Models\AcademicCycle;
use App\Models\AcademicSeries;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAcademicSeriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AcademicSeries::class) ?? false;
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
        return [
            'cycle_id' => ['required', 'integer', 'exists:academic_cycles,id'],
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $cycle = AcademicCycle::query()->find($this->integer('cycle_id'));

            if (! $cycle) {
                return;
            }

            if ($cycle->category !== AcademicCycleCategory::UpperSecondary) {
                $validator->errors()->add('cycle_id', __('academic.series.validation.cycle_requires_upper_secondary'));
            }

            $exists = AcademicSeries::query()
                ->where('cycle_id', $cycle->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $this->input('name'))])
                ->exists();

            if ($exists) {
                $validator->errors()->add('name', __('academic.series.validation.name_taken'));
            }
        });
    }
}
