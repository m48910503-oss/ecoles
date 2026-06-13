<?php

namespace App\Enums\Academic;

enum SchoolDayType: string
{
    case Continuous = 'continuous';
    case Discontinuous = 'discontinuous';

    public function label(): string
    {
        return __("academic.shared.school_day_types.{$this->value}");
    }

    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
