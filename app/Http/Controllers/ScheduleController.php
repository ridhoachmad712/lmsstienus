<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCourseAccess;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Room;
use App\Models\TimeSlot;
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

        return view('schedule.course', [
            'course' => $course,
            'schedules' => $course->schedules()->with(['ruangan', 'timeSlot'])->get(),
            'rooms' => Room::orderBy('name')->get(),
            'timeSlots' => TimeSlot::orderBy('sort')->orderBy('start_time')->get(),
        ]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        $this->ensureCourseOwner($request, $course);

        $data = $request->validate([
            'day' => ['required', 'integer', 'between:1,7'],
            'time_slot_id' => ['required', 'exists:time_slots,id'],
            'room_id' => ['nullable', 'exists:rooms,id'],
        ]);

        $slot = TimeSlot::findOrFail($data['time_slot_id']);
        $room = $data['room_id'] ? Room::find($data['room_id']) : null;

        // Simpan juga string jam/ruang (dipakai tampilan & deteksi bentrok KRS).
        $course->schedules()->create([
            'day' => $data['day'],
            'time_slot_id' => $slot->id,
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
            'room_id' => $room?->id,
            'room' => $room?->name,
        ]);

        return back()->with('status', 'Jadwal ditambahkan.');
    }

    public function destroy(Request $request, ClassSchedule $schedule): RedirectResponse
    {
        $this->ensureCourseOwner($request, $schedule->course);
        $schedule->delete();

        return back()->with('status', 'Jadwal dihapus.');
    }
}
