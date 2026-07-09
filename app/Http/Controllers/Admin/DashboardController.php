<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\KrsController;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Beranda admin/kaprodi: ringkasan kampus (kaprodi → lingkup prodinya). */
    public function index(Request $request): View
    {
        $user = $request->user();
        $prodiId = $user->isKaprodi() ? $user->prodi_id : null;

        $countUsers = fn (string $role) => User::where('role', $role)
            ->when($prodiId, fn ($q) => $q->where('prodi_id', $prodiId))
            ->count();

        $courseQuery = fn () => Course::query()->when($prodiId, fn ($q) => $q->where('prodi_id', $prodiId));

        $stats = [
            'dosen' => $countUsers(User::ROLE_DOSEN),
            'mahasiswa' => $countUsers(User::ROLE_MAHASISWA),
            'courses' => $courseQuery()->count(),
            'active_courses' => $courseQuery()->where('status', Course::STATUS_ACTIVE)->count(),
        ];

        $activeKeys = Semester::activeKeys();
        $prodi = $user->prodi;

        // Status KRS periode aktif utama + jumlah mahasiswa yang KRS-nya menunggu persetujuan wali.
        [$year, $semester] = explode('-', Semester::primaryKey(), 2);
        $krsPending = User::where('role', User::ROLE_MAHASISWA)
            ->when($prodiId, fn ($q) => $q->where('prodi_id', $prodiId))
            ->whereHas('enrollments', fn ($q) => $q
                ->where('status', Enrollment::STATUS_SUBMITTED)
                ->whereHas('course', fn ($c) => $c->where('year', $year)->where('semester', $semester)))
            ->count();

        $krs = [
            'open' => KrsController::krsOpen(),
            'max_sks' => KrsController::maxSks(),
            'pending' => $krsPending,
            'period' => Semester::keyLabel($year.'-'.$semester),
        ];

        $isAdmin = $user->isAdmin();

        // Kartu statistik [label, value, sub, icon, color, route|null]
        $statCards = [
            ['Dosen', $stats['dosen'], null, 'ti-user-star', 'blue', $isAdmin ? 'admin.staff.index' : null],
            ['Mahasiswa', $stats['mahasiswa'], null, 'ti-users', 'green', 'admin.students.index'],
            ['Kelas aktif', $stats['active_courses'], 'dari '.$stats['courses'].' kelas', 'ti-school', 'azure', 'admin.courses.index'],
            ['Semester aktif', count($activeKeys), collect($activeKeys)->map(fn ($k) => Semester::keyLabel($k))->implode(', '), 'ti-calendar-stats', 'purple', $isAdmin ? 'admin.semesters.index' : null],
        ];

        // Kelompok menu: Data Master / Akademik / LMS / Sistem
        $master = [];
        if ($isAdmin) {
            $master[] = ['admin.prodi.index', 'ti-building', 'Program Studi', 'Daftar prodi'];
        }
        $master[] = ['admin.kurikulum.index', 'ti-notebook', 'Kurikulum', 'Versi kurikulum per prodi'];
        $master[] = ['admin.matakuliah.index', 'ti-book', 'Mata Kuliah', 'Katalog MK, SKS & prasyarat'];
        if ($isAdmin) {
            $master[] = ['admin.staff.index', 'ti-user-star', 'Dosen & Kaprodi', 'Akun staf + prodi'];
        }
        $master[] = ['admin.students.index', 'ti-users', 'Mahasiswa', 'Akun & biodata mahasiswa'];
        if ($isAdmin) {
            $master[] = ['admin.rooms.index', 'ti-door', 'Ruangan', 'Daftar ruang kuliah'];
            $master[] = ['admin.timeslots.index', 'ti-clock-hour-8', 'Sesi Kuliah', 'Slot jam baku'];
            $master[] = ['admin.gradeScale.edit', 'ti-award', 'Skala Nilai', 'Ambang konversi huruf'];
        }

        $akademik = [['admin.academic.index', 'ti-chart-bar', 'Rekap Akademik', 'IPK/IPS & deteksi bermasalah']];
        if ($isAdmin) {
            $akademik[] = ['admin.semesters.index', 'ti-calendar-stats', 'Kelola Semester', 'Semester aktif & KRS'];
        }
        $akademik[] = ['academic.calendar', 'ti-calendar-event', 'Kalender Akademik', 'Agenda KRS/UTS/UAS/libur'];

        $menuGroups = [
            ['Data Master', 'ti-database', 'green', $master],
            ['Akademik', 'ti-books', 'blue', $akademik],
            ['LMS', 'ti-device-laptop', 'azure', [['admin.courses.index', 'ti-school', 'Pengawasan Kelas', 'Pantau kelas & progresnya']]],
        ];
        if ($isAdmin) {
            $menuGroups[] = ['Sistem', 'ti-settings', 'purple', [
                ['admin.settings.edit', 'ti-palette', 'Tampilan', 'Branding & tema aplikasi'],
                ['admin.ai.edit', 'ti-sparkles', 'Integrasi AI', 'Kunci & model AI'],
                ['admin.activity.index', 'ti-history', 'Riwayat Aktivitas', 'Log tindakan pengguna'],
                ['admin.backups.index', 'ti-database', 'Backup', 'Cadangan basis data'],
            ]];
        }

        return view('admin.dashboard', compact('stats', 'activeKeys', 'prodi', 'krs', 'isAdmin', 'statCards', 'menuGroups'));
    }
}
