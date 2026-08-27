<?php

namespace App\Providers;

use App\Console\Commands\GenerateHojaVidaPdfCommand;
use App\Models\Equipo;
use App\Models\User;
use App\Policies\EquipoPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function () {
            return Password::min(10)->mixedCase()->numbers();
        });

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Equipo::class, EquipoPolicy::class);

        RateLimiter::for('ai-chat', function (Request $request) {
            return Limit::perMinute(20)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        RateLimiter::for('geocode', function (Request $request) {
            return Limit::perMinute(10)->by((string) ($request->user()?->id ?: $request->ip()));
        });

        $this->commands([
            GenerateHojaVidaPdfCommand::class,
        ]);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Con Laragon bajo /public, forzar la raíz real del request
        // para que route()/url() no apunten a /equipos sin el prefijo.
        if (!$this->app->runningInConsole()) {
            $this->app->booted(function () {
                try {
                    $request = request();
                    if ($request) {
                        URL::forceRootUrl($request->root());
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            });
        }
    }
}
