<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['course_id', 'user_id', 'status', 'enrolled_at', 'submitted_at', 'approved_at', 'approved_by'])]
class Enrollment extends Model
{
    /** Rencana (di KRS mahasiswa, belum diajukan). */
    public const STATUS_DRAFT = 'draft';
    /** Diajukan ke dosen wali, menunggu persetujuan. */
    public const STATUS_SUBMITTED = 'diajukan';
    /** Disetujui — enrollment aktif (mahasiswa dapat akses kelas). */
    public const STATUS_APPROVED = 'disetujui';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Rencana',
        self::STATUS_SUBMITTED => 'Diajukan',
        self::STATUS_APPROVED => 'Disetujui',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Warna badge Tabler per status. */
    public function statusColor(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'green',
            self::STATUS_SUBMITTED => 'yellow',
            default => 'secondary',
        };
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Dosen wali yang menyetujui. */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
