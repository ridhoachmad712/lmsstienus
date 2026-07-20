<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /** @var array<string,string>|null */
    protected static ?array $cache = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        if (static::$cache === null) {
            try {
                static::$cache = static::pluck('value', 'key')->all();
            } catch (\Throwable $e) {
                static::$cache = [];
            }
        }

        return static::$cache[$key] ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        static::$cache = null;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = static::get($key);

        return $v === null ? $default : ($v === '1' || $v === 'true');
    }

    /**
     * Status buka berbasis jadwal opsional. Bila rentang tanggal (start/end) diisi,
     * status mengikuti tanggal hari ini (otomatis). Bila keduanya kosong, jatuh ke
     * sakelar manual ($boolKey). Mendukung rentang terbuka (hanya start / hanya end).
     */
    public static function scheduleOpen(string $startKey, string $endKey, string $boolKey): bool
    {
        $start = static::get($startKey);
        $end = static::get($endKey);

        if ($start || $end) {
            $today = now()->toDateString();

            return (! $start || $today >= $start) && (! $end || $today <= $end);
        }

        return static::bool($boolKey, false);
    }
}
