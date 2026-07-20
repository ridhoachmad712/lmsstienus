<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'code', 'kaprodi_id'])]
class Prodi extends Model
{
    protected $table = 'prodi';

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Dosen yang menjabat sebagai kaprodi (kepala) prodi ini. */
    public function kaprodi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kaprodi_id');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
