<?php

namespace App\Models;

use App\Enums\Academic\CalendarEventStatus;
use App\Enums\Academic\CalendarEventType;
use App\Enums\Academic\CalendarTargetType;
use App\Enums\Academic\CalendarEventVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class CalendarEvent extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'academic_calendar_id',
        'academic_period_id',
        'title',
        'description',
        'event_type',
        'start_datetime',
        'end_datetime',
        'is_full_day',
        'location',
        'target_type',
        'target_id',
        'status',
        'visibility',
        'requires_validation',
        'created_by',
        'validated_by',
        'validated_at',
        'published_by',
        'published_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'archived_by',
        'archived_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'event_type' => CalendarEventType::class,
        'target_type' => CalendarTargetType::class,
        'status' => CalendarEventStatus::class,
        'visibility' => CalendarEventVisibility::class,
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'validated_at' => 'datetime',
        'published_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'archived_at' => 'datetime',
        'is_full_day' => 'boolean',
        'requires_validation' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $event): void {
            if ($event->isForceDeleting() && $event->status?->isPublished()) {
                throw ValidationException::withMessages([
                    'event' => __('academic.events.messages.published_force_delete_forbidden'),
                ]);
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendar::class, 'academic_calendar_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id');
    }

    public function targetCycle(): BelongsTo
    {
        return $this->belongsTo(AcademicCycle::class, 'target_id');
    }

    public function targetLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class, 'target_id');
    }

    public function targetClassroom(): BelongsTo
    {
        return $this->belongsTo(AcademicClassroom::class, 'target_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function archiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function targetReferenceLabel(): string
    {
        return match ($this->target_type) {
            CalendarTargetType::Global,
            CalendarTargetType::Staff,
            CalendarTargetType::Teachers,
            CalendarTargetType::Students,
            CalendarTargetType::Parents => $this->target_type->label(),
            CalendarTargetType::Cycle => $this->targetCycle?->name ?? __('academic.common.reference_id').' #'.$this->target_id,
            CalendarTargetType::Level => $this->targetLevel?->name ?? __('academic.common.reference_id').' #'.$this->target_id,
            CalendarTargetType::ClassRoom => $this->targetClassroom?->label() ?? ($this->target_id ? __('academic.common.reference_id').' #'.$this->target_id : __('academic.common.not_configured')),
        };
    }
}
