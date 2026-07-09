<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Prodi;
use App\Models\User;
use App\Services\AcademicSummary;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Rekap akademik mahasiswa: IPK/IPS/SKS + deteksi bermasalah (kaprodi ter-scope prodi). */
class AcademicController extends Controller
{
    /** Ambang IPK "bermasalah". */
    private const IPK_MIN = 2.0;

    public function index(Request $request): View
    {
        $user = $request->user();
        $prodiId = $user->isKaprodi()
            ? $user->prodi_id
            : ($request->integer('prodi') ?: null);
        $onlyBermasalah = $request->query('filter') === 'bermasalah';

        $students = User::where('role', User::ROLE_MAHASISWA)
            ->when($user->isKaprodi(), fn ($q) => $q->where('prodi_id', $user->prodi_id))
            ->when($prodiId && $user->isAdmin(), fn ($q) => $q->where('prodi_id', $prodiId))
            ->with('prodi')
            ->orderBy('name')
            ->get();

        $rows = $students->map(function ($s) {
            // Cache akademik: isi sekali bila belum ada (lazy), lalu dipakai apa adanya.
            if ($s->ipk_cache === null) {
                $s->refreshAcademicCache();
            }

            $ipk = (float) ($s->ipk_cache ?? 0);
            $sks = (int) ($s->sks_cache ?? 0);

            return [
                'student' => $s,
                'ipk' => $ipk,
                'sks' => $sks,
                'ips' => $s->ips_cache,
                'semester_ke' => AcademicSummary::semesterKeFor($s->entry_year),
                'status_color' => AcademicSummary::colorForStatus($s->student_status ?? 'aktif'),
                'bermasalah' => $sks > 0 && $ipk < self::IPK_MIN,
            ];
        });

        // Statistik ringkas (atas seluruh mahasiswa dalam lingkup).
        $withGrades = $rows->filter(fn ($r) => $r['sks'] > 0);
        $stats = [
            'total' => $rows->count(),
            'avg_ipk' => $withGrades->count() ? round($withGrades->avg(fn ($r) => $r['ipk']), 2) : null,
            'bermasalah' => $rows->where('bermasalah', true)->count(),
        ];

        // Urut: bermasalah dulu, lalu IPK terendah.
        $rows = $rows->sortBy([
            fn ($a, $b) => ($b['bermasalah'] <=> $a['bermasalah']),
            fn ($a, $b) => ($a['ipk'] <=> $b['ipk']),
        ])->values();

        if ($onlyBermasalah) {
            $rows = $rows->where('bermasalah', true)->values();
        }

        $rows = $this->paginate($rows, 25);

        return view('admin.akademik.index', [
            'rows' => $rows,
            'stats' => $stats,
            'prodis' => Prodi::orderBy('name')->get(),
            'prodiId' => $prodiId,
            'onlyBermasalah' => $onlyBermasalah,
            'ipkMin' => self::IPK_MIN,
        ]);
    }

    /** Paginasi manual atas koleksi hasil komputasi. */
    private function paginate($items, int $perPage): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator($slice, $items->count(), $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }
}
