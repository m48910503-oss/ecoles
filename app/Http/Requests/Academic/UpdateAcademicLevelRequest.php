<?php

namespace App\Http\Requests\Academic;

use App\Models\AcademicCycle;
use App\Models\AcademicLevel;
use App\Models\AcademicSeries;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAcademicLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $level = $this->route('level');

        return $level ? ($this->user()?->can('update', $level) ?? false) : false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'uses_series' => $this->boolean('uses_series'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var AcademicLevel $level */
        $level = $this->route('level');

        return [
            'cycle_id' => ['required', 'integer', 'exists:academic_cycles,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['sometimes', 'nullable', 'string', 'max:100', Rule::unique('academic_levels', 'code')->ignore($level)],
            'short_name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:999'],
            'uses_series' => ['required', 'boolean'],
            'series_ids' => [Rule::excludeIf(fn () => ! $this->boolean('uses_series')), 'nullable', 'array'],
            'series_ids.*' => [Rule::excludeIf(fn () => ! $this->boolean('uses_series')), 'integer', 'distinct'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var AcademicLevel $level */
            $level = $this->route('level');
            $cycle = AcademicCycle::query()->find($this->integer('cycle_id'));

            if (! $cycle) {
                return;
            }

            $exists = AcademicLevel::query()
                ->where('cycle_id', $cycle->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $this->input('name'))])
                ->whereKeyNot($level->id)
                ->exists();

            if ($exists) {
                $validator->errors()->add('name', __('academic.levels.validation.name_taken'));
            }

            if ($this->boolean('uses_series') && ! $cycle->supportsSeries()) {
                $validator->errors()->add('uses_series', __('academic.levels.validation.uses_series_requires_upper_secondary'));

                return;
            }

            if (! $this->boolean('uses_series')) {
                return;
            }

            $seriesIds = collect($this->input('series_ids', []))
                ->filter(fn ($seriesId) => $seriesId !== null && $seriesId !== '')
                ->map(fn ($seriesId) => (int) $seriesId)
                ->unique()
                ->values();

            if ($seriesIds->isEmpty()) {
                $validator->errors()->add('series_ids', __('academic.levels.validation.series_required_when_using_series'));

                return;
            }

            $validSeriesIds = AcademicSeries::query()
                ->where('cycle_id', $cycle->id)
                ->where('is_active', true)
                ->whereIn('id', $seriesIds->all())
                ->pluck('id')
                ->map(fn ($seriesId) => (int) $seriesId)
                ->all();

            if (count($validSeriesIds) !== $seriesIds->count()) {
                $validator->errors()->add('series_ids', __('academic.levels.validation.series_must_belong_to_cycle'));
            }
        });
    }
}
