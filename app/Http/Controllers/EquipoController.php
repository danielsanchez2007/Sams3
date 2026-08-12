<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\HistorialMantenimiento;
use App\Models\AuditoriaEquipo;
use App\Models\User;
use Illuminate\Http\Request;

class EquipoController extends Controller
{
    public function complete()
    {
        $equipos = Equipo::with('responsable')->get();
        $users = User::where('active', true)->get();
        
        return view('admin.equipos.complete', compact('equipos', 'users'));
    }

    public function create()
    {
        $users = User::where('active', true)->get();
        return view('admin.equipos.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|max:50|unique:equipos,codigo',
            'nombre' => 'required|string|max:200',
            'tipo_equipo' => 'required|in:' . implode(',', array_keys(Equipo::TIPOS)),
            'clase_equipo' => 'required|in:' . implode(',', array_keys(Equipo::CLASES)),
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'serie' => 'nullable|string|max:100',
            'ubicacion' => 'nullable|string|max:200',
            'responsable_id' => 'nullable|exists:users,id',
            'fecha_adquisicion' => 'nullable|date',
            'valor_compra' => 'nullable|numeric|min:0',
            'vida_util' => 'nullable|integer|min:1',
            'observaciones' => 'nullable|string'
        ]);

        $equipo = Equipo::create([
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'tipo_equipo' => $request->tipo_equipo,
            'clase_equipo' => $request->clase_equipo,
            'marca' => $request->marca,
            'modelo' => $request->modelo,
            'serie' => $request->serie,
            'estado' => 'activo',
            'ubicacion' => $request->ubicacion,
            'responsable_id' => $request->responsable_id,
            'fecha_adquisicion' => $request->fecha_adquisicion,
            'valor_compra' => $request->valor_compra,
            'vida_util' => $request->vida_util,
            'observaciones' => $request->observaciones,
            'activo' => true
        ]);

        return redirect()->route('equipos.complete')->with('success', '✅ Equipo creado exitosamente');
    }

    public function edit(Equipo $equipo)
    {
        $users = User::where('active', true)->get();
        return view('admin.equipos.edit', compact('equipo', 'users'));
    }

    public function update(Request $request, Equipo $equipo)
    {
        $request->validate([
            'codigo' => 'required|string|max:50|unique:equipos,codigo,' . $equipo->id,
            'nombre' => 'required|string|max:200',
            'tipo_equipo' => 'required|in:' . implode(',', array_keys(Equipo::TIPOS)),
            'clase_equipo' => 'required|in:' . implode(',', array_keys(Equipo::CLASES)),
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'serie' => 'nullable|string|max:100',
            'estado' => 'required|in:' . implode(',', array_keys(Equipo::ESTADOS)),
            'ubicacion' => 'nullable|string|max:200',
            'responsable_id' => 'nullable|exists:users,id',
            'fecha_adquisicion' => 'nullable|date',
            'valor_compra' => 'nullable|numeric|min:0',
            'vida_util' => 'nullable|integer|min:1',
            'observaciones' => 'nullable|string'
        ]);

        $equipo->update([
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'tipo_equipo' => $request->tipo_equipo,
            'clase_equipo' => $request->clase_equipo,
            'marca' => $request->marca,
            'modelo' => $request->modelo,
            'serie' => $request->serie,
            'estado' => $request->estado,
            'ubicacion' => $request->ubicacion,
            'responsable_id' => $request->responsable_id,
            'fecha_adquisicion' => $request->fecha_adquisicion,
            'valor_compra' => $request->valor_compra,
            'vida_util' => $request->vida_util,
            'observaciones' => $request->observaciones
        ]);

        return redirect()->route('equipos.complete')->with('success', '✅ Equipo actualizado exitosamente');
    }

    public function destroy(Equipo $equipo)
    {
        $equipo->delete();
        
        return response()->json([
            'success' => true,
            'message' => '🗑️ Equipo eliminado exitosamente.'
        ]);
    }

    public function toggleStatus(Equipo $equipo)
    {
        $equipo->update(['activo' => !$equipo->activo]);
        
        $status = $equipo->activo ? 'activado' : 'desactivado';
        return response()->json([
            'success' => true,
            'message' => "✅ Equipo {$status} exitosamente."
        ]);
    }

    public function darBaja(Request $request, Equipo $equipo)
    {
        $request->validate([
            'motivo_baja' => 'required|string',
            'fecha_baja' => 'required|date'
        ]);

        $equipo->update([
            'estado' => 'baja',
            'fecha_baja' => $request->fecha_baja,
            'motivo_baja' => $request->motivo_baja
        ]);

        return redirect()->route('equipos.complete')->with('success', '✅ Equipo dado de baja exitosamente');
    }

    // Métodos para diferentes tipos de equipos
    public function tipos()
    {
        $tiposEquipos = TipoEquipo::with('clases')->activos()->get();
        $clasesEquipos = ClaseEquipo::with('tipoEquipo')->activas()->get();
        $estadosEquipos = Equipo::ESTADOS;
        
        // Obtener conteo por tipo
        $conteoPorTipo = [];
        foreach ($tiposEquipos as $key => $value) {
            $conteoPorTipo[$key] = [
                'nombre' => $value->nombre,
                'cantidad' => Equipo::where('tipo_equipo', $key)->count(),
                'icono' => $this->getIconoPorTipo($key),
                'clases' => $value->clases->pluck('nombre', 'id')
            ];
        }
        
        return view('admin.equipos.tipos-gestion', compact('tiposEquipos', 'clasesEquipos', 'estadosEquipos', 'conteoPorTipo'));
    }

    public function materialDidactico()
    {
        $equipos = Equipo::materialDidactico()->with('responsable')->get();
        return view('admin.equipos.material-didactico', compact('equipos'));
    }

    public function equiposBaja()
    {
        $equipos = Equipo::deBaja()->with('responsable')->get();
        return view('admin.equipos.baja', compact('equipos'));
    }

    public function auditoria()
    {
        $equipos = Equipo::all();
        return view('admin.equipos.auditoria', compact('equipos'));
    }

    public function realizarAuditoria(Request $request, Equipo $equipo)
    {
        $request->validate([
            'estado_fisico' => 'required|in:' . implode(',', array_keys(AuditoriaEquipo::ESTADOS_FISICOS)),
            'estado_funcional' => 'required|in:' . implode(',', array_keys(AuditoriaEquipo::ESTADOS_FUNCIONALES)),
            'ubicacion_verificada' => 'boolean',
            'responsable_verificado' => 'boolean',
            'observaciones' => 'nullable|string',
            'recomendaciones' => 'nullable|string',
            'cumple_normas' => 'boolean',
            'puntuacion' => 'nullable|integer|min:1|max:100'
        ]);

        AuditoriaEquipo::create([
            'equipo_id' => $equipo->id,
            'auditor_id' => auth()->id(),
            'fecha_auditoria' => now(),
            'estado_fisico' => $request->estado_fisico,
            'estado_funcional' => $request->estado_funcional,
            'ubicacion_verificada' => $request->boolean('ubicacion_verificada'),
            'responsable_verificado' => $request->boolean('responsable_verificado'),
            'observaciones' => $request->observaciones,
            'recomendaciones' => $request->recomendaciones,
            'cumple_normas' => $request->boolean('cumple_normas'),
            'puntuacion' => $request->puntuacion
        ]);

        return redirect()->route('equipos.auditoria')->with('success', '✅ Auditoría realizada exitosamente');
    }

    public function historialMantenimientos(Equipo $equipo)
    {
        $mantenimientos = $equipo->historialMantenimientos()->with('tecnico')->orderBy('fecha_mantenimiento', 'desc')->get();
        return view('admin.equipos.historial-mantenimientos', compact('equipo', 'mantenimientos'));
    }

    public function agregarMantenimiento(Request $request, Equipo $equipo)
    {
        $request->validate([
            'tipo_mantenimiento' => 'required|in:' . implode(',', array_keys(HistorialMantenimiento::TIPOS)),
            'descripcion' => 'required|string',
            'fecha_mantenimiento' => 'required|date',
            'costo' => 'nullable|numeric|min:0',
            'repuestos' => 'nullable|string',
            'observaciones' => 'nullable|string'
        ]);

        HistorialMantenimiento::create([
            'equipo_id' => $equipo->id,
            'tipo_mantenimiento' => $request->tipo_mantenimiento,
            'descripcion' => $request->descripcion,
            'fecha_mantenimiento' => $request->fecha_mantenimiento,
            'tecnico_id' => auth()->id(),
            'costo' => $request->costo,
            'repuestos' => $request->repuestos,
            'observaciones' => $request->observaciones,
            'estado_equipo' => $equipo->estado
        ]);

        return redirect()->route('equipos.historial-mantenimientos', $equipo)->with('success', '✅ Mantenimiento registrado exitosamente');
    }

    // Método auxiliar para obtener iconos por tipo
    private function getIconoPorTipo($tipo)
    {
        $iconos = [
            'computo' => 'monitor',
            'impresora' => 'printer',
            'escaner' => 'scan',
            'proyector' => 'projector',
            'telefono' => 'phone',
            'servidor' => 'server',
            'red' => 'wifi',
            'mobiliario' => 'home',
            'herramienta' => 'wrench',
            'material_didactico' => 'book-open'
        ];
        
        return $iconos[$tipo] ?? 'package';
    }
}
