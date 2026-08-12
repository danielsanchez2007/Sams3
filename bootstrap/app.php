<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [
            \App\Http\Middleware\PreventAuthPageCache::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
        $middleware->alias([
            'profile.complete' => \App\Http\Middleware\EnsureProfileComplete::class,
            'password.must_change' => \App\Http\Middleware\EnsurePasswordChanged::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La página expiró. Actualiza e intenta de nuevo.',
                ], 419);
            }

            if ($request->user()) {
                return redirect()->route('admin.dashboard')
                    ->with('warning', 'Por seguridad la sesión de la página expiró. Continúa desde aquí.');
            }

            return redirect()->route('login')
                ->with('warning', 'La sesión de seguridad expiró. Vuelve a iniciar sesión.');
        });
    })->create();
