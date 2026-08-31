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
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB
        );
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(function () {
            $user = auth()->user();
            if ($user?->must_change_password) {
                return route('password.secure.show');
            }
            if ($user && ! $user->isProfileComplete()) {
                return route('profile.show');
            }

            return route('admin.dashboard');
        });
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
                $user = $request->user();
                $dest = $user->must_change_password
                    ? 'password.secure.show'
                    : ($user->isProfileComplete() ? 'admin.dashboard' : 'profile.show');

                return redirect()->route($dest)
                    ->with('warning', 'Por seguridad la sesión de la página expiró. Continúa desde aquí.');
            }

            $loginRedirect = redirect()->route('login')
                ->with('warning', 'La sesión de seguridad expiró. Vuelve a escribir tu contraseña e intenta de nuevo.');

            if ($request->isMethod('POST') && ($request->routeIs('login') || $request->is('login'))) {
                $loginRedirect->withInput($request->only('email'));
            }

            return $loginRedirect;
        });

        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->expectsJson() || $request->is('asistente/*') || $request->is('empresa/geocode');
        });
    })->create();
