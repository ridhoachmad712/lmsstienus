<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Transcript;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TranscriptController extends Controller
{
    /** Transkrip milik mahasiswa yang login. */
    public function mine(Request $request): View
    {
        $student = $request->user();

        return view('transcripts.show', array_merge(
            (new Transcript())->forStudent($student),
            ['student' => $student, 'self' => true],
        ));
    }

    public function minePdf(Request $request)
    {
        return $this->pdf($request->user());
    }

    /** Transkrip mahasiswa (admin/kaprodi; kaprodi ter-scope prodi). */
    public function show(Request $request, User $student): View
    {
        $this->authorizeView($request, $student);

        return view('transcripts.show', array_merge(
            (new Transcript())->forStudent($student),
            ['student' => $student, 'self' => false],
        ));
    }

    public function showPdf(Request $request, User $student)
    {
        $this->authorizeView($request, $student);

        return $this->pdf($student);
    }

    private function authorizeView(Request $request, User $student): void
    {
        abort_unless($student->isMahasiswa(), 404);

        $user = $request->user();
        if ($user->isKaprodi()) {
            abort_unless($student->prodi_id === $user->prodi_id, 403);
        }
    }

    private function pdf(User $student)
    {
        $data = (new Transcript())->forStudent($student);
        $pdf = Pdf::loadView('transcripts.pdf', array_merge($data, [
            'student' => $student->loadMissing('prodi'),
        ]))->setPaper('a4', 'portrait');

        return $pdf->download('transkrip-'.Str::slug($student->name).'.pdf');
    }
}
