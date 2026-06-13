<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TeacherInvitation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_REVOKED = 'revoked';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'teacher_id',
        'recipient_email',
        'token_hash',
        'status',
        'sent_at',
        'completed_at',
        'validated_at',
        'expires_at',
        'revoked_at',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'validated_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForTeacher(Builder $query, int $teacherId): Builder
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

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

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return trans('people.teachers.invitations.statuses');
    }

    public static function statusTone(string $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'amber',
            self::STATUS_COMPLETED => 'sky',
            self::STATUS_VALIDATED => 'emerald',
            self::STATUS_REVOKED => 'rose',
            'expired' => 'amber',
            default => 'slate',
        };
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING && ! $this->isExpired();
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isValidated(): bool
    {
        return $this->status === self::STATUS_VALIDATED;
    }

    public function isRevoked(): bool
    {
        return $this->status === self::STATUS_REVOKED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->expires_at !== null
            && $this->expires_at->isPast();
    }

    public function displayStatus(): string
    {
        return $this->isExpired() ? 'expired' : $this->status;
    }

    public function statusLabel(): string
    {
        $options = static::statusOptions();

        return $options[$this->displayStatus()] ?? Str::headline(str_replace('_', ' ', $this->displayStatus()));
    }

    public function statusToneValue(): string
    {
        return static::statusTone($this->displayStatus());
    }

    public function stateTitle(): string
    {
        return match ($this->displayStatus()) {
            self::STATUS_PENDING => __('people.teachers.invitations.alerts.pending_title'),
            self::STATUS_COMPLETED => __('people.teachers.invitations.alerts.completed_title'),
            self::STATUS_VALIDATED => __('people.teachers.invitations.alerts.validated_title'),
            self::STATUS_REVOKED => __('people.teachers.invitations.alerts.revoked_title'),
            'expired' => __('people.teachers.invitations.alerts.expired_title'),
            default => __('people.teachers.invitations.title'),
        };
    }

    public function stateDescription(): string
    {
        return match ($this->displayStatus()) {
            self::STATUS_PENDING => __('people.teachers.invitations.alerts.pending_description', [
                'date' => $this->expires_at?->translatedFormat('d M Y H:i') ?? '—',
            ]),
            self::STATUS_COMPLETED => __('people.teachers.invitations.alerts.completed_description'),
            self::STATUS_VALIDATED => __('people.teachers.invitations.alerts.validated_description'),
            self::STATUS_REVOKED => __('people.teachers.invitations.alerts.revoked_description'),
            'expired' => __('people.teachers.invitations.alerts.expired_description', [
                'date' => $this->expires_at?->translatedFormat('d M Y H:i') ?? '—',
            ]),
            default => __('people.teachers.invitations.description'),
        };
    }
}
