<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\User;
use App\Services\Transcript;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class KrsController extends Controller
{
    /** Batas SKS default bila belum diatur admin. */
    public const DEFAULT_MAX_SKS = 24;

    public static function krsOpen(): bool
    {
        return Setting::scheduleOpen('krs_start', 'krs_end', 'krs_open');
    }

    /** Batas SKS global (plafon) yang diset admin. */
    public static function maxSks(): int
    {
        return (int) Setting::get('krs_max_sks', (string) self::DEFAULT_MAX_SKS);
    }

    /** Jatah SKS per-mahasiswa berdasar IPS semester lalu (aturan SIAKAD), dibatasi plafon admin. */
    public function quotaFor(User $student): int
    {
        $ips = (new \App\Services\AcademicSummary())->forStudent($student)['ips_terakhir'];

        $base = match (true) {
            $ips === null => 24,   // mahasiswa baru: paket penuh
            $ips >= 3.0 => 24,
            $ips >= 2.5 => 22,
            $ips >= 2.0 => 20,
            $ips >= 1.5 => 18,
            default => 15,
        };

        return min($base, self::maxSks());
    }

    /** Id mata kuliah yang SUDAH LULUS mahasiswa (nilai final & point ≥ 1.0 / bukan E). */
    private function passedMataKuliahIds(User $student): array
    {
        $ids = [];
        foreach ((new Transcript())->forStudent($student)['periods'] as $p) {
            foreach ($p['items'] as $it) {
                if (($it['counted'] ?? false) && ($it['point'] ?? 0) >= 1.0) {
                    if ($mkId = $it['course']->mata_kuliah_id ?? null) {
                        $ids[$mkId] = true;
                    }
                }
            }
        }

        return array_keys($ids);
    }

    /** Periode KRS = periode aktif utama, mis. ["2026", "Ganjil"]. */
    private function period(): array
    {
        return explode('-', Semester::primaryKey(), 2);
    }

    /** SKS sebuah enrollment (0 bila kelas belum menaut mata kuliah). */
    private function sksOf(Enrollment $e): int
    {
        return (int) ($e->course->mataKuliah->sks ?? 0);
    }

    /** Dua slot jadwal bentrok: hari sama & rentang jam (HH:MM) beririsan. */
    private function overlap(array $a, array $b): bool
    {
        return $a['day'] === $b['day'] && $a['start'] < $b['end'] && $b['start'] < $a['end'];
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        [$year, $semester] = $this->period();

        $myKrs = Enrollment::where('user_id', $user->id)
            ->whereHas('course', fn ($q) => $q->where('year', $year)->where('semester', $semester))
            ->with(['course.mataKuliah', 'course.lecturer', 'course.schedules', 'approver'])
            ->get()
            ->sortBy(fn ($e) => $e->course->mataKuliah->code ?? $e->course->code)
            ->values();

        $takenCourseIds = $myKrs->pluck('course_id');

        $available = Course::where('status', Course::STATUS_ACTIVE)
            ->where('year', $year)->where('semester', $semester)
            ->whereNotIn('id', $takenCourseIds)
            ->with(['mataKuliah', 'lecturer', 'prodi', 'schedules'])
            ->withCount(['enrollments as seats_taken_count' => fn ($q) => $q
                ->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_SUBMITTED])])
            ->get()
            ->sortBy(fn ($c) => $c->mataKuliah->code ?? $c->code)
            ->values();

        $totalSks = $myKrs->sum(fn ($e) => $this->sksOf($e));

        // Slot jadwal kelas yang ada di KRS — untuk deteksi bentrok.
        $krsSlots = [];
        foreach ($myKrs as $e) {
            foreach ($e->course->schedules as $s) {
                $krsSlots[] = ['day' => $s->day, 'start' => $s->start_time, 'end' => $s->end_time, 'course_id' => $e->course_id];
            }
        }

        // Kelas di KRS yang jamnya bentrok dengan kelas lain di KRS.
        $clashCourseIds = [];
        foreach ($krsSlots as $a) {
            foreach ($krsSlots as $b) {
                if ($a['course_id'] !== $b['course_id'] && $this->overlap($a, $b)) {
                    $clashCourseIds[$a['course_id']] = true;
                }
            }
        }

        // Kelas tersedia yang jamnya bentrok dengan KRS saat ini (peringatan sebelum menambah).
        $availableWarn = [];
        foreach ($available as $c) {
            foreach ($c->schedules as $s) {
                $slot = ['day' => $s->day, 'start' => $s->start_time, 'end' => $s->end_time];
                foreach ($krsSlots as $a) {
                    if ($this->overlap($a, $slot)) {
                        $availableWarn[$c->id] = true;
                        break 2;
                    }
                }
            }
        }

        return view('krs.index', [
            'periodLabel' => Semester::keyLabel($year.'-'.$semester),
            'myKrs' => $myKrs,
            'available' => $available,
            'totalSks' => $totalSks,
            'maxSks' => $this->quotaFor($user),
            'krsOpen' => self::krsOpen(),
            'advisor' => $user->advisor,
            'hasSubmitted' => $myKrs->contains(fn ($e) => $e->status === Enrollment::STATUS_SUBMITTED),
            'clashCourseIds' => array_keys($clashCourseIds),
            'availableWarn' => $availableWarn,
        ]);
    }

    /** Tambah satu kelas ke KRS (status draft). */
    public function add(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();
        abort_unless(self::krsOpen(), 403, 'Periode KRS sedang tutup.');

        [$year, $semester] = $this->period();
        if ($course->status !== Course::STATUS_ACTIVE || (int) $course->year !== (int) $year || $course->semester !== $semester) {
            return back()->with('error', 'Kelas tidak tersedia untuk periode KRS ini.');
        }

        if (Enrollment::where('course_id', $course->id)->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'Kelas sudah ada di KRS Anda.');
        }

        if ($course->isFull()) {
            return back()->with('error', 'Kuota kelas '.$course->name.' sudah penuh.');
        }

        // Prasyarat mata kuliah harus sudah lulus.
        $course->loadMissing('mataKuliah.prasyarat');
        $prasyarat = $course->mataKuliah?->prasyarat ?? collect();
        if ($prasyarat->isNotEmpty()) {
            $passed = $this->passedMataKuliahIds($user);
            $belum = $prasyarat->reject(fn ($mk) => in_array($mk->id, $passed, true));
            if ($belum->isNotEmpty()) {
                return back()->with('error', 'Belum bisa mengambil '.$course->name.' — prasyarat belum lulus: '.$belum->pluck('code')->implode(', ').'.');
            }
        }

        $sks = (int) ($course->mataKuliah->sks ?? 0);
        $quota = $this->quotaFor($user);
        $current = $this->currentSks($user->id, $year, $semester);
        if ($current + $sks > $quota) {
            return back()->with('error', 'Melebihi jatah '.$quota.' SKS (berdasar IPS semester lalu). KRS saat ini '.$current.' SKS.');
        }

        Enrollment::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => Enrollment::STATUS_DRAFT,
        ]);

        return back()->with('status', $course->name.' ditambahkan ke KRS.');
    }

    /** Hapus satu kelas dari KRS (selama belum disetujui & KRS masih buka). */
    public function remove(Request $request, Enrollment $enrollment): RedirectResponse
    {
        abort_unless($enrollment->user_id === $request->user()->id, 403);
        abort_unless(self::krsOpen(), 403, 'Periode KRS sedang tutup.');

        if ($enrollment->status === Enrollment::STATUS_APPROVED) {
            return back()->with('error', 'Kelas yang sudah disetujui tidak dapat dihapus sendiri. Hubungi dosen wali.');
        }

        $enrollment->delete();

        return back()->with('status', 'Kelas dihapus dari KRS.');
    }

    /** Ajukan seluruh rencana (draft) ke dosen wali. */
    public function submit(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless(self::krsOpen(), 403, 'Periode KRS sedang tutup.');

        if (! $user->advisor_id) {
            return back()->with('error', 'Anda belum memiliki dosen wali. Hubungi admin/kaprodi.');
        }

        [$year, $semester] = $this->period();

        $drafts = Enrollment::where('user_id', $user->id)
            ->where('status', Enrollment::STATUS_DRAFT)
            ->whereHas('course', fn ($q) => $q->where('year', $year)->where('semester', $semester))
            ->get();

        if ($drafts->isEmpty()) {
            return back()->with('error', 'Tidak ada rencana baru untuk diajukan.');
        }

        $quota = $this->quotaFor($user);
        $total = $this->currentSks($user->id, $year, $semester);
        if ($total > $quota) {
            return back()->with('error', 'Total '.$total.' SKS melebihi jatah '.$quota.' SKS (berdasar IPS semester lalu).');
        }

        foreach ($drafts as $e) {
            $e->update(['status' => Enrollment::STATUS_SUBMITTED, 'submitted_at' => now()]);
        }

        // Beri tahu dosen wali ada pengajuan KRS.
        \App\Services\Notifier::toUser($user->advisor_id, 'krs', 'Pengajuan KRS',
            $user->name.' mengajukan KRS ('.$drafts->count().' kelas) untuk disetujui.',
            route('perwalian.krs', $user));

        return back()->with('status', $drafts->count().' kelas diajukan ke dosen wali untuk disetujui.');
    }

    /** Cetak KRS periode aktif (PDF) — ber-tanda tangan mahasiswa & dosen wali. */
    public function pdf(Request $request)
    {
        $user = $request->user();
        [$year, $semester] = $this->period();

        $items = Enrollment::where('user_id', $user->id)
            ->whereHas('course', fn ($q) => $q->where('year', $year)->where('semester', $semester))
            ->with(['course.mataKuliah', 'course.lecturer'])
            ->get()
            ->sortBy(fn ($e) => $e->course->mataKuliah->code ?? $e->course->code)
            ->values();

        $pdf = Pdf::loadView('krs.pdf', [
            'student' => $user->loadMissing(['prodi', 'advisor']),
            'items' => $items,
            'totalSks' => $items->sum(fn ($e) => $this->sksOf($e)),
            'periodLabel' => Semester::keyLabel($year.'-'.$semester),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('krs-'.Str::slug($user->name).'-'.$year.'-'.$semester.'.pdf');
    }

    /** Total SKS non-tolak (draft + diajukan + disetujui) pada periode. */
    private function currentSks(int $userId, string $year, string $semester): int
    {
        return (int) Enrollment::where('user_id', $userId)
            ->whereHas('course', fn ($q) => $q->where('year', $year)->where('semester', $semester))
            ->with('course.mataKuliah')
            ->get()
            ->sum(fn ($e) => (int) ($e->course->mataKuliah->sks ?? 0));
    }
}
