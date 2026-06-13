<?php

namespace App\Services\Academic;

use App\Enums\Academic\AcademicCycleCategory;
use App\Models\AcademicCycle;
use App\Models\User;
use App\Services\Core\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AcademicCycleService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): AcademicCycle
    {
        return DB::transaction(function () use ($validated, $actor): AcademicCycle {
            $attributes = $this->normalizeAttributes($validated);
            $cycle = AcademicCycle::query()->create($attributes);

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_cycle.created',
                auditable: $cycle,
                newValues: $cycle->fresh()->toArray(),
                description: "Création du cycle {$cycle->name}.",
                user: $actor,
            );

            return $cycle;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(AcademicCycle $cycle, array $validated, User $actor): AcademicCycle
    {
        return DB::transaction(function () use ($cycle, $validated, $actor): AcademicCycle {
            $oldValues = $cycle->toArray();

            $attributes = $this->normalizeAttributes($validated, $cycle);
            $cycle->update($attributes);

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_cycle.updated',
                auditable: $cycle,
                oldValues: $oldValues,
                newValues: $cycle->fresh()->toArray(),
                description: "Mise à jour du cycle {$cycle->name}.",
                user: $actor,
            );

            return $cycle->refresh();
        });
    }

    public function toggleActive(AcademicCycle $cycle, User $actor): AcademicCycle
    {
        return DB::transaction(function () use ($cycle, $actor): AcademicCycle {
            $oldValues = $cycle->toArray();

            if ($cycle->is_active) {
                $this->assertCanDeactivate($cycle);
            }

            $cycle->update(['is_active' => ! $cycle->is_active]);

            $this->auditLogService->log(
                module: 'academic',
                action: $cycle->is_active ? 'academic_cycle.enabled' : 'academic_cycle.disabled',
                auditable: $cycle,
                oldValues: $oldValues,
                newValues: $cycle->fresh()->toArray(),
                description: ($cycle->is_active ? 'Activation' : 'Désactivation')." du cycle {$cycle->name}.",
                user: $actor,
            );

            return $cycle->refresh();
        });
    }

    public function delete(AcademicCycle $cycle, User $actor): void
    {
        if ($cycle->levels()->exists() || $cycle->series()->exists() || $cycle->calendars()->exists() || $cycle->schoolDayConfigurations()->exists()) {
            throw ValidationException::withMessages([
                'cycle' => __('academic.cycles.validation.delete_blocked_linked_records'),
            ]);
        }

        DB::transaction(function () use ($cycle, $actor): void {
            $oldValues = $cycle->toArray();
            $label = $cycle->name;

            $cycle->delete();

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_cycle.deleted',
                auditable: $cycle,
                oldValues: $oldValues,
                description: "Suppression du cycle {$label}.",
                user: $actor,
            );
        });
    }

    private function assertCanDeactivate(AcademicCycle $cycle): void
    {
        if ($cycle->levels()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'cycle' => __('academic.cycles.validation.deactivation_blocked_active_levels'),
            ]);
        }

        if ($cycle->series()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'cycle' => __('academic.cycles.validation.deactivation_blocked_active_series'),
            ]);
        }

        if ($cycle->calendars()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'cycle' => __('academic.cycles.validation.deactivation_blocked_active_calendars'),
            ]);
        }

        if ($cycle->schoolDayConfigurations()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'cycle' => __('academic.cycles.validation.deactivation_blocked_active_day_configs'),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeAttributes(array $validated, ?AcademicCycle $cycle = null): array
    {
        $generated = $this->generatedAttributesFor((string) ($validated['name'] ?? $cycle?->name ?? 'Cycle'));

        return [
            'name' => $validated['name'],
            'code' => $cycle?->code ?: $generated['code'],
            'category' => $cycle?->category?->value ?? $generated['category'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $cycle?->sort_order ?? $generated['sort_order'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];
    }

    /**
     * @return array{code: string, category: string, sort_order: int}
     */
    private function generatedAttributesFor(string $name): array
    {
        return [
            'code' => $this->generatedCodeFor($name),
            'category' => $this->generatedCategoryFor($name),
            'sort_order' => $this->generatedSortOrder(),
        ];
    }

    private function generatedCodeFor(string $name): string
    {
        $baseCode = Str::upper(Str::substr(Str::slug($name, '-'), 0, 80));
        $baseCode = $baseCode !== '' ? $baseCode : 'CYCLE';

        $candidate = $baseCode;
        $suffix = 2;

        while (AcademicCycle::query()->where('code', $candidate)->exists()) {
            $candidate = Str::limit("{$baseCode}-{$suffix}", 100, '');
            $suffix++;
        }

        return $candidate;
    }

    private function generatedCategoryFor(string $name): string
    {
        $normalizedName = mb_strtolower(trim($name));

        return match (true) {
            str_contains($normalizedName, 'maternelle'),
            str_contains($normalizedName, 'préscol'),
            str_contains($normalizedName, 'prescol') => AcademicCycleCategory::Preschool->value,
            str_contains($normalizedName, 'primaire') => AcademicCycleCategory::Primary->value,
            str_contains($normalizedName, 'secondaire premier'),
            str_contains($normalizedName, 'secondaire 1'),
            str_contains($normalizedName, '1er cycle'),
            str_contains($normalizedName, 'premier cycle') => AcademicCycleCategory::LowerSecondary->value,
            str_contains($normalizedName, 'secondaire second'),
            str_contains($normalizedName, 'second cycle'),
            str_contains($normalizedName, '2e cycle'),
            str_contains($normalizedName, 'lycée') => AcademicCycleCategory::UpperSecondary->value,
            default => AcademicCycleCategory::Primary->value,
        };
    }

    private function generatedSortOrder(): int
    {
        return ((int) AcademicCycle::query()->max('sort_order')) + 1;
    }
}
