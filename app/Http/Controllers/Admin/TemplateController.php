<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Unduh berkas contoh (template) CSV untuk import Data Master. */
class TemplateController extends Controller
{
    /** entitas => [judul kolom, contoh baris]. */
    private const TEMPLATES = [
        'prodi' => [
            ['kode', 'nama'],
            ['AK', 'Akuntansi'],
        ],
        'kurikulum' => [
            ['nama', 'tahun', 'kode_prodi', 'aktif'],
            ['Kurikulum 2024', '2024', 'AK', '1'],
        ],
        'matakuliah' => [
            ['kode', 'nama', 'sks', 'semester', 'jenis', 'kode_prodi'],
            ['AK101', 'Pengantar Akuntansi', '3', '1', 'wajib', 'AK'],
        ],
        'staff' => [
            ['nama', 'email', 'peran', 'kode_prodi', 'nip', 'nidn'],
            ['Budi Santoso, S.E., M.M.', 'budi@kampus.ac.id', 'dosen', 'AK', '198501012010011001', '0101018501'],
        ],
        'rooms' => [
            ['kode', 'nama', 'kapasitas', 'catatan'],
            ['R101', 'Ruang 101', '40', 'Gedung A'],
        ],
        'timeslots' => [
            ['nama', 'mulai', 'selesai', 'urutan'],
            ['Sesi 1', '08:00', '09:40', '1'],
        ],
    ];

    public function download(string $entity): StreamedResponse
    {
        abort_unless(isset(self::TEMPLATES[$entity]), 404);

        [$header, $sample] = self::TEMPLATES[$entity];
        $filename = "template-{$entity}.csv";

        return response()->streamDownload(function () use ($header, $sample) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 agar Excel membuka karakter non-ASCII dengan benar.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header);
            fputcsv($out, $sample);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
