<?php

namespace App\Services\Core;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SchoolService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    public function ensureDefaultSchool(): School
    {
        $defaultSchool = School::query()->default()->first();

        if ($defaultSchool) {
            return $defaultSchool;
        }

        $attributes = $this->defaultSchoolAttributes();

        return School::query()->create([
            ...$attributes,
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    public function syncDefaultSchoolFromSettings(): School
    {
        $defaultSchool = $this->ensureDefaultSchool();

        if ($this->isMultiSchoolEnabled() && School::query()->count() > 1) {
            return $defaultSchool;
        }

        $attributes = $this->defaultSchoolAttributes();

        $defaultSchool->fill([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'phone' => $attributes['phone'],
            'code' => $defaultSchool->code ?: $attributes['code'],
        ])->save();

        return $defaultSchool->fresh();
    }

    public function isMultiSchoolEnabled(): bool
    {
        $activeAcademicYearMode = AcademicYear::query()
            ->active()
            ->value('multi_school_enabled');

        if ($activeAcademicYearMode !== null) {
            return (bool) $activeAcademicYearMode;
        }

        return false;
    }

    /**
     * @return array{enabled: bool, school_count: int, current_school_name: string, current_school_code: string, default_school_name: string, default_school_code: string, active_year_id: int|null, active_year_name: string|null}
     */
    public function multiSchoolStatus(): array
    {
        $defaultSchool = $this->ensureDefaultSchool();
        $currentSchool = app(SchoolContext::class)->current();
        $activeAcademicYear = AcademicYear::query()->active()->first();

        return [
            'enabled' => $this->isMultiSchoolEnabled(),
            'school_count' => School::query()->count(),
            'current_school_name' => $currentSchool->name,
            'current_school_code' => $currentSchool->code,
            'default_school_name' => $defaultSchool->name,
            'default_school_code' => $defaultSchool->code,
            'active_year_id' => $activeAcademicYear?->id,
            'active_year_name' => $activeAcademicYear?->name,
        ];
    }

    /**
     * @return array{total: int, active: int, inactive: int, current_school_name: string, current_school_code: string, default_school_name: string}
     */
    public function stats(): array
    {
        $currentSchool = app(SchoolContext::class)->current();
        $defaultSchool = $this->ensureDefaultSchool();

        return [
            'total' => School::query()->count(),
            'active' => School::query()->active()->count(),
            'inactive' => School::query()->where('is_active', false)->count(),
            'current_school_name' => $currentSchool->name,
            'current_school_code' => $currentSchool->code,
            'default_school_name' => $defaultSchool->name,
        ];
    }

    /**
     * @return array{enabled: bool, current_school_id: int|null, schools: array<int, array{id: int, name: string, code: string}>}
     */
    public function switcherData(User $user): array
    {
        $currentSchool = app(SchoolContext::class)->current();
        $enabled = $this->isMultiSchoolEnabled()
            && School::query()->active()->count() > 1
            && $user->hasPermissionTo('core.schools.switch');

        return [
            'enabled' => $enabled,
            'current_school_id' => $enabled ? $currentSchool->id : null,
            'schools' => $enabled
                ? School::query()
                    ->active()
                    ->orderByDesc('is_default')
                    ->orderBy('name')
                    ->get(['id', 'name', 'code'])
                    ->map(fn (School $school): array => [
                        'id' => $school->id,
                        'name' => $school->name,
                        'code' => $school->code,
                    ])
                    ->all()
                : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): School
    {
        if (! $this->isMultiSchoolEnabled()) {
            throw ValidationException::withMessages([
                'school' => __('core.schools.validation.create_requires_multi'),
            ]);
        }

        return DB::transaction(function () use ($validated, $actor): School {
            $school = School::query()->create([
                'code' => $this->generatedCodeFor($validated['name']),
                'name' => $validated['name'],
                'email' => $validated['email'] ?: null,
                'phone' => $validated['phone'] ?: null,
                'address' => $validated['address'] ?: null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
                'is_default' => false,
            ]);

            $this->auditLogService->log(
                module: 'core',
                action: 'school.created',
                auditable: $school,
                newValues: $school->toArray(),
                description: "Création de l'établissement {$school->name}.",
                user: $actor,
            );

            return $school;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(School $school, array $validated, User $actor): School
    {
        if ($school->is_default && array_key_exists('is_active', $validated) && ! $validated['is_active']) {
            throw ValidationException::withMessages([
                'is_active' => __('core.schools.validation.default_must_remain_active'),
            ]);
        }

        return DB::transaction(function () use ($school, $validated, $actor): School {
            $oldValues = $school->toArray();

            $school->fill([
                'code' => $school->code ?: $this->generatedCodeFor($validated['name']),
                'name' => $validated['name'],
                'email' => $validated['email'] ?: null,
                'phone' => $validated['phone'] ?: null,
                'address' => $validated['address'] ?: null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ])->save();

            if ($school->is_default) {
                $this->syncSettingsFromDefaultSchool($school);
            }

            $this->auditLogService->log(
                module: 'core',
                action: 'school.updated',
                auditable: $school,
                oldValues: $oldValues,
                newValues: $school->fresh()->toArray(),
                description: "Mise à jour de l'établissement {$school->name}.",
                user: $actor,
            );

            return $school->fresh();
        });
    }

    public function switchCurrentSchool(School $school, User $actor): void
    {
        if (! $this->isMultiSchoolEnabled()) {
            throw ValidationException::withMessages([
                'school_id' => __('core.schools.validation.switch_requires_multi'),
            ]);
        }

        if (! $school->is_active) {
            throw ValidationException::withMessages([
                'school_id' => __('core.schools.validation.switch_inactive'),
            ]);
        }

        request()->session()->put('current_school_id', $school->id);

        $this->auditLogService->log(
            module: 'core',
            action: 'school.switched',
            auditable: $school,
            newValues: ['current_school_id' => $school->id],
            description: "Changement de l'établissement courant vers {$school->name}.",
            user: $actor,
        );
    }

    public function delete(School $school, User $actor): void
    {
        if ($school->is_default) {
            throw ValidationException::withMessages([
                'school' => __('core.schools.validation.delete_default_forbidden'),
            ]);
        }

        if ($school->is_active) {
            throw ValidationException::withMessages([
                'school' => __('core.schools.validation.delete_requires_inactive'),
            ]);
        }

        if ($school->calendars()->exists() || $school->events()->exists()) {
            throw ValidationException::withMessages([
                'school' => __('core.schools.validation.delete_linked_records'),
            ]);
        }

        DB::transaction(function () use ($school, $actor): void {
            $oldValues = $school->toArray();
            $name = $school->name;

            if ((int) request()->session()->get('current_school_id') === (int) $school->id) {
                request()->session()->forget('current_school_id');
            }

            $school->delete();

            $this->auditLogService->log(
                module: 'core',
                action: 'school.deleted',
                oldValues: $oldValues,
                description: "Suppression de l'établissement {$name}.",
                user: $actor,
            );
        });
    }

    /**
     * @return array{code: string, name: string, email: string|null, phone: string|null}
     */
    private function defaultSchoolAttributes(): array
    {
        $name = $this->settingValue('general', 'school_name', config('app.name', 'ERP Scolaire'));

        return [
            'code' => $this->defaultCodeFor($name),
            'name' => $name,
            'email' => $this->nullableSettingValue('general', 'support_email'),
            'phone' => $this->nullableSettingValue('general', 'school_phone'),
        ];
    }

    private function settingValue(string $group, string $key, string $fallback): string
    {
        return (string) (SystemSetting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->value('value') ?: $fallback);
    }

    private function nullableSettingValue(string $group, string $key): ?string
    {
        $value = SystemSetting::query()
            ->where('group', $group)
            ->where('key', $key)
            ->value('value');

        return filled($value) ? (string) $value : null;
    }

    private function defaultCodeFor(string $name): string
    {
        $baseCode = Str::upper(Str::substr(Str::slug($name, '-'), 0, 50));

        return $baseCode !== '' ? $baseCode : 'ECOLE-PRINCIPALE';
    }

    private function generatedCodeFor(string $name): string
    {
        $base = $this->defaultCodeFor($name);
        $candidate = $base;
        $suffix = 2;

        while (School::query()->where('code', $candidate)->exists()) {
            $candidate = Str::limit("{$base}-{$suffix}", 50, '');
            $suffix++;
        }

        return $candidate;
    }

    private function syncSettingsFromDefaultSchool(School $school): void
    {
        SystemSetting::query()->updateOrCreate(
            ['group' => 'general', 'key' => 'school_name'],
            ['value' => $school->name, 'type' => 'string']
        );

        SystemSetting::query()->updateOrCreate(
            ['group' => 'general', 'key' => 'school_phone'],
            ['value' => (string) ($school->phone ?? ''), 'type' => 'string']
        );

        SystemSetting::query()->updateOrCreate(
            ['group' => 'general', 'key' => 'support_email'],
            ['value' => (string) ($school->email ?? ''), 'type' => 'string']
        );
    }
}
