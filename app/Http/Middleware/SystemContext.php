<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SystemContext
{
    /** Sinkronkan konteks navigasi dari prefix URL agar bookmark langsung selalu konsisten. */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('siakad') || $request->is('siakad/*')) {
            $request->session()->put('active_system', 'siakad');
        } elseif ($request->is('lms') || $request->is('lms/*')) {
            $request->session()->put('active_system', 'lms');
        }

        return $next($request);
    }
}
