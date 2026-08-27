<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Services\EmpresaContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    private function empresaByUserOrRole(\App\Models\User $user): ?Empresa
    {
        if ($user->empresa_id) {
            return Empresa::find($user->empresa_id);
        }

        $roleName = strtoupper((string) ($user->role?->name ?? ''));
        if ($roleName === '' || !str_contains($roleName, '-')) {
            return null;
        }
        $pref = strtok($roleName, '-');
        if (!$pref) {
            return null;
        }

        return Empresa::query()->whereRaw('UPPER(prefijo) = ?', [$pref])->first();
    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        // Get user by email
        $email = mb_strtolower(trim((string) $request->email));
        try {
            $user = \App\Models\User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        } catch (QueryException $e) {
            $raw = $e->getMessage() . ' ' . (string) $e->getPrevious()?->getMessage();
            if (str_contains($raw, '2002') || str_contains($raw, 'denegó') || str_contains($raw, 'refused')) {
                $message = config('app.debug')
                    ? 'No se puede conectar a la base de datos. Inicia MySQL en Laragon (Start All) y verifica DB_HOST, DB_PORT y DB_DATABASE en .env.'
                    : 'No se pudo iniciar sesión. Intenta de nuevo más tarde.';

                return back()
                    ->withErrors(['email' => $message])
                    ->withInput($request->only('email'));
            }
            throw $e;
        }

        if ($user && Hash::check($request->password, $user->password)) {
            if (!$user->active) {
                return back()
                    ->withErrors(['email' => 'Tu cuenta está pendiente de aprobación por un administrador.'])
                    ->withInput($request->only('email'));
            }
            if (!$user->role_id) {
                return back()
                    ->withErrors(['email' => 'Tu cuenta aún no tiene rol asignado. Contacta al administrador.'])
                    ->withInput($request->only('email'));
            }
            // Password is correct, log in the user
            Auth::login($user, $request->filled('remember'));
            $request->session()->regenerate();
            $this->clearLoginAttempts($request);

            $empresa = $this->empresaByUserOrRole($user);
            if ($empresa) {
                // Corrige usuarios creados sin empresa_id aunque su rol sí sea de empresa.
                if (!$user->empresa_id) {
                    $user->empresa_id = $empresa->id;
                    $user->save();
                }
                EmpresaContext::entrarEmpresa($empresa);
            } else {
                // Admin global: por defecto entra al contexto "Prevention World" (o la primera empresa),
                // para evitar pantallas 403 por "No hay una empresa activa".
                $pw = EmpresaContext::preventionWorld();
                $fallback = $pw ?: Empresa::query()->orderBy('id')->first();
                if ($fallback) {
                    EmpresaContext::entrarEmpresa($fallback);
                } else {
                    EmpresaContext::salirEmpresa();
                }
            }
            
            return redirect()->intended($this->redirectPath());
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
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
        return Str::lower($request->input($this->username())) . '|' . $request->ip();
    }
}
