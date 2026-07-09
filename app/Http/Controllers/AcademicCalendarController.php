<?php

namespace App\Http\Controllers;

use App\Models\AcademicEvent;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Kalender akademik kampus: lihat (semua), kelola (admin). */
class AcademicCalendarController extends Controller
{
    public function index(Request $request): View
    {
        $period = (string) $request->query('periode', Semester::primaryKey());
        if (! str_contains($period, '-')) {
            $period = Semester::primaryKey();
        }
        [$year, $semester] = explode('-', $period, 2);

        $events = AcademicEvent::where('year', $year)->where('semester', $semester)
            ->orderBy('start_date')->get();

        // Pilihan periode: gabungan periode ber-agenda + periode aktif.
        $eventKeys = AcademicEvent::select('year', 'semester')->distinct()->get()
            ->map(fn ($e) => $e->year.'-'.$e->semester);
        $keys = collect($eventKeys->all())          // base collection (hindari merge Eloquent saat kosong)
            ->merge(Semester::activeKeys())
            ->unique()
            ->sortByDesc(fn ($k) => Semester::sortValue($k))
            ->values();

        return view('academic-calendar.index', [
            'events' => $events,
            'period' => $period,
            'periodLabel' => Semester::keyLabel($period),
            'periods' => $keys,
            'canManage' => $request->user()->isAdmin(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:'.implode(',', array_keys(AcademicEvent::TYPES))],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'semester' => ['required', 'in:Ganjil,Genap,Antara'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        AcademicEvent::create($data);

        return back()->with('status', 'Agenda akademik ditambahkan.');
    }

    public function destroy(AcademicEvent $event): RedirectResponse
    {
        $event->delete();

        return back()->with('status', 'Agenda akademik dihapus.');
    }
}
