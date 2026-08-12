<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Obliga a definir una contraseña fuerte tras el registro o alta con contraseña provisional.
 */
class EnsurePasswordChanged
{
    protected array $except = [
        'password.secure.show',
        'password.secure.update',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs($this->except)) {
            return $next($request);
        }

        return redirect()
            ->route('password.secure.show')
            ->with('warning', 'Por seguridad debes establecer una contraseña más segura antes de continuar.');
    }
}
