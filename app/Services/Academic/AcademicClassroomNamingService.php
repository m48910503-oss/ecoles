<?php

namespace App\Services\Academic;

use App\Enums\Academic\AcademicCycleCategory;
use App\Models\AcademicClassroom;
use App\Models\AcademicLevel;
use App\Models\AcademicSeries;

class AcademicClassroomNamingService
{
    public function usesNumberNaming(AcademicLevel $level): bool
    {
        return $level->cycle?->category === AcademicCycleCategory::UpperSecondary;
    }

    public function usesLetterNaming(AcademicLevel $level): bool
    {
        return ! $this->usesNumberNaming($level);
    }

    public function baseLabel(AcademicLevel $level): string
    {
        return trim((string) ($level->short_name ?: $level->name));
    }

    public function baseName(AcademicLevel $level, ?AcademicSeries $series = null): string
    {
        $baseLabel = $this->baseLabel($level);

        if ($baseLabel === '') {
            return '';
        }

        if (! $this->usesNumberNaming($level)) {
            return $baseLabel;
        }

        $seriesLabel = trim((string) ($series?->short_name ?: $series?->name));

        return $seriesLabel !== ''
            ? "{$baseLabel} {$seriesLabel}"
            : $baseLabel;
    }

    public function defaultLetter(): string
    {
        return '';
    }

    public function defaultNumber(): int
    {
        return 1;
    }

    /**
     * @return array<int, string>
     */
    public function letters(): array
    {
        return range('A', 'Z');
    }

    public function buildName(
        AcademicLevel $level,
        ?AcademicSeries $series,
        bool $customName,
        string $manualName,
        string $sectionLetter,
        int $sectionNumber,
    ): string {
        if ($customName) {
            return trim($manualName);
        }

        $baseName = $this->baseName($level, $series);

        if ($baseName === '') {
            return trim($manualName);
        }

        if ($this->usesNumberNaming($level)) {
            $number = max(1, $sectionNumber);

            return $number <= 1
                ? $baseName
                : "{$baseName}{$number}";
        }

        $letter = strtoupper(trim($sectionLetter));

        return $letter !== ''
            ? "{$baseName} - {$letter}"
            : $baseName;
    }

    public function resolveAutomaticSectionLetter(
        AcademicLevel $level,
        int $schoolId,
        int $academicYearId,
        ?AcademicClassroom $ignoreClassroom = null,
    ): string {
        if ($this->usesNumberNaming($level)) {
            return '';
        }

        $baseName = $this->baseName($level);

        if ($baseName === '') {
            return $this->defaultLetter();
        }

        $classroomNames = $this->classroomScopeQuery($level, null, $schoolId, $academicYearId, $ignoreClassroom)
            ->pluck('name')
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->values();

        if ($classroomNames->isEmpty()) {
            return $this->defaultLetter();
        }

        $usedLetters = $classroomNames
            ->map(fn (string $name) => $this->extractSectionLetter($name, $baseName))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($classroomNames->contains($baseName)) {
            $usedLetters[] = 'A';
        }

        $usedLetters = array_values(array_unique($usedLetters));

        for ($index = 1; $index <= 1000; $index++) {
            $candidate = $this->alphabeticLabel($index);

            if (! in_array($candidate, $usedLetters, true)) {
                return $candidate;
            }
        }

        return 'B';
    }

    public function resolveAutomaticSectionNumber(
        AcademicLevel $level,
        ?AcademicSeries $series,
        int $schoolId,
        int $academicYearId,
        ?AcademicClassroom $ignoreClassroom = null,
    ): int {
        $baseName = $this->baseName($level, $series);

        if ($baseName === '') {
            return $this->defaultNumber();
        }

        $classroomNames = $this->classroomScopeQuery($level, $series, $schoolId, $academicYearId, $ignoreClassroom)
            ->pluck('name')
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->values();

        if ($classroomNames->isEmpty()) {
            return $this->defaultNumber();
        }

        $usedNumbers = $classroomNames
            ->map(fn (string $name) => $this->extractSectionNumber($name, $baseName))
            ->filter(fn (?int $number) => $number !== null)
            ->unique()
            ->values()
            ->all();

        if ($classroomNames->contains($baseName)) {
            $usedNumbers[] = 1;
        }

        $usedNumbers = array_values(array_unique(array_map('intval', $usedNumbers)));

        for ($candidate = 1; $candidate <= 99; $candidate++) {
            if (! in_array($candidate, $usedNumbers, true)) {
                return $candidate;
            }
        }

        return max($usedNumbers) + 1;
    }

    private function classroomScopeQuery(
        AcademicLevel $level,
        ?AcademicSeries $series,
        int $schoolId,
        int $academicYearId,
        ?AcademicClassroom $ignoreClassroom = null,
    )
    {
        return AcademicClassroom::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('level_id', $level->id)
            ->when($this->usesNumberNaming($level), fn ($query) => $query->where('series_id', $series?->id))
            ->when($ignoreClassroom, fn ($query) => $query->whereKeyNot($ignoreClassroom->id));
    }

    private function extractSectionLetter(string $name, string $baseName): ?string
    {
        $pattern = '/^'.preg_quote($baseName, '/').'\s*-\s*([A-Z]+)$/u';

        if (! preg_match($pattern, trim($name), $matches)) {
            return null;
        }

        return strtoupper($matches[1]);
    }

    private function extractSectionNumber(string $name, string $baseName): ?int
    {
        $pattern = '/^'.preg_quote($baseName, '/').'\s*(\d+)$/u';

        if (! preg_match($pattern, trim($name), $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private function alphabeticLabel(int $index): string
    {
        $label = '';

        while ($index > 0) {
            $index--;
            $label = chr(65 + ($index % 26)).$label;
            $index = intdiv($index, 26);
        }

        return $label;
    }
}
