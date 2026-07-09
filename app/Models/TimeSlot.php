<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Sesi kuliah — slot jam baku (mis. "Sesi 1" 08:00–09:40). */
#[Fillable(['name', 'start_time', 'end_time', 'sort'])]
class TimeSlot extends Model
{
    protected $casts = ['sort' => 'integer'];

    public function schedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }

    /** Label: "Sesi 1 (08:00–09:40)". */
    public function label(): string
    {
        return $this->name.' ('.$this->start_time.'–'.$this->end_time.')';
    }
}
