<?php

namespace App\Models;

use App\Enums\Academic\AcademicPeriodType;
use App\Enums\Academic\CalendarScopeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicCalendar extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'academic_year_id',
        'academic_configuration_id',
        'name',
        'description',
        'scope_type',
        'cycle_id',
        'level_id',
        'class_id',
        'period_type',
        'period_count',
        'is_active',
        'validated_at',
        'validated_by',
        'created_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'scope_type' => CalendarScopeType::class,
        'period_type' => AcademicPeriodType::class,
        'period_count' => 'integer',
        'is_active' => 'boolean',
        'validated_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(AcademicConfiguration::class, 'academic_configuration_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(AcademicCycle::class, 'cycle_id');
    }

    public function coveredCycles(): BelongsToMany
    {
        return $this->belongsToMany(
            AcademicCycle::class,
            'academic_calendar_cycles',
            'academic_calendar_id',
            'academic_cycle_id'
        )
            ->withTimestamps()
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class, 'level_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(AcademicClassroom::class, 'class_id');
    }

    public function periods(): HasMany
    {
        return $this->hasMany(AcademicPeriod::class, 'academic_calendar_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'academic_calendar_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
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
        return match ($this->scope_type) {
            CalendarScopeType::Global => null,
            CalendarScopeType::Cycle => $this->coveredCycleReferenceLabel(),
            CalendarScopeType::Level => $this->level?->name ?? __('academic.common.reference_id').' #'.$this->level_id,
            CalendarScopeType::ClassRoom => $this->classroom?->label() ?? ($this->class_id ? __('academic.common.reference_id').' #'.$this->class_id : null),
        };
    }

    private function coveredCycleReferenceLabel(): ?string
    {
        $cycles = $this->relationLoaded('coveredCycles')
            ? $this->coveredCycles
            : $this->coveredCycles()->get(['academic_cycles.id', 'academic_cycles.name']);

        if ($cycles->isEmpty() && $this->cycle) {
            $cycles = collect([$this->cycle]);
        }

        return $cycles->pluck('name')->filter()->implode(' + ') ?: null;
    }
}
