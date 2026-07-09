<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(): View
    {
        $rooms = Room::withCount('schedules')->orderBy('name')->get();

        return view('admin.master.rooms', compact('rooms'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        Room::create($data);
        Activity::log('create', "Menambah ruangan {$data['name']}");

        return back()->with('status', "Ruangan {$data['name']} ditambahkan.");
    }

    public function destroy(Room $room): RedirectResponse
    {
        $used = $room->schedules()->count();
        if ($used > 0) {
            return back()->with('error', "Tidak bisa menghapus {$room->name} — dipakai {$used} jadwal.");
        }

        $name = $room->name;
        $room->delete();
        Activity::log('delete', "Menghapus ruangan {$name}");

        return back()->with('status', "Ruangan {$name} dihapus.");
    }
}
