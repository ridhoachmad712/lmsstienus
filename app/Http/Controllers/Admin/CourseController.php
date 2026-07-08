<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Prodi;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pengawasan kelas: kaprodi (lingkup prodinya) & admin (seluruh kampus). */
class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $prodiId = $user->isKaprodi()
            ? $user->prodi_id
            : ($request->integer('prodi') ?: null);
        $status = in_array($request->query('status'), ['active', 'completed'], true) ? $request->query('status') : null;
        $q = trim((string) $request->query('q', ''));

        $courses = Course::query()
            ->when($user->isKaprodi(), fn ($x) => $x->where('prodi_id', $user->prodi_id))
            ->when($prodiId && $user->isAdmin(), fn ($x) => $x->where('prodi_id', $prodiId))
            ->when($status, fn ($x) => $x->where('status', $status))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w
                ->where('name', 'like', "%$q%")
                ->orWhere('code', 'like', "%$q%")
                ->orWhere('class_name', 'like', "%$q%")))
            ->with(['lecturer', 'prodi', 'mataKuliah'])
            ->withCount(['students', 'meetings', 'assignments'])
            ->withSum('gradeComponents as weight_total', 'weight')
            ->orderByDesc('year')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.courses.index', [
            'courses' => $courses,
            'prodis' => Prodi::orderBy('name')->get(),
            'prodiId' => $prodiId,
            'status' => $status,
            'q' => $q,
        ]);
    }
}
