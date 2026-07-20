<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    use ImportsCsv;

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

    public function update(Request $request, Room $room): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:100'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $room->update($data);
        Activity::log('update', "Mengubah ruangan {$room->name}");

        return back()->with('status', "Ruangan {$room->name} diperbarui.");
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

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $deleted = 0;
        $skipped = 0;
        foreach (Room::whereIn('id', $data['ids'])->withCount('schedules')->get() as $room) {
            if ($room->schedules_count > 0) {
                $skipped++;

                continue;
            }
            $room->delete();
            $deleted++;
        }
        Activity::log('delete', "Hapus massal ruangan: {$deleted} dihapus, {$skipped} dilewati");

        return back()->with('status', "$deleted ruangan dihapus."
            .($skipped > 0 ? " $skipped dilewati karena masih dipakai jadwal." : ''));
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $created = 0;
        $skipped = 0;
        foreach ($this->readCsvRows($request->file('file'), ['kode', 'code', 'nama']) as $cols) {
            $code = $cols[0] ?? '';
            $name = $cols[1] ?? '';
            $capacity = ($cols[2] ?? '') !== '' ? (int) $cols[2] : null;
            $note = $cols[3] ?? null;
            if ($name === '') {
                $skipped++;

                continue;
            }
            Room::create([
                'code' => $code !== '' ? $code : null,
                'name' => $name,
                'capacity' => $capacity,
                'note' => $note !== '' ? $note : null,
            ]);
            $created++;
        }
        Activity::log('create', "Import ruangan: {$created} dibuat, {$skipped} dilewati");

        return back()->with('status', "Import selesai: $created ruangan dibuat, $skipped dilewati.");
    }
}
