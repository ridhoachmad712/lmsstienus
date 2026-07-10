<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEvaluation;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Rekap hasil EDOM per kelas/dosen (kaprodi ter-scope prodi). */
class EdomController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $prodiId = $user->isKaprodi()
            ? $user->prodi_id
            : ($request->integer('prodi') ?: null);

        $courses = Course::query()
            ->when($user->isKaprodi(), fn ($q) => $q->where('prodi_id', $user->prodi_id))
            ->when($prodiId && $user->isAdmin(), fn ($q) => $q->where('prodi_id', $prodiId))
            ->has('evaluations')
            ->with(['lecturer', 'prodi', 'evaluations'])
            ->orderByDesc('year')->orderBy('name')
            ->get();

        $qCount = count(CourseEvaluation::QUESTIONS);

        $rows = $courses->map(function ($c) use ($qCount) {
            $evals = $c->evaluations;

            $perQ = [];
            for ($i = 0; $i < $qCount; $i++) {
                $vals = $evals->map(fn ($e) => $e->answers[$i] ?? null)->filter(fn ($v) => ! is_null($v));
                $perQ[$i] = $vals->count() ? round($vals->avg(), 2) : null;
            }

            return [
                'course' => $c,
                'n' => $evals->count(),
                'perQ' => $perQ,
                'overall' => $evals->count() ? round($evals->avg(fn ($e) => $e->average()), 2) : null,
                'comments' => $evals->pluck('comment')->filter()->values(),
            ];
        });

        $overallAvg = $rows->whereNotNull('overall')->avg('overall');

        return view('admin.edom.index', [
            'rows' => $rows,
            'questions' => CourseEvaluation::QUESTIONS,
            'overallAvg' => $overallAvg ? round($overallAvg, 2) : null,
            'totalResponden' => $rows->sum('n'),
            'prodis' => Prodi::orderBy('name')->get(),
            'prodiId' => $prodiId,
        ]);
    }
}
