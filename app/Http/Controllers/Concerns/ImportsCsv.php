<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\UploadedFile;

/** Pembantu import CSV sederhana (tanpa dependensi) untuk Data Master. */
trait ImportsCsv
{
    /**
     * Baca berkas CSV menjadi array baris (tiap baris = array kolom, sudah di-trim).
     * Baris pertama dilewati bila terdeteksi sebagai judul kolom (mengandung salah
     * satu $headerHints, case-insensitive). Baris kosong diabaikan.
     *
     * @param  array<int,string>  $headerHints
     * @return array<int,array<int,string>>
     */
    protected function readCsvRows(UploadedFile $file, array $headerHints = []): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return [];
        }

        $rows = [];
        $first = true;
        while (($cols = fgetcsv($handle)) !== false) {
            // Lewati baris judul kolom.
            if ($first) {
                $first = false;
                $joined = strtolower(implode(',', $cols));
                foreach ($headerHints as $hint) {
                    if (str_contains($joined, strtolower($hint))) {
                        continue 2;
                    }
                }
            }

            $cols = array_map(fn ($c) => trim((string) $c), $cols);
            // Abaikan baris yang benar-benar kosong.
            if (implode('', $cols) === '') {
                continue;
            }
            $rows[] = $cols;
        }
        fclose($handle);

        return $rows;
    }
}
