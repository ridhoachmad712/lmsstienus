<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Mode samaran: admin masuk sebagai akun dosen/mahasiswa (untuk dukungan/audit). */
class ImpersonationController extends Controller
{
    private const KEY = 'impersonator_id';

    /** Admin mulai menyamar sebagai user lain. */
    public function start(Request $request, User $user): RedirectResponse
    {
        $admin = $request->user();
        abort_unless($admin->isAdmin(), 403);
        abort_if($user->isAdmin(), 403, 'Tidak dapat menyamar sebagai admin lain.');
        abort_if($user->id === $admin->id, 403);

        if ($request->session()->has(self::KEY)) {
            return back()->with('error', 'Sudah dalam mode samaran. Kembali ke admin dulu.');
        }

        Activity::log('impersonate', "Masuk sebagai {$user->name} ({$user->role})");

        Auth::login($user);
        $request->session()->regenerate();               // ID baru, data (KEY) tetap
        $request->session()->put(self::KEY, $admin->id);

        return redirect()->route('dashboard')
            ->with('status', 'Anda kini masuk sebagai '.$user->name.' ('.$user->role.').');
    }

    /** Akhiri samaran & kembali ke akun admin semula. */
    public function stop(Request $request): RedirectResponse
    {
        $id = $request->session()->pull(self::KEY);

        if (! $id) {
            return redirect()->route('dashboard');
        }

        Auth::loginUsingId((int) $id);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('status', 'Kembali ke akun admin.');
    }
}
