<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ForcedPasswordController extends Controller
{
    public function show()
    {
        if (! auth()->user()->must_change_password) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.password-secure');
    }

    public function update(Request $request)
    {
        $user = $request->user();
        if (! $user->must_change_password) {
            return redirect()->route('admin.dashboard');
        }

        $request->validate([
            'password' => [
                'required',
                'string',
                'confirmed',
                'max:72',
                Password::defaults()->symbols(),
            ],
        ]);

        $user->forceFill([
            'password' => $request->input('password'),
            'must_change_password' => false,
        ])->save();

        $request->session()->regenerate();

        return redirect()
            ->intended(route('admin.dashboard'))
            ->with('success', 'Contraseña segura guardada. Ya puedes usar el sistema.');
    }
}
