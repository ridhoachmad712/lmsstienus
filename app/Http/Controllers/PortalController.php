<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    /** Pintu masuk setelah login: pengguna memilih ruang kerja. */
    public function index(Request $request): View
    {
        return view('portal.index', ['user' => $request->user()]);
    }

    /** Masuk ke ruang kerja administrasi akademik. */
    public function siakad(Request $request): View
    {
        $request->session()->put('active_system', 'siakad');

        return view('portal.siakad', ['user' => $request->user()]);
    }

    /** Masuk ke ruang kerja pembelajaran. */
    public function lms(Request $request): RedirectResponse
    {
        $request->session()->put('active_system', 'lms');
        $user = $request->user();

        if ($user->isStaff()) {
            return redirect()->route('admin.courses.index');
        }

        return redirect()->route($user->isMahasiswa() ? 'dashboard.mahasiswa' : 'dashboard.dosen');
    }
}
