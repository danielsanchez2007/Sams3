<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Equipo;
use App\Models\EquipoAsignacion;
use App\Models\EquipoAsignacionSolicitud;
use App\Models\EquipoAsignacionSolicitudItem;
use App\Models\User;
use App\Services\EmpresaContext;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Html as SpreadsheetHtmlWriter;

class AsignarController extends Controller
{
    public function index(Request $request): View
    {
        $this->assertCanViewModule('asignar');
        $empresaId = $this->resolveEmpresaId();
        $empresas = Empresa::activas()->orderMatrizFirst()->orderBy('nombre')->get(['id', 'nombre']);
        $q = trim((string) $request->query('q', ''));
        $empresaIdFilter = $request->query('empresa_id', '');

        $usersWithEquipos = User::query()
            ->when($empresaId, fn (Builder $query) => $query->where('empresa_id', $empresaId))
            ->whereHas('equipoAsignaciones')
            ->with([
                'equipoAsignaciones.equipo.imagenes',
                'equipoAsignaciones.equipo.empresa',
            ])
            ->orderBy('name')
            ->get();

        $pendingForMe = EquipoAsignacionSolicitud::query()
            ->with(['creador:id,name,last_name,email', 'items.equipo:id,nombre,codigo', 'items.fromUser:id,name,last_name'])
            ->where('to_user_id', auth()->id())
            ->where('estado', 'pendiente')
            ->whereIn('workflow_step', ['pendiente_usuario', 'correccion_usuario'])
            ->orderByDesc('id')
            ->get();

        return view('admin.asignar.index', [
            'empresas' => $empresas,
            'q' => $q,
            'empresaId' => $empresaIdFilter,
            'usersWithEquipos' => $usersWithEquipos,
            'pendingForMe' => $pendingForMe,
        ]);
    }

    public function misCosas(): View
    {
        [$misEquipos, $misActasResumen] = $this->buildMisCosasData((int) auth()->id());
        return view('admin.asignar.mis-cosas', compact('misEquipos', 'misActasResumen'));
    }

    /**
     * Listado de usuarios para el filtro (foto, nombre). Opcional búsqueda por nombre/email.
     */
    public function users(Request $request)
    {
        $this->assertCanViewModule('asignar');
        $empresaId = $this->resolveEmpresaId();
        $q = trim((string) $request->query('q', ''));
        $users = User::query()
            ->where('active', true)
            ->when($empresaId, fn (Builder $query) => $query->where('empresa_id', $empresaId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qry) use ($q) {
                    $qry->where('name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'last_name', 'email', 'photo']);

        return response()->json([
            'users' => $users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'last_name' => $u->last_name,
                'full_name' => trim($u->name . ' ' . ($u->last_name ?? '')),
                'email' => $u->email,
                'photo_url' => $u->photo ? asset('storage/' . $u->photo) : null,
            ]),
        ]);
    }

    /**
     * Listado de equipos disponibles (no asignados). Filtro por empresa. Incluye dos imágenes principales y nombre/descripción.
     */
    public function equipos(Request $request)
    {
        $this->assertCanViewModule('asignar');
        $empresaId = $request->query('empresa_id', '');
        $q = trim((string) $request->query('q', ''));

        $equipos = Equipo::query()
            ->where('activo', true)
            ->sinTipoPapeleria()
            ->whereDoesntHave('asignacion')
            ->when($empresaId !== '' && is_numeric($empresaId), fn ($query) => $query->where('empresa_id', (int) $empresaId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qry) use ($q) {
                    $qry->where('nombre', 'like', "%{$q}%")
                        ->orWhere('descripcion', 'like', "%{$q}%")
                        ->orWhere('codigo', 'like', "%{$q}%");
                });
            })
            ->with(['imagenes' => fn ($q) => $q->orderBy('id')->limit(2), 'empresa'])
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'equipos' => $equipos->map(function ($e) {
                $imgs = $e->imagenes->take(2);
                return [
                    'id' => $e->id,
                    'nombre' => $e->nombre,
                    'descripcion' => $e->descripcion ?? '',
                    'codigo' => $e->codigo ?? '',
                    'empresa_nombre' => $e->empresa?->nombre ?? '—',
                    'imagenes' => $imgs->map(fn ($i) => asset('storage/' . $i->path))->values()->all(),
                ];
            }),
        ]);
    }

    /**
     * Flujo directo legado (se mantiene para compatibilidad, sin formato previo).
     */
    public function store(Request $request)
    {
        $this->assertCanEditModule('asignar');
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'equipo_ids' => 'required|array',
            'equipo_ids.*' => 'integer|exists:equipos,id',
        ]);

        $userId = (int) $request->user_id;
        $equipoIds = array_map('intval', (array) $request->equipo_ids);
        $equipoIds = array_unique($equipoIds);

        $assigned = [];
        $skipped = [];

        foreach ($equipoIds as $equipoId) {
            $exists = EquipoAsignacion::where('equipo_id', $equipoId)->exists();
            if ($exists) {
                $skipped[] = $equipoId;
                continue;
            }
            EquipoAsignacion::create([
                'user_id' => $userId,
                'equipo_id' => $equipoId,
            ]);
            $assigned[] = $equipoId;
        }

        return response()->json([
            'success' => true,
            'message' => count($assigned) > 0
                ? 'Equipos asignados correctamente.'
                : (count($skipped) > 0 ? 'Esos equipos ya están asignados.' : 'Nada que asignar.'),
            'assigned' => $assigned,
            'skipped' => $skipped,
        ]);
    }

    public function preview(Request $request): View
    {
        $this->assertCanEditModule('asignar');
        $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'equipo_ids' => ['required', 'array', 'min:1'],
            'equipo_ids.*' => ['required', 'integer', 'exists:equipos,id'],
        ]);

        $toUser = User::query()->findOrFail((int) $request->input('to_user_id'));
        $empresaId = $this->resolveEmpresaId();
        if ($empresaId && (int) $toUser->empresa_id !== (int) $empresaId) {
            return redirect()->route('asignar.index')->with('error', 'Solo puedes enviar solicitudes a usuarios de la empresa activa.');
        }
        $equipoIds = collect((array) $request->input('equipo_ids'))->map(fn ($v) => (int) $v)->unique()->values()->all();

        $equipos = Equipo::query()
            ->with(['asignacion.user:id,name,last_name', 'tipoEquipo:id,nombre,alias', 'claseEquipo:id,nombre,alias'])
            ->whereIn('id', $equipoIds)
            ->get();

        if ($equipos->isEmpty()) {
            return redirect()->route('asignar.index')->with('error', 'No hay equipos válidos seleccionados.');
        }

        $renderedHtml = $this->buildAssignmentTemplateHtml($toUser, $equipos);

        return view('admin.asignar.preview', [
            'toUser' => $toUser,
            'equipos' => $equipos,
            'renderedHtml' => $renderedHtml,
            'equipoIds' => $equipos->pluck('id')->all(),
        ]);
    }

    public function submitSolicitud(Request $request)
    {
        $this->assertCanEditModule('asignar');
        $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'equipo_ids' => ['required', 'array', 'min:1'],
            'equipo_ids.*' => ['required', 'integer', 'exists:equipos,id'],
            'edited_html' => ['required', 'string', 'max:500000'],
        ]);

        $toUserId = (int) $request->input('to_user_id');
        $empresaId = $this->resolveEmpresaId();
        if ($empresaId) {
            $targetExistsInEmpresa = User::query()->where('id', $toUserId)->where('empresa_id', $empresaId)->exists();
            if (!$targetExistsInEmpresa) {
                return redirect()->route('asignar.index')->with('error', 'El usuario destino no pertenece a la empresa activa.');
            }
        }
        $equipoIds = collect((array) $request->input('equipo_ids'))->map(fn ($v) => (int) $v)->unique()->values()->all();

        $equipos = Equipo::query()
            ->with('asignacion.user:id,name,last_name')
            ->whereIn('id', $equipoIds)
            ->get();

        if ($equipos->isEmpty()) {
            return redirect()->route('asignar.index')->with('error', 'No se encontraron equipos para enviar la solicitud.');
        }

        DB::transaction(function () use ($toUserId, $equipos, $request) {
            $solicitud = EquipoAsignacionSolicitud::query()->create([
                'created_by' => auth()->id(),
                'to_user_id' => $toUserId,
                'tipo' => 'entrega',
                'estado' => 'pendiente',
                'workflow_step' => 'pendiente_usuario',
                'html_formulario' => HtmlSanitizer::sanitizeTemplateHtml((string) $request->input('edited_html')),
            ]);

            foreach ($equipos as $equipo) {
                EquipoAsignacionSolicitudItem::query()->create([
                    'solicitud_id' => $solicitud->id,
                    'equipo_id' => $equipo->id,
                    'from_user_id' => $equipo->asignacion?->user_id,
                ]);
            }
        });

        return redirect()->route('asignar.index')->with('success', 'Solicitud enviada al usuario. Debe aceptarla para completar la asignación/traspaso.');
    }

    public function responderSolicitud(Request $request, int $solicitudId)
    {
        $request->validate([
            'accion' => ['required', 'in:rechazar'],
        ]);

        $solicitud = EquipoAsignacionSolicitud::query()
            ->with('items.equipo')
            ->where('id', $solicitudId)
            ->where('to_user_id', auth()->id())
            ->where('estado', 'pendiente')
            ->firstOrFail();

        $solicitud->update([
            'estado' => 'rechazada',
            'respondido_at' => now(),
        ]);

        return redirect()->route('asignar.index')->with('success', 'Solicitud rechazada.');
    }

    public function showAceptarSolicitud(int $solicitudId): View
    {
        $solicitud = EquipoAsignacionSolicitud::query()
            ->with(['creador:id,name,last_name,email', 'items.equipo:id,nombre,codigo'])
            ->where('id', $solicitudId)
            ->where('to_user_id', auth()->id())
            ->where('estado', 'pendiente')
            ->firstOrFail();

        $firmaUrl = auth()->user()?->signature ? asset('storage/' . auth()->user()->signature) : null;

        return view('admin.asignar.aceptar', compact('solicitud', 'firmaUrl'));
    }

    public function aceptarSolicitudFirmada(Request $request, int $solicitudId)
    {
        $request->validate([
            'signed_html' => ['required', 'string'],
            'firma_confirmada' => ['required', 'in:1'],
        ]);

        $solicitud = EquipoAsignacionSolicitud::query()
            ->with('items.equipo')
            ->where('id', $solicitudId)
            ->where('to_user_id', auth()->id())
            ->where('estado', 'pendiente')
            ->firstOrFail();

        if (empty(auth()->user()?->signature)) {
            return redirect()->route('profile.show')->with('error', 'Debes subir tu firma en el perfil antes de aceptar una asignación.');
        }

        DB::transaction(function () use ($solicitud, $request) {
            if (($solicitud->tipo ?? 'entrega') === 'devolucion') {
                // En devolución: usuario completa y firma, los equipos se liberan y queda revisión documental del admin.
                foreach ($solicitud->items as $item) {
                    EquipoAsignacion::query()->where('equipo_id', $item->equipo_id)->delete();
                }

                $solicitud->update([
                    'estado' => 'pendiente',
                    'workflow_step' => 'pendiente_revision_admin',
                    'respondido_at' => now(),
                    'html_formulario' => HtmlSanitizer::sanitizeTemplateHtml((string) $request->input('signed_html')),
                    'comentario_revision' => null,
                ]);
            } else {
                foreach ($solicitud->items as $item) {
                    EquipoAsignacion::query()->where('equipo_id', $item->equipo_id)->delete();
                    EquipoAsignacion::query()->create([
                        'user_id' => $solicitud->to_user_id,
                        'equipo_id' => $item->equipo_id,
                    ]);
                }

                $solicitud->update([
                    'estado' => 'aceptada',
                    'workflow_step' => 'finalizada',
                    'respondido_at' => now(),
                    'html_formulario' => HtmlSanitizer::sanitizeTemplateHtml((string) $request->input('signed_html')),
                    'comentario_revision' => null,
                ]);
            }
        });

        $message = ($solicitud->tipo ?? 'entrega') === 'devolucion'
            ? 'Formato de devolución enviado con firma. Los equipos quedaron disponibles y el acta queda pendiente de revisión del administrador.'
            : 'Solicitud aceptada con firma. Los equipos quedaron asignados a tu usuario.';

        return redirect()->route('asignar.index')->with('success', $message);
    }

    public function seguimiento(): View
    {
        $this->assertCanViewModule('asignar');

        $solicitudes = EquipoAsignacionSolicitud::query()
            ->with([
                'destinatario:id,name,last_name,email,photo',
                'items.equipo:id,nombre,codigo',
                'items.equipo.asignacion.user:id,name,last_name',
            ])
            ->where('created_by', auth()->id())
            ->orderByDesc('id')
            ->get();

        return view('admin.asignar.seguimiento', compact('solicitudes'));
    }

    public function verFormatoSeguimiento(int $solicitudId): View
    {
        $this->assertCanViewModule('asignar');

        $solicitud = EquipoAsignacionSolicitud::query()
            ->with(['destinatario:id,name,last_name,email', 'items.equipo:id,nombre,codigo'])
            ->where('id', $solicitudId)
            ->where('created_by', auth()->id())
            ->firstOrFail();

        $firmaAdminUrl = auth()->user()?->signature ? asset('storage/' . auth()->user()->signature) : null;

        return view('admin.asignar.ver-formato', compact('solicitud', 'firmaAdminUrl'));
    }

    public function guardarFormatoSeguimiento(Request $request, int $solicitudId)
    {
        $this->assertCanEditModule('asignar');

        $request->validate([
            'edited_html' => ['required', 'string', 'max:500000'],
        ]);

        $solicitud = EquipoAsignacionSolicitud::query()
            ->where('id', $solicitudId)
            ->where('created_by', auth()->id())
            ->firstOrFail();

        if (($solicitud->tipo ?? 'entrega') === 'devolucion' && ($solicitud->workflow_step ?? '') === 'pendiente_revision_admin') {
            return redirect()->route('asignar.seguimiento.formato', $solicitud->id)
                ->with('error', 'Este formato ya fue enviado por el usuario y quedó bloqueado para edición del administrador.');
        }

        $solicitud->update([
            'html_formulario' => HtmlSanitizer::sanitizeTemplateHtml((string) $request->input('edited_html')),
        ]);

        return redirect()->route('asignar.seguimiento.formato', $solicitud->id)
            ->with('success', 'Formato actualizado correctamente.');
    }

    public function devolucionForm(int $solicitudId): View
    {
        $this->assertCanViewModule('asignar');

        $solicitud = EquipoAsignacionSolicitud::query()
            ->with(['destinatario:id,name,last_name,email', 'items.equipo:id,nombre,codigo,descripcion'])
            ->where('id', $solicitudId)
            ->where('created_by', auth()->id())
            ->where('estado', 'aceptada')
            ->firstOrFail();

        $equipos = $solicitud->items->pluck('equipo')->filter()->values();
        // En devolución, reutilizamos el formato previamente diligenciado para completarlo nuevamente.
        $renderedHtml = (string) ($solicitud->html_formulario ?: '');
        if (trim($renderedHtml) === '') {
            $renderedHtml = '<h3>ACTA DE DEVOLUCION DE EQUIPOS</h3>'
                . '<p>Usuario que devuelve: ' . e(trim(($solicitud->destinatario?->name ?? '') . ' ' . ($solicitud->destinatario?->last_name ?? ''))) . '</p>'
                . '<p>Fecha: ' . now()->format('Y-m-d') . '</p>'
                . '<p>Total equipos: ' . $equipos->count() . '</p>'
                . '<p>Observaciones:</p><div style="min-height:80px;border:1px solid #ccc;"></div>';
        }

        $firmaAdminUrl = auth()->user()?->signature ? asset('storage/' . auth()->user()->signature) : null;

        return view('admin.asignar.devolucion', compact('solicitud', 'equipos', 'renderedHtml', 'firmaAdminUrl'));
    }

    public function submitDevolucion(Request $request, int $solicitudId)
    {
        $this->assertCanEditModule('asignar');

        $request->validate([
            'equipo_ids' => ['required', 'array', 'min:1'],
            'equipo_ids.*' => ['required', 'integer', 'exists:equipos,id'],
            'edited_html' => ['required', 'string', 'max:500000'],
            'firma_admin_confirmada' => ['required', 'in:1'],
        ]);

        $base = EquipoAsignacionSolicitud::query()
            ->with(['destinatario', 'items.equipo'])
            ->where('id', $solicitudId)
            ->where('created_by', auth()->id())
            ->where('estado', 'aceptada')
            ->firstOrFail();

        if (empty(auth()->user()?->signature)) {
            return redirect()->route('profile.show')->with('error', 'Debes tener firma en el perfil para enviar una devolución.');
        }

        $selectedIds = collect((array) $request->input('equipo_ids'))->map(fn ($v) => (int) $v)->unique()->values();
        $equiposPermitidos = $base->items->pluck('equipo_id')->map(fn ($v) => (int) $v)->all();
        $validIds = $selectedIds->filter(fn ($id) => in_array($id, $equiposPermitidos, true))->values();

        if ($validIds->isEmpty()) {
            return redirect()->route('asignar.seguimiento')->with('error', 'No seleccionaste equipos válidos para devolución.');
        }

        DB::transaction(function () use ($base, $request, $validIds) {
            $sol = EquipoAsignacionSolicitud::query()->create([
                'created_by' => auth()->id(),
                'to_user_id' => $base->to_user_id,
                'tipo' => 'devolucion',
                'estado' => 'pendiente',
                'workflow_step' => 'pendiente_usuario',
                'html_formulario' => HtmlSanitizer::sanitizeTemplateHtml((string) $request->input('edited_html')),
            ]);

            foreach ($validIds as $equipoId) {
                EquipoAsignacionSolicitudItem::query()->create([
                    'solicitud_id' => $sol->id,
                    'equipo_id' => (int) $equipoId,
                    'from_user_id' => $base->to_user_id,
                ]);
            }
        });

        return redirect()->route('asignar.seguimiento')->with('success', 'Acta de devolución enviada. El usuario debe aceptarla para liberar los equipos.');
    }

    public function resolverRevisionDevolucion(Request $request, int $solicitudId)
    {
        $this->assertCanEditModule('asignar');

        $request->validate([
            'accion' => ['required', 'in:aceptar,corregir'],
            'comentario_revision' => ['nullable', 'string', 'max:1500'],
        ]);

        $sol = EquipoAsignacionSolicitud::query()
            ->with('items')
            ->where('id', $solicitudId)
            ->where('created_by', auth()->id())
            ->where('tipo', 'devolucion')
            ->where('estado', 'pendiente')
            ->where('workflow_step', 'pendiente_revision_admin')
            ->firstOrFail();

        if ($request->input('accion') === 'corregir') {
            $sol->update([
                'workflow_step' => 'correccion_usuario',
                'comentario_revision' => trim((string) $request->input('comentario_revision', '')) ?: 'Por favor completa correctamente el formato y vuelve a enviarlo.',
            ]);
            return redirect()->route('asignar.seguimiento')->with('success', 'Se devolvió al usuario para corrección.');
        }

        DB::transaction(function () use ($sol) {
            foreach ($sol->items as $item) {
                EquipoAsignacion::query()->where('equipo_id', $item->equipo_id)->delete();
            }

            $sol->update([
                'estado' => 'aceptada',
                'workflow_step' => 'finalizada',
                'respondido_at' => now(),
                'comentario_revision' => null,
            ]);
        });

        return redirect()->route('asignar.seguimiento')->with('success', 'Devolución aceptada. Los equipos quedaron disponibles nuevamente.');
    }

    /**
     * Quitar asignación de un equipo a un usuario (dejar equipo disponible de nuevo).
     */
    public function destroy(Request $request, int $asignacion)
    {
        $this->assertCanEditModule('asignar');
        $a = EquipoAsignacion::find($asignacion);
        if (!$a) {
            return response()->json(['success' => false, 'message' => 'Asignación no encontrada.'], 404);
        }
        $a->delete();
        return response()->json(['success' => true, 'message' => 'Asignación eliminada.']);
    }

    private function buildAssignmentTemplateHtml(User $toUser, $equipos): string
    {
        $templatePath = $this->resolveAssignmentTemplatePath();
        if ($templatePath && is_file($templatePath)) {
            try {
                $html = $this->excelToHtml($templatePath);
                $html = str_replace(
                    ['{{USUARIO_DESTINO}}', '{{FECHA}}', '{{TOTAL_EQUIPOS}}'],
                    [trim(($toUser->name ?? '') . ' ' . ($toUser->last_name ?? '')), now()->format('Y-m-d'), (string) $equipos->count()],
                    $html
                );
                return $html;
            } catch (\Throwable $e) {
                // Fallback a HTML nativo.
            }
        }

        return '<h3>ASIGNACION DE EQUIPOS</h3><p>Usuario destino: ' . e(trim(($toUser->name ?? '') . ' ' . ($toUser->last_name ?? ''))) . '</p><p>Fecha: ' . now()->format('Y-m-d') . '</p><p>Total equipos: ' . $equipos->count() . '</p><p>Observaciones:</p><div style="min-height:80px;border:1px solid #ccc;"></div>';
    }

    private function resolveAssignmentTemplatePath(): ?string
    {
        $storageRelative = 'asignacion/plantilla_asignacion.xlsx';
        if (Storage::disk('public')->exists($storageRelative)) {
            return Storage::disk('public')->path($storageRelative);
        }

        $desktopPath = 'C:\Users\Daniel\Desktop\ASIGNACION DE EQUIPO 1 (1).xlsx';
        if (is_file($desktopPath)) {
            return $desktopPath;
        }

        return null;
    }

    private function excelToHtml(string $absolutePath): string
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $writer = new SpreadsheetHtmlWriter($spreadsheet);
        $writer->setSheetIndex(0);
        $writer->setPreCalculateFormulas(false);
        $writer->setImagesRoot('');

        ob_start();
        $writer->save('php://output');
        $html = (string) ob_get_clean();

        return $html;
    }

    private function resolveEmpresaId(): ?int
    {
        return EmpresaContext::empresaId() ?? auth()->user()?->empresa_id;
    }

    private function buildMisCosasData(int $userId): array
    {
        $devolucionesPendRevision = EquipoAsignacionSolicitud::query()
            ->with('items:id,solicitud_id,equipo_id')
            ->where('to_user_id', $userId)
            ->where('tipo', 'devolucion')
            ->where('estado', 'pendiente')
            ->where('workflow_step', 'pendiente_revision_admin')
            ->get();

        // Si existe devolución ya diligenciada y pendiente de revisión admin, ese equipo no debe verse "en uso".
        $equiposNoEnUsoIds = $devolucionesPendRevision
            ->flatMap(fn ($s) => $s->items->pluck('equipo_id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $misEquipos = EquipoAsignacion::query()
            ->with(['equipo.imagenes', 'equipo.tipoEquipo', 'equipo.claseEquipo'])
            ->where('user_id', $userId)
            ->when($equiposNoEnUsoIds->isNotEmpty(), fn ($q) => $q->whereNotIn('equipo_id', $equiposNoEnUsoIds->all()))
            ->latest('id')
            ->get();

        $misActas = EquipoAsignacionSolicitud::query()
            ->with(['creador:id,name,last_name', 'items.equipo:id,nombre,codigo'])
            ->where('to_user_id', $userId)
            ->where('estado', 'aceptada')
            ->orderByDesc('respondido_at')
            ->orderByDesc('id')
            ->get();

        $devolucionesUsuario = EquipoAsignacionSolicitud::query()
            ->with('items:id,solicitud_id,equipo_id')
            ->where('to_user_id', $userId)
            ->where('tipo', 'devolucion')
            ->whereIn('estado', ['pendiente', 'aceptada'])
            ->get();

        $equiposEnFlujoDevolucionIds = $devolucionesUsuario
            ->flatMap(fn ($s) => $s->items->pluck('equipo_id'))
            ->map(fn ($id) => (int) $id)
            ->unique();

        $misActasResumen = collect();
        foreach ($misActas as $acta) {
            if (($acta->tipo ?? 'entrega') !== 'entrega') {
                continue;
            }

            $entregaIds = $acta->items->pluck('equipo_id')->map(fn ($id) => (int) $id);
            $enDevolucion = $entregaIds->filter(fn ($id) => $equiposEnFlujoDevolucionIds->contains($id))->count();
            $pendientes = max(0, $entregaIds->count() - $enDevolucion);

            if ($enDevolucion > 0) {
                $misActasResumen->push([
                    'id' => $acta->id,
                    'tipo' => 'ENTREGA',
                    'estado_label' => 'Terminada',
                    'estado_color' => 'green',
                    'count' => $enDevolucion,
                    'creador' => trim(($acta->creador?->name ?? '') . ' ' . ($acta->creador?->last_name ?? '')),
                    'fecha' => $acta->respondido_at,
                    'html' => $acta->html_formulario,
                ]);
            }

            if ($pendientes > 0) {
                $misActasResumen->push([
                    'id' => $acta->id,
                    'tipo' => 'ENTREGA',
                    'estado_label' => 'Pendiente',
                    'estado_color' => 'yellow',
                    'count' => $pendientes,
                    'creador' => trim(($acta->creador?->name ?? '') . ' ' . ($acta->creador?->last_name ?? '')),
                    'fecha' => $acta->respondido_at,
                    'html' => $acta->html_formulario,
                ]);
            }
        }

        return [$misEquipos, $misActasResumen];
    }
}
