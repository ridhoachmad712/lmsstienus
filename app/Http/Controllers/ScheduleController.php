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

        // Deteksi bentrok tingkat institusi (kelas aktif pada periode yang sama, hari & jam beririsan).
        if ($conflict = $this->findConflict($course, $data['day'], $slot, $room)) {
            return back()->with('error', $conflict);
        }

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

    /**
     * Cari bentrok ruang/dosen: jadwal kelas AKTIF lain di periode sama, hari sama,
     * jam beririsan. Mengembalikan pesan bentrok, atau null bila aman.
     */
    private function findConflict(Course $course, int $day, TimeSlot $slot, ?Room $room): ?string
    {
        $candidates = ClassSchedule::where('day', $day)
            ->where('start_time', '<', $slot->end_time)
            ->where('end_time', '>', $slot->start_time)
            ->where('course_id', '!=', $course->id)
            ->whereHas('course', fn ($q) => $q
                ->where('status', Course::STATUS_ACTIVE)
                ->where('year', $course->year)
                ->where('semester', $course->semester))
            ->with('course')
            ->get();

        $jam = $slot->start_time.'–'.$slot->end_time;

        if ($room) {
            $bentrokRuang = $candidates->firstWhere('room_id', $room->id);
            if ($bentrokRuang) {
                return "Ruang {$room->name} sudah dipakai kelas \"{$bentrokRuang->course->name}\" pada {$slot->name} ({$jam}).";
            }
        }

        $bentrokDosen = $candidates->first(fn ($c) => $c->course->user_id === $course->user_id);
        if ($bentrokDosen) {
            return "Dosen pengampu sudah mengajar kelas \"{$bentrokDosen->course->name}\" pada {$slot->name} ({$jam}).";
        }

        return null;
    }

    public function destroy(Request $request, ClassSchedule $schedule): RedirectResponse
    {
        $this->ensureCourseOwner($request, $schedule->course);
        $schedule->delete();

        return back()->with('status', 'Jadwal dihapus.');
    }
}
