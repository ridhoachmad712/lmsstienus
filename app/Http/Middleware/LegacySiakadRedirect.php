<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LegacySiakadRedirect
{
    /**
     * Saat SIAKAD lama diaktifkan, endpoint akademik Laravel tidak boleh menjadi
     * sumber data kedua. Permintaan baca diarahkan ke pintu SIAKAD; mutasi ditolak.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('services.legacy_siakad.enabled')
            || ! $request->is('siakad/*')
            || $request->routeIs('portal.siakad')) {
            return $next($request);
        }

        if ($request->isMethodSafe()) {
            return redirect()->route('portal.siakad');
        }

        abort(409, 'Pengelolaan akademik dilakukan di SIAKAD lama.');
    }
}
