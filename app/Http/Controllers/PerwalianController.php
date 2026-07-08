<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Transcript;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PerwalianController extends Controller
{
    /** Daftar mahasiswa bimbingan dosen wali. */
    public function index(Request $request): View
    {
        $advisees = $request->user()->advisees()
            ->with('prodi')
            ->orderBy('name')
            ->get();

        return view('perwalian.index', ['advisees' => $advisees]);
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
}
