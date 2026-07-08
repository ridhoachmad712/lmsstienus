<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\ClassSchedule;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    use ChecksCourseAccess;

    /** Jadwal mingguan pribadi: kuliah (mahasiswa) / mengajar (dosen). */
    public function index(Request $request): View
    {
        $user = $request->user();
        $courses = ($user->isDosen() ? $user->teachingCourses() : $user->enrolledCourses())
            ->where('courses.status', Course::STATUS_ACTIVE)
            ->with('schedules')
            ->get();

        $byDay = [];
        foreach ($courses as $course) {
            foreach ($course->schedules as $s) {
                $byDay[$s->day][] = ['s' => $s, 'course' => $course];
            }
        }
        foreach ($byDay as &$slots) {
            usort($slots, fn ($a, $b) => strcmp($a['s']->start_time, $b['s']->start_time));
        }
        unset($slots);
        ksort($byDay);

        return view('jadwal.index', ['byDay' => $byDay, 'isDosen' => $user->isDosen()]);
    }

    /** Jadwal satu kelas (tab); dosen pemilik bisa kelola. */
    public function course(Request $request, Course $course): View
    {
        $this->ensureCourseAccess($request, $course);

        return view('schedule.course', ['course' => $course, 'schedules' => $course->schedules()->get()]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->ensureCourseOwner($request, $course);

        $data = $request->validate([
            'day' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room' => ['nullable', 'string', 'max:50'],
        ]);

        $course->schedules()->create($data);

        return back()->with('status', 'Jadwal ditambahkan.');
    }

    public function destroy(Request $request, ClassSchedule $schedule): RedirectResponse
    {
        $this->ensureCourseOwner($request, $schedule->course);
        $schedule->delete();

        return back()->with('status', 'Jadwal dihapus.');
    }
}
