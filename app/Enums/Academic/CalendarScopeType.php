<?php

namespace App\Enums\Academic;

enum CalendarScopeType: string
{
    case Global = 'global';
    case Cycle = 'cycle';
    case Level = 'level';
    case ClassRoom = 'class';

    public function label(): string
    {
        return match ($this) {
            self::Global => __('academic.shared.scope_types.global'),
            self::Cycle => __('academic.shared.scope_types.cycle'),
            self::Level => __('academic.shared.scope_types.level'),
            self::ClassRoom => __('academic.shared.scope_types.class'),
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
