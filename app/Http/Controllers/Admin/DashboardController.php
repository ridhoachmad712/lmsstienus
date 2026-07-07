<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Semester;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** Beranda admin: ringkasan kampus + pintasan pengelolaan. */
    public function index(): View
    {
        $stats = [
            'dosen' => User::where('role', User::ROLE_DOSEN)->count(),
            'mahasiswa' => User::where('role', User::ROLE_MAHASISWA)->count(),
            'courses' => Course::count(),
            'active_courses' => Course::where('status', Course::STATUS_ACTIVE)->count(),
        ];

        $activeKeys = Semester::activeKeys();

        return view('admin.dashboard', compact('stats', 'activeKeys'));
    }
}
