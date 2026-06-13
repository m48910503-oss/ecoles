<?php

namespace App\Models;

use App\Enums\Academic\AcademicCycleCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class AcademicCycle extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'category',
        'description',
        'sort_order',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'category' => AcademicCycleCategory::class,
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function calendars(): HasMany
    {
        return $this->hasMany(AcademicCalendar::class, 'cycle_id');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(AcademicLevel::class, 'cycle_id');
    }

    public function series(): HasMany
    {
        return $this->hasMany(AcademicSeries::class, 'cycle_id');
    }

    public function classrooms(): HasManyThrough
    {
        return $this->hasManyThrough(
            AcademicClassroom::class,
            AcademicLevel::class,
            'cycle_id',
            'level_id',
            'id',
            'id',
        );
    }

    public function schoolDayConfigurations(): HasMany
    {
        return $this->hasMany(SchoolDayConfiguration::class, 'cycle_id');
    }

    public function supportsSeries(): bool
    {
        return $this->category?->supportsSeries() ?? false;
    }
}
