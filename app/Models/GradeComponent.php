<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'name', 'type', 'weight', 'description'])]
class GradeComponent extends Model
{
    /** Tipe standar komponen nilai => label bawaan. */
    public const TYPES = [
        'kehadiran' => 'Aktivitas/Kehadiran',
        'tugas' => 'Tugas',
        'kuis' => 'Quiz',
        'uts' => 'UTS',
        'uas' => 'UAS',
        'project' => 'Project',
        'lainnya' => 'Lainnya',
    ];

    /** Nama bawaan untuk sebuah tipe (kosong bila 'lainnya' — nama diisi manual). */
    public static function defaultName(string $type): string
    {
        return $type === 'lainnya' ? '' : (self::TYPES[$type] ?? '');
    }

    /** Komponen yang nilainya diambil otomatis dari persentase kehadiran. */
    public function isAttendance(): bool
    {
        return $this->type === 'kehadiran';
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }
}
