<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'type', 'start_date', 'end_date', 'year', 'semester', 'note'])]
class AcademicEvent extends Model
{
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'year' => 'integer',
    ];

    /** Jenis agenda: label + warna Tabler + ikon. */
    public const TYPES = [
        'krs' => ['Pengisian KRS', 'blue', 'ti-clipboard-list'],
        'kuliah' => ['Perkuliahan', 'green', 'ti-school'],
        'uts' => ['UTS', 'orange', 'ti-pencil'],
        'uas' => ['UAS', 'red', 'ti-pencil'],
        'nilai' => ['Input/Batas Nilai', 'purple', 'ti-clipboard-check'],
        'libur' => ['Libur', 'teal', 'ti-beach'],
        'lainnya' => ['Lainnya', 'secondary', 'ti-calendar-event'],
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->type][0] ?? ucfirst($this->type);
    }

    public function typeColor(): string
    {
        return self::TYPES[$this->type][1] ?? 'secondary';
    }

    public function typeIcon(): string
    {
        return self::TYPES[$this->type][2] ?? 'ti-calendar-event';
    }

    /** Rentang tanggal ter-format. */
    public function dateRange(): string
    {
        $start = $this->start_date?->translatedFormat('d M Y');
        if (! $this->end_date || $this->end_date->equalTo($this->start_date)) {
            return (string) $start;
        }

        return $start.' – '.$this->end_date->translatedFormat('d M Y');
    }

    /** Sedang berlangsung (hari ini di antara start & end). */
    public function isOngoing(): bool
    {
        $end = $this->end_date ?? $this->start_date;

        return today()->betweenIncluded($this->start_date, $end);
    }

    /** Sudah lewat. */
    public function isPast(): bool
    {
        return ($this->end_date ?? $this->start_date)->lt(today());
    }
}
