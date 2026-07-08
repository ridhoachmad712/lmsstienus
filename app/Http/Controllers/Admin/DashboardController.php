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

        return view('admin.dashboard', compact('stats', 'activeKeys', 'prodi', 'krs'));
    }
}
