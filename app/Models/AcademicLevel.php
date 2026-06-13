<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicLevel extends Model
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
        'uses_series',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
        'uses_series' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(AcademicCycle::class, 'cycle_id');
    }

    public function calendars(): HasMany
    {
        return $this->hasMany(AcademicCalendar::class, 'level_id');
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(AcademicClassroom::class, 'level_id');
    }

    public function coefficients(): HasMany
    {
        return $this->hasMany(AcademicCoefficient::class, 'level_id');
    }

    public function conductSettings(): HasMany
    {
        return $this->hasMany(AcademicConductSetting::class, 'level_id');
    }

    /**
     * @return BelongsToMany<AcademicSeries, $this>
     */
    public function series(): BelongsToMany
    {
        return $this->belongsToMany(
            AcademicSeries::class,
            'academic_level_series',
            'level_id',
            'series_id'
        )->withTimestamps();
    }

    /**
     * @return BelongsToMany<AcademicYear, $this>
     */
    public function academicYears(): BelongsToMany
    {
        return $this->belongsToMany(
            AcademicYear::class,
            'academic_year_levels',
            'academic_level_id',
            'academic_year_id'
        )->withTimestamps();
    }
}
