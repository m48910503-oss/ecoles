<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PersonDocument extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'documentable_type',
        'documentable_id',
        'document_type',
        'original_name',
        'file_path',
        'mime_type',
        'size',
        'is_sensitive',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_sensitive' => 'bool',
        'size' => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function scopeForSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where('school_id', $schoolId);
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function documentTypeLabel(): string
    {
        $options = static::typeOptionsFor($this->documentable_type ?: $this->documentable);

        return $options[$this->document_type] ?? Str::headline(str_replace('_', ' ', $this->document_type));
    }

    public function humanReadableSize(): string
    {
        $bytes = max(0, (int) $this->size);
        $units = ['octets', 'Ko', 'Mo', 'Go'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return $index === 0
            ? $bytes.' '.$units[$index]
            : number_format($bytes, 1, ',', ' ').' '.$units[$index];
    }

    /**
     * @param  Student|Guardian|Staff|Teacher|string  $documentable
     * @return array<string, string>
     */
    public static function typeOptionsFor(Student|Guardian|Staff|Teacher|string $documentable): array
    {
        $class = is_string($documentable) ? $documentable : $documentable::class;

        return match ($class) {
            Student::class => trans('people.documents.options.student'),
            Guardian::class => trans('people.documents.options.parent'),
            Staff::class => trans('people.documents.options.staff'),
            Teacher::class => trans('people.documents.options.teacher'),
            default => trans('people.documents.options.generic'),
        };
    }
}
