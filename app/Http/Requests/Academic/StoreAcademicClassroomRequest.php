<?php

namespace App\Http\Requests\Academic;

use App\Models\AcademicClassroom;
use App\Models\AcademicCycle;
use App\Models\AcademicLevel;
use App\Models\AcademicSeries;
use App\Models\AcademicYear;
use App\Services\Academic\AcademicClassroomNamingService;
use App\Services\Core\SchoolContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAcademicClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', AcademicClassroom::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $nameFormatter = app(AcademicClassroomNamingService::class);
        $customName = $this->boolean('custom_name');
        $level = AcademicLevel::query()->with(['cycle', 'series'])->find($this->integer('level_id'));
        $series = $this->filled('series_id')
            ? AcademicSeries::query()->find($this->integer('series_id'))
            : null;
        $schoolId = $this->resolvedSchoolId();
        $academicYearId = $this->resolvedAcademicYearId();
        $automaticSectionNumber = $level && $academicYearId && $nameFormatter->usesNumberNaming($level) && $series
            ? $nameFormatter->resolveAutomaticSectionNumber(
                level: $level,
                series: $series,
                schoolId: $schoolId,
                academicYearId: $academicYearId,
            )
            : $nameFormatter->defaultNumber();
        $requestedSectionNumber = $this->filled('section_number')
            ? max(1, $this->integer('section_number'))
            : $nameFormatter->defaultNumber();
        $sectionNumber = $level && $nameFormatter->usesNumberNaming($level)
            ? max($requestedSectionNumber, $automaticSectionNumber)
            : $requestedSectionNumber;
        $sectionLetter = $level && $academicYearId
            ? $nameFormatter->resolveAutomaticSectionLetter(
                level: $level,
                schoolId: $schoolId,
                academicYearId: $academicYearId,
            )
            : $nameFormatter->defaultLetter();

        $this->merge([
            'academic_year_id' => $academicYearId,
            'custom_name' => $customName,
            'section_letter' => $sectionLetter,
            'section_number' => $sectionNumber,
            'is_active' => $this->boolean('is_active'),
            'name' => $level
                ? $nameFormatter->buildName(
                    level: $level,
                    series: $series,
                    customName: $customName,
                    manualName: trim((string) $this->input('name')),
                    sectionLetter: $sectionLetter,
                    sectionNumber: $sectionNumber,
                )
                : trim((string) $this->input('name')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'school_id' => ['nullable', 'integer', 'exists:schools,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'cycle_id' => ['required', 'integer', Rule::exists('academic_cycles', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'level_id' => ['required', 'integer', Rule::exists('academic_levels', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'series_id' => ['nullable', 'integer', Rule::exists('academic_series', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'custom_name' => ['required', 'boolean'],
            'section_number' => ['nullable', 'integer', 'min:1', 'max:99'],
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:999'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $cycle = AcademicCycle::query()->where('is_active', true)->find($this->integer('cycle_id'));
            $level = AcademicLevel::query()->with(['cycle', 'series'])->where('is_active', true)->find($this->integer('level_id'));
            $series = $this->filled('series_id')
                ? AcademicSeries::query()->where('is_active', true)->find($this->integer('series_id'))
                : null;
            $academicYear = AcademicYear::query()
                ->with(['cycles:id', 'levels:id', 'series:id'])
                ->find($this->integer('academic_year_id'));

            if (! $cycle || ! $level || ! $academicYear) {
                return;
            }

            if ($level->cycle_id !== $cycle->id) {
                $validator->errors()->add('level_id', __('academic.classes.validation.level_cycle_mismatch'));

                return;
            }

            $academicYearId = $this->resolvedAcademicYearId();

            if (! $academicYearId) {
                $validator->errors()->add('academic_year_id', __('academic.classes.validation.academic_year_required'));

                return;
            }

            if (! $academicYear->cycles->contains('id', $cycle->id)) {
                $validator->errors()->add('cycle_id', __('academic.classes.validation.cycle_not_available_for_year'));

                return;
            }

            if (! $academicYear->levels->contains('id', $level->id)) {
                $validator->errors()->add('level_id', __('academic.classes.validation.level_not_available_for_year'));

                return;
            }

            if ($level->uses_series && ! $series) {
                $validator->errors()->add('series_id', __('academic.classes.validation.series_required_for_level'));

                return;
            }

            if (! $level->uses_series && $series) {
                $validator->errors()->add('series_id', __('academic.classes.validation.series_not_allowed_for_level'));

                return;
            }

            $allowedSeriesIds = $level->series
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn ($seriesId) => (int) $seriesId)
                ->all();

            if ($series && ! in_array($series->id, $allowedSeriesIds, true)) {
                $validator->errors()->add('series_id', __('academic.classes.validation.series_cycle_mismatch'));
            }

            if ($series && ! $academicYear->series->contains('id', $series->id)) {
                $validator->errors()->add('series_id', __('academic.classes.validation.series_not_available_for_year'));
            }

            $schoolId = $this->resolvedSchoolId();

            $nameExists = AcademicClassroom::query()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $academicYearId)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $this->input('name'))])
                ->exists();

            if ($nameExists) {
                $validator->errors()->add('name', __('academic.classes.validation.name_taken'));
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

    private function resolvedAcademicYearId(): ?int
    {
        if ($this->filled('academic_year_id')) {
            return $this->integer('academic_year_id');
        }

        $academicYearId = AcademicYear::query()->active()->value('id');

        return $academicYearId ? (int) $academicYearId : null;
    }
}
