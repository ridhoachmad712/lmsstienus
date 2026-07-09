<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'capacity', 'note'])]
class Room extends Model
{
    protected $casts = ['capacity' => 'integer'];

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    /** Label: "KODE — Nama" atau Nama saja. */
    public function label(): string
    {
        return $this->code ? $this->code.' — '.$this->name : $this->name;
    }
}
