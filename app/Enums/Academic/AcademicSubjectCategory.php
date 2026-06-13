<?php

namespace App\Enums\Academic;

enum AcademicSubjectCategory: string
{
    case Language = 'language';
    case Science = 'science';
    case SocialScience = 'social_science';
    case Arts = 'arts';
    case Sport = 'sport';
    case Technical = 'technical';
    case LifeSkills = 'life_skills';
    case Other = 'other';

    public function label(): string
    {
        return __('academic.shared.subject_categories.'.$this->value);
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
