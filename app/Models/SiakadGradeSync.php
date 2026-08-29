<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'course_id', 'user_id', 'siakad_schedule_id', 'siakad_academic_year_id',
    'numeric_score', 'letter_grade', 'quality_point', 'status', 'attempts',
    'error_message', 'payload_hash', 'finalized_by', 'finalized_at', 'synced_at',
])]
class SiakadGradeSync extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SYNCED = 'synced';
    public const STATUS_FAILED = 'failed';
    public const STATUS_STALE = 'stale';

    protected function casts(): array
    {
        return [
            'numeric_score' => 'decimal:2',
            'quality_point' => 'decimal:2',
            'attempts' => 'integer',
            'finalized_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_SYNCED => 'Tersinkron',
            self::STATUS_FAILED => 'Gagal',
            self::STATUS_STALE => 'Perlu sinkron ulang',
            default => 'Menunggu',
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_SYNCED => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_STALE => 'orange',
            default => 'yellow',
        };
    }
}
