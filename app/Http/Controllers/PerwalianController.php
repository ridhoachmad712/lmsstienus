<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Semester;
use App\Models\User;
use App\Services\Notifier;
use App\Services\Transcript;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PerwalianController extends Controller
{
    /** Daftar mahasiswa bimbingan dosen wali. */
    public function index(Request $request): View
    {
        [$year, $semester] = explode('-', Semester::primaryKey(), 2);

        $advisees = $request->user()->advisees()
            ->with('prodi')
            ->withCount(['enrollments as krs_pending_count' => fn ($q) => $q
                ->where('status', Enrollment::STATUS_SUBMITTED)
                ->whereHas('course', fn ($c) => $c->where('year', $year)->where('semester', $semester))])
            ->orderBy('name')
            ->get();

        $summary = new \App\Services\AcademicSummary();
        $summaries = $advisees->mapWithKeys(fn ($m) => [$m->id => $summary->forStudent($m)]);

        return view('perwalian.index', [
            'advisees' => $advisees,
            'summaries' => $summaries,
            'periodLabel' => Semester::keyLabel($year.'-'.$semester),
        ]);
    }

    private function ensureAdvisee(Request $request, User $student): void
    {
        abort_unless($student->advisor_id === $request->user()->id, 403);
    }

    public function transkrip(Request $request, User $student): View
    {
        $this->ensureAdvisee($request, $student);

        return view('transcripts.show', array_merge(
            (new Transcript())->forStudent($student),
            ['student' => $student, 'self' => false, 'pdfUrl' => route('perwalian.transkrip.pdf', $student),
                'khsUrl' => fn ($key) => route('perwalian.khs.pdf', [$student, 'period' => $key])],
        ));
    }

    /** KHS satu semester milik mahasiswa bimbingan (PDF). */
    public function khsPdf(Request $request, User $student)
    {
        $this->ensureAdvisee($request, $student);

        $data = (new Transcript())->forStudent($student);
        $key = (string) $request->query('period');
        $period = $data['periods'][$key] ?? abort(404, 'Periode tidak ditemukan.');

        $pdf = Pdf::loadView('transcripts.khs', [
            'student' => $student->loadMissing(['prodi', 'advisor']),
            'period' => $period,
            'ipk' => $data['ipk'],
            'total_sks' => $data['total_sks'],
        ])->setPaper('a4', 'portrait');

        return $pdf->download('khs-'.Str::slug($student->name).'-'.Str::slug($key).'.pdf');
    }

    public function transkripPdf(Request $request, User $student)
    {
        $this->ensureAdvisee($request, $student);

        $data = (new Transcript())->forStudent($student);
        $pdf = Pdf::loadView('transcripts.pdf', array_merge($data, [
            'student' => $student->loadMissing('prodi'),
        ]))->setPaper('a4', 'portrait');

        return $pdf->download('transkrip-'.Str::slug($student->name).'.pdf');
    }

    /** Tinjau KRS mahasiswa bimbingan (periode aktif). */
    public function krs(Request $request, User $student): View
    {
        $this->ensureAdvisee($request, $student);
        [$year, $semester] = explode('-', Semester::primaryKey(), 2);

        $items = $student->enrollments()
            ->whereHas('course', fn ($q) => $q->where('year', $year)->where('semester', $semester))
            ->with(['course.mataKuliah', 'course.lecturer'])
            ->get()
            ->sortBy(fn ($e) => $e->course->mataKuliah->code ?? $e->course->code)
            ->values();

        return view('perwalian.krs', [
            'student' => $student->loadMissing('prodi'),
            'items' => $items,
            'periodLabel' => Semester::keyLabel($year.'-'.$semester),
            'totalSks' => $items->sum(fn ($e) => (int) ($e->course->mataKuliah->sks ?? 0)),
            'pendingCount' => $items->where('status', Enrollment::STATUS_SUBMITTED)->count(),
        ]);
    }

    /** Setujui seluruh kelas yang diajukan → enrollment aktif. */
    public function approveKrs(Request $request, User $student): RedirectResponse
    {
        $this->ensureAdvisee($request, $student);
        [$year, $semester] = explode('-', Semester::primaryKey(), 2);

        $submitted = $student->enrollments()
            ->where('status', Enrollment::STATUS_SUBMITTED)
            ->whereHas('course', fn ($q) => $q->where('year', $year)->where('semester', $semester))
            ->with('course')
            ->get();

        if ($submitted->isEmpty()) {
            return back()->with('error', 'Tidak ada pengajuan KRS untuk disetujui.');
        }

        $approved = 0;
        $penuh = [];
        foreach ($submitted as $e) {
            $course = $e->course;
            // Cek ulang kuota saat menyetujui (cegah kelebihan kapasitas).
            $terisi = $course->enrollments()->where('status', Enrollment::STATUS_APPROVED)->count();
            if ($course->quota !== null && $terisi >= $course->quota) {
                $penuh[] = $course->name;

                continue;
            }

            $e->update([
                'status' => Enrollment::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => $request->user()->id,
                'enrolled_at' => $e->enrolled_at ?? now(),
            ]);
            $approved++;
        }

        if ($approved > 0) {
            Notifier::toUser($student, 'krs', 'KRS disetujui',
                $approved.' kelas KRS '.Semester::keyLabel($year.'-'.$semester).' Anda disetujui.',
                route('krs.index'));
        }

        $msg = $approved > 0
            ? "KRS {$student->name} disetujui ({$approved} kelas). Mahasiswa kini dapat mengakses kelasnya."
            : 'Tidak ada kelas yang disetujui.';
        if ($penuh) {
            return back()->with($approved > 0 ? 'status' : 'error', $msg.' Kelas penuh (dilewati): '.implode(', ', $penuh).'.');
        }

        return back()->with('status', $msg);
    }

    /** Tolak pengajuan → kembalikan ke Rencana agar mahasiswa dapat merevisi. */
    public function rejectKrs(Request $request, User $student): RedirectResponse
    {
        $this->ensureAdvisee($request, $student);
        [$year, $semester] = explode('-', Semester::primaryKey(), 2);

        $affected = $student->enrollments()
            ->where('status', Enrollment::STATUS_SUBMITTED)
            ->whereHas('course', fn ($q) => $q->where('year', $year)->where('semester', $semester))
            ->update([
                'status' => Enrollment::STATUS_DRAFT,
                'submitted_at' => null,
            ]);

        if ($affected === 0) {
            return back()->with('error', 'Tidak ada pengajuan KRS untuk ditolak.');
        }

        Notifier::toUser($student, 'krs', 'KRS dikembalikan',
            'Pengajuan KRS '.Semester::keyLabel($year.'-'.$semester).' Anda dikembalikan dosen wali untuk direvisi.',
            route('krs.index'));

        return back()->with('status', 'Pengajuan KRS dikembalikan ke mahasiswa untuk direvisi.');
    }
}
