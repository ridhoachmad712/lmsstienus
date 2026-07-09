<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['course_id', 'day', 'start_time', 'end_time', 'room', 'room_id', 'time_slot_id'])]
class ClassSchedule extends Model
{
    protected $casts = ['day' => 'integer'];

    public const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    /** Nama ruang untuk tampilan (master bila ada, jika tidak teks bebas lama). */
    public function roomLabel(): ?string
    {
        return $this->ruangan?->name ?? $this->room;
    }

    public function dayLabel(): string
    {
        return self::DAYS[$this->day] ?? '—';
    }
}
