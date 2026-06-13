<?php

namespace App\Services\People;

use App\Models\AuditLog;
use App\Models\Staff;
use App\Models\User;
use App\Services\Core\AuditLogService;
use App\Services\Core\SchoolContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StaffService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function indexQuery(int $schoolId, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $contract = trim((string) ($filters['contract'] ?? ''));
        $account = trim((string) ($filters['account'] ?? ''));

        return Staff::query()
            ->forSchool($schoolId)
            ->with(['user'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('matricule', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('first_names', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhere('system_role', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($contract !== '', fn ($query) => $query->where('contract_type', $contract))
            ->when($account === 'linked', fn ($query) => $query->whereNotNull('user_id'))
            ->when($account === 'unlinked', fn ($query) => $query->whereNull('user_id'));
    }

    public function stats(int $schoolId): array
    {
        $query = Staff::query()->forSchool($schoolId);

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->active()->count(),
            'linked_account' => (clone $query)->whereNotNull('user_id')->count(),
            'without_account' => (clone $query)->withoutAccount()->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): Staff
    {
        return DB::transaction(function () use ($validated, $actor): Staff {
            $school = $this->schoolContext->current();

            $this->ensureNoDuplicateContact(
                schoolId: (int) $school->id,
                email: Arr::get($validated, 'email'),
                phone: Arr::get($validated, 'phone'),
            );

            $staff = Staff::query()->create($this->staffPayload($validated, $actor, (int) $school->id));

            $staff->refresh()->load(['user']);

            $this->auditLogService->log(
                module: 'people',
                action: 'staff.created',
                auditable: $staff,
                newValues: $staff->toArray(),
                description: "Création du dossier personnel {$staff->fullName()}.",
                user: $actor,
            );

            return $staff;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(Staff $staff, array $validated, User $actor): Staff
    {
        return DB::transaction(function () use ($staff, $validated, $actor): Staff {
            $oldValues = $staff->load(['user'])->toArray();

            $this->ensureNoDuplicateContact(
                schoolId: (int) $staff->school_id,
                email: Arr::get($validated, 'email'),
                phone: Arr::get($validated, 'phone'),
                exceptId: (int) $staff->id,
            );

            $staff->update($this->staffPayload($validated, $actor, (int) $staff->school_id, $staff));

            $staff->refresh()->load(['user']);

            $this->auditLogService->log(
                module: 'people',
                action: 'staff.updated',
                auditable: $staff,
                oldValues: $oldValues,
                newValues: $staff->toArray(),
                description: "Mise à jour du dossier personnel {$staff->fullName()}.",
                user: $actor,
            );

            return $staff;
        });
    }

    public function toggleStatus(Staff $staff, User $actor): Staff
    {
        return DB::transaction(function () use ($staff, $actor): Staff {
            $oldValues = $staff->load(['user'])->toArray();
            $newStatus = $staff->status === 'actif' ? 'suspendu' : 'actif';

            $staff->update([
                'status' => $newStatus,
                'updated_by' => $actor->id,
            ]);

            $staff->refresh()->load(['user']);

            $this->auditLogService->log(
                module: 'people',
                action: $newStatus === 'actif' ? 'staff.reactivated' : 'staff.suspended',
                auditable: $staff,
                oldValues: $oldValues,
                newValues: $staff->toArray(),
                description: ($newStatus === 'actif' ? 'Réactivation' : 'Suspension')." du dossier personnel {$staff->fullName()}.",
                user: $actor,
            );

            return $staff;
        });
    }

    /**
     * @return Collection<int, AuditLog>
     */
    public function historyFor(Staff $staff, int $limit = 10): Collection
    {
        return AuditLog::query()
            ->with('user:id,name')
            ->where('module', 'people')
            ->where('auditable_type', $staff->getMorphClass())
            ->where('auditable_id', $staff->id)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function nextMatricule(int $schoolId): string
    {
        $year = (string) now()->year;
        $prefix = "PERS-{$year}-";

        $lastMatricule = Staff::query()
            ->forSchool($schoolId)
            ->where('matricule', 'like', $prefix.'%')
            ->orderByDesc('matricule')
            ->value('matricule');

        $nextNumber = 1;

        if (is_string($lastMatricule) && preg_match('/^PERS-'.$year.'-(\d{4})$/', $lastMatricule, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function staffPayload(array $validated, User $actor, int $schoolId, ?Staff $staff = null): array
    {
        return [
            'school_id' => $schoolId,
            'user_id' => $staff?->user_id,
            'matricule' => $staff?->matricule ?? $this->nextMatricule($schoolId),
            'last_name' => trim((string) $validated['last_name']),
            'first_names' => trim((string) $validated['first_names']),
            'gender' => (string) $validated['gender'],
            'phone' => $this->nullableString(Arr::get($validated, 'phone')),
            'email' => $this->nullableString(Arr::get($validated, 'email')),
            'address' => $this->nullableString(Arr::get($validated, 'address')),
            'position' => (string) Arr::get($validated, 'position'),
            'system_role' => $this->nullableString(Arr::get($validated, 'system_role')) ?? 'autre',
            'contract_type' => (string) Arr::get($validated, 'contract_type', 'contractuel'),
            'service_start_date' => Arr::get($validated, 'service_start_date') ?: null,
            'status' => (string) Arr::get($validated, 'status', 'actif'),
            'notes' => $this->nullableString(Arr::get($validated, 'notes')),
            'created_by' => $staff?->created_by ?? $actor->id,
            'updated_by' => $actor->id,
        ];
    }

    /**
     * @param  int  $schoolId
     * @param  mixed  $email
     * @param  mixed  $phone
     * @param  int|null  $exceptId
     */
    private function ensureNoDuplicateContact(int $schoolId, mixed $email, mixed $phone, ?int $exceptId = null): void
    {
        $normalizedEmail = $this->nullableString($email);
        $normalizedPhone = $this->nullableString($phone);

        if ($normalizedEmail === null && $normalizedPhone === null) {
            return;
        }

        $query = Staff::query()->forSchool($schoolId);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $query->where(function ($nestedQuery) use ($normalizedEmail, $normalizedPhone): void {
            if ($normalizedEmail !== null) {
                $nestedQuery->orWhereRaw('LOWER(email) = ?', [mb_strtolower($normalizedEmail)]);
            }

            if ($normalizedPhone !== null) {
                $nestedQuery->orWhereRaw('TRIM(phone) = ?', [trim($normalizedPhone)]);
            }
        });

        $duplicate = $query->first();

        if (! $duplicate) {
            return;
        }

        $errors = [];

        if ($normalizedEmail !== null && $duplicate->email !== null && mb_strtolower($duplicate->email) === mb_strtolower($normalizedEmail)) {
            $errors['email'] = __('people.staff.validation.duplicate_contact');
        }

        if ($normalizedPhone !== null && $duplicate->phone !== null && trim($duplicate->phone) === trim($normalizedPhone)) {
            $errors['phone'] = __('people.staff.validation.duplicate_contact');
        }

        if ($errors === []) {
            $errors['phone'] = __('people.staff.validation.duplicate_contact');
        }

        throw ValidationException::withMessages($errors);
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return $value === null ? null : trim((string) $value);
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
