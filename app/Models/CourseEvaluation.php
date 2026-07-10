<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Evaluasi Dosen oleh Mahasiswa (EDOM) — satu per (mahasiswa, kelas). */
#[Fillable(['course_id', 'user_id', 'answers', 'comment'])]
class CourseEvaluation extends Model
{
    protected $casts = ['answers' => 'array'];

    /** Butir kuesioner (urut; skor 1–4). */
    public const QUESTIONS = [
        'Dosen hadir dan tepat waktu',
        'Materi disampaikan dengan jelas',
        'Dosen menguasai materi',
        'Adil & transparan dalam penilaian',
        'Responsif terhadap pertanyaan mahasiswa',
    ];

    public const SCALE = 4;

    public const SCALE_LABELS = [1 => 'Kurang', 2 => 'Cukup', 3 => 'Baik', 4 => 'Sangat Baik'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** Rata-rata skor responden ini (lintas butir). */
    public function average(): float
    {
        $a = $this->answers ?: [];

        return $a ? round(array_sum($a) / count($a), 2) : 0.0;
    }
}
