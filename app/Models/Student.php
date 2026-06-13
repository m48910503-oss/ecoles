<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Student extends Model
{
    use HasFactory;

    public const GENDERS = [
        'masculin',
        'feminin',
        'autre',
    ];

    public const STATUSES = [
        'preinscrit',
        'en_attente_validation',
        'en_attente_paiement',
        'actif',
        'suspendu',
        'transfere',
        'radie',
        'ancien_eleve',
    ];

    public const RELATIONSHIP_TYPES = [
        'pere',
        'mere',
        'tuteur_legal',
        'oncle',
        'tante',
        'grand_parent',
        'responsable_financier',
        'contact_urgence',
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
        'date_of_birth',
        'place_of_birth',
        'nationality',
        'photo_path',
        'academic_classroom_id',
        'entry_academic_year_id',
        'status',
        'status_reason',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'actif');
    }

    public function scopeWithoutParents(Builder $query): Builder
    {
        return $query->doesntHave('parents');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentClassroom(): BelongsTo
    {
        return $this->belongsTo(AcademicClassroom::class, 'academic_classroom_id');
    }

    public function entryAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'entry_academic_year_id');
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'student_parent', 'student_id', 'parent_id')
            ->withPivot([
                'relationship_type',
                'is_primary_contact',
                'is_financial_responsible',
                'can_receive_notifications',
                'can_pickup_child',
                'notes',
                'created_by',
                'updated_by',
            ])
            ->withTimestamps();
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(PersonDocument::class, 'documentable');
    }

    public function primaryParents(): BelongsToMany
    {
        return $this->parents()->wherePivot('is_primary_contact', true);
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

        return mb_strtoupper($names ?: mb_substr($this->last_name ?: 'E', 0, 1));
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
        return trans('people.students.options.statuses');
    }

    /**
     * @return array<string, string>
     */
    public static function relationshipOptions(): array
    {
        return trans('people.students.options.relationship_types');
    }

    public static function statusTone(string $status): string
    {
        return match ($status) {
            'actif' => 'emerald',
            'suspendu' => 'rose',
            'en_attente_validation', 'en_attente_paiement' => 'amber',
            'preinscrit' => 'sky',
            'transfere' => 'violet',
            'radie', 'ancien_eleve' => 'slate',
            default => 'slate',
        };
    }

    public function isActiveStatus(): bool
    {
        return $this->status === 'actif';
    }
}
