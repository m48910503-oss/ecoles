<?php

namespace App\Models;

use App\Enums\Academic\AcademicPeriodType;
use App\Enums\Academic\CalendarScopeType;
use App\Enums\Academic\SchoolDayType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicConfiguration extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'academic_year_id',
        'scope_type',
        'cycle_id',
        'scope_signature',
        'period_type',
        'period_count',
        'school_day_type',
        'is_active',
        'is_locked',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'scope_type' => CalendarScopeType::class,
        'period_type' => AcademicPeriodType::class,
        'school_day_type' => SchoolDayType::class,
        'period_count' => 'integer',
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $configuration): void {
            $scopeType = $configuration->scope_type instanceof CalendarScopeType
                ? $configuration->scope_type
                : CalendarScopeType::tryFrom((string) $configuration->scope_type) ?? CalendarScopeType::Global;

            $configuration->scope_type = $scopeType->value;

            if ($scopeType === CalendarScopeType::Global) {
                $configuration->cycle_id = null;
                $configuration->scope_signature = 'global';

                return;
            }

            if (! filled($configuration->scope_signature) || $configuration->scope_signature === 'global') {
                $configuration->scope_signature = 'cycle-group:'.((int) ($configuration->cycle_id ?? 0));
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(AcademicCycle::class, 'cycle_id');
    }

    public function coveredCycles(): BelongsToMany
    {
        return $this->belongsToMany(
            AcademicCycle::class,
            'academic_configuration_cycles',
            'academic_configuration_id',
            'academic_cycle_id'
        )
            ->withTimestamps()
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function calendars(): HasMany
    {
        return $this->hasMany(AcademicCalendar::class, 'academic_configuration_id');
    }

    public function calendar(): HasOne
    {
        return $this->hasOne(AcademicCalendar::class, 'academic_configuration_id');
    }

    public function globalCalendar(): HasOne
    {
        return $this->calendar();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(AcademicPeriod::class);
    }

    public function uniqueScopeKey(): string
    {
        return $this->scope_type === CalendarScopeType::Cycle
            ? 'cycle-group:'.implode('-', $this->resolvedCoveredCycleIds())
            : 'global';
    }

    public function resolvedCoveredCycleIds(): array
    {
        $cycleIds = $this->relationLoaded('coveredCycles')
            ? $this->coveredCycles->pluck('id')
            : $this->coveredCycles()->pluck('academic_cycles.id');

        if ($cycleIds->isEmpty() && $this->cycle_id) {
            $cycleIds = collect([(int) $this->cycle_id]);
        }

        return $cycleIds
            ->map(fn ($cycleId) => (int) $cycleId)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function singleCoveredCycleId(): ?int
    {
        $cycleIds = $this->resolvedCoveredCycleIds();

        return count($cycleIds) === 1 ? $cycleIds[0] : null;
    }

    public function coversCycle(int $cycleId): bool
    {
        return in_array($cycleId, $this->resolvedCoveredCycleIds(), true);
    }

    public function scopeReferenceLabel(): ?string
    {
        if ($this->scope_type !== CalendarScopeType::Cycle) {
            return null;
        }

        $cycles = $this->relationLoaded('coveredCycles')
            ? $this->coveredCycles
            : $this->coveredCycles()->get(['academic_cycles.id', 'academic_cycles.name']);

        if ($cycles->isEmpty() && $this->cycle) {
            $cycles = collect([$this->cycle]);
        }

        if ($cycles->isEmpty()) {
            return __('academic.common.not_configured');
        }

        return $cycles
            ->pluck('name')
            ->filter()
            ->implode(' + ');
    }
}
