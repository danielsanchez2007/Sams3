<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Evita que login/registro queden en caché del navegador (atrás / bfcache) con token CSRF viejo.
 */
class PreventAuthPageCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $isAuthGuestPage = $request->routeIs('login', 'register', 'password.request', 'password.reset', 'password.email', 'password.update')
            || $request->is('login', 'register')
            || str_starts_with($request->path(), 'password/');

        if ($isAuthGuestPage) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
