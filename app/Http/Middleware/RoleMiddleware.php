<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Proteksi route berdasarkan role user.
     * Pemakaian: ->middleware('role:dosen') atau 'role:dosen,mahasiswa'
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Perhitungkan jabatan rangkap (dosen yang merangkap kaprodi) via hasRole().
        $allowed = $user && collect($roles)->contains(fn ($role) => $user->hasRole($role));

        if (! $allowed) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
