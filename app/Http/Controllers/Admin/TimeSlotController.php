<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TimeSlot;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimeSlotController extends Controller
{
    public function index(): View
    {
        $slots = TimeSlot::withCount('schedules')->orderBy('sort')->orderBy('start_time')->get();

        return view('admin.master.timeslots', compact('slots'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);
        $data['sort'] ??= 0;

        TimeSlot::create($data);
        Activity::log('create', "Menambah sesi {$data['name']} ({$data['start_time']}–{$data['end_time']})");

        return back()->with('status', "Sesi {$data['name']} ditambahkan.");
    }

    public function destroy(TimeSlot $timeslot): RedirectResponse
    {
        $used = $timeslot->schedules()->count();
        if ($used > 0) {
            return back()->with('error', "Tidak bisa menghapus {$timeslot->name} — dipakai {$used} jadwal.");
        }

        $name = $timeslot->name;
        $timeslot->delete();
        Activity::log('delete', "Menghapus sesi {$name}");

        return back()->with('status', "Sesi {$name} dihapus.");
    }
}
