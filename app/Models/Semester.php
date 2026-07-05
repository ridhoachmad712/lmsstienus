<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['year', 'semester'])]
class Semester extends Model
{
    /** Urutan semester dalam satu tahun (untuk pengurutan periode). */
    public const SEM_ORDER = ['Antara' => 1, 'Genap' => 2, 'Ganjil' => 3];

    protected $casts = [
        'year' => 'integer',
    ];

    /** Label tampilan, mis. "Ganjil 2025". */
    public function label(): string
    {
        return $this->semester.' '.$this->year;
    }

    /** Label untuk sebuah kunci periode "THN-Sem", mis. "2026-Genap" → "Genap 2026". */
    public static function keyLabel(string $key): string
    {
        [$y, $s] = array_pad(explode('-', $key, 2), 2, '');

        return trim($s.' '.$y);
    }

    /** Nilai urut sebuah kunci (tahun*10 + urutan semester), untuk sortByDesc. */
    public static function sortValue(string $key): int
    {
        [$y, $s] = array_pad(explode('-', $key, 2), 2, '');

        return ((int) $y) * 10 + (self::SEM_ORDER[$s] ?? 0);
    }

    /**
     * Kunci periode yang aktif, mis. ["2026-Genap", "2026-Antara"].
     * Bisa lebih dari satu. Fallback ke pengaturan tunggal lama bila belum diset.
     */
    public static function activeKeys(): array
    {
        $raw = Setting::get('active_periods');

        if ($raw !== null && $raw !== '') {
            $keys = json_decode($raw, true);
            if (is_array($keys)) {
                $keys = array_values(array_unique(array_filter(
                    $keys,
                    fn ($k) => is_string($k) && str_contains($k, '-'),
                )));
                if ($keys !== []) {
                    return $keys;
                }
            }
        }

        // Kompatibilitas: pengaturan tunggal lama.
        return [((int) Setting::get('academic_year', (string) date('Y'))).'-'.Setting::get('semester', 'Ganjil')];
    }

    public static function isActive(string $key): bool
    {
        return in_array($key, static::activeKeys(), true);
    }

    /** Periode aktif "utama" (paling baru) — dipakai sebagai default form kelas baru. */
    public static function primaryKey(): string
    {
        $keys = static::activeKeys();
        usort($keys, fn ($a, $b) => static::sortValue($b) <=> static::sortValue($a));

        return $keys[0] ?? (((int) date('Y')).'-Ganjil');
    }

    /** Simpan himpunan periode aktif + sinkronkan pengaturan tunggal ke periode utama. */
    public static function setActiveKeys(array $keys): void
    {
        $keys = array_values(array_unique(array_filter(
            $keys,
            fn ($k) => is_string($k) && str_contains($k, '-'),
        )));

        Setting::put('active_periods', json_encode($keys));

        // Sinkronkan academic_year/semester ke periode terbaru (default kelas baru).
        if ($keys !== []) {
            usort($keys, fn ($a, $b) => static::sortValue($b) <=> static::sortValue($a));
            [$y, $s] = explode('-', $keys[0], 2);
            Setting::put('academic_year', (string) (int) $y);
            Setting::put('semester', $s);
        }
    }
}
