<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['prodi_id', 'code', 'name', 'sks'])]
class MataKuliah extends Model
{
    protected $table = 'mata_kuliah';

    protected $casts = ['sks' => 'integer'];

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    /** Kelas (paralel) yang mengampu mata kuliah ini. */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
