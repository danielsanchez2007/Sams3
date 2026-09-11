<?php

namespace App\Http\Controllers;

use App\Models\AvisoEmpresa;
use App\Models\Empresa;
use App\Services\EmpresaContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SugerenciaController extends Controller
{
    public function index()
    {
        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        $isAdmin = auth()->user() && !auth()->user()->empresa_id;

        if ($isAdmin) {
            $empresaActiva = EmpresaContext::empresaActiva();
            $empresas = Empresa::activas()->orderMatrizFirst()->orderBy('nombre')->get();
            $avisos = $empresaActiva
                ? AvisoEmpresa::where('empresa_id', $empresaActiva->id)->with(['empresa', 'creador'])->orderByDesc('created_at')->get()
                : AvisoEmpresa::with(['empresa', 'creador'])->orderByDesc('created_at')->get();
            return view('admin.sugerencias.index-admin', compact('empresas', 'avisos', 'empresaActiva'));
        }

        $avisos = AvisoEmpresa::where('empresa_id', $empresaId)
            ->with('creador')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.sugerencias.index', compact('avisos'));
    }

    public function store(Request $request)
    {
        if (auth()->user()?->empresa_id) {
            abort(403, 'No autorizado.');
        }

        $empresaActiva = EmpresaContext::empresaActiva();
        $empresaId = $empresaActiva?->id ?? $request->empresa_id;

        if (!$empresaId) {
            return redirect()->back()->with('error', 'Selecciona una empresa o entra en una empresa para enviar el aviso.');
        }

        $request->validate([
            'mensaje' => 'nullable|string|max:2000',
            'empresa_id' => ['nullable', 'exists:empresas,id'],
        ]);

        if ($empresaActiva && $request->empresa_id && (int) $request->empresa_id !== (int) $empresaActiva->id) {
            return redirect()->back()->with('error', 'El aviso debe ir para la empresa en la que estás.');
        }

        AvisoEmpresa::create([
            'empresa_id' => $empresaId,
            'tipo' => 'observacion',
            'mensaje' => $request->mensaje,
            'created_by' => auth()->id(),
        ]);

        Cache::forget('sams_aviso_tipo_' . $empresaId);

        return redirect()->route('sugerencias.index')->with('success', 'Aviso creado correctamente. Aparecerá en rojo hasta que la empresa cargue una imagen de cumplimiento.');
    }

    public function cumplir(Request $request, AvisoEmpresa $aviso)
    {
        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;

        if (!$empresaId || (int) $aviso->empresa_id !== (int) $empresaId) {
            abort(403, 'No autorizado.');
        }

        if ($aviso->tipo === 'ok') {
            return redirect()->route('sugerencias.index')->with('success', 'Este aviso ya está cumplido.');
        }

        $request->validate([
            'imagen' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        if ($aviso->imagen_cumplimiento_path && Storage::disk('public')->exists($aviso->imagen_cumplimiento_path)) {
            Storage::disk('public')->delete($aviso->imagen_cumplimiento_path);
        }

        $path = $request->file('imagen')->store('aviso_cumplimientos', 'public');

        $aviso->update([
            'tipo' => 'ok',
            'imagen_cumplimiento_path' => $path,
        ]);

        Cache::forget('sams_aviso_tipo_' . $aviso->empresa_id);

        return redirect()->route('sugerencias.index')->with('success', 'Aviso cumplido. Se cargó la imagen correctamente.');
    }
}
