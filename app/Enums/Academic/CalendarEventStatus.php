<?php

namespace App\Enums\Academic;

enum CalendarEventStatus: string
{
    case Draft = 'draft';
    case PendingValidation = 'pending_validation';
    case Validated = 'validated';
    case Published = 'published';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public function label(): string
    {
        return __("academic.shared.event_statuses.{$this->value}");
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::PendingValidation => 'amber',
            self::Validated => 'violet',
            self::Published => 'emerald',
            self::Cancelled => 'rose',
            self::Archived => 'sky',
        };
    }

    public function isPublished(): bool
    {
        return $this === self::Published;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    /**
     * @return array<int, self>
     */
    public static function editableCases(): array
    {
        return [
            self::Draft,
            self::PendingValidation,
            self::Validated,
        ];
    }
}
