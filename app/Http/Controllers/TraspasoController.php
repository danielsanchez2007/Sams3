<?php

namespace App\Http\Controllers;

use App\Http\Requests\TraspasoEquipoRequest;
use App\Models\Equipo;
use App\Models\EquipoAuditoriaTraspaso;
use App\Models\MaterialDidacticoTraspaso;
use App\Models\TipoEquipo;
use App\Services\CodigoEquipoService;
use App\Services\EmpresaContext;
use Illuminate\Support\Facades\DB;
use Throwable;

class TraspasoController extends Controller
{
    public function traspasar(TraspasoEquipoRequest $request)
    {
        $equipo = Equipo::query()->with('tipoEquipo')->whereKey($request->integer('equipo_id'))->firstOrFail();
        $motivo = trim((string) $request->input('motivo', ''));

        try {
            if ($request->destino === 'baja') {
                if (!$equipo->activo) {
                    return redirect()->back()->with('error', 'Ese equipo ya está dado de baja.');
                }

                return redirect()->route('equipos.bajas.form', $equipo);
            }

            if ($request->destino === 'auditoria') {
                if (!$equipo->activo) {
                    return redirect()->back()->with('error', 'Solo equipos activos pueden traspasarse a auditoría.');
                }
                $codigoActual = (string) $equipo->codigo;
                if (CodigoEquipoService::esAuditoria($codigoActual)) {
                    return redirect()->back()->with('error', 'Ese equipo ya está en auditoría.');
                }

                return DB::transaction(function () use ($equipo, $request, $motivo) {
                    $codigoAnterior = (string) $equipo->codigo;
                    $codigoAud = CodigoEquipoService::nextCodigoAuditoria($equipo);

                    EquipoAuditoriaTraspaso::create([
                        'equipo_id' => $equipo->id,
                        'codigo_aud' => $codigoAud,
                        'codigo_anterior' => $codigoAnterior,
                        'motivo' => $motivo,
                        'traspasado_por' => auth()->id(),
                        'traspasado_en' => now(),
                    ]);

                    $equipo->update(['codigo' => $codigoAud, 'serial' => $codigoAud]);
                    CodigoEquipoService::liberarInventario($codigoAnterior);

                    $from = $request->input('from', $request->query('from', 'auditoria'));
                    $route = $from === 'material-didactico' ? 'equipos.material-didactico.index' : 'equipos.auditoria.index';

                    return redirect()->route($route)->with('success', "✅ Equipo traspasado a auditoría ({$codigoAud}). El código de inventario quedó disponible.");
                });
            }

            if ($request->destino === 'didactico') {
                if (!$equipo->activo) {
                    return redirect()->back()->with('error', 'Solo equipos activos pueden traspasarse a material didáctico.');
                }
                $codigoActual = (string) $equipo->codigo;
                if (CodigoEquipoService::esMaterial($codigoActual)) {
                    return redirect()->back()->with('error', 'Ese equipo ya está en material didáctico.');
                }

                $empresaId = $equipo->empresa_id ?? EmpresaContext::empresaId() ?? null;
                $tipoDidacticoId = (int) TipoEquipo::query()
                    ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
                    ->where(function ($q) {
                        $q->whereRaw('LOWER(nombre) LIKE ?', ['%didact%'])
                            ->orWhereRaw('LOWER(nombre) LIKE ?', ['%didáct%'])
                            ->orWhereRaw('LOWER(nombre) LIKE ?', ['%material%']);
                    })
                    ->orderBy('id')
                    ->value('id');

                if ($tipoDidacticoId <= 0) {
                    $tipo = TipoEquipo::query()->firstOrCreate(
                        ['nombre' => 'Material Didáctico', 'empresa_id' => $empresaId],
                        ['descripcion' => null, 'activo' => true]
                    );
                    $tipoDidacticoId = (int) $tipo->id;
                }

                return DB::transaction(function () use ($equipo, $tipoDidacticoId, $request, $motivo) {
                    $codigoAnterior = (string) $equipo->codigo;
                    $codigoMd = CodigoEquipoService::nextCodigoMaterial($equipo, $tipoDidacticoId);

                    MaterialDidacticoTraspaso::create([
                        'equipo_id' => $equipo->id,
                        'codigo_md' => $codigoMd,
                        'codigo_anterior' => $codigoAnterior,
                        'motivo' => $motivo,
                        'traspasado_por' => auth()->id(),
                        'traspasado_en' => now(),
                    ]);

                    $equipo->update([
                        'codigo' => $codigoMd,
                        'serial' => $codigoMd,
                        'tipo_equipo_id' => $tipoDidacticoId,
                    ]);

                    CodigoEquipoService::liberarInventario($codigoAnterior);

                    $from = $request->input('from', $request->query('from', 'material-didactico'));
                    $route = $from === 'auditoria' ? 'equipos.auditoria.index' : 'equipos.material-didactico.index';

                    return redirect()->route($route)->with('success', "✅ Equipo traspasado a material didáctico ({$codigoMd}). El código de inventario quedó disponible.");
                });
            }

            // inventario
            $codigoActual = (string) $equipo->codigo;
            if (CodigoEquipoService::esInventario($codigoActual) && $equipo->activo) {
                return redirect()->back()->with('error', 'Ese equipo ya está en inventario.');
            }

            return DB::transaction(function () use ($equipo, $request, $motivo) {
                $codigoAnterior = (string) $equipo->codigo;
                $codigoIn = CodigoEquipoService::nextCodigoInventario($equipo);

                $equipo->update([
                    'codigo' => $codigoIn,
                    'serial' => $codigoIn,
                    'activo' => true,
                ]);

                // Registrar motivo en la tabla correspondiente según el origen
                if (CodigoEquipoService::esAuditoria($codigoAnterior)) {
                    EquipoAuditoriaTraspaso::create([
                        'equipo_id' => $equipo->id,
                        'codigo_aud' => $codigoAnterior,
                        'codigo_anterior' => $codigoIn,
                        'motivo' => 'Retorno a inventario: ' . $motivo,
                        'traspasado_por' => auth()->id(),
                        'traspasado_en' => now(),
                    ]);
                } elseif (CodigoEquipoService::esMaterial($codigoAnterior)) {
                    MaterialDidacticoTraspaso::create([
                        'equipo_id' => $equipo->id,
                        'codigo_md' => $codigoAnterior,
                        'codigo_anterior' => $codigoIn,
                        'motivo' => 'Retorno a inventario: ' . $motivo,
                        'traspasado_por' => auth()->id(),
                        'traspasado_en' => now(),
                    ]);
                }

                $from = $request->input('from', $request->query('from', 'auditoria'));
                $route = $from === 'material-didactico' ? 'equipos.material-didactico.index' : 'equipos.auditoria.index';

                return redirect()->route($route)->with('success', "✅ Equipo traspasado a inventario ({$codigoIn})");
            });
        } catch (Throwable $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->with('error', 'No se pudo completar el traspaso: ' . $e->getMessage());
        }
    }
}
