<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseEvaluation;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** EDOM — pengisian evaluasi dosen oleh mahasiswa. */
class EvaluationController extends Controller
{
    public static function edomOpen(): bool
    {
        return Setting::bool('edom_open', false);
    }

    /** Wajibkan EDOM (kunci akses nilai sampai terisi). */
    public static function edomRequired(): bool
    {
        return Setting::bool('edom_required', false);
    }

    /** Gerbang aktif = EDOM dibuka & diwajibkan. */
    public static function gateActive(): bool
    {
        return self::edomOpen() && self::edomRequired();
    }

    /** Kelas periode aktif yang diikuti tapi belum dievaluasi mahasiswa. */
    public static function pendingCourses(User $student)
    {
        [$year, $semester] = explode('-', Semester::primaryKey(), 2);

        $courses = $student->enrolledCourses()
            ->where('courses.status', Course::STATUS_ACTIVE)
            ->where('year', $year)->where('semester', $semester)
            ->get();

        $done = CourseEvaluation::where('user_id', $student->id)->pluck('course_id')->all();

        return $courses->reject(fn ($c) => in_array($c->id, $done, true))->values();
    }

    /** Mahasiswa wajib mengevaluasi kelas ini dulu (untuk gerbang nilai per-kelas). */
    public static function mustEvaluateCourse(User $student, Course $course): bool
    {
        if (! self::gateActive()) {
            return false;
        }

        [$year, $semester] = explode('-', Semester::primaryKey(), 2);
        if ($course->status !== Course::STATUS_ACTIVE || (int) $course->year !== (int) $year || $course->semester !== $semester) {
            return false;
        }
        if (! $course->students()->whereKey($student->id)->exists()) {
            return false;
        }

        return ! CourseEvaluation::where('course_id', $course->id)->where('user_id', $student->id)->exists();
    }

    /** Ada EDOM tertunda (untuk gerbang KHS/transkrip). */
    public static function hasPending(User $student): bool
    {
        return self::gateActive() && self::pendingCourses($student)->isNotEmpty();
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $courses = $user->enrolledCourses()
            ->where('courses.status', Course::STATUS_ACTIVE)
            ->with('lecturer')
            ->get();

        $doneCourseIds = CourseEvaluation::where('user_id', $user->id)
            ->pluck('course_id')->all();

        return view('evaluasi.index', [
            'courses' => $courses,
            'doneCourseIds' => $doneCourseIds,
            'edomOpen' => self::edomOpen(),
            'questions' => CourseEvaluation::QUESTIONS,
            'scaleLabels' => CourseEvaluation::SCALE_LABELS,
        ]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();
        abort_unless(self::edomOpen(), 403, 'Periode evaluasi sedang tutup.');
        abort_unless($course->students()->whereKey($user->id)->exists(), 403, 'Anda tidak terdaftar di kelas ini.');

        if (CourseEvaluation::where('course_id', $course->id)->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'Anda sudah mengevaluasi kelas ini.');
        }

        $count = count(CourseEvaluation::QUESTIONS);
        $data = $request->validate([
            'answers' => ['required', 'array', 'size:'.$count],
            'answers.*' => ['required', 'integer', 'between:1,'.CourseEvaluation::SCALE],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        CourseEvaluation::create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'answers' => array_map('intval', array_values($data['answers'])),
            'comment' => $data['comment'] ?? null,
        ]);

        return back()->with('status', 'Terima kasih, evaluasi untuk '.$course->name.' tersimpan.');
    }
}
