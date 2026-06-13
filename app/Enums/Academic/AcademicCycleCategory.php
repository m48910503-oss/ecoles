<?php

namespace App\Enums\Academic;

enum AcademicCycleCategory: string
{
    case Preschool = 'preschool';
    case Primary = 'primary';
    case LowerSecondary = 'lower_secondary';
    case UpperSecondary = 'upper_secondary';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $category): string => $category->value, self::cases());
    }

    public function label(): string
    {
        return __('academic.shared.cycle_categories.'.$this->value);
    }

    public function managementGroup(): string
    {
        return match ($this) {
            self::Preschool => 'preschool',
            self::Primary => 'primary',
            self::LowerSecondary, self::UpperSecondary => 'secondary',
        };
    }

    public function managementGroupLabel(): string
    {
        return __('academic.shared.cycle_groups.'.$this->managementGroup());
    }

    /**
     * @return array<int, string>
     */
    public static function managementGroupCategories(string $group): array
    {
        return match ($group) {
            'preschool' => [self::Preschool->value],
            'primary' => [self::Primary->value],
            'secondary' => [self::LowerSecondary->value, self::UpperSecondary->value],
            default => self::values(),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function managementGroups(): array
    {
        return [
            'preschool' => self::Preschool->managementGroupLabel(),
            'primary' => self::Primary->managementGroupLabel(),
            'secondary' => self::LowerSecondary->managementGroupLabel(),
        ];
    }

    public function teacherTrack(): string
    {
        return match ($this) {
            // Preschool and primary are managed as class-teacher tracks.
            self::Preschool, self::Primary => 'primary',
            self::LowerSecondary, self::UpperSecondary => 'secondary',
        };
    }

    public function teacherTrackLabel(): string
    {
        return __('people.teachers.options.tracks.'.$this->teacherTrack());
    }

    public function tone(): string
    {
        return match ($this) {
            self::Preschool => 'amber',
            self::Primary => 'emerald',
            self::LowerSecondary => 'sky',
            self::UpperSecondary => 'violet',
        };
    }

    public function supportsSeries(): bool
    {
        return $this === self::UpperSecondary;
    }
}
