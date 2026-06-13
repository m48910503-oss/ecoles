<?php

namespace App\Http\Requests\Academic;

use App\Enums\Academic\CalendarEventStatus;
use App\Enums\Academic\CalendarEventType;
use App\Enums\Academic\CalendarTargetType;
use App\Enums\Academic\CalendarEventVisibility;
use App\Enums\Academic\CalendarScopeType;
use App\Models\AcademicCalendar;
use App\Models\AcademicClassroom;
use App\Models\AcademicCycle;
use App\Models\AcademicLevel;
use App\Models\AcademicPeriod;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CalendarEvent::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_full_day' => $this->boolean('is_full_day'),
            'requires_validation' => $this->boolean('requires_validation'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $initialStatuses = [
            CalendarEventStatus::PendingValidation->value,
            CalendarEventStatus::Validated->value,
        ];
        $isFullDay = $this->boolean('is_full_day');
        $locationRequired = in_array(
            $this->string('event_type')->toString(),
            CalendarEventType::requiringLocationValues(),
            true,
        );

        return [
            'academic_calendar_id' => ['required', 'integer', 'exists:academic_calendars,id'],
            'academic_period_id' => ['nullable', 'integer', 'exists:academic_periods,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:4000'],
            'event_type' => ['required', Rule::in(CalendarEventType::values())],
            'event_date' => [Rule::requiredIf($isFullDay), 'nullable', 'date'],
            'start_datetime' => [Rule::requiredIf(! $isFullDay), 'nullable', 'date'],
            'end_datetime' => [Rule::requiredIf(! $isFullDay), 'nullable', 'date', 'after_or_equal:start_datetime'],
            'is_full_day' => ['required', 'boolean'],
            'location' => [Rule::requiredIf($locationRequired), 'nullable', 'string', 'max:255'],
            'target_type' => ['required', Rule::in(CalendarTargetType::values())],
            'target_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', Rule::in($initialStatuses)],
            'visibility' => ['required', Rule::in(CalendarEventVisibility::values())],
            'requires_validation' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $targetType = $this->string('target_type')->toString();
            $calendar = AcademicCalendar::query()
                ->with(['academicYear.cycles:id', 'academicYear.levels:id', 'coveredCycles:id'])
                ->find($this->integer('academic_calendar_id'));
            $coveredCycleIds = $calendar?->resolvedCoveredCycleIds() ?? [];
            $calendarRestrictsCycles = $calendar?->scope_type?->value === CalendarScopeType::Cycle->value && $coveredCycleIds !== [];
            $eventDateBounds = $this->resolvedEventDateBounds();

            if ($calendar && $eventDateBounds && $calendar->academicYear?->start_date && $calendar->academicYear?->end_date) {
                $yearStart = $calendar->academicYear->start_date->copy()->startOfDay();
                $yearEnd = $calendar->academicYear->end_date->copy()->endOfDay();

                if ($eventDateBounds['start']->lt($yearStart) || $eventDateBounds['end']->gt($yearEnd)) {
                    $validator->errors()->add('event_date', __('academic.events.validation.outside_academic_year'));
                }
            }

            if ($this->filled('academic_period_id')) {
                $period = AcademicPeriod::query()->find($this->integer('academic_period_id'));

                if (! $period) {
                    $validator->errors()->add('academic_period_id', __('academic.events.validation.period_invalid'));
                } elseif ($calendar && (int) $period->academic_calendar_id !== (int) $calendar->id) {
                    $validator->errors()->add('academic_period_id', __('academic.events.validation.period_calendar_mismatch'));
                } elseif ($calendar && (! $period->start_date || ! $period->end_date)) {
                    $validator->errors()->add('academic_period_id', __('academic.events.validation.period_dates_missing'));
                } elseif (
                    $calendar
                    && $eventDateBounds
                    && ($eventDateBounds['start']->lt($period->start_date->copy()->startOfDay())
                        || $eventDateBounds['end']->gt($period->end_date->copy()->endOfDay()))
                ) {
                    $validator->errors()->add('academic_period_id', __('academic.events.validation.period_date_mismatch'));
                }
            }

            if (in_array($targetType, [
                CalendarTargetType::Global->value,
                CalendarTargetType::Staff->value,
                CalendarTargetType::Teachers->value,
                CalendarTargetType::Students->value,
                CalendarTargetType::Parents->value,
            ], true)) {
                return;
            }

            if (! $this->filled('target_id')) {
                $validator->errors()->add('target_id', __('academic.events.validation.target_required'));
            }

            if ($targetType === CalendarTargetType::Cycle->value && $this->filled('target_id')) {
                $cycle = AcademicCycle::query()->find($this->integer('target_id'));

                if (! $cycle) {
                    $validator->errors()->add('target_id', __('academic.events.validation.cycle_target_invalid'));
                } elseif ($calendarRestrictsCycles && ! in_array($cycle->id, $coveredCycleIds, true)) {
                    $validator->errors()->add('target_id', __('academic.events.validation.cycle_target_year_mismatch'));
                } elseif ($calendar && (! $calendar->academicYear || ! $calendar->academicYear->cycles->contains('id', $cycle->id))) {
                    $validator->errors()->add('target_id', __('academic.events.validation.cycle_target_year_mismatch'));
                }
            }

            if ($targetType === CalendarTargetType::Level->value && $this->filled('target_id')) {
                $level = AcademicLevel::query()->find($this->integer('target_id'));

                if (! $level) {
                    $validator->errors()->add('target_id', __('academic.events.validation.level_target_invalid'));
                } elseif ($calendarRestrictsCycles && ! in_array((int) $level->cycle_id, $coveredCycleIds, true)) {
                    $validator->errors()->add('target_id', __('academic.events.validation.level_target_year_mismatch'));
                } elseif ($calendar && (! $calendar->academicYear || ! $calendar->academicYear->levels->contains('id', $level->id))) {
                    $validator->errors()->add('target_id', __('academic.events.validation.level_target_year_mismatch'));
                }
            }

            if ($targetType === CalendarTargetType::ClassRoom->value && $this->filled('target_id')) {
                $classroom = AcademicClassroom::query()->find($this->integer('target_id'));

                if (! $classroom) {
                    $validator->errors()->add('target_id', __('academic.events.validation.class_target_invalid'));
                } elseif ($calendar) {
                    if ($classroom->school_id !== $calendar->school_id) {
                        $validator->errors()->add('target_id', __('academic.events.validation.class_target_school_mismatch'));
                    } elseif ($calendarRestrictsCycles && ! in_array((int) $classroom->level?->cycle_id, $coveredCycleIds, true)) {
                        $validator->errors()->add('target_id', __('academic.events.validation.class_target_year_mismatch'));
                    } elseif ($classroom->academic_year_id !== $calendar->academic_year_id) {
                        $validator->errors()->add('target_id', __('academic.events.validation.class_target_year_mismatch'));
                    }
                }
            }
        });
    }

    /**
     * @return array{start: Carbon, end: Carbon}|null
     */
    private function resolvedEventDateBounds(): ?array
    {
        if ($this->boolean('is_full_day')) {
            if (! $this->filled('event_date')) {
                return null;
            }

            $eventDate = Carbon::parse((string) $this->input('event_date'));

            return [
                'start' => $eventDate->copy()->startOfDay(),
                'end' => $eventDate->copy()->endOfDay(),
            ];
        }

        if (! $this->filled('start_datetime') || ! $this->filled('end_datetime')) {
            return null;
        }

        return [
            'start' => Carbon::parse((string) $this->input('start_datetime')),
            'end' => Carbon::parse((string) $this->input('end_datetime')),
        ];
    }
}
