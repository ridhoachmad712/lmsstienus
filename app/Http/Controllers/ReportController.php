<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\Attendance;
use App\Models\Course;
use App\Services\AttendanceService;
use App\Services\GradeCalculator;
use App\Support\Grades;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReportController extends Controller
{
    use ChecksCourseAccess;

    /** Halaman ringkasan laporan perkuliahan (untuk pemantauan). */
    public function index(Request $request, Course $course): View
    {
        $this->ensureCourseOwner($request, $course);

        return view('reports.index', $this->buildData($course));
    }

    /** Unduh laporan perkuliahan sebagai PDF (dokumen formal). */
    public function pdf(Request $request, Course $course)
    {
        $this->ensureCourseOwner($request, $course);

        $data = $this->buildData($course);

        $pdf = Pdf::loadView('reports.pdf', $data)->setPaper('a4', 'landscape');

        // Nomor halaman + tanggal cetak via canvas (elemen position:fixed memicu
        // salah-paginasi di DomPDF, jadi ditulis langsung ke tiap halaman).
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text($canvas->get_width() - 130, 575, 'Halaman {PAGE_NUM} / {PAGE_COUNT}', $font, 8, [0.6, 0.6, 0.6]);
        $canvas->page_text(30, 575, 'Dicetak '.$data['generatedAt']->translatedFormat('d M Y, H:i').' WITA', $font, 8, [0.6, 0.6, 0.6]);

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="laporan-perkuliahan-'.Str::slug($course->name).'.pdf"',
        ]);
    }

    /** Rangkum seluruh data laporan sebuah kelas (dipakai halaman & PDF). */
    private function buildData(Course $course): array
    {
        $course->loadMissing('lecturer', 'syllabus');

        $grid = (new AttendanceService())->gridForCourse($course);
        $grades = (new GradeCalculator())->forCourse($course);

        $meetings = $course->meetings()->with('materials')->get();

        // Hadir & total tercatat per pertemuan (untuk BAP).
        $records = Attendance::whereIn('meeting_id', $meetings->pluck('id'))->get(['meeting_id', 'status']);
        $attByMeeting = $records->groupBy('meeting_id')->map(fn ($g) => [
            'total' => $g->count(),
            'hadir' => $g->where('status', 'hadir')->count(),
        ]);

        // Rekap tugas & kuis: jumlah pengumpulan, sudah dinilai, rata-rata nilai.
        $assignments = $course->assignments()
            ->withCount('submissions')
            ->withCount(['submissions as graded_count' => fn ($q) => $q->whereNotNull('score')])
            ->withAvg(['submissions as avg_score' => fn ($q) => $q->whereNotNull('score')], 'score')
            ->get()
            ->sortBy([['type', 'asc'], ['title', 'asc']])
            ->values();

        // Distribusi nilai huruf (urut sesuai skala aktif).
        $dist = [];
        foreach (Grades::scale() as $s) {
            $dist[$s['letter']] = 0;
        }
        foreach ($grades['rows'] as $r) {
            $dist[$r['letter']] = ($dist[$r['letter']] ?? 0) + 1;
        }

        // Agregat kehadiran.
        $percents = collect($grid['summary'])->pluck('percent')->filter(fn ($v) => ! is_null($v));
        $attAvg = $percents->count() ? round($percents->avg(), 1) : null;
        $attBelow75 = $percents->filter(fn ($v) => $v < 75)->count();

        // Kelulusan (nilai akhir >= 60) & kelengkapan penilaian per mahasiswa.
        $totalStudents = $grades['rows']->count();
        $lulus = $grades['rows']->filter(fn ($r) => $r['final'] >= 60)->count();
        $gradesComplete = $grades['rows']->filter(function ($r) {
            foreach ($r['components'] as $v) {
                if (is_null($v)) {
                    return false;
                }
            }

            return true;
        })->count();

        // Mahasiswa berisiko: nilai akhir < 60 atau kehadiran < 75%.
        $risk = $grades['rows']->map(function ($r) use ($grid) {
            $pct = $grid['summary'][$r['student']->id]['percent'] ?? null;

            return [
                'student' => $r['student'],
                'final' => $r['final'],
                'letter' => $r['letter'],
                'percent' => $pct,
                'low_grade' => $r['final'] < 60,
                'low_att' => ! is_null($pct) && $pct < 75,
            ];
        })->filter(fn ($x) => $x['low_grade'] || $x['low_att'])->values();

        $completeness = [
            'meetings_total' => $meetings->count(),
            'meetings_held' => $meetings->filter(fn ($m) => $m->date && $m->date->lessThanOrEqualTo(now()))->count(),
            'attendance_sessions' => $grid['sessions'],
            'weight_total' => $grades['summary']['weight_total'],
            'weight_ok' => $grades['summary']['weight_total'] === 100,
            'has_components' => $grades['components']->isNotEmpty(),
            'has_syllabus' => (bool) $course->syllabus,
            'grades_complete' => $gradesComplete,
            'grades_complete_all' => $totalStudents > 0 && $gradesComplete === $totalStudents,
        ];

        return [
            'course' => $course,
            'meetings' => $meetings,
            'attByMeeting' => $attByMeeting,
            'grid' => $grid,
            'components' => $grades['components'],
            'rows' => $grades['rows'],
            'summary' => $grades['summary'],
            'autoComponentIds' => $grades['autoComponentIds'],
            'assignments' => $assignments,
            'totalStudents' => $totalStudents,
            'dist' => $dist,
            'attAvg' => $attAvg,
            'attBelow75' => $attBelow75,
            'lulus' => $lulus,
            'risk' => $risk,
            'completeness' => $completeness,
            'generatedAt' => now(),
        ];
    }
}
