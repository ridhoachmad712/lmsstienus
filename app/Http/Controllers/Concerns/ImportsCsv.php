<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\UploadedFile;

/** Pembantu import CSV sederhana (tanpa dependensi) untuk Data Master. */
trait ImportsCsv
{
    /**
     * Baca berkas CSV menjadi array baris (tiap baris = array kolom, sudah di-trim).
     * Pemisah kolom dideteksi otomatis (koma, titik-koma, atau tab) — Excel versi
     * Indonesia kerap memakai ";". BOM UTF-8 di awal berkas dibuang. Baris pertama
     * dilewati bila terdeteksi judul kolom (mengandung salah satu $headerHints).
     * Baris kosong diabaikan.
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

        $delimiter = $this->detectDelimiter($file->getRealPath());

        $rows = [];
        $first = true;
        while (($cols = fgetcsv($handle, 0, $delimiter)) !== false) {
            // Buang BOM UTF-8 dari sel pertama pada baris awal.
            if ($first && isset($cols[0])) {
                $cols[0] = preg_replace('/^\x{FEFF}/u', '', (string) $cols[0]);
            }

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

    /** Tebak pemisah kolom dari baris pertama berkas: pilih yang paling banyak muncul. */
    private function detectDelimiter(string $path): string
    {
        $line = '';
        if (($fh = fopen($path, 'r')) !== false) {
            $line = (string) fgets($fh);
            fclose($fh);
        }
        $line = preg_replace('/^\x{FEFF}/u', '', $line);

        $candidates = [
            ';' => substr_count($line, ';'),
            ',' => substr_count($line, ','),
            "\t" => substr_count($line, "\t"),
        ];
        arsort($candidates);
        $best = array_key_first($candidates);

        // Bila tidak ada satu pun pemisah terdeteksi, default koma.
        return $candidates[$best] > 0 ? $best : ',';
    }
}
