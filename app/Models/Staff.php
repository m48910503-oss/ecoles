<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Staff extends Model
{
    use HasFactory;

    public const STATUSES = [
        'actif',
        'suspendu',
        'inactif',
    ];

    public const CONTRACT_TYPES = [
        'titulaire',
        'contractuel',
        'vacataire',
        'benevole',
        'stagiaire',
    ];

    public const POSITIONS = [
        'directeur',
        'directeur_section',
        'censeur',
        'surveillant_general',
        'secretaire',
        'comptable',
        'responsable_salle_informatique',
        'agent_administratif',
        'agent_technique',
        'autre',
    ];

    public const SYSTEM_ROLES = [
        'directeur',
        'directeur-adjoint',
        'censeur',
        'censeur-adjoint',
        'surveillant-general',
        'surveillant-adjoint',
        'secretaire',
        'comptable',
        'autre',
    ];

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'user_id',
        'matricule',
        'last_name',
        'first_names',
        'gender',
        'phone',
        'email',
        'address',
        'position',
        'system_role',
        'contract_type',
        'service_start_date',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'service_start_date' => 'date',
    ];

    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'actif');
    }

    public function scopeWithoutAccount(Builder $query): Builder
    {
        return $query->doesntHave('user');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(PersonDocument::class, 'documentable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function fullName(): string
    {
        return trim($this->last_name.' '.$this->first_names);
    }

    public function initials(): string
    {
        $names = collect([$this->last_name, $this->first_names])
            ->map(fn (string $value) => trim((string) $value))
            ->filter()
            ->flatMap(fn (string $value) => preg_split('/\s+/', $value) ?: [])
            ->filter()
            ->map(fn (string $value) => mb_substr($value, 0, 1))
            ->take(2)
            ->join('');

        return mb_strtoupper($names ?: mb_substr($this->last_name ?: 'P', 0, 1));
    }

    public function positionLabel(): string
    {
        $options = static::positionOptions();

        return $options[$this->position] ?? Str::headline(str_replace('_', ' ', (string) $this->position));
    }

    public function systemRoleLabel(): string
    {
        $options = static::systemRoleOptions();

        return $options[$this->system_role] ?? Str::headline(str_replace('_', ' ', (string) $this->system_role));
    }

    public function accountRoleSlug(): ?string
    {
        return match ($this->system_role) {
            'directeur' => 'directeur',
            'directeur_section' => 'directeur-adjoint',
            'censeur' => 'censeur',
            'surveillant_general' => 'surveillant-general',
            'secretaire' => 'secretaire',
            'comptable' => 'comptable',
            'responsable_salle_informatique',
            'agent_administratif',
            'agent_technique',
            'autre',
            null,
            '' => 'personnel',
            default => 'personnel',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function genderOptions(): array
    {
        return trans('people.shared.genders');
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return trans('people.staff.options.statuses');
    }

    /**
     * @return array<string, string>
     */
    public static function contractOptions(): array
    {
        return trans('people.staff.options.contract_types');
    }

    /**
     * @return array<string, string>
     */
    public static function positionOptions(): array
    {
        return trans('people.staff.options.positions');
    }

    /**
     * @return array<string, string>
     */
    public static function systemRoleOptions(): array
    {
        return trans('people.staff.options.system_roles');
    }

    public static function statusTone(string $status): string
    {
        return match ($status) {
            'actif' => 'emerald',
            'suspendu' => 'rose',
            'inactif' => 'slate',
            default => 'slate',
        };
    }

    public static function contractTone(string $contractType): string
    {
        return match ($contractType) {
            'titulaire' => 'emerald',
            'contractuel' => 'sky',
            'vacataire' => 'amber',
            'benevole' => 'violet',
            'stagiaire' => 'slate',
            default => 'slate',
        };
    }
}
