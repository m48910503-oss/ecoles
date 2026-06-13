<?php

namespace App\Enums\Academic;

enum CalendarTargetType: string
{
    case Global = 'global';
    case Cycle = 'cycle';
    case Level = 'level';
    case ClassRoom = 'class';
    case Staff = 'staff';
    case Teachers = 'teachers';
    case Students = 'students';
    case Parents = 'parents';

    public function label(): string
    {
        return __("academic.shared.target_types.{$this->value}");
    }

    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
