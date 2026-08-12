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

        $path = $request->path();
        $isAuthGuestPage = in_array($path, ['login', 'register'], true)
            || str_starts_with($path, 'password/');

        if ($isAuthGuestPage && $response->getStatusCode() === 200) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}
