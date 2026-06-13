<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'preferred_locale',
        'password',
        'is_active',
        'suspended_at',
        'suspended_reason',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'suspended_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot(['assigned_by', 'assigned_at'])
            ->withTimestamps();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function guardian(): HasOne
    {
        return $this->hasOne(Guardian::class, 'user_id');
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class, 'user_id');
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class, 'user_id');
    }

    public function isAdministrator(): bool
    {
        return $this->hasRole('administrateur');
    }

    public function hasRole(string|array $roles): bool
    {
        $slugs = collect((array) $roles)
            ->map(fn (string $role) => mb_strtolower(trim($role)))
            ->all();

        return $this->roles->contains(
            fn (Role $role) => in_array($role->slug, $slugs, true)
        );
    }

    public function hasPermissionTo(string $permission): bool
    {
        return $this->roles
            ->flatMap(fn (Role $role) => $role->permissions)
            ->contains(fn (Permission $candidate) => $candidate->name === $permission);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if ($permissions === []) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, Permission>
     */
    public function permissions(): Collection
    {
        return $this->roles
            ->flatMap(fn (Role $role) => $role->permissions)
            ->unique('id')
            ->values();
    }

    public function primaryRoleName(): ?string
    {
        return $this->roles->sortBy('name')->first()?->name;
    }

    public function isParentPortalUser(): bool
    {
        return $this->roles->count() === 1 && $this->hasRole('parent');
    }

    public function isTeacherPortalUser(): bool
    {
        return $this->roles->count() === 1 && $this->hasRole('enseignant');
    }

    public function isStudentPortalUser(): bool
    {
        return $this->roles->count() === 1 && $this->hasRole('eleve');
    }

    public function isSchoolPortalUser(): bool
    {
        return $this->isParentPortalUser()
            || $this->isTeacherPortalUser()
            || $this->isStudentPortalUser();
    }

    public function portalSpaceLabel(): ?string
    {
        return match (true) {
            $this->isParentPortalUser() => __('ui.nav.parent_space'),
            $this->isTeacherPortalUser() => __('ui.nav.teacher_space'),
            $this->isStudentPortalUser() => __('ui.nav.student_space'),
            default => null,
        };
    }
}
