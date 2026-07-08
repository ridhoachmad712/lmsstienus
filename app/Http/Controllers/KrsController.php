<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Semester;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KrsController extends Controller
{
    /** Batas SKS default bila belum diatur admin. */
    public const DEFAULT_MAX_SKS = 24;

    public static function krsOpen(): bool
    {
        return Setting::bool('krs_open', false);
    }

    public static function maxSks(): int
    {
        return (int) Setting::get('krs_max_sks', (string) self::DEFAULT_MAX_SKS);
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
            'maxSks' => self::maxSks(),
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

        $sks = (int) ($course->mataKuliah->sks ?? 0);
        $current = $this->currentSks($user->id, $year, $semester);
        if ($current + $sks > self::maxSks()) {
            return back()->with('error', 'Melebihi batas '.self::maxSks().' SKS. KRS saat ini '.$current.' SKS.');
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

        $total = $this->currentSks($user->id, $year, $semester);
        if ($total > self::maxSks()) {
            return back()->with('error', 'Total '.$total.' SKS melebihi batas '.self::maxSks().' SKS.');
        }

        foreach ($drafts as $e) {
            $e->update(['status' => Enrollment::STATUS_SUBMITTED, 'submitted_at' => now()]);
        }

        return back()->with('status', $drafts->count().' kelas diajukan ke dosen wali untuk disetujui.');
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
