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
        // Statistik kelas per periode (semua dosen), termasuk mahasiswa unik & SKS ditawarkan.
        $courses = Course::query()->with(['students:id', 'mataKuliah:id,sks'])->get();
        $byPeriod = $courses->groupBy(fn ($c) => $c->year.'-'.$c->semester);

        // Semester yang dikelola admin (punya id → bisa dihapus dari daftar).
        $managed = Semester::all()->keyBy(fn ($s) => $s->year.'-'.$s->semester);

        // Gabungkan kunci dari tabel semester + periode kelas (agar tidak ada yang hilang).
        $keys = $managed->keys()->merge($byPeriod->keys())->unique();

        $activeKeys = Semester::activeKeys();
        $maxActiveSort = collect($activeKeys)->map(fn ($k) => Semester::sortValue($k))->max();

        $periods = $keys->map(function ($key) use ($managed, $byPeriod, $activeKeys, $maxActiveSort) {
            [$year, $semester] = explode('-', $key, 2);
            $group = $byPeriod->get($key, collect());
            $sort = (int) $year * 10 + (self::SEM_ORDER[$semester] ?? 0);
            $isActive = in_array($key, $activeKeys, true);

            // Status periode: aktif / arsip (lebih lama dari aktif) / akan datang / nonaktif.
            if ($isActive) {
                $status = ['Aktif', 'green', 'ti-circle-check-filled'];
            } elseif ($maxActiveSort === null) {
                $status = ['Nonaktif', 'secondary', 'ti-circle'];
            } elseif ($sort < $maxActiveSort) {
                $status = ['Arsip', 'secondary', 'ti-archive'];
            } else {
                $status = ['Akan datang', 'blue', 'ti-clock-hour-4'];
            }

            return (object) [
                'id' => $managed->get($key)?->id,
                'year' => (int) $year,
                'semester' => $semester,
                'key' => $key,
                'label' => $semester.' '.$year,
                'academic_year' => Semester::academicYear((int) $year, $semester),
                'courses_count' => $group->count(),
                'lecturers_count' => $group->pluck('user_id')->unique()->count(),
                'students_count' => $group->pluck('students')->flatten()->pluck('id')->unique()->count(),
                'sks_total' => (int) $group->pluck('mataKuliah')->filter()->unique('id')->sum('sks'),
                'sort' => $sort,
                'is_active' => $isActive,
                'status_label' => $status[0],
                'status_color' => $status[1],
                'status_icon' => $status[2],
            ];
        })->sortByDesc('sort')->values();

        $academicYear = Setting::get('academic_year', (string) date('Y'));
        $semester = Setting::get('semester', 'Ganjil');
        $krsOpen = \App\Http\Controllers\KrsController::krsOpen();      // status terkomputasi (jadwal/manual)
        $krsManual = Setting::bool('krs_open');                        // nilai sakelar manual mentah
        $krsMaxSks = \App\Http\Controllers\KrsController::maxSks();
        $krsPeriodLabel = Semester::keyLabel(Semester::primaryKey());
        $krsStart = Setting::get('krs_start');
        $krsEnd = Setting::get('krs_end');
        $edomOpen = \App\Http\Controllers\EvaluationController::edomOpen();
        $edomManual = Setting::bool('edom_open');
        $edomRequired = \App\Http\Controllers\EvaluationController::edomRequired();
        $edomStart = Setting::get('edom_start');
        $edomEnd = Setting::get('edom_end');

        return view('admin.semesters.index', compact(
            'periods', 'activeKeys', 'academicYear', 'semester',
            'krsOpen', 'krsManual', 'krsMaxSks', 'krsPeriodLabel', 'krsStart', 'krsEnd',
            'edomOpen', 'edomManual', 'edomRequired', 'edomStart', 'edomEnd',
        ));
    }

    /** Buka/tutup periode EDOM + opsi wajib (kunci nilai) + jadwal tanggal opsional. */
    public function updateEdom(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'edom_open' => ['nullable', 'boolean'],
            'edom_required' => ['nullable', 'boolean'],
            'edom_start' => ['nullable', 'date'],
            'edom_end' => ['nullable', 'date', 'after_or_equal:edom_start'],
        ]);

        Setting::put('edom_open', $request->boolean('edom_open') ? '1' : '0');
        Setting::put('edom_required', $request->boolean('edom_required') ? '1' : '0');
        Setting::put('edom_start', $data['edom_start'] ?? null);
        Setting::put('edom_end', $data['edom_end'] ?? null);

        $open = \App\Http\Controllers\EvaluationController::edomOpen();
        Activity::log('update', 'Mengubah EDOM: '.($open ? 'BUKA' : 'TUTUP'));

        return back()->with('status', 'Pengaturan EDOM disimpan — status saat ini: '.($open ? 'DIBUKA' : 'DITUTUP').'.');
    }

    /** Buka/tutup periode pengisian KRS + batas SKS + jadwal tanggal opsional. */
    public function updateKrs(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'krs_open' => ['nullable', 'boolean'],
            'krs_max_sks' => ['required', 'integer', 'min:1', 'max:30'],
            'krs_start' => ['nullable', 'date'],
            'krs_end' => ['nullable', 'date', 'after_or_equal:krs_start'],
        ]);

        Setting::put('krs_open', $request->boolean('krs_open') ? '1' : '0');
        Setting::put('krs_max_sks', (string) $data['krs_max_sks']);
        Setting::put('krs_start', $data['krs_start'] ?? null);
        Setting::put('krs_end', $data['krs_end'] ?? null);

        $open = \App\Http\Controllers\KrsController::krsOpen();
        Activity::log('update', 'Mengubah pengaturan KRS: '.($open ? 'BUKA' : 'TUTUP').", maks {$data['krs_max_sks']} SKS");

        return back()->with('status', 'Pengaturan KRS disimpan — pengisian saat ini: '.($open ? 'DIBUKA' : 'DITUTUP').'.');
    }

    /** Tambah semester baru ke daftar (opsional langsung diaktifkan). */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'semester' => ['required', 'in:Ganjil,Genap,Antara'],
            'activate' => ['nullable', 'boolean'],
        ]);

        if (Semester::where('year', $data['year'])->where('semester', $data['semester'])->exists()) {
            return back()->with('error', "Semester {$data['semester']} {$data['year']} sudah ada di daftar.");
        }

        Semester::create(['year' => $data['year'], 'semester' => $data['semester']]);
        Activity::log('create', "Menambahkan semester {$data['semester']} {$data['year']}");

        $msg = "Semester {$data['semester']} {$data['year']} ditambahkan.";

        // Aktifkan saat dibuat: gabungkan ke himpunan periode aktif yang ada.
        if ($request->boolean('activate')) {
            $key = $data['year'].'-'.$data['semester'];
            Semester::setActiveKeys(array_merge(Semester::activeKeys(), [$key]));
            Activity::log('update', "Mengaktifkan semester {$data['semester']} {$data['year']}");
            $msg .= ' Semester ini langsung diaktifkan.';
        }

        return back()->with('status', $msg);
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

        // Guard: periode yang dinonaktifkan tapi masih punya kelas → beri peringatan (tetap diproses).
        $before = Semester::activeKeys();
        $removed = array_diff($before, $data['periods']);
        $warnings = [];
        foreach ($removed as $key) {
            [$y, $s] = explode('-', $key, 2);
            $classes = Course::where('year', $y)->where('semester', $s)->count();
            if ($classes > 0) {
                $warnings[] = Semester::keyLabel($key)." ({$classes} kelas)";
            }
        }

        Semester::setActiveKeys($data['periods']);

        $labels = collect($data['periods'])
            ->sortByDesc(fn ($k) => Semester::sortValue($k))
            ->map(fn ($k) => Semester::keyLabel($k))
            ->implode(', ');

        Activity::log('update', "Menyetel semester aktif: {$labels}");

        $flash = back()->with('status', 'Semester aktif diperbarui: '.$labels.'.');
        if ($warnings !== []) {
            $flash->with('warning', 'Perhatian: semester berikut dinonaktifkan padahal masih memuat kelas — '
                .implode(', ', $warnings).'. Kelasnya tidak lagi tampil di Dashboard/Kelas Saya sampai diaktifkan kembali.');
        }

        return $flash;
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
