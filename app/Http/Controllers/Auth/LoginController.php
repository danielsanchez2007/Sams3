<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\User;
use App\Services\EmpresaContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Hash bcrypt fijo solo para igualar tiempos cuando el correo no existe.
     */
    private const DUMMY_PASSWORD_HASH = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    protected $redirectTo = '/admin';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function username()
    {
        return 'email';
    }

    protected function maxAttempts()
    {
        return 5;
    }

    protected function decayMinutes()
    {
        return 15;
    }

    protected function throttleKey(Request $request)
    {
        return Str::lower(trim((string) $request->input($this->username()))).'|'.$request->ip();
    }

    public function login(Request $request)
    {
        $this->validateLogin($request);

        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        $email = mb_strtolower(trim((string) $request->email));

        try {
            $user = User::query()->with('role')->whereRaw('LOWER(email) = ?', [$email])->first();
            $passwordOk = Hash::check(
                (string) $request->password,
                $user?->getAuthPassword() ?: self::DUMMY_PASSWORD_HASH
            );

            if ($user && $passwordOk) {
                if (! $user->active) {
                    return back()
                        ->withErrors(['email' => 'Tu cuenta está pendiente de aprobación por un administrador.'])
                        ->withInput($request->only('email'));
                }
                if (! $user->role_id) {
                    return back()
                        ->withErrors(['email' => 'Tu cuenta aún no tiene rol asignado. Contacta al administrador.'])
                        ->withInput($request->only('email'));
                }

                Auth::login($user, $request->boolean('remember'));
                $request->session()->regenerate();
                $this->clearLoginAttempts($request);
                $this->establishEmpresaContext($user);
                $request->session()->forget('url.intended');

                return redirect()->to($this->pathAfterLogin($user));
            }

            $this->incrementLoginAttempts($request);

            return $this->sendFailedLoginResponse($request);
        } catch (QueryException $e) {
            $raw = $e->getMessage().' '.(string) $e->getPrevious()?->getMessage();
            if (str_contains($raw, '2002') || str_contains($raw, 'denegó') || str_contains($raw, 'refused')) {
                $message = config('app.debug')
                    ? 'No se puede conectar a la base de datos. Inicia MySQL en Laragon (Start All) y verifica DB_HOST, DB_PORT y DB_DATABASE en .env.'
                    : 'No se pudo iniciar sesión. Intenta de nuevo más tarde.';

                return back()
                    ->withErrors(['email' => $message])
                    ->withInput($request->only('email'));
            }
            Log::error('Login query failed', ['exception' => $e->getMessage()]);

            return back()
                ->withErrors(['email' => 'No se pudo iniciar sesión. Intenta de nuevo.'])
                ->withInput($request->only('email'));
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Login unexpected error', ['exception' => $e->getMessage()]);

            return back()
                ->withErrors(['email' => 'No se pudo iniciar sesión. Actualiza la página e inténtalo de nuevo.'])
                ->withInput($request->only('email'));
        }
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('sistema.info');
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            $this->username() => ['Estas credenciales no coinciden con nuestros registros.'],
        ]);
    }

    protected function sendLockoutResponse(Request $request)
    {
        $seconds = $this->limiter()->availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            $this->username() => [
                'Demasiados intentos. Espera '.$seconds.' segundos e inténtalo de nuevo.',
            ],
        ])->status(429);
    }

    private function establishEmpresaContext(User $user): void
    {
        $empresa = $this->empresaByUserOrRole($user);
        if ($empresa) {
            if (! $user->empresa_id) {
                $user->empresa_id = $empresa->id;
                $user->save();
            }
            EmpresaContext::entrarEmpresa($empresa);

            return;
        }

        $fallback = EmpresaContext::preventionWorld() ?: Empresa::query()->orderBy('id')->first();
        if ($fallback) {
            EmpresaContext::entrarEmpresa($fallback);
        } else {
            EmpresaContext::salirEmpresa();
        }
    }

    private function pathAfterLogin(User $user): string
    {
        if ($user->must_change_password) {
            return route('password.secure.show');
        }
        if (! $user->isProfileComplete()) {
            return route('profile.show');
        }

        return route('admin.dashboard');
    }

    private function empresaByUserOrRole(User $user): ?Empresa
    {
        if ($user->empresa_id) {
            return Empresa::find($user->empresa_id);
        }

        $roleName = strtoupper((string) ($user->role?->name ?? ''));
        if ($roleName === '' || ! str_contains($roleName, '-')) {
            return null;
        }
        $pref = strtok($roleName, '-');
        if (! $pref) {
            return null;
        }

        return Empresa::query()->whereRaw('UPPER(prefijo) = ?', [$pref])->first();
    }
}
