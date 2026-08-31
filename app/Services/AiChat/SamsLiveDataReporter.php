<?php

namespace App\Services\AiChat;

use App\Models\EquipoAsignacion;
use App\Models\PrestamoTemporal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Genera un bloque de texto con datos reales de SAMS para enriquecer el prompt del asistente.
 * Respeta el alcance de empresa vía global scope de Equipo y permisos del usuario.
 */
final class SamsLiveDataReporter
{
    /**
     * @param  array<string, mixed>  $ctx  ['modules' => array|'all', 'isPwGlobalAdmin' => bool]
     */
    public function build(string $message, array $ctx): ?string
    {
        $n = Str::lower(Str::ascii(trim($message)));
        if (strlen($n) < 4) {
            return null;
        }

        $mods = $ctx['modules'] ?? ['all'];
        $isGlobalAdmin = (bool) ($ctx['isPwGlobalAdmin'] ?? false);
        // Administrador global: siempre puede leer inventario/usuarios en datos en vivo aunque el mapa de módulos de empresa no traiga la clave exacta.
        $modsScope = $isGlobalAdmin ? ['all'] : $mods;
        $lines = [];

        $helpIntent = (bool) preg_match(
            '/\b(modulos|módulos|modulo|módulo|ayuda|que hace sams|qué hace sams|funciones del sistema|menu del sistema|menú del sistema|como funciona sams|cómo funciona sams)\b/u',
            $n
        );
        if ($helpIntent) {
            $guide = trim((string) config('sams-assistant.program_knowledge', ''));
            if ($guide !== '') {
                $lines[] = "### Guía del programa SAMS\n" . $guide;
            }
        }

        $inventoryIntent = (bool) preg_match(
            '/\b(cuanto|cuantos|cuantas|cuntos|cuntas|cuento|cuanto hay|cuantos hay|total|tenemos|tenesmo|tienen|listar|listado|inventario|existencias|stock|hay)\b/u',
            $n
        );
        $equipMention = (bool) preg_match(
            '/\b(equipo|equipos|arnes|arnes|cascos|casco|eslinga|eslingas|botas|mosqueton|mosqueton|linea de vida|papeleria|oficina|seguridad)\b/u',
            $n
        );
        $equipEmpresaIntent = str_contains($n, 'equipo')
            && (str_contains($n, 'empresa') || str_contains($n, 'cada empresa') || str_contains($n, 'por empresa'));
        $equipTipoIntent = str_contains($n, 'tipo') || str_contains($n, 'clase') || str_contains($n, 'categoria') || str_contains($n, 'categoría');

        $kindHint = $this->detectEquipmentKind($n);
        $wantsEquipInventory = $this->moduleAllowed($modsScope, 'equipos')
            && (
                ($inventoryIntent && ($equipMention || str_contains($n, 'inventario') || $kindHint !== null))
                || ($equipMention && $equipEmpresaIntent)
                || ($equipMention && $equipTipoIntent && (str_contains($n, 'cuant') || str_contains($n, 'cunt') || str_contains($n, 'total') || str_contains($n, 'tenem')))
            );
        if ($wantsEquipInventory) {
            $lines[] = $this->inventoryBlock($n, $isGlobalAdmin);
            if ($equipEmpresaIntent || $equipTipoIntent) {
                $lines[] = $this->equiposPorEmpresaYTipoBlock($isGlobalAdmin);
            }
        }

        $prestamoIntent = (bool) preg_match(
            '/\b(prestamo|prestamos|prestado|prestados|temporal|temporales|prestar|prestado a)\b/u',
            $n
        );
        if ($prestamoIntent && $this->moduleAllowed($modsScope, 'prestamos_temporales')) {
            $lines[] = $this->prestamosActivosBlock($isGlobalAdmin);
        }

        $assignIntent = (bool) preg_match(
            '/\b(asignad|asignacion|asignación|quien tiene|en uso|posee)\b/u',
            $n
        );
        if ($assignIntent && ($this->moduleAllowed($modsScope, 'asignar') || $this->moduleAllowed($modsScope, 'equipos'))) {
            $lines[] = $this->asignacionesBlock($isGlobalAdmin);
        }

        $userStatsIntent = str_contains($n, 'usuar')
            || str_contains($n, 'cedula')
            || str_contains($n, 'cedula')
            || str_contains($n, 'sexo')
            || str_contains($n, 'hombre')
            || str_contains($n, 'mujer');
        if ($userStatsIntent && $this->moduleAllowed($modsScope, 'users')) {
            $lines[] = $this->usuariosBlock($n, $isGlobalAdmin);
        }

        $lines = array_values(array_filter($lines, fn ($b) => $b !== null && $b !== ''));
        if ($lines === []) {
            return null;
        }

        return implode("\n\n", $lines);
    }

    /**
     * @param  array<int|string, string>|list<string>|array{0: 'all'}  $mods
     */
    private function moduleAllowed(array $mods, string $key): bool
    {
        if ($mods === ['all']) {
            return true;
        }
        if (array_is_list($mods)) {
            return in_array($key, $mods, true);
        }

        return array_key_exists($key, $mods);
    }

    /**
     * Consulta directa a `equipos` (sin Eloquent) para evitar conflictos con GROUP BY / ONLY_FULL_GROUP_BY
     * y mantener el mismo alcance que la sesión (empresa activa).
     */
    private function equiposInventoryBaseQuery(?string $kind, bool $isGlobalAdmin = false): QueryBuilder
    {
        $empresaId = $isGlobalAdmin ? null : \App\Services\EmpresaContext::empresaId();

        $q = DB::table('equipos as e')
            ->whereNull('e.deleted_at')
            ->where('e.activo', 1);

        if ($empresaId !== null) {
            $q->where('e.empresa_id', $empresaId);
        }

        if ($kind !== null) {
            $needle = str_replace(['é', 'á', 'í', 'ó', 'ú'], ['e', 'a', 'i', 'o', 'u'], $kind);
            $like = '%' . $needle . '%';
            $q->where(function (QueryBuilder $w) use ($like) {
                $w->whereRaw('LOWER(e.nombre) LIKE ?', [$like])
                    ->orWhereExists(function (QueryBuilder $sub) use ($like) {
                        $sub->select(DB::raw('1'))
                            ->from('clase_equipos as ce')
                            ->whereColumn('ce.id', 'e.clase_equipo_id')
                            ->where(function (QueryBuilder $inner) use ($like) {
                                $inner->whereRaw('LOWER(ce.nombre) LIKE ?', [$like])
                                    ->orWhereRaw('LOWER(IFNULL(ce.alias, \'\')) LIKE ?', [$like]);
                            });
                    })
                    ->orWhereExists(function (QueryBuilder $sub) use ($like) {
                        $sub->select(DB::raw('1'))
                            ->from('tipo_equipos as te')
                            ->whereColumn('te.id', 'e.tipo_equipo_id')
                            ->where(function (QueryBuilder $inner) use ($like) {
                                $inner->whereRaw('LOWER(te.nombre) LIKE ?', [$like])
                                    ->orWhereRaw('LOWER(IFNULL(te.alias, \'\')) LIKE ?', [$like]);
                            });
                    });
            });
        }

        return $q;
    }

    private function inventoryBlock(string $n, bool $isGlobalAdmin = false): string
    {
        $kind = $this->detectEquipmentKind($n);

        $total = (int) $this->equiposInventoryBaseQuery($kind, $isGlobalAdmin)->count();

        $byEmpresa = $this->equiposInventoryBaseQuery($kind, $isGlobalAdmin)
            ->select('e.empresa_id', DB::raw('COUNT(*) as total'))
            ->groupBy('e.empresa_id')
            ->orderByDesc('total')
            ->get();

        $ids = $byEmpresa->pluck('empresa_id')->filter()->unique()->values();
        $nombres = [];
        if ($ids->isNotEmpty()) {
            $nombres = DB::table('empresas')->whereIn('id', $ids)->pluck('nombre', 'id')->all();
        }

        $label = $kind === null ? 'Todos los equipos activos' : 'Equipos que coinciden con «' . $kind . '» (nombre, clase o tipo)';
        $rowLines = [];
        foreach ($byEmpresa as $row) {
            $nombre = $row->empresa_id === null
                ? 'Sin empresa'
                : (string) ($nombres[$row->empresa_id] ?? ('Empresa #' . $row->empresa_id));
            $rowLines[] = '- ' . $nombre . ': **' . $row->total . '**';
        }
        $rows = implode("\n", $rowLines);

        $out = "### {$label}\n";
        $out .= "- **Total (en tu alcance actual):** {$total}\n";
        if ($rows !== '') {
            $out .= "- **Por empresa:**\n" . $rows;
        }

        return $out;
    }

    /**
     * Desglose por empresa y tipo de equipo (tabla tipo_equipos), mismo alcance que inventario.
     */
    private function equiposPorEmpresaYTipoBlock(bool $isGlobalAdmin = false): string
    {
        $empresaId = $isGlobalAdmin ? null : \App\Services\EmpresaContext::empresaId();

        $q = DB::table('equipos as e')
            ->whereNull('e.deleted_at')
            ->where('e.activo', 1);

        if ($empresaId !== null) {
            $q->where('e.empresa_id', $empresaId);
        }

        $rows = $q->select('e.empresa_id', 'e.tipo_equipo_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('e.empresa_id', 'e.tipo_equipo_id')
            ->orderBy('e.empresa_id')
            ->orderByDesc('cnt')
            ->get();

        $empIds = $rows->pluck('empresa_id')->filter(static fn ($v) => $v !== null)->unique()->values();
        $tipoIds = $rows->pluck('tipo_equipo_id')->filter(static fn ($v) => $v !== null)->unique()->values();

        $empNombres = $empIds->isNotEmpty()
            ? DB::table('empresas')->whereIn('id', $empIds)->pluck('nombre', 'id')->all()
            : [];
        $tipoNombres = $tipoIds->isNotEmpty()
            ? DB::table('tipo_equipos')->whereIn('id', $tipoIds)->pluck('nombre', 'id')->all()
            : [];

        /** @var array<string, list<array{tipo: string, cnt: int}>> $byEmpresa */
        $byEmpresa = [];
        foreach ($rows as $r) {
            $eid = $r->empresa_id;
            $empLabel = $eid === null
                ? 'Sin empresa'
                : (string) ($empNombres[$eid] ?? ('Empresa #' . $eid));
            $tid = $r->tipo_equipo_id;
            $tipoLabel = $tid === null
                ? 'Sin tipo'
                : (string) ($tipoNombres[$tid] ?? ('Tipo #' . $tid));
            $byEmpresa[$empLabel][] = ['tipo' => $tipoLabel, 'cnt' => (int) $r->cnt];
        }

        ksort($byEmpresa, SORT_NATURAL | SORT_FLAG_CASE);

        $lines = ['### Equipos por empresa y tipo (activos)'];
        foreach ($byEmpresa as $empLabel => $tipos) {
            $lines[] = '- **' . $empLabel . ':**';
            foreach ($tipos as $t) {
                $lines[] = '  - ' . $t['tipo'] . ': **' . $t['cnt'] . '**';
            }
        }

        return implode("\n", $lines);
    }

    private function detectEquipmentKind(string $n): ?string
    {
        if (str_contains($n, 'arnes')) {
            return 'arnés';
        }
        if (str_contains($n, 'casco')) {
            return 'casco';
        }
        if (str_contains($n, 'eslinga')) {
            return 'eslinga';
        }
        if (str_contains($n, 'bota')) {
            return 'bota';
        }
        if (str_contains($n, 'mosqueton') || str_contains($n, 'mosquetón')) {
            return 'mosquetón';
        }
        if (str_contains($n, 'papeleria') || str_contains($n, 'papelería')) {
            return 'papelería';
        }
        if (str_contains($n, 'oficina')) {
            return 'oficina';
        }
        if (str_contains($n, 'seguridad')) {
            return 'seguridad';
        }

        return null;
    }

    private function prestamosActivosBlock(bool $isGlobalAdmin = false): ?string
    {
        $empresaId = $isGlobalAdmin ? null : \App\Services\EmpresaContext::empresaId();

        $q = PrestamoTemporal::query()
            ->whereIn('estado', ['activo', 'pendiente_revision'])
            ->when($empresaId, function (Builder $b) use ($empresaId) {
                $b->whereHas('items', function (Builder $iq) use ($empresaId) {
                    $iq->whereHas('equipo', function (Builder $eq) use ($empresaId) {
                        $eq->where('empresa_id', $empresaId);
                    });
                });
            })
            ->with([
                'destinatario:id,name,last_name,email',
                'items' => fn ($iq) => $iq->with(['equipo:id,nombre,codigo,empresa_id']),
            ])
            ->orderByDesc('id')
            ->limit(40);

        $rows = $q->get();
        if ($rows->isEmpty()) {
            return "### Préstamos temporales activos\n- No hay préstamos en estado **activo** o **pendiente de revisión** en este momento.";
        }

        $lines = ["### Préstamos temporales (activo / pendiente revisión)", 'Usa estos datos tal cual en tu respuesta.'];
        foreach ($rows as $p) {
            $u = $p->destinatario;
            $nombreU = $u
                ? (trim(($u->name ?? '') . ' ' . ($u->last_name ?? '')) ?: ('ID ' . $u->id))
                : 'Usuario no disponible';
            $emailU = $u ? ($u->email ?? 'sin correo') : 'sin correo';
            $salida = $p->fecha_salida ? Carbon::parse($p->fecha_salida)->format('Y-m-d') : '—';
            $dias = $p->fecha_salida ? Carbon::parse($p->fecha_salida)->diffInDays(Carbon::now()) : null;
            $diasTxt = $dias !== null ? (string) $dias . ' días desde la salida' : 'días N/D';
            $equiposTxt = $p->items->map(function ($it) {
                $eq = $it->equipo;
                if (! $eq) {
                    return 'equipo #' . $it->equipo_id;
                }

                return ($eq->codigo ?: $eq->nombre) . ' (' . ($eq->nombre ?: 'sin nombre') . ')';
            })->implode('; ');

            $lines[] = sprintf(
                '- **Préstamo #%s** → usuario **%s** (%s), salida **%s**, lleva **%s**. Equipos: %s. Estado: **%s**.',
                $p->id,
                $nombreU,
                $emailU,
                $salida,
                $diasTxt,
                $equiposTxt !== '' ? $equiposTxt : '(sin ítems)',
                $p->estado
            );
        }

        return implode("\n", $lines);
    }

    private function asignacionesBlock(bool $isGlobalAdmin = false): string
    {
        $empresaId = $isGlobalAdmin ? null : \App\Services\EmpresaContext::empresaId();

        $base = EquipoAsignacion::query()
            ->when($empresaId, function (Builder $b) use ($empresaId) {
                $b->whereHas('equipo', function (Builder $eq) use ($empresaId) {
                    $eq->where('empresa_id', $empresaId);
                });
            });

        $total = (clone $base)->count();
        $sample = (clone $base)
            ->with(['user:id,name,last_name,email', 'equipo:id,nombre,codigo,empresa_id'])
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        $lines = ["### Asignaciones de equipos (registro actual)", "- **Total de asignaciones registradas (en tu alcance):** {$total}"];

        if ($sample->isEmpty()) {
            $lines[] = '- No hay filas en `equipo_asignaciones` para listar.';

            return implode("\n", $lines);
        }

        $lines[] = '- **Ejemplos recientes (hasta 25):**';
        foreach ($sample as $a) {
            $u = $a->user;
            $eq = $a->equipo;
            $nombreU = $u ? trim(($u->name ?? '') . ' ' . ($u->last_name ?? '')) : 'usuario #' . $a->user_id;
            $eqTxt = $eq ? (($eq->codigo ?: '') . ' ' . ($eq->nombre ?? '')) : 'equipo #' . $a->equipo_id;
            $fecha = $a->asignado_at ? $a->asignado_at->format('Y-m-d H:i') : '—';
            $lines[] = '  - ' . trim($eqTxt) . ' → **' . $nombreU . '** (asignado_at: ' . $fecha . ')';
        }

        return implode("\n", $lines);
    }

    private function usuariosBlock(string $n, bool $isGlobalAdmin = false): string
    {
        $activeEmpresaId = $isGlobalAdmin ? null : \App\Services\EmpresaContext::empresaId();

        $q = \App\Models\User::query();
        if ($activeEmpresaId) {
            $q->where('empresa_id', $activeEmpresaId);
        }

        preg_match('/\b(\d{5,20})\b/', $n, $mCedula);
        $cedula = $mCedula[1] ?? null;
        $nameLike = null;
        if (preg_match('/\b(llamad[oa]?|nombre)\s+([a-z]{3,})\b/u', $n, $mName)) {
            $nameLike = trim((string) ($mName[2] ?? ''));
        }
        if ($cedula !== null && $cedula !== '') {
            $q->where('document_number', $cedula);
        }
        if ($nameLike) {
            $q->where(function ($w) use ($nameLike) {
                $w->whereRaw('LOWER(name) LIKE ?', ['%' . $nameLike . '%'])
                    ->orWhereRaw('LOWER(COALESCE(last_name, "")) LIKE ?', ['%' . $nameLike . '%']);
            });
        }

        $total = (clone $q)->count();
        $activos = (clone $q)->where('active', true)->count();
        $hombres = (clone $q)->whereIn('gender', ['masculino', 'hombre', 'male', 'm'])->count();
        $mujeres = (clone $q)->whereIn('gender', ['femenino', 'mujer', 'female', 'f'])->count();

        $byEmpresa = DB::table('users')
            ->when($activeEmpresaId, fn (QueryBuilder $b) => $b->where('empresa_id', $activeEmpresaId))
            ->when($cedula, fn (QueryBuilder $b) => $b->where('document_number', $cedula))
            ->when($nameLike, function (QueryBuilder $b) use ($nameLike) {
                $b->where(function (QueryBuilder $w) use ($nameLike) {
                    $w->whereRaw('LOWER(name) LIKE ?', ['%' . $nameLike . '%'])
                        ->orWhereRaw('LOWER(COALESCE(last_name, "")) LIKE ?', ['%' . $nameLike . '%']);
                });
            })
            ->select('empresa_id', DB::raw('COUNT(*) as total'))
            ->groupBy('empresa_id')
            ->orderByDesc('total')
            ->get();

        $sample = (clone $q)->select(['id', 'active'])->limit(10)->get();

        $ids = $byEmpresa->pluck('empresa_id')->filter()->unique()->values();
        $nombres = [];
        if ($ids->isNotEmpty()) {
            $nombres = DB::table('empresas')->whereIn('id', $ids)->pluck('nombre', 'id')->all();
        }

        $rowLines = [];
        foreach ($byEmpresa as $row) {
            $nombre = $row->empresa_id === null
                ? 'Sin empresa'
                : (string) ($nombres[$row->empresa_id] ?? ('Empresa #' . $row->empresa_id));
            $rowLines[] = '- ' . $nombre . ': **' . $row->total . '**';
        }
        $rows = implode("\n", $rowLines);

        $out = "### Usuarios\n";
        if ($cedula) {
            $out .= "- **Filtro cédula:** {$cedula}\n";
        }
        if ($nameLike) {
            $out .= "- **Filtro nombre:** {$nameLike}\n";
        }
        $out .= "- **Total:** {$total} | **Activos:** {$activos}\n";
        $out .= "- **Sexo:** Hombres {$hombres} | Mujeres {$mujeres}\n";
        if ($byEmpresa->isNotEmpty()) {
            $out .= "- **Por empresa:**\n" . $rows;
        }
        if ($sample->isNotEmpty()) {
            $out .= "\n- **Coincidencias (hasta 10):**\n";
            foreach ($sample as $u) {
                $out .= '- Usuario #' . $u->id
                    . ' | ' . ($u->active ? 'activo' : 'inactivo') . "\n";
            }
        }

        return $out;
    }
}
