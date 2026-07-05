<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        // Di mode demo, pendaftaran mandiri tidak berlaku (akses 1-klik).
        abort_if(config('demo.enabled'), 404);

        return view('auth.register');
    }

    /**
     * Pendaftaran mandiri mahasiswa via kode kelas: membuat akun baru sekaligus
     * meng-enroll ke kelas pemilik kode. Email/NIM yang sudah terdaftar diarahkan
     * untuk masuk (mencegah akun ganda & enroll akun milik orang lain).
     */
    public function store(Request $request): RedirectResponse
    {
        abort_if(config('demo.enabled'), 404);

        $data = $request->validate([
            'join_code' => ['required', 'string', 'max:12'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'nim_nip' => ['required', 'string', 'max:50', 'unique:users,nim_nip'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'nim_nip.unique' => 'NIM ini sudah terdaftar. Jika ini milik Anda, silakan masuk lalu gabung kelas dengan kodenya.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $code = strtoupper(trim($data['join_code']));
        $course = Course::where('join_code', $code)
            ->where('status', Course::STATUS_ACTIVE)
            ->first();

        if (! $course) {
            throw ValidationException::withMessages([
                'join_code' => 'Kode kelas tidak valid atau kelas tidak aktif.',
            ]);
        }

        $email = strtolower(trim($data['email']));

        // Email sudah punya akun → arahkan masuk, jangan buat akun ganda.
        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Email ini sudah terdaftar. Silakan masuk, lalu gabung kelas dengan kodenya.',
            ]);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $email,
            'nim_nip' => $data['nim_nip'],
            'role' => User::ROLE_MAHASISWA,
            'password' => Hash::make($data['password']),
        ]);

        $course->students()->attach($user->id, ['enrolled_at' => now()]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('courses.show', $course)
            ->with('status', 'Pendaftaran berhasil. Anda tergabung di kelas '.$course->name.'.');
    }
}
