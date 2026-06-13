<?php

namespace App\Enums\Academic;

enum AcademicPeriodType: string
{
    case Trimester = 'trimester';
    case Semester = 'semester';

    public function label(): string
    {
        return __("academic.shared.period_types.{$this->value}");
    }

    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    public function defaultCount(): int
    {
        return match ($this) {
            self::Trimester => 3,
            self::Semester => 2,
        };
    }

    public function defaultLabelFor(int $position): string
    {
        return match ($this) {
            self::Trimester => __('academic.shared.generated_period_name.trimester', ['number' => $position]),
            self::Semester => __('academic.shared.generated_period_name.semester', ['number' => $position]),
        };
    }
}
