<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['prodi_id', 'kurikulum_id', 'code', 'name', 'sks', 'semester_no', 'jenis'])]
class MataKuliah extends Model
{
    protected $table = 'mata_kuliah';

    protected $casts = ['sks' => 'integer', 'semester_no' => 'integer'];

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function kurikulum(): BelongsTo
    {
        return $this->belongsTo(Kurikulum::class);
    }

    /** Mata kuliah prasyarat (harus lulus dulu). */
    public function prasyarat(): BelongsToMany
    {
        return $this->belongsToMany(MataKuliah::class, 'mata_kuliah_prasyarat', 'mata_kuliah_id', 'prasyarat_id');
    }

    /** Kelas (paralel) yang mengampu mata kuliah ini. */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
