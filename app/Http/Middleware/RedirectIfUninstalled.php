<?php

namespace App\Http\Middleware;

use App\Support\DeployEnvironment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfUninstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (DeployEnvironment::shouldForceInstaller($request)) {
            return redirect('/instalar');
        }

        return $next($request);
    }
}
