<?php

namespace App\Models;

use App\Enums\Academic\AcademicCycleCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Teacher extends Model
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

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'user_id',
        'academic_cycle_id',
        'main_classroom_id',
        'main_subject_id',
        'matricule',
        'last_name',
        'first_names',
        'gender',
        'phone',
        'email',
        'address',
        'specialty',
        'diploma',
        'contract_type',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'actif');
    }

    public function scopeWithoutAssignments(Builder $query): Builder
    {
        return $query->doesntHave('assignments');
    }

    public function scopePrimaryTrack(Builder $query): Builder
    {
        return $query->whereHas('academicCycle', function (Builder $cycleQuery): void {
            $cycleQuery->whereIn('category', [
                AcademicCycleCategory::Preschool->value,
                AcademicCycleCategory::Primary->value,
            ]);
        });
    }

    public function scopeSecondaryTrack(Builder $query): Builder
    {
        return $query->whereHas('academicCycle', function (Builder $cycleQuery): void {
            $cycleQuery->whereIn('category', [
                AcademicCycleCategory::LowerSecondary->value,
                AcademicCycleCategory::UpperSecondary->value,
            ]);
        });
    }

    public function scopeWithoutPedagogicPlacement(Builder $query): Builder
    {
        return $query
            ->whereNull('main_classroom_id')
            ->whereNull('main_subject_id')
            ->doesntHave('assignments');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function academicCycle(): BelongsTo
    {
        return $this->belongsTo(AcademicCycle::class);
    }

    public function mainClassroom(): BelongsTo
    {
        return $this->belongsTo(AcademicClassroom::class, 'main_classroom_id');
    }

    public function mainSubject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class, 'main_subject_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(PersonDocument::class, 'documentable');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TeacherSubjectAssignment::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(TeacherInvitation::class);
    }

    public function latestInvitation(): HasOne
    {
        return $this->hasOne(TeacherInvitation::class)->latestOfMany();
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
        return trans('people.teachers.options.statuses');
    }

    /**
     * @return array<string, string>
     */
    public static function contractOptions(): array
    {
        return trans('people.teachers.options.contract_types');
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

    public function pedagogicTrack(): ?string
    {
        return $this->academicCycle?->category?->teacherTrack();
    }

    public function isPrimaryTrack(): bool
    {
        return $this->pedagogicTrack() === 'primary';
    }

    public function isSecondaryTrack(): bool
    {
        return $this->pedagogicTrack() === 'secondary';
    }

    public function pedagogicTrackLabel(): string
    {
        $track = $this->pedagogicTrack();

        if ($track === null) {
            return __('people.teachers.options.tracks.unclassified');
        }

        return __('people.teachers.options.tracks.'.$track);
    }

    public function pedagogicTrackTone(): string
    {
        return match ($this->pedagogicTrack()) {
            'primary' => 'emerald',
            'secondary' => 'sky',
            default => 'slate',
        };
    }

    public function pedagogicPlacementTypeLabel(): ?string
    {
        return match ($this->pedagogicTrack()) {
            'primary' => __('people.teachers.form.main_classroom'),
            'secondary' => __('people.teachers.form.main_subject'),
            default => $this->specialty !== null ? __('people.teachers.form.specialty') : null,
        };
    }

    public function pedagogicPlacementLabel(): ?string
    {
        return match ($this->pedagogicTrack()) {
            'primary' => $this->mainClassroom?->label() ?? $this->mainClassroom?->name,
            'secondary' => $this->mainSubject?->name,
            default => $this->specialty,
        };
    }
}
