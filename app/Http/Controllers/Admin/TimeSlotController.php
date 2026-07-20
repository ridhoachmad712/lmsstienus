<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ImportsCsv;
use App\Http\Controllers\Controller;
use App\Models\TimeSlot;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimeSlotController extends Controller
{
    use ImportsCsv;

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

    public function update(Request $request, TimeSlot $timeslot): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);
        $data['sort'] ??= 0;

        $timeslot->update($data);
        Activity::log('update', "Mengubah sesi {$timeslot->name}");

        return back()->with('status', "Sesi {$timeslot->name} diperbarui.");
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

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $deleted = 0;
        $skipped = 0;
        foreach (TimeSlot::whereIn('id', $data['ids'])->withCount('schedules')->get() as $slot) {
            if ($slot->schedules_count > 0) {
                $skipped++;

                continue;
            }
            $slot->delete();
            $deleted++;
        }
        Activity::log('delete', "Hapus massal sesi: {$deleted} dihapus, {$skipped} dilewati");

        return back()->with('status', "$deleted sesi dihapus."
            .($skipped > 0 ? " $skipped dilewati karena masih dipakai jadwal." : ''));
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $created = 0;
        $skipped = 0;
        foreach ($this->readCsvRows($request->file('file'), ['nama', 'name', 'mulai']) as $cols) {
            $name = $cols[0] ?? '';
            $start = $this->normalizeTime($cols[1] ?? '');
            $end = $this->normalizeTime($cols[2] ?? '');
            $sort = ($cols[3] ?? '') !== '' ? (int) $cols[3] : 0;

            if ($name === '' || ! $start || ! $end || $start >= $end) {
                $skipped++;

                continue;
            }
            TimeSlot::create(['name' => $name, 'start_time' => $start, 'end_time' => $end, 'sort' => $sort]);
            $created++;
        }
        Activity::log('create', "Import sesi kuliah: {$created} dibuat, {$skipped} dilewati");

        return back()->with('status', "Import selesai: $created sesi dibuat, $skipped dilewati.");
    }

    /** Normalisasi "8:0" / "08.00" → "08:00"; kembalikan null bila tidak valid. */
    private function normalizeTime(string $raw): ?string
    {
        $raw = str_replace('.', ':', trim($raw));
        if (! preg_match('/^(\d{1,2}):(\d{1,2})$/', $raw, $m)) {
            return null;
        }
        $h = (int) $m[1];
        $i = (int) $m[2];
        if ($h > 23 || $i > 59) {
            return null;
        }

        return sprintf('%02d:%02d', $h, $i);
    }
}
