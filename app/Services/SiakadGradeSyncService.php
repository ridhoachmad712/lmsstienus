<?php

namespace App\Services;

use App\Models\Course;
use App\Models\SiakadGradeSync;
use App\Models\User;
use App\Support\Grades;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SiakadGradeSyncService
{
    public function __construct(private readonly GradeCalculator $calculator) {}

    /** Ringkasan koneksi dan kandidat jadwal untuk halaman nilai dosen. */
    public function overview(Course $course): array
    {
        $enabled = (bool) config('services.legacy_siakad.grade_sync_enabled');
        $configured = $this->isConfigured();
        $candidates = collect();
        $connectionError = null;

        if ($enabled && $configured) {
            try {
                $candidates = $this->scheduleCandidates($course);
            } catch (Throwable $exception) {
                report($exception);
                $connectionError = 'Database SIAKAD tidak dapat dihubungi. Periksa konfigurasi koneksi.';
            }
        }

        return [
            'enabled' => $enabled,
            'configured' => $configured,
            'candidates' => $candidates,
            'connectionError' => $connectionError,
        ];
    }

    public function isConfigured(): bool
    {
        $connection = (array) config('database.connections.siakad', []);
        $driver = $connection['driver'] ?? null;

        if (! filled($connection['database'] ?? null)) {
            return false;
        }

        return $driver === 'sqlite'
            || filled($connection['url'] ?? null)
            || filled($connection['username'] ?? null);
    }

    /** Jadwal SIAKAD yang kode mata kuliah dan periodenya cocok dengan kelas LMS. */
    public function scheduleCandidates(Course $course): Collection
    {
        $this->assertAvailable();
        $expectedYear = $this->legacyAcademicYearCode($course);

        $query = DB::connection('siakad')->table('jadwal_mengajar as jadwal')
            ->join('thn_akademik as tahun', 'tahun.id_thn_akademik', '=', 'jadwal.id_thn_akademik')
            ->where('jadwal.kode_mk', $course->code)
            ->select([
                'jadwal.id_jadwal',
                'jadwal.kode_prodi',
                'jadwal.nip',
                'jadwal.kode_mk',
                'jadwal.id_thn_akademik',
                'tahun.ket',
                'tahun.thn_akademik',
            ]);

        if ($expectedYear !== null) {
            $query->where('tahun.thn_akademik', $expectedYear);
        } else {
            $query->where('tahun.thn_akademik', 'like', $course->year.'%');
        }

        $lecturerId = trim((string) $course->lecturer?->nim_nip);

        return $query->orderBy('jadwal.id_jadwal')->get()
            ->map(function ($row) use ($lecturerId) {
                $row->lecturer_match = $lecturerId !== '' && (string) $row->nip === $lecturerId;

                return $row;
            })
            ->sortByDesc('lecturer_match')
            ->values();
    }

    /** Tetapkan jadwal resmi; ID eksternal tidak dibuat sebagai foreign key lokal. */
    public function assignSchedule(Course $course, ?int $scheduleId): void
    {
        if ($scheduleId === null) {
            $this->saveScheduleMapping($course, null);

            return;
        }

        $schedule = $this->findSchedule($scheduleId);
        $this->assertScheduleMatchesCourse($course, $schedule);
        $this->saveScheduleMapping($course, $scheduleId);
    }

    /**
     * Finalisasi snapshot nilai dan sinkronkan ke KHS resmi SIAKAD.
     * Setiap mahasiswa memakai transaksi sendiri agar kegagalan satu NIM tidak
     * membatalkan mahasiswa lain, sekaligus memungkinkan retry yang idempoten.
     */
    public function syncCourse(Course $course, User $actor): array
    {
        $this->assertAvailable();

        if (! $course->isCompleted()) {
            throw new DomainException('Kelas harus ditandai selesai sebelum nilai dikirim ke SIAKAD.');
        }

        $course->loadMissing(['lecturer', 'students']);
        $schedule = $this->resolveSchedule($course);
        $gradeData = $this->calculator->forCourse($course);

        if ($gradeData['components']->isEmpty() || $gradeData['summary']['weight_total'] !== 100) {
            throw new DomainException('Komponen nilai harus tersedia dengan total bobot tepat 100%.');
        }

        foreach ($gradeData['rows'] as $row) {
            if (collect($row['components'])->contains(fn ($value) => $value === null)) {
                throw new DomainException('Masih ada mahasiswa dengan komponen nilai yang belum lengkap.');
            }
        }

        $scale = $this->legacyGradeScale();
        $finalizedAt = now();
        $course->forceFill([
            'siakad_schedule_id' => (int) $schedule->id_jadwal,
            'grades_finalized_at' => $finalizedAt,
            'grades_finalized_by' => $actor->id,
        ])->save();

        $result = [
            'total' => $gradeData['rows']->count(),
            'synced' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        foreach ($gradeData['rows'] as $row) {
            $outcome = $this->syncStudent(
                $course,
                $row['student'],
                (float) $row['final'],
                $schedule,
                $scale,
                $actor,
                $finalizedAt,
            );
            $result[$outcome]++;
        }

        return $result;
    }

    private function syncStudent(
        Course $course,
        User $student,
        float $score,
        object $schedule,
        Collection $scale,
        User $actor,
        $finalizedAt,
    ): string {
        $legacyGrade = $this->gradeForScore($scale, $score);
        $nim = trim((string) $student->nim_nip);
        $payloadHash = hash('sha256', json_encode([
            'nim' => $nim,
            'schedule' => (int) $schedule->id_jadwal,
            'academic_year' => (int) $schedule->id_thn_akademik,
            'score' => number_format($score, 2, '.', ''),
            'grade' => (string) $legacyGrade->grade,
            'point' => number_format((float) $legacyGrade->bobot, 2, '.', ''),
        ], JSON_THROW_ON_ERROR));

        $sync = SiakadGradeSync::firstOrNew([
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);

        if ($sync->exists
            && $sync->status === SiakadGradeSync::STATUS_SYNCED
            && hash_equals((string) $sync->payload_hash, $payloadHash)) {
            $sync->forceFill([
                'finalized_by' => $actor->id,
                'finalized_at' => $finalizedAt,
            ])->save();

            return 'skipped';
        }

        $sync->fill([
            'siakad_schedule_id' => (int) $schedule->id_jadwal,
            'siakad_academic_year_id' => (int) $schedule->id_thn_akademik,
            'numeric_score' => $score,
            'letter_grade' => (string) $legacyGrade->grade,
            'quality_point' => (float) $legacyGrade->bobot,
            'status' => SiakadGradeSync::STATUS_PENDING,
            'attempts' => ((int) $sync->attempts) + 1,
            'error_message' => null,
            'payload_hash' => $payloadHash,
            'finalized_by' => $actor->id,
            'finalized_at' => $finalizedAt,
            'synced_at' => null,
        ])->save();

        try {
            if ($nim === '') {
                throw new DomainException('NIM mahasiswa belum diisi pada akun LMS.');
            }

            $connection = DB::connection('siakad');
            $krsRows = $connection->table('krs_mhs')
                ->where('nim_npm', $nim)
                ->where('id_jadwal', $schedule->id_jadwal)
                ->where('id_thn_akademik', $schedule->id_thn_akademik)
                ->get(['kode_prodi']);

            if ($krsRows->isEmpty()) {
                throw new DomainException('Mahasiswa tidak terdaftar pada KRS resmi untuk jadwal ini.');
            }
            if ($krsRows->count() > 1) {
                throw new DomainException('KRS resmi mahasiswa terduplikasi; periksa data SIAKAD.');
            }

            $krs = $krsRows->first();
            $connection->transaction(function () use ($connection, $nim, $schedule, $krs, $score, $legacyGrade) {
                $khsQuery = $connection->table('khs_mhs')
                    ->where('kode_prodi', $krs->kode_prodi)
                    ->where('nim_npm', $nim)
                    ->where('id_jadwal', $schedule->id_jadwal)
                    ->where('id_thn_akademik', $schedule->id_thn_akademik);

                $khsCount = (clone $khsQuery)->count();
                if ($khsCount === 0) {
                    throw new DomainException('Baris KHS resmi tidak ditemukan untuk KRS mahasiswa.');
                }
                if ($khsCount > 1) {
                    throw new DomainException('Baris KHS resmi terduplikasi; periksa data SIAKAD.');
                }

                (clone $khsQuery)->lockForUpdate()->first();

                $khsQuery->update([
                    'nilai_akhir' => Grades::num($score),
                    'bobot' => Grades::num((float) $legacyGrade->bobot),
                    'grade' => (string) $legacyGrade->grade,
                ]);
            });

            $sync->forceFill([
                'status' => SiakadGradeSync::STATUS_SYNCED,
                'error_message' => null,
                'synced_at' => now(),
            ])->save();

            return 'synced';
        } catch (DomainException $exception) {
            $this->markFailed($sync, $exception->getMessage());

            return 'failed';
        } catch (QueryException $exception) {
            report($exception);
            $this->markFailed($sync, 'Database SIAKAD menolak operasi sinkronisasi. Hubungi administrator.');

            return 'failed';
        } catch (Throwable $exception) {
            report($exception);
            $this->markFailed($sync, 'Terjadi gangguan saat sinkronisasi. Silakan coba lagi.');

            return 'failed';
        }
    }

    private function markFailed(SiakadGradeSync $sync, string $message): void
    {
        $sync->forceFill([
            'status' => SiakadGradeSync::STATUS_FAILED,
            'error_message' => mb_substr($message, 0, 1000),
            'synced_at' => null,
        ])->save();
    }

    private function resolveSchedule(Course $course): object
    {
        if ($course->siakad_schedule_id) {
            $schedule = $this->findSchedule((int) $course->siakad_schedule_id);
            $this->assertScheduleMatchesCourse($course, $schedule);

            return $schedule;
        }

        $candidates = $this->scheduleCandidates($course);
        $lecturerMatches = $candidates->where('lecturer_match', true)->values();
        $usable = $lecturerMatches->count() === 1 ? $lecturerMatches : $candidates;

        if ($usable->count() !== 1) {
            throw new DomainException($usable->isEmpty()
                ? 'Jadwal SIAKAD yang cocok tidak ditemukan. Atur pemetaan jadwal terlebih dahulu.'
                : 'Ditemukan beberapa jadwal SIAKAD. Pilih jadwal resmi terlebih dahulu.');
        }

        return $usable->first();
    }

    private function saveScheduleMapping(Course $course, ?int $scheduleId): void
    {
        $previous = $course->siakad_schedule_id === null ? null : (int) $course->siakad_schedule_id;
        if ($previous === $scheduleId) {
            return;
        }

        $course->forceFill([
            'siakad_schedule_id' => $scheduleId,
            'grades_finalized_at' => null,
            'grades_finalized_by' => null,
        ])->save();
        $course->gradeSyncs()->update([
            'status' => SiakadGradeSync::STATUS_STALE,
            'error_message' => 'Pemetaan jadwal berubah; nilai perlu disinkronkan ulang.',
            'synced_at' => null,
        ]);
    }

    private function findSchedule(int $scheduleId): object
    {
        $this->assertAvailable();
        $schedule = DB::connection('siakad')->table('jadwal_mengajar as jadwal')
            ->join('thn_akademik as tahun', 'tahun.id_thn_akademik', '=', 'jadwal.id_thn_akademik')
            ->where('jadwal.id_jadwal', $scheduleId)
            ->select([
                'jadwal.id_jadwal', 'jadwal.kode_prodi', 'jadwal.nip', 'jadwal.kode_mk',
                'jadwal.id_thn_akademik', 'tahun.ket', 'tahun.thn_akademik',
            ])
            ->first();

        if (! $schedule) {
            throw new DomainException('Jadwal yang dipilih tidak ditemukan pada SIAKAD.');
        }

        return $schedule;
    }

    private function assertScheduleMatchesCourse(Course $course, object $schedule): void
    {
        if (strcasecmp((string) $schedule->kode_mk, (string) $course->code) !== 0) {
            throw new DomainException('Kode mata kuliah pada jadwal SIAKAD tidak cocok dengan kelas LMS.');
        }

        $expectedYear = $this->legacyAcademicYearCode($course);
        if ($expectedYear !== null && (int) $schedule->thn_akademik !== $expectedYear) {
            throw new DomainException('Periode jadwal SIAKAD tidak cocok dengan kelas LMS.');
        }
        if ($expectedYear === null && ! str_starts_with((string) $schedule->thn_akademik, (string) $course->year)) {
            throw new DomainException('Tahun jadwal SIAKAD tidak cocok dengan kelas LMS.');
        }

        $lecturerId = trim((string) $course->lecturer?->nim_nip);
        if ($lecturerId !== '' && trim((string) $schedule->nip) !== $lecturerId) {
            throw new DomainException('NIP dosen pada jadwal SIAKAD tidak cocok dengan dosen pemilik kelas LMS.');
        }
    }

    private function legacyAcademicYearCode(Course $course): ?int
    {
        $suffix = match (strtolower((string) $course->semester)) {
            'ganjil' => '1',
            'genap' => '2',
            default => null,
        };

        return $suffix === null ? null : (int) ((string) $course->year.$suffix);
    }

    private function legacyGradeScale(): Collection
    {
        $scale = DB::connection('siakad')->table('tbl_grade')
            ->get(['grade', 'bobot', 'nilai_awal', 'nilai_akhir'])
            ->map(function ($row) {
                $row->min = (float) $row->nilai_awal;
                $row->max = (float) $row->nilai_akhir;

                return $row;
            })
            ->sortByDesc('min')
            ->values();

        if ($scale->isEmpty()) {
            throw new DomainException('Skala nilai SIAKAD belum dikonfigurasi.');
        }

        return $scale;
    }

    private function gradeForScore(Collection $scale, float $score): object
    {
        $grade = $scale->first(fn ($row) => $score >= $row->min && $score <= $row->max);
        $grade ??= $scale->first(fn ($row) => $score >= $row->min);

        if (! $grade) {
            throw new DomainException('Nilai tidak memiliki padanan grade pada SIAKAD.');
        }

        return $grade;
    }

    private function assertAvailable(): void
    {
        if (! config('services.legacy_siakad.grade_sync_enabled')) {
            throw new RuntimeException('Sinkronisasi nilai SIAKAD belum diaktifkan oleh administrator.');
        }
        if (! $this->isConfigured()) {
            throw new RuntimeException('Koneksi database SIAKAD belum dikonfigurasi pada LMS.');
        }
    }
}
