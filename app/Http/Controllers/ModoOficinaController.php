<?php

namespace App\Http\Controllers;

use App\Services\EmpresaContext;
use App\Services\VistaOficina;
use Illuminate\Http\RedirectResponse;

class ModoOficinaController extends Controller
{
    public function entrar(): RedirectResponse
    {
        abort_unless(VistaOficina::esAdministradorGlobal(auth()->user()), 403);

        $pw = EmpresaContext::preventionWorld();
        abort_unless($pw, 404, 'No existe la empresa Prevention World. Ejecuta: php artisan sams:ensure-prevention-world');

        session([VistaOficina::SESSION_MODO_OFICINA => true]);
        EmpresaContext::entrarEmpresa($pw);

        return redirect()
            ->route('asignar.index')
            ->with('success', 'Estás en el apartado Oficina (Prevention World).');
    }

    public function salir(): RedirectResponse
    {
        abort_unless(VistaOficina::esAdministradorGlobal(auth()->user()), 403);

        session()->forget(VistaOficina::SESSION_MODO_OFICINA);
        EmpresaContext::salirEmpresa();

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Vista Prevention World — SAMS completo.');
    }
}
