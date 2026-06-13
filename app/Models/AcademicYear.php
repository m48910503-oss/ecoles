<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AcademicYear extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'start_date',
        'end_date',
        'is_active',
        'enrollment_open',
        'multi_school_enabled',
        'notes',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'enrollment_open' => 'boolean',
        'multi_school_enabled' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function calendars(): HasMany
    {
        return $this->hasMany(AcademicCalendar::class);
    }

    public function cycles(): BelongsToMany
    {
        return $this->belongsToMany(AcademicCycle::class, 'academic_year_cycles')
            ->withTimestamps()
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function levels(): BelongsToMany
    {
        return $this->belongsToMany(AcademicLevel::class, 'academic_year_levels', 'academic_year_id', 'academic_level_id')
            ->withTimestamps()
            ->orderBy('academic_levels.sort_order')
            ->orderBy('academic_levels.name');
    }

    public function series(): BelongsToMany
    {
        return $this->belongsToMany(AcademicSeries::class, 'academic_year_series', 'academic_year_id', 'academic_series_id')
            ->withTimestamps()
            ->orderBy('academic_series.sort_order')
            ->orderBy('academic_series.name');
    }

    public function academicConfigurations(): HasMany
    {
        return $this->hasMany(AcademicConfiguration::class);
    }

    public function activeAcademicConfiguration(): HasOne
    {
        return $this->hasOne(AcademicConfiguration::class)->where('is_active', true);
    }

    public function academicPeriods(): HasMany
    {
        return $this->hasMany(AcademicPeriod::class);
    }

    public function schoolDayConfigurations(): HasMany
    {
        return $this->hasMany(SchoolDayConfiguration::class);
    }

    public function isCurrentPeriod(): bool
    {
        if (! $this->start_date || ! $this->end_date) {
            return false;
        }

        $today = now()->startOfDay();

        return $today->betweenIncluded(
            $this->start_date->copy()->startOfDay(),
            $this->end_date->copy()->endOfDay(),
        );
    }
}
