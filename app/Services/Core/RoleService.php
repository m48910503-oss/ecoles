<?php

namespace App\Services\Core;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RoleService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): Role
    {
        return DB::transaction(function () use ($validated, $actor): Role {
            $role = Role::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'is_optional' => (bool) ($validated['is_optional'] ?? false),
                'is_system' => false,
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            $role->permissions()->sync($validated['permission_ids'] ?? []);
            $role->load('permissions');

            $this->auditLogService->log(
                module: 'core',
                action: 'role.created',
                auditable: $role,
                newValues: $role->toArray(),
                description: "Création du rôle {$role->name}.",
                user: $actor,
            );

            return $role;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(Role $role, array $validated, User $actor): Role
    {
        return DB::transaction(function () use ($role, $validated, $actor): Role {
            $oldValues = $role->load('permissions')->toArray();

            $role->update([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'is_optional' => (bool) ($validated['is_optional'] ?? false),
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]);

            $role->permissions()->sync($validated['permission_ids'] ?? []);
            $role->refresh()->load('permissions');

            $this->auditLogService->log(
                module: 'core',
                action: 'role.updated',
                auditable: $role,
                oldValues: $oldValues,
                newValues: $role->toArray(),
                description: "Mise à jour du rôle {$role->name}.",
                user: $actor,
            );

            return $role;
        });
    }
}
