<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
        $this->middleware('throttle:6,1')->only('register');
    }

    public function showRegistrationForm()
    {
        abort_unless(config('sams.allow_registration', false), 404);

        $empresas = Empresa::activas()->orderBy('nombre')->get(['id', 'nombre']);

        return view('auth.register', compact('empresas'));
    }

    public function register(Request $request)
    {
        abort_unless(config('sams.allow_registration', false), 404);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'max:72', Password::defaults()],
            'empresa_id' => ['required', 'integer', Rule::exists('empresas', 'id')],
        ]);

        $empresa = Empresa::activas()->whereKey((int) $request->input('empresa_id'))->first();
        if (! $empresa) {
            return back()->withErrors(['empresa_id' => 'Empresa no válida o inactiva.'])->withInput();
        }

        User::create([
            'name' => $request->input('name'),
            'last_name' => $request->input('last_name'),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'password' => $request->input('password'),
            'must_change_password' => true,
            'empresa_id' => $empresa->id,
            'role_id' => null,
            'active' => false,
        ]);

        return redirect()
            ->route('login')
            ->with('success', 'Registro enviado. Tu cuenta queda pendiente hasta que un administrador apruebe el acceso y te asigne un rol.');
    }
}
