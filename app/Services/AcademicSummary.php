<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Semester;
use App\Models\User;

/**
 * Ringkasan akademik mahasiswa (gaya SIAKAD): IPK, IPS terakhir, SKS kumulatif,
 * semester ke-, status akademik, & status KRS periode aktif.
 */
class AcademicSummary
{
    public function __construct(private ?Transcript $transcript = null)
    {
        $this->transcript ??= new Transcript();
    }

    /**
     * @return array{
     *   ipk: float, sks_kumulatif: int, ips_terakhir: ?float, ips_label: ?string,
     *   semester_ke: ?int, status: string, status_color: string,
     *   sks_krs: int, krs_status: string, periode_label: string
     * }
     */
    public function forStudent(User $student): array
    {
        $t = $this->transcript->forStudent($student);

        // IPS terakhir = periode ber-nilai (completed) paling baru.
        $ipsTerakhir = null;
        $ipsLabel = null;
        foreach ($t['periods'] as $p) {
            if (! is_null($p['ips'] ?? null)) {
                $ipsTerakhir = $p['ips'];
                $ipsLabel = $p['label'];
            }
        }

        [$year, $semester] = explode('-', Semester::primaryKey(), 2);

        return [
            'ipk' => $t['ipk'],
            'sks_kumulatif' => $t['total_sks'],
            'ips_terakhir' => $ipsTerakhir,
            'ips_label' => $ipsLabel,
            'semester_ke' => $this->semesterKe($student, $year, $semester),
            'status' => $student->student_status ?? 'aktif',
            'status_color' => $this->statusColor($student->student_status ?? 'aktif'),
            'sks_krs' => $this->sksKrs($student, $year, $semester),
            'krs_status' => $this->krsStatus($student, $year, $semester),
            'periode_label' => Semester::keyLabel($year.'-'.$semester),
        ];
    }

    /** Semester ke- berdasarkan angkatan & periode aktif (Ganjil=+1, Genap/Antara=+2). */
    private function semesterKe(User $student, string $year, string $semester): ?int
    {
        if (! $student->entry_year) {
            return null;
        }

        $offset = $semester === 'Ganjil' ? 1 : 2;

        return max(1, ((int) $year - (int) $student->entry_year) * 2 + $offset);
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            'aktif' => 'green',
            'cuti' => 'yellow',
            'lulus' => 'blue',
            'keluar' => 'red',
            default => 'secondary',
        };
    }

    /** SKS yang diambil pada KRS periode aktif (semua status non-tolak). */
    private function sksKrs(User $student, string $year, string $semester): int
    {
        return (int) Enrollment::where('user_id', $student->id)
            ->whereHas('course', fn ($q) => $q->where('year', $year)->where('semester', $semester))
            ->with('course.mataKuliah')
            ->get()
            ->sum(fn ($e) => (int) ($e->course->mataKuliah->sks ?? 0));
    }

    /** Status KRS periode aktif: belum | draft | diajukan | disetujui. */
    private function krsStatus(User $student, string $year, string $semester): string
    {
        $statuses = Enrollment::where('user_id', $student->id)
            ->whereHas('course', fn ($q) => $q->where('year', $year)->where('semester', $semester))
            ->pluck('status');

        if ($statuses->isEmpty()) {
            return 'belum';
        }
        if ($statuses->contains(Enrollment::STATUS_SUBMITTED)) {
            return Enrollment::STATUS_SUBMITTED;
        }
        if ($statuses->contains(Enrollment::STATUS_DRAFT)) {
            return Enrollment::STATUS_DRAFT;
        }

        return Enrollment::STATUS_APPROVED;
    }
}
