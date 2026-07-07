<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
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

        return view('admin.dashboard', compact('stats', 'activeKeys', 'prodi'));
    }
}
