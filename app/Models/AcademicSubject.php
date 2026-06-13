<?php

namespace App\Models;

use App\Enums\Academic\AcademicSubjectCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicSubject extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'name',
        'code',
        'short_name',
        'category',
        'description',
        'is_optional_by_default',
        'is_conduct',
        'is_active',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'category' => AcademicSubjectCategory::class,
        'is_optional_by_default' => 'boolean',
        'is_conduct' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function coefficients(): HasMany
    {
        return $this->hasMany(AcademicCoefficient::class, 'academic_subject_id');
    }

    public function conductSettings(): HasMany
    {
        return $this->hasMany(AcademicConductSetting::class, 'academic_subject_id');
    }
}
