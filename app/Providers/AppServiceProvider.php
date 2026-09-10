<?php

namespace App\Providers;

use App\Console\Commands\GenerateHojaVidaPdfCommand;
use App\Models\Equipo;
use App\Models\User;
use App\Policies\EquipoPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Facades\Event;
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

        Blade::directive('safeHtml', function ($expression) {
            return "<?php echo \\App\\Support\\HtmlSanitizer::sanitizeTemplateHtml((string) ({$expression})); ?>";
        });

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Equipo::class, EquipoPolicy::class);

        Event::listen(Authenticated::class, function (Authenticated $event): void {
            $event->user->loadMissing(['role', 'empresa']);
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

        // Usar el origen real del request (incluye /public si aplica) para que
        // el formulario de login no publique a otro host o sin el prefijo.
        if (!$this->app->runningInConsole()) {
            $this->app->booted(function () {
                try {
                    $request = request();
                    if (! $request) {
                        return;
                    }
                    $root = rtrim($request->getSchemeAndHttpHost().$request->getBasePath(), '/');
                    if ($root !== '') {
                        URL::forceRootUrl($root);
                    }
                    if ($request->isSecure()) {
                        URL::forceScheme('https');
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            });
        }
    }
}
