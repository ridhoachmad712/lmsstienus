<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseEvaluation;
use App\Models\Setting;
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
