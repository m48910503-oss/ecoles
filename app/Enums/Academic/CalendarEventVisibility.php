<?php

namespace App\Enums\Academic;

enum CalendarEventVisibility: string
{
    case Public = 'public';
    case Internal = 'internal';
    case Private = 'private';

    public function label(): string
    {
        return __("academic.shared.event_visibility.{$this->value}");
    }

    public function tone(): string
    {
        return match ($this) {
            self::Public => 'sky',
            self::Internal => 'slate',
            self::Private => 'rose',
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
