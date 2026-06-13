<?php

namespace App\Services\Academic;

use App\Models\AcademicClassroom;
use App\Models\AcademicCycle;
use App\Models\AcademicLevel;
use App\Models\AcademicSeries;
use App\Models\User;
use App\Services\Core\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AcademicLevelService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): AcademicLevel
    {
        return DB::transaction(function () use ($validated, $actor): AcademicLevel {
            $cycle = AcademicCycle::query()->findOrFail($validated['cycle_id']);
            $usesSeries = (bool) $validated['uses_series'];
            $seriesIds = $this->extractSeriesIds($validated, $usesSeries);

            $this->assertSeriesUsageConsistency($cycle, $usesSeries);
            $this->assertSeriesSelectionMatchesCycle($cycle, $seriesIds);

            $level = AcademicLevel::query()->create($this->normalizeAttributes($validated));
            $level->series()->sync($seriesIds);

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_level.created',
                auditable: $level,
                newValues: $level->fresh(['series'])->toArray(),
                description: "Création du niveau {$level->name}.",
                user: $actor,
            );

            return $level;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(AcademicLevel $level, array $validated, User $actor): AcademicLevel
    {
        return DB::transaction(function () use ($level, $validated, $actor): AcademicLevel {
            $oldValues = $level->loadMissing('series')->toArray();
            $cycle = AcademicCycle::query()->findOrFail($validated['cycle_id']);
            $usesSeries = (bool) $validated['uses_series'];
            $seriesIds = $this->extractSeriesIds($validated, $usesSeries);

            $this->assertSeriesUsageConsistency($cycle, $usesSeries);
            $this->assertSeriesSelectionMatchesCycle($cycle, $seriesIds);
            $this->assertSeriesChangesAreCompatibleWithLinkedClassrooms($level, $usesSeries, $seriesIds);

            if ($level->cycle_id !== (int) $validated['cycle_id'] && $level->calendars()->exists()) {
                throw ValidationException::withMessages([
                    'cycle_id' => __('academic.levels.validation.cycle_change_blocked'),
                ]);
            }

            $level->update($this->normalizeAttributes($validated, $level));
            $level->series()->sync($seriesIds);

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_level.updated',
                auditable: $level,
                oldValues: $oldValues,
                newValues: $level->fresh(['series'])->toArray(),
                description: "Mise à jour du niveau {$level->name}.",
                user: $actor,
            );

            return $level->refresh();
        });
    }

    public function toggleActive(AcademicLevel $level, User $actor): AcademicLevel
    {
        return DB::transaction(function () use ($level, $actor): AcademicLevel {
            $oldValues = $level->toArray();

            if ($level->is_active && $level->calendars()->where('is_active', true)->exists()) {
                throw ValidationException::withMessages([
                    'level' => __('academic.levels.validation.deactivation_blocked_active_calendars'),
                ]);
            }

            $level->update(['is_active' => ! $level->is_active]);

            $this->auditLogService->log(
                module: 'academic',
                action: $level->is_active ? 'academic_level.enabled' : 'academic_level.disabled',
                auditable: $level,
                oldValues: $oldValues,
                newValues: $level->fresh()->toArray(),
                description: ($level->is_active ? 'Activation' : 'Désactivation')." du niveau {$level->name}.",
                user: $actor,
            );

            return $level->refresh();
        });
    }

    public function delete(AcademicLevel $level, User $actor): void
    {
        if (
            $level->calendars()->exists()
            || $level->classrooms()->exists()
            || $level->coefficients()->exists()
            || $level->conductSettings()->exists()
        ) {
            throw ValidationException::withMessages([
                'level' => __('academic.levels.validation.delete_blocked_linked_records'),
            ]);
        }

        DB::transaction(function () use ($level, $actor): void {
            $oldValues = $level->toArray();
            $label = $level->name;

            $level->delete();

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_level.deleted',
                auditable: $level,
                oldValues: $oldValues,
                description: "Suppression du niveau {$label}.",
                user: $actor,
            );
        });
    }

    private function assertSeriesUsageConsistency(AcademicCycle $cycle, bool $usesSeries): void
    {
        if ($usesSeries && ! $cycle->supportsSeries()) {
            throw ValidationException::withMessages([
                'uses_series' => __('academic.levels.validation.uses_series_requires_upper_secondary'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, int>
     */
    private function extractSeriesIds(array $validated, bool $usesSeries): array
    {
        if (! $usesSeries) {
            return [];
        }

        return collect($validated['series_ids'] ?? [])
            ->filter(fn ($seriesId) => $seriesId !== null && $seriesId !== '')
            ->map(fn ($seriesId) => (int) $seriesId)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $seriesIds
     */
    private function assertSeriesSelectionMatchesCycle(AcademicCycle $cycle, array $seriesIds): void
    {
        if ($seriesIds === []) {
            return;
        }

        $validSeriesIds = AcademicSeries::query()
            ->where('cycle_id', $cycle->id)
            ->where('is_active', true)
            ->whereIn('id', $seriesIds)
            ->pluck('id')
            ->map(fn ($seriesId) => (int) $seriesId)
            ->all();

        if (count($validSeriesIds) !== count($seriesIds)) {
            throw ValidationException::withMessages([
                'series_ids' => __('academic.levels.validation.series_must_belong_to_cycle'),
            ]);
        }
    }

    /**
     * @param  array<int, int>  $seriesIds
     */
    private function assertSeriesChangesAreCompatibleWithLinkedClassrooms(AcademicLevel $level, bool $usesSeries, array $seriesIds): void
    {
        if (! $level->classrooms()->exists()) {
            return;
        }

        if ((bool) $level->uses_series !== $usesSeries) {
            throw ValidationException::withMessages([
                'uses_series' => __('academic.levels.validation.series_change_blocked_linked_classes'),
            ]);
        }

        if (! $usesSeries) {
            return;
        }

        $linkedClassSeriesIds = $level->classrooms()
            ->whereNotNull('series_id')
            ->pluck('series_id')
            ->map(fn ($seriesId) => (int) $seriesId)
            ->all();

        if (array_diff($linkedClassSeriesIds, $seriesIds) !== []) {
            throw ValidationException::withMessages([
                'series_ids' => __('academic.levels.validation.series_change_blocked_linked_classes'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeAttributes(array $validated, ?AcademicLevel $level = null): array
    {
        $generated = $this->generatedAttributesFor((string) ($validated['name'] ?? $level?->name ?? 'Niveau'));

        return [
            'cycle_id' => (int) $validated['cycle_id'],
            'name' => $validated['name'],
            'code' => $level?->code ?: ($validated['code'] ?? $generated['code']),
            'short_name' => $validated['short_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort_order' => $level?->sort_order ?? ($validated['sort_order'] ?? $generated['sort_order']),
            'uses_series' => (bool) ($validated['uses_series'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];
    }

    /**
     * @return array{code: string, sort_order: int}
     */
    private function generatedAttributesFor(string $name): array
    {
        return [
            'code' => $this->generatedCodeFor($name),
            'sort_order' => $this->generatedSortOrder(),
        ];
    }

    private function generatedCodeFor(string $name): string
    {
        $baseCode = Str::upper(Str::substr(Str::slug($name, '-'), 0, 80));
        $baseCode = $baseCode !== '' ? $baseCode : 'NIVEAU';

        $candidate = $baseCode;
        $suffix = 2;

        while (AcademicLevel::query()->where('code', $candidate)->exists()) {
            $candidate = Str::limit("{$baseCode}-{$suffix}", 100, '');
            $suffix++;
        }

        return $candidate;
    }

    private function generatedSortOrder(): int
    {
        return ((int) AcademicLevel::query()->max('sort_order')) + 1;
    }
}
