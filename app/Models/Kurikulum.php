<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['prodi_id', 'name', 'year', 'is_active'])]
class Kurikulum extends Model
{
    protected $table = 'kurikulum';

    protected $casts = ['year' => 'integer', 'is_active' => 'boolean'];

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function mataKuliah(): HasMany
    {
        return $this->hasMany(MataKuliah::class);
    }

    public function label(): string
    {
        return $this->name.' ('.$this->year.')';
    }
}
