<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileComplete
{
    /**
     * Rutas que siempre se permiten aunque el perfil no esté completo.
     */
    protected array $except = [
        'profile.show',
        'profile.update',
        'password.secure.show',
        'password.secure.update',
        'logout',
        'admin.docs.manual-tecnico',
        'admin.docs.manual-tecnico-pdf',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if ($user->isProfileComplete()) {
            return $next($request);
        }

        if ($request->routeIs($this->except)) {
            return $next($request);
        }

        return redirect()->route('profile.show')
            ->with('warning', 'Completa tu perfil (foto, firma, tipo y número de documento) para poder usar el sistema.');
    }
}
