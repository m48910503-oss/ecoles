<?php

namespace App\Enums\Academic;

enum CalendarEventType: string
{
    case PreRentree = 'pre_rentree';
    case Rentree = 'rentree';
    case Conge = 'conge';
    case Vacances = 'vacances';
    case JourFerie = 'jour_ferie';
    case Composition = 'composition';
    case Examen = 'examen';
    case ConseilClasse = 'conseil_classe';
    case Reunion = 'reunion';
    case ActiviteScolaire = 'activite_scolaire';
    case Td = 'td';
    case Rattrapage = 'rattrapage';
    case Renforcement = 'renforcement';
    case Administratif = 'administratif';
    case Pedagogique = 'pedagogique';
    case Autre = 'autre';

    public function label(): string
    {
        return __("academic.shared.event_types.{$this->value}");
    }

    public function tone(): string
    {
        return match ($this) {
            self::Td => 'sky',
            self::Composition, self::Examen => 'violet',
            self::Conge, self::Vacances, self::JourFerie => 'emerald',
            self::Rattrapage, self::Renforcement => 'amber',
            self::ConseilClasse, self::Reunion => 'rose',
            default => 'slate',
        };
    }

    public function requiresLocation(): bool
    {
        return in_array($this, [
            self::Reunion,
            self::ConseilClasse,
            self::ActiviteScolaire,
            self::Td,
            self::Rattrapage,
            self::Renforcement,
            self::Administratif,
            self::Pedagogique,
            self::Autre,
            self::Composition,
            self::Examen,
        ], true);
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    /**
     * @return array<int, string>
     */
    public static function requiringLocationValues(): array
    {
        return array_values(array_map(
            static fn (self $case): string => $case->value,
            array_filter(self::cases(), static fn (self $case): bool => $case->requiresLocation()),
        ));
    }
}
