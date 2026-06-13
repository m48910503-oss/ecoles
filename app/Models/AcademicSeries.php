<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicSeries extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'cycle_id',
        'name',
        'code',
        'short_name',
        'description',
        'sort_order',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(AcademicCycle::class, 'cycle_id');
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(AcademicClassroom::class, 'series_id');
    }

    public function coefficients(): HasMany
    {
        return $this->hasMany(AcademicCoefficient::class, 'series_id');
    }

    /**
     * @return BelongsToMany<AcademicLevel, $this>
     */
    public function levels(): BelongsToMany
    {
        return $this->belongsToMany(
            AcademicLevel::class,
            'academic_level_series',
            'series_id',
            'level_id'
        )->withTimestamps();
    }

    /**
     * @return BelongsToMany<AcademicYear, $this>
     */
    public function academicYears(): BelongsToMany
    {
        return $this->belongsToMany(
            AcademicYear::class,
            'academic_year_series',
            'academic_series_id',
            'academic_year_id'
        )->withTimestamps();
    }
}
