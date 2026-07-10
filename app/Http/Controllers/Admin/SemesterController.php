<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Semester;
use App\Models\Setting;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SemesterController extends Controller
{
    /** Urutan semester dalam satu tahun (untuk pengurutan periode). */
    private const SEM_ORDER = ['Antara' => 1, 'Genap' => 2, 'Ganjil' => 3];

    /** Daftar semester terkelola, digabung dengan periode kelas yang ada. */
    public function index(): View
    {
        // Statistik kelas per periode (semua dosen), termasuk hitung mahasiswa unik.
        $courses = Course::query()->with('students:id')->get();
        $byPeriod = $courses->groupBy(fn ($c) => $c->year.'-'.$c->semester);

        // Semester yang dikelola admin (punya id → bisa dihapus dari daftar).
        $managed = Semester::all()->keyBy(fn ($s) => $s->year.'-'.$s->semester);

        // Gabungkan kunci dari tabel semester + periode kelas (agar tidak ada yang hilang).
        $keys = $managed->keys()->merge($byPeriod->keys())->unique();

        $periods = $keys->map(function ($key) use ($managed, $byPeriod) {
            [$year, $semester] = explode('-', $key, 2);
            $group = $byPeriod->get($key, collect());

            return (object) [
                'id' => $managed->get($key)?->id,
                'year' => (int) $year,
                'semester' => $semester,
                'key' => $key,
                'label' => $semester.' '.$year,
                'courses_count' => $group->count(),
                'lecturers_count' => $group->pluck('user_id')->unique()->count(),
                'students_count' => $group->pluck('students')->flatten()->pluck('id')->unique()->count(),
                'sort' => (int) $year * 10 + (self::SEM_ORDER[$semester] ?? 0),
            ];
        })->sortByDesc('sort')->values();

        $academicYear = Setting::get('academic_year', (string) date('Y'));
        $semester = Setting::get('semester', 'Ganjil');
        $activeKeys = Semester::activeKeys();
        $krsOpen = \App\Http\Controllers\KrsController::krsOpen();
        $krsMaxSks = \App\Http\Controllers\KrsController::maxSks();
        $krsPeriodLabel = Semester::keyLabel(Semester::primaryKey());
        $edomOpen = \App\Http\Controllers\EvaluationController::edomOpen();
        $edomRequired = \App\Http\Controllers\EvaluationController::edomRequired();

        return view('admin.semesters.index', compact('periods', 'activeKeys', 'academicYear', 'semester', 'krsOpen', 'krsMaxSks', 'krsPeriodLabel', 'edomOpen', 'edomRequired'));
    }

    /** Buka/tutup periode EDOM + opsi wajib (kunci nilai). */
    public function updateEdom(Request $request): RedirectResponse
    {
        $open = $request->boolean('edom_open');
        $required = $request->boolean('edom_required');
        Setting::put('edom_open', $open ? '1' : '0');
        Setting::put('edom_required', $required ? '1' : '0');
        Activity::log('update', 'Mengubah EDOM: '.($open ? 'BUKA' : 'TUTUP').($required ? ', WAJIB' : ''));

        return back()->with('status', 'Evaluasi Dosen (EDOM) '.($open ? 'DIBUKA' : 'DITUTUP').($required ? ' & diwajibkan' : '').'.');
    }

    /** Buka/tutup periode pengisian KRS + batas SKS. */
    public function updateKrs(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'krs_open' => ['nullable', 'boolean'],
            'krs_max_sks' => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        $open = (bool) ($data['krs_open'] ?? false);
        Setting::put('krs_open', $open ? '1' : '0');
        Setting::put('krs_max_sks', (string) $data['krs_max_sks']);

        Activity::log('update', 'Mengubah pengaturan KRS: '.($open ? 'BUKA' : 'TUTUP').", maks {$data['krs_max_sks']} SKS");

        return back()->with('status', 'Pengaturan KRS diperbarui — pengisian '.($open ? 'DIBUKA' : 'DITUTUP').'.');
    }

    /** Tambah semester baru ke daftar. */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'semester' => ['required', 'in:Ganjil,Genap,Antara'],
        ]);

        if (Semester::where('year', $data['year'])->where('semester', $data['semester'])->exists()) {
            return back()->with('error', "Semester {$data['semester']} {$data['year']} sudah ada di daftar.");
        }

        Semester::create($data);
        Activity::log('create', "Menambahkan semester {$data['semester']} {$data['year']}");

        return back()->with('status', "Semester {$data['semester']} {$data['year']} ditambahkan.");
    }

    /** Tetapkan satu atau lebih periode sebagai semester aktif (disimpan ke pengaturan). */
    public function updateActive(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'periods' => ['required', 'array', 'min:1'],
            'periods.*' => ['string', 'regex:/^\d{4}-(Ganjil|Genap|Antara)$/'],
        ], [
            'periods.required' => 'Pilih minimal satu semester untuk diaktifkan.',
            'periods.min' => 'Pilih minimal satu semester untuk diaktifkan.',
        ]);

        Semester::setActiveKeys($data['periods']);

        $labels = collect($data['periods'])
            ->sortByDesc(fn ($k) => Semester::sortValue($k))
            ->map(fn ($k) => Semester::keyLabel($k))
            ->implode(', ');

        Activity::log('update', "Menyetel semester aktif: {$labels}");

        return back()->with('status', 'Semester aktif diperbarui: '.$labels.'.');
    }

    /** Hapus semester dari daftar — ditolak jika masih ada kelas di periode itu. */
    public function destroy(Semester $semester): RedirectResponse
    {
        $count = Course::where('year', $semester->year)->where('semester', $semester->semester)->count();

        if ($count > 0) {
            return back()->with('error', "Tidak bisa menghapus {$semester->label()} — masih ada {$count} kelas di periode ini. Pindahkan atau hapus kelasnya terlebih dahulu.");
        }

        $label = $semester->label();
        $semester->delete();
        Activity::log('delete', "Menghapus semester {$label}");

        return back()->with('status', "Semester {$label} dihapus dari daftar.");
    }
}
