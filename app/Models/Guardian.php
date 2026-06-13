<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Guardian extends Model
{
    use HasFactory;

    protected $table = 'parents';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'user_id',
        'last_name',
        'first_names',
        'gender',
        'phone',
        'email',
        'address',
        'profession',
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

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_parent', 'parent_id', 'student_id')
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
        return trans('people.shared.guardian_statuses');
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
}
