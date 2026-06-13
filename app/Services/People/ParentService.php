<?php

namespace App\Services\People;

use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\User;
use App\Services\Core\AuditLogService;
use App\Services\Core\SchoolContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ParentService
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
        $gender = trim((string) ($filters['gender'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $account = trim((string) ($filters['account'] ?? ''));
        $children = trim((string) ($filters['children'] ?? ''));

        return Guardian::query()
            ->forSchool($schoolId)
            ->with(['user'])
            ->withCount('students')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('last_name', 'like', "%{$search}%")
                        ->orWhere('first_names', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($gender !== '', fn ($query) => $query->where('gender', $gender))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($account === 'linked', fn ($query) => $query->whereNotNull('user_id'))
            ->when($account === 'unlinked', fn ($query) => $query->whereNull('user_id'))
            ->when($children === 'with', fn ($query) => $query->has('students'))
            ->when($children === 'without', fn ($query) => $query->doesntHave('students'));
    }

    public function stats(int $schoolId): array
    {
        $query = Guardian::query()->forSchool($schoolId);

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->active()->count(),
            'linked_account' => (clone $query)->whereNotNull('user_id')->count(),
            'without_children' => (clone $query)->doesntHave('students')->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): Guardian
    {
        return DB::transaction(function () use ($validated, $actor): Guardian {
            $school = $this->schoolContext->current();

            $this->ensureNoDuplicateContact(
                schoolId: (int) $school->id,
                email: Arr::get($validated, 'email'),
                phone: Arr::get($validated, 'phone'),
            );

            $parent = Guardian::query()->create($this->payload($validated, $actor, (int) $school->id));

            $parent->refresh()->load(['user', 'students']);

            $this->auditLogService->log(
                module: 'people',
                action: 'parent.created',
                auditable: $parent,
                newValues: $parent->toArray(),
                description: "Création du dossier parent {$parent->fullName()}.",
                user: $actor,
            );

            return $parent;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(Guardian $parent, array $validated, User $actor): Guardian
    {
        return DB::transaction(function () use ($parent, $validated, $actor): Guardian {
            $oldValues = $parent->load(['user', 'students'])->toArray();

            $this->ensureNoDuplicateContact(
                schoolId: (int) $parent->school_id,
                email: Arr::get($validated, 'email'),
                phone: Arr::get($validated, 'phone'),
                exceptId: (int) $parent->id,
            );

            $parent->update($this->payload($validated, $actor, (int) $parent->school_id, $parent));

            $parent->refresh()->load(['user', 'students']);

            $this->auditLogService->log(
                module: 'people',
                action: 'parent.updated',
                auditable: $parent,
                oldValues: $oldValues,
                newValues: $parent->toArray(),
                description: "Mise à jour du dossier parent {$parent->fullName()}.",
                user: $actor,
            );

            return $parent;
        });
    }

    /**
     * @return Collection<int, AuditLog>
     */
    public function historyFor(Guardian $parent, int $limit = 10): Collection
    {
        return AuditLog::query()
            ->with('user:id,name')
            ->where('module', 'people')
            ->where('auditable_type', $parent->getMorphClass())
            ->where('auditable_id', $parent->id)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated, User $actor, int $schoolId, ?Guardian $parent = null): array
    {
        $status = (string) Arr::get($validated, 'status', 'actif');

        return [
            'school_id' => $schoolId,
            'user_id' => $parent?->user_id,
            'last_name' => trim((string) $validated['last_name']),
            'first_names' => trim((string) $validated['first_names']),
            'gender' => (string) $validated['gender'],
            'phone' => $this->nullableString(Arr::get($validated, 'phone')),
            'email' => $this->nullableString(Arr::get($validated, 'email')),
            'address' => $this->nullableString(Arr::get($validated, 'address')),
            'profession' => $this->nullableString(Arr::get($validated, 'profession')),
            'status' => $status,
            'notes' => $this->nullableString(Arr::get($validated, 'notes')),
            'created_by' => $parent?->created_by ?? $actor->id,
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
            throw ValidationException::withMessages([
                'phone' => __('people.parents.validation.contact_required'),
            ]);
        }

        $query = Guardian::query()->forSchool($schoolId);

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
            $errors['email'] = __('people.parents.validation.duplicate_contact');
        }

        if ($normalizedPhone !== null && $duplicate->phone !== null && trim($duplicate->phone) === trim($normalizedPhone)) {
            $errors['phone'] = __('people.parents.validation.duplicate_contact');
        }

        if ($errors === []) {
            $errors['phone'] = __('people.parents.validation.duplicate_contact');
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
