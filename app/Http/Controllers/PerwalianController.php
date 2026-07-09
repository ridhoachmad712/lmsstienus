<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Semester;
use App\Models\User;
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
            ['student' => $student, 'self' => false, 'pdfUrl' => route('perwalian.transkrip.pdf', $student)],
        ));
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
            ->get();

        if ($submitted->isEmpty()) {
            return back()->with('error', 'Tidak ada pengajuan KRS untuk disetujui.');
        }

        foreach ($submitted as $e) {
            $e->update([
                'status' => Enrollment::STATUS_APPROVED,
                'approved_at' => now(),
                'approved_by' => $request->user()->id,
                'enrolled_at' => $e->enrolled_at ?? now(),
            ]);
        }

        return back()->with('status', 'KRS '.$student->name.' disetujui ('.$submitted->count().' kelas). Mahasiswa kini dapat mengakses kelasnya.');
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

        return back()->with('status', 'Pengajuan KRS dikembalikan ke mahasiswa untuk direvisi.');
    }
}
