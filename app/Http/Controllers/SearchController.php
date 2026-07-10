<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\MataKuliah;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $user = $request->user();

        $courses = collect();
        $assignments = collect();
        $students = collect();
        $lecturers = collect();
        $mataKuliah = collect();

        if ($q !== '') {
            $like = '%'.$q.'%';

            if ($user->isStaff()) {
                // Admin/kaprodi: cari lintas entitas (kaprodi ter-scope prodi).
                $prodiId = $user->isKaprodi() ? $user->prodi_id : null;

                $students = User::where('role', User::ROLE_MAHASISWA)
                    ->when($prodiId, fn ($x) => $x->where('prodi_id', $prodiId))
                    ->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('nim_nip', 'like', $like)->orWhere('email', 'like', $like))
                    ->with('prodi')->limit(15)->get();

                $lecturers = User::whereIn('role', [User::ROLE_DOSEN, User::ROLE_KAPRODI])
                    ->when($prodiId, fn ($x) => $x->where('prodi_id', $prodiId))
                    ->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('nim_nip', 'like', $like)->orWhere('email', 'like', $like))
                    ->with('prodi')->limit(15)->get();

                $mataKuliah = MataKuliah::when($prodiId, fn ($x) => $x->where('prodi_id', $prodiId))
                    ->where(fn ($x) => $x->where('code', 'like', $like)->orWhere('name', 'like', $like))
                    ->limit(15)->get();

                $courses = Course::when($prodiId, fn ($x) => $x->where('prodi_id', $prodiId))
                    ->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('code', 'like', $like))
                    ->with('lecturer')->limit(15)->get();

                return view('search.index', compact('q', 'courses', 'assignments', 'students', 'lecturers', 'mataKuliah'));
            }

            // Dosen/mahasiswa: cari dalam lingkup kelasnya.
            $courseQuery = $user->isDosen() ? $user->teachingCourses() : $user->enrolledCourses();

            $courses = (clone $courseQuery)
                ->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('code', 'like', $like))
                ->limit(10)->get();

            $courseIds = (clone $courseQuery)->pluck('courses.id');

            $assignments = Assignment::whereIn('course_id', $courseIds)
                ->where('title', 'like', $like)
                ->with('course')
                ->limit(10)->get();

            if ($user->isDosen()) {
                $students = User::where('role', User::ROLE_MAHASISWA)
                    ->whereIn('id', function ($sub) use ($courseIds) {
                        $sub->select('user_id')->from('enrollments')->whereIn('course_id', $courseIds);
                    })
                    ->where(fn ($x) => $x->where('name', 'like', $like)
                        ->orWhere('nim_nip', 'like', $like)
                        ->orWhere('email', 'like', $like))
                    ->limit(10)->get();
            }
        }

        return view('search.index', compact('q', 'courses', 'assignments', 'students', 'lecturers', 'mataKuliah'));
    }
}
