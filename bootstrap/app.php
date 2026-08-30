<?php

use App\Http\Middleware\DemoGuard;
use App\Http\Middleware\LegacySiakadRedirect;
use App\Http\Middleware\RequirePasswordChange;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SystemContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // Pengaman mode demo (efektif hanya saat DEMO_MODE=true)
        $middleware->appendToGroup('web', DemoGuard::class);
        $middleware->appendToGroup('web', SystemContext::class);
        $middleware->appendToGroup('web', LegacySiakadRedirect::class);
        $middleware->appendToGroup('web', RequirePasswordChange::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
