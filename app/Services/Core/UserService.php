<?php

namespace App\Services\Core;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): User
    {
        return DB::transaction(function () use ($validated, $actor): User {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => Arr::get($validated, 'is_active', true),
                'suspended_at' => Arr::get($validated, 'is_active', true) ? null : now(),
                'suspended_reason' => Arr::get($validated, 'is_active', true)
                    ? null
                    : Arr::get($validated, 'suspended_reason'),
            ]);

            $this->syncRoles($user, $validated['role_ids'] ?? [], $actor);

            $this->auditLogService->log(
                module: 'core',
                action: 'user.created',
                auditable: $user,
                newValues: $user->fresh()->toArray(),
                description: "Création de l'utilisateur {$user->email}.",
                user: $actor,
            );

            return $user->load('roles');
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(User $user, array $validated, User $actor): User
    {
        return DB::transaction(function () use ($user, $validated, $actor): User {
            $oldValues = $user->load('roles')->toArray();

            $payload = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'is_active' => Arr::get($validated, 'is_active', true),
            ];

            if (! empty($validated['password'])) {
                $payload['password'] = Hash::make($validated['password']);
            }

            if ($payload['is_active']) {
                $payload['suspended_at'] = null;
                $payload['suspended_reason'] = null;
            } else {
                $payload['suspended_at'] = $user->suspended_at ?? now();
                $payload['suspended_reason'] = Arr::get($validated, 'suspended_reason');
            }

            $user->update($payload);

            $this->syncRoles($user, $validated['role_ids'] ?? [], $actor);

            $user->refresh()->load('roles');

            $this->auditLogService->log(
                module: 'core',
                action: $user->is_active ? 'user.updated' : 'user.suspended',
                auditable: $user,
                oldValues: $oldValues,
                newValues: $user->toArray(),
                description: "Mise à jour du compte {$user->email}.",
                user: $actor,
            );

            return $user;
        });
    }

    /**
     * @param  array<int, int|string>  $roleIds
     */
    private function syncRoles(User $user, array $roleIds, User $actor): void
    {
        $syncData = collect($roleIds)
            ->mapWithKeys(fn ($roleId) => [
                (int) $roleId => [
                    'assigned_by' => $actor->id,
                    'assigned_at' => now(),
                ],
            ])
            ->all();

        $user->roles()->sync($syncData);
    }
}
