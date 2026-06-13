<?php

namespace App\Services\Academic;

use App\Models\AcademicCycle;
use App\Models\AcademicSeries;
use App\Models\User;
use App\Services\Core\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AcademicSeriesService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): AcademicSeries
    {
        return DB::transaction(function () use ($validated, $actor): AcademicSeries {
            $cycle = AcademicCycle::query()->findOrFail($validated['cycle_id']);
            $this->assertCycleSupportsSeries($cycle);
            $attributes = $this->normalizeAttributes($validated);
            $series = AcademicSeries::query()->create($attributes);

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_series.created',
                auditable: $series,
                newValues: $series->fresh()->toArray(),
                description: "Création de la série {$series->name}.",
                user: $actor,
            );

            return $series;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(AcademicSeries $series, array $validated, User $actor): AcademicSeries
    {
        return DB::transaction(function () use ($series, $validated, $actor): AcademicSeries {
            $oldValues = $series->toArray();
            $cycle = AcademicCycle::query()->findOrFail($validated['cycle_id']);
            $this->assertCycleSupportsSeries($cycle);
            $attributes = $this->normalizeAttributes($validated, $series);

            $series->update($attributes);

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_series.updated',
                auditable: $series,
                oldValues: $oldValues,
                newValues: $series->fresh()->toArray(),
                description: "Mise à jour de la série {$series->name}.",
                user: $actor,
            );

            return $series->refresh();
        });
    }

    public function delete(AcademicSeries $series, User $actor): void
    {
        if ($series->classrooms()->exists() || $series->coefficients()->exists()) {
            throw ValidationException::withMessages([
                'series' => __('academic.series.validation.delete_blocked_linked_records'),
            ]);
        }

        DB::transaction(function () use ($series, $actor): void {
            $oldValues = $series->toArray();
            $label = $series->name;

            $series->delete();

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_series.deleted',
                auditable: $series,
                oldValues: $oldValues,
                description: "Suppression de la série {$label}.",
                user: $actor,
            );
        });
    }

    public function toggleActive(AcademicSeries $series, User $actor): AcademicSeries
    {
        return DB::transaction(function () use ($series, $actor): AcademicSeries {
            $oldValues = $series->toArray();

            $series->update(['is_active' => ! $series->is_active]);

            $this->auditLogService->log(
                module: 'academic',
                action: $series->is_active ? 'academic_series.enabled' : 'academic_series.disabled',
                auditable: $series,
                oldValues: $oldValues,
                newValues: $series->fresh()->toArray(),
                description: ($series->is_active ? 'Activation' : 'Désactivation')." de la série {$series->name}.",
                user: $actor,
            );

            return $series->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeAttributes(array $validated, ?AcademicSeries $series = null): array
    {
        $generated = $this->generatedAttributesFor((string) ($validated['name'] ?? $series?->name ?? 'Serie'));

        return [
            'cycle_id' => (int) $validated['cycle_id'],
            'name' => $validated['name'],
            'code' => $series?->code ?: $generated['code'],
            'short_name' => $validated['short_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'sort_order' => $series?->sort_order ?? $generated['sort_order'],
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
        $baseCode = $baseCode !== '' ? $baseCode : 'SERIE';

        $candidate = $baseCode;
        $suffix = 2;

        while (AcademicSeries::query()->where('code', $candidate)->exists()) {
            $candidate = Str::limit("{$baseCode}-{$suffix}", 100, '');
            $suffix++;
        }

        return $candidate;
    }

    private function generatedSortOrder(): int
    {
        return ((int) AcademicSeries::query()->max('sort_order')) + 1;
    }

    private function assertCycleSupportsSeries(AcademicCycle $cycle): void
    {
        if (! $cycle->supportsSeries()) {
            throw ValidationException::withMessages([
                'cycle_id' => __('academic.series.validation.cycle_requires_upper_secondary'),
            ]);
        }
    }
}
