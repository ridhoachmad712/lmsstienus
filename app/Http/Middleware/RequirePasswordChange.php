<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->must_change_password
            && ! $request->routeIs('profile.edit', 'profile.password', 'logout', 'impersonate.stop')) {
            return redirect()->route('profile.edit')
                ->with('warning', 'Demi keamanan, ganti kata sandi awal sebelum menggunakan sistem.');
        }

        return $next($request);
    }
}
