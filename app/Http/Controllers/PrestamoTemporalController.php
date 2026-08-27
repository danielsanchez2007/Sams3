<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\AvisoEmpresa;
use App\Models\PrestamoTemporal;
use App\Models\PrestamoTemporalItem;
use App\Models\User;
use App\Services\EmpresaContext;
use App\Services\VistaOficina;
use App\Support\HtmlSanitizer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Html as SpreadsheetHtmlWriter;

class PrestamoTemporalController extends Controller
{
    public function index(Request $request): View
    {
        $this->assertCanViewModule('prestamos_temporales');
        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;

        $q = trim((string) $request->query('q', ''));
        $estado = trim((string) $request->query('estado', ''));

        // Solo quien puede prestar necesita listas pesadas; evita timeout / pantalla en negro al navegar.
        $users = collect();
        $equipos = collect();
        if ($this->canGestionarPrestamos()) {
            $users = User::query()
                ->where('active', true)
                ->where('empresa_id', $empresaId)
                ->orderBy('name')
                ->get(['id', 'name', 'last_name', 'email']);

            $equipos = Equipo::query()
                ->select(['id', 'nombre', 'codigo', 'empresa_id', 'tipo_equipo_id', 'clase_equipo_id'])
                ->where('activo', true)
                ->when($empresaId, fn ($qq) => $qq->where('empresa_id', $empresaId))
                ->with([
                    'tipoEquipo:id,nombre,alias',
                    'claseEquipo:id,nombre,alias',
                    'imagenes' => fn ($q) => $q->select(['id', 'equipo_id', 'tipo', 'path']),
                ])
                ->withExists([
                    'prestamoTemporalItems as tiene_prestamo_temporal_activo' => function ($sub) {
                        $sub->whereHas('prestamo', fn ($p) => $p->whereIn('estado', ['activo', 'pendiente_revision']));
                    },
                ])
                ->withExists('asignacion')
                ->orderBy('nombre')
                ->get();
        }

        $prestamosRecibidos = collect();
        $prestamosPendRevision = collect();

        if ($this->canGestionarPrestamos()) {
            $prestamosPendRevision = PrestamoTemporal::query()
                ->with([
                    'destinatario:id,name,last_name,email,photo',
                ])
                ->withCount('items')
                ->where('created_by', auth()->id())
                ->whereIn('estado', ['activo', 'pendiente_revision'])
                ->when($q !== '', function ($qq) use ($q) {
                    $qq->where(function ($sub) use ($q) {
                        $sub->whereHas('destinatario', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
                            ->orWhereHas('items.equipo', fn ($e) => $e->where('nombre', 'like', "%{$q}%")->orWhere('codigo', 'like', "%{$q}%"));
                    });
                })
                ->orderByRaw("CASE WHEN estado = 'pendiente_revision' THEN 0 ELSE 1 END")
                ->latest('fecha_fin')
                ->get();
        }

        $prestamosRecibidos = PrestamoTemporal::query()
            ->with(['creador:id,name,last_name,email,photo'])
            ->withCount('items')
            ->where('to_user_id', auth()->id())
            ->whereIn('estado', ['activo', 'pendiente_revision'])
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($sub) use ($q) {
                    $sub->whereHas('creador', fn ($u) => $u->where('name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
                        ->orWhereHas('items.equipo', fn ($e) => $e->where('nombre', 'like', "%{$q}%")->orWhere('codigo', 'like', "%{$q}%"));
                });
            })
            ->latest('id')
            ->get();

        return view('admin.prestamos-temporales.index', compact(
            'users',
            'equipos',
            'prestamosRecibidos',
            'prestamosPendRevision'
            , 'q', 'estado'
        ));
    }

    public function store(Request $request)
    {
        $this->assertCanEditModule('prestamos_temporales');
        abort_unless($this->canGestionarPrestamos(), 403);

        $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'fecha_salida' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_salida'],
            'equipo_ids' => ['required', 'array', 'min:1', 'max:21'],
            'equipo_ids.*' => ['required', 'integer', 'exists:equipos,id'],
            'edited_html' => ['required', 'string', 'max:500000'],
        ]);

        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        $toUser = User::query()->findOrFail((int) $request->input('to_user_id'));
        if ($empresaId && (int) $toUser->empresa_id !== (int) $empresaId) {
            return back()->with('error', 'El usuario seleccionado no pertenece a la empresa activa.');
        }

        $equipoIds = collect((array) $request->input('equipo_ids'))->map(fn ($v) => (int) $v)->unique()->values();
        $equipos = Equipo::query()
            ->with('prestamoTemporalItems.prestamo')
            ->whereIn('id', $equipoIds)
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->get();

        if ($equipos->isEmpty()) {
            return back()->with('error', 'No hay equipos válidos para prestar.');
        }

        $activos = $equipos->filter(function (Equipo $equipo) {
            return $equipo->prestamoTemporalItems->contains(function (PrestamoTemporalItem $item) {
                return in_array($item->prestamo?->estado, ['activo', 'pendiente_revision'], true);
            });
        });
        if ($activos->isNotEmpty()) {
            return back()->with('error', 'Uno o más equipos ya tienen préstamo temporal activo.');
        }

        DB::transaction(function () use ($request, $equipos) {
            $prestamo = PrestamoTemporal::query()->create([
                'created_by' => auth()->id(),
                'to_user_id' => (int) $request->input('to_user_id'),
                'fecha_salida' => $request->input('fecha_salida'),
                'fecha_fin' => $request->input('fecha_fin'),
                'estado' => 'activo',
                'html_formulario' => HtmlSanitizer::sanitizeTemplateHtml((string) $request->input('edited_html')),
            ]);

            foreach ($equipos as $equipo) {
                PrestamoTemporalItem::query()->create([
                    'prestamo_id' => $prestamo->id,
                    'equipo_id' => $equipo->id,
                ]);
            }
        });

        return redirect()->route('prestamos-temporales.index')->with('success', 'Préstamo temporal enviado correctamente.');
    }

    public function formDevolver(Request $request, int $prestamoId): View
    {
        $prestamo = PrestamoTemporal::query()
            ->with(['creador:id,name,last_name,email', 'items.equipo:id,nombre,codigo'])
            ->where('id', $prestamoId)
            ->where('to_user_id', auth()->id())
            ->where('estado', 'activo')
            ->firstOrFail();

        $anticipada = (bool) $request->boolean('anticipada');
        $firmaUrl = auth()->user()?->signature ? asset('storage/' . auth()->user()->signature) : null;

        return view('admin.prestamos-temporales.devolver', compact('prestamo', 'firmaUrl', 'anticipada'));
    }

    public function devolver(Request $request, int $prestamoId)
    {
        $prestamo = PrestamoTemporal::query()
            ->where('id', $prestamoId)
            ->where('to_user_id', auth()->id())
            ->where('estado', 'activo')
            ->firstOrFail();

        $anticipada = (bool) $request->boolean('anticipada');
        if (!$anticipada && $prestamo->fecha_fin && now()->startOfDay()->lt($prestamo->fecha_fin->copy()->startOfDay())) {
            return redirect()->route('prestamos-temporales.index')
                ->with('error', 'La devolución solo se habilita en la fecha programada (' . $prestamo->fecha_fin->format('Y-m-d') . ') o después.');
        }

        $request->validate([
            'signed_html' => ['required', 'string'],
            'firma_confirmada' => ['required', 'in:1'],
            'anticipada' => ['nullable', 'in:0,1'],
        ]);

        if (empty(auth()->user()?->signature)) {
            return redirect()->route('profile.show')->with('error', 'Debes subir tu firma en tu perfil para devolver un prestamo.');
        }

        $prestamo->update([
            'estado' => 'pendiente_revision',
            'devuelto_at' => now(),
            'html_formulario' => HtmlSanitizer::sanitizeTemplateHtml((string) $request->input('signed_html')),
        ]);

        return redirect()->route('prestamos-temporales.index')->with('success', 'Solicitud de devolución enviada al administrador.');
    }

    public function revisar(Request $request, int $prestamoId)
    {
        abort_unless($this->canGestionarPrestamos(), 403);

        $prestamo = PrestamoTemporal::query()
            ->with([
                'destinatario:id,name,last_name,email',
                'items.equipo:id,nombre,codigo',
                'items.equipo.imagenes:id,equipo_id,tipo,path',
            ])
            ->where('id', $prestamoId)
            ->where('created_by', auth()->id())
            ->whereIn('estado', ['activo', 'pendiente_revision'])
            ->firstOrFail();

        $isBeforeDate = $prestamo->fecha_fin && now()->startOfDay()->lt($prestamo->fecha_fin->copy()->startOfDay());
        $anticipada = (bool) $request->boolean('anticipada');
        $requiresAuth = $anticipada || $isBeforeDate || $prestamo->estado === 'activo';

        $rules = [
            'reviewed_html' => ['nullable', 'string'],
        ];
        if ($requiresAuth) {
            $rules['credential_user_id'] = ['required', 'integer', 'exists:users,id'];
            $rules['credential_password'] = ['required', 'string', 'min:4'];
        }
        foreach ($prestamo->items as $item) {
            $rules["revision.{$item->id}.estado"] = ['required', 'in:si,no,novedad'];
            $rules["revision.{$item->id}.novedad"] = ['nullable', 'string', 'max:1000'];
        }
        $data = $request->validate($rules);

        if ($requiresAuth) {
            $credentialUserId = (int) data_get($data, 'credential_user_id');
            $allowedIds = [auth()->id(), (int) $prestamo->to_user_id];
            if (!in_array($credentialUserId, $allowedIds, true)) {
                return back()->withErrors([
                    'credential_user_id' => 'Solo se acepta contraseña del administrador actual o del usuario destinatario del préstamo.',
                ])->withInput();
            }

            $credentialUser = User::query()->find($credentialUserId);
            $password = (string) data_get($data, 'credential_password');
            if (!$credentialUser || !Hash::check($password, (string) $credentialUser->password)) {
                return back()->withErrors([
                    'credential_password' => 'La contraseña ingresada no es válida para autorizar la devolución anticipada.',
                ])->withInput();
            }
        }

        DB::transaction(function () use ($prestamo, $data) {
            $avisos = [];
            foreach ($prestamo->items as $item) {
                $estado = (string) data_get($data, "revision.{$item->id}.estado", 'si');
                $novedad = trim((string) data_get($data, "revision.{$item->id}.novedad", ''));
                $item->update([
                    'revision_estado' => $estado,
                    'revision_novedad' => in_array($estado, ['novedad', 'no'], true) ? $novedad : null,
                    'revision_by' => auth()->id(),
                    'revision_at' => now(),
                ]);

                if (in_array($estado, ['no', 'novedad'], true)) {
                    $equipo = $item->equipo;
                    $img = $equipo?->imagenes?->firstWhere('tipo', 'general')
                        ?: $equipo?->imagenes?->firstWhere('tipo', 'etiqueta');
                    $imgUrl = $img?->path ? asset('storage/' . $img->path) : 'Sin imagen';
                    $tipoNovedad = $estado === 'no' ? 'MALO' : 'NOVEDAD';
                    $mensaje = "Revisión préstamo temporal #{$prestamo->id} - {$tipoNovedad}\n"
                        . 'Fecha revisión: ' . now()->format('Y-m-d H:i') . "\n"
                        . 'Equipo: ' . ($equipo?->codigo ?? 'N/A') . ' - ' . ($equipo?->nombre ?? 'N/A') . "\n"
                        . 'Usuario préstamo: ' . trim((string) (($prestamo->destinatario?->name ?? '') . ' ' . ($prestamo->destinatario?->last_name ?? ''))) . "\n"
                        . 'Revisor: ' . trim((string) ((auth()->user()?->name ?? '') . ' ' . (auth()->user()?->last_name ?? ''))) . "\n"
                        . 'Detalle: ' . ($novedad !== '' ? $novedad : 'Sin detalle') . "\n"
                        . 'Foto equipo: ' . $imgUrl;
                    $avisos[] = [
                        'tipo' => 'observacion',
                        'mensaje' => $mensaje,
                        'created_by' => auth()->id(),
                    ];
                }
            }

            $prestamo->update([
                'estado' => 'finalizado',
                'devuelto_at' => $prestamo->devuelto_at ?: now(),
                'finalizado_at' => now(),
                'html_formulario' => HtmlSanitizer::sanitizeTemplateHtml((string) data_get($data, 'reviewed_html', $prestamo->html_formulario)),
            ]);

            $empresaDestino = (int) (EmpresaContext::empresaId() ?: auth()->user()?->empresa_id ?: 0);
            if ($empresaDestino > 0) {
                foreach ($avisos as $aviso) {
                    AvisoEmpresa::query()->create([
                        'empresa_id' => $empresaDestino,
                        'tipo' => $aviso['tipo'],
                        'mensaje' => $aviso['mensaje'],
                        'created_by' => $aviso['created_by'],
                    ]);
                }
            }
        });

        return redirect()->route('prestamos-temporales.index')->with('success', 'Revisión de devolución completada.');
    }

    public function formRevisar(Request $request, int $prestamoId): View
    {
        abort_unless($this->canGestionarPrestamos(), 403);

        $prestamo = PrestamoTemporal::query()
            ->with([
                'destinatario:id,name,last_name,email,photo',
                'items.equipo:id,nombre,codigo',
                'items.equipo.imagenes:id,equipo_id,tipo,path',
            ])
            ->where('id', $prestamoId)
            ->where('created_by', auth()->id())
            ->whereIn('estado', ['activo', 'pendiente_revision'])
            ->firstOrFail();

        $isBeforeDate = $prestamo->fecha_fin && now()->startOfDay()->lt($prestamo->fecha_fin->copy()->startOfDay());
        $anticipada = (bool) $request->boolean('anticipada');
        if ($isBeforeDate && !$anticipada) {
            abort(403, 'La recepción de devolución se habilita en la fecha programada.');
        }

        return view('admin.prestamos-temporales.revisar', [
            'prestamo' => $prestamo,
            'renderedHtml' => (string) $prestamo->html_formulario,
            'anticipada' => $anticipada || $prestamo->estado === 'activo',
            'isBeforeDate' => $isBeforeDate,
        ]);
    }

    public function preview(Request $request): View
    {
        $this->assertCanEditModule('prestamos_temporales');
        abort_unless($this->canGestionarPrestamos(), 403);

        $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'fecha_salida' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_salida'],
            'equipo_ids' => ['required', 'array', 'min:1', 'max:21'],
            'equipo_ids.*' => ['required', 'integer', 'exists:equipos,id'],
        ]);

        $empresaId = EmpresaContext::empresaId() ?: auth()->user()?->empresa_id;
        $toUser = User::query()->findOrFail((int) $request->input('to_user_id'));
        if ($empresaId && (int) $toUser->empresa_id !== (int) $empresaId) {
            abort(422, 'El usuario destino no pertenece a la empresa activa.');
        }
        $prestador = auth()->user();
        $equipoIds = collect((array) $request->input('equipo_ids'))->map(fn ($v) => (int) $v)->unique()->values()->all();
        $equipos = Equipo::query()
            ->whereIn('id', $equipoIds)
            ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);
        $fechaInicio = Carbon::parse($request->input('fecha_salida'))->startOfDay();
        $fechaFin = Carbon::parse($request->input('fecha_fin'))->startOfDay();
        $renderedHtml = $this->buildTemplateHtml($toUser, $prestador, $equipos, $fechaInicio, $fechaFin);

        return view('admin.prestamos-temporales.preview', [
            'toUser' => $toUser,
            'prestador' => $prestador,
            'equipos' => $equipos,
            'equipoIds' => $equipos->pluck('id')->all(),
            'fechaSalida' => $fechaInicio->format('Y-m-d'),
            'fechaFin' => $fechaFin->format('Y-m-d'),
            'fechaSalidaFmt' => $fechaInicio->format('d/m/Y'),
            'fechaFinFmt' => $fechaFin->format('d/m/Y'),
            'renderedHtml' => $renderedHtml,
        ]);
    }

    private function canGestionarPrestamos(): bool
    {
        $role = strtolower(trim(auth()->user()?->role?->name ?? ''));
        return in_array($role, ['administrador', 'adminoficina'], true);
    }

    private function buildTemplateHtml(User $toUser, ?User $prestador, $equipos, Carbon $fechaInicio, Carbon $fechaFin): string
    {
        $templatePath = $this->resolveTemplatePath();
        if ($templatePath && is_file($templatePath)) {
            try {
                $spreadsheet = IOFactory::load($templatePath);
                $sheet = $spreadsheet->getActiveSheet();
                if ($this->isCf17RemisionSheet($sheet)) {
                    $this->fillCf17Sheet($sheet, $toUser, $prestador, $equipos, $fechaInicio, $fechaFin);
                }
                $html = $this->spreadsheetToHtml($spreadsheet);
                $html = str_replace(
                    ['{{USUARIO_DESTINO}}', '{{FECHA_SALIDA}}', '{{FECHA_FIN}}', '{{TOTAL_EQUIPOS}}', '{{LISTA_EQUIPOS}}'],
                    [
                        e($this->userDisplayName($toUser)),
                        e($fechaInicio->format('Y-m-d')),
                        e($fechaFin->format('Y-m-d')),
                        (string) $equipos->count(),
                        $this->equiposListHtml($equipos),
                    ],
                    $html
                );

                return $this->appendFirmasBlock($html, $prestador, $toUser);
            } catch (\Throwable) {
            }
        }

        return $this->appendFirmasBlock(
            '<h3>Préstamo temporal de equipos</h3>'
            . '<p><strong>Destinatario:</strong> ' . e($this->userDisplayName($toUser)) . '</p>'
            . '<p><strong>Fecha inicio del préstamo:</strong> ' . e($fechaInicio->format('d/m/Y')) . '</p>'
            . '<p><strong>Fecha fin prevista:</strong> ' . e($fechaFin->format('d/m/Y')) . '</p>'
            . '<p><strong>Quien entrega:</strong> ' . e($this->userDisplayName($prestador)) . '</p>'
            . '<p><strong>Equipos (' . $equipos->count() . '):</strong></p>'
            . $this->equiposListHtml($equipos)
            . '<p>Observaciones:</p><div style="min-height:80px;border:1px solid #ccc;"></div>',
            $prestador,
            $toUser
        );
    }

    private function resolveTemplatePath(): ?string
    {
        $disk = Storage::disk('public');
        foreach ([
            'prestamos/prestamotemporal.xlsx',
            'prestamos/CF17_Remision_V2.xlsx',
            'prestamos/plantilla_prestamo.xlsx',
        ] as $relative) {
            if ($disk->exists($relative)) {
                return $disk->path($relative);
            }
        }

        $desktopPath = 'C:\Users\Daniel\Desktop\prestamotemporal.xlsx';

        return is_file($desktopPath) ? $desktopPath : null;
    }

    private function spreadsheetToHtml(Spreadsheet $spreadsheet): string
    {
        $writer = new SpreadsheetHtmlWriter($spreadsheet);
        $writer->setSheetIndex(0);
        $writer->setPreCalculateFormulas(false);
        $writer->setImagesRoot('');

        ob_start();
        $writer->save('php://output');

        return (string) ob_get_clean();
    }

    private function isCf17RemisionSheet(Worksheet $sheet): bool
    {
        $b51 = trim((string) $sheet->getCell('B51')->getValue());

        return str_contains($b51, 'Fecha de Envío');
    }

    private function fillCf17Sheet(Worksheet $sheet, User $toUser, ?User $prestador, $equipos, Carbon $fechaInicio, Carbon $fechaFin): void
    {
        $sheet->setCellValue('D8', $this->userDisplayName($toUser));
        $sheet->setCellValue('H8', $this->formatUserAddress($toUser));
        $sheet->setCellValue('D9', $this->formatUserDocument($toUser));
        $sheet->setCellValue('H9', $this->formatUserContact($toUser));

        $sheet->setCellValue('D13', $this->userDisplayName($prestador));
        $sheet->setCellValue('H13', $this->formatUserAddress($prestador));
        $sheet->setCellValue('D14', $this->formatUserDocument($prestador));
        $sheet->setCellValue('H14', $this->formatUserContact($prestador));

        $sheet->setCellValue('B41', 'Préstamo temporal de equipos (SAMS). Período: '
            . $fechaInicio->format('d/m/Y') . ' al ' . $fechaFin->format('d/m/Y') . '.');

        $sheet->setCellValue('D51', $fechaInicio->format('d/m/Y'));
        $sheet->setCellValue('H51', $fechaFin->format('d/m/Y'));

        $row = 17;
        foreach ($equipos as $equipo) {
            if ($row > 37) {
                break;
            }
            $sheet->setCellValue('C' . $row, (string) $equipo->codigo);
            $sheet->setCellValue('D' . $row, (string) $equipo->nombre);
            $sheet->setCellValue('H' . $row, (string) $equipo->codigo);
            $sheet->setCellValue('I' . $row, 1);
            $row++;
        }
    }

    private function appendFirmasBlock(string $html, ?User $prestador, User $toUser): string
    {
        $prestNombre = e($this->userDisplayName($prestador));
        $destNombre = e($this->userDisplayName($toUser));
        $prestImg = '';
        if ($prestador?->signature) {
            $url = e(asset('storage/' . $prestador->signature));
            $prestImg = '<p style="margin:4px 0 0 0;"><img src="' . $url . '" alt="Firma quien entrega" style="max-height:90px;border:1px solid #ddd;padding:4px;background:#fff"/></p>';
        }
        $destImg = '';
        if ($toUser->signature) {
            $url = e(asset('storage/' . $toUser->signature));
            $destImg = '<p style="margin:4px 0 0 0;"><img src="' . $url . '" alt="Firma quien recibe" style="max-height:90px;border:1px solid #ddd;padding:4px;background:#fff"/></p>';
        }

        $bloque = '<div class="sams-firmas-remision" style="margin-top:20px;padding:12px;border:1px solid #ccc;font-family:sans-serif;font-size:13px">'
            . '<p style="margin:0 0 8px 0;font-weight:bold">Firmas (puede mover este bloque dentro del formato si lo necesita)</p>'
            . '<table style="width:100%;border-collapse:collapse"><tr>'
            . '<td style="width:50%;vertical-align:top;padding:8px;border:1px solid #ddd">'
            . '<strong>Entrega</strong><br><span>' . $prestNombre . '</span>' . $prestImg
            . (empty($prestador?->signature) ? '<p style="color:#666;font-size:12px;margin:8px 0 0 0">Sin firma en perfil: suba la firma en el perfil del usuario que entrega o pegue la imagen aquí.</p>' : '')
            . '</td>'
            . '<td style="width:50%;vertical-align:top;padding:8px;border:1px solid #ddd">'
            . '<strong>Recibe</strong><br><span>' . $destNombre . '</span>' . $destImg
            . (empty($toUser->signature) ? '<p style="color:#666;font-size:12px;margin:8px 0 0 0">Sin firma en perfil del destinatario: puede pegar imagen o completar a mano al imprimir.</p>' : '')
            . '</td>'
            . '</tr></table></div>';

        return $html . "\n" . $bloque;
    }

    private function equiposListHtml($equipos): string
    {
        $rows = '';
        foreach ($equipos as $eq) {
            $rows .= '<tr><td>' . e((string) $eq->codigo) . '</td><td>' . e((string) $eq->nombre) . '</td><td>1</td></tr>';
        }

        return '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse"><thead><tr><th>Código</th><th>Equipo</th><th>Cant.</th></tr></thead><tbody>'
            . $rows . '</tbody></table>';
    }

    private function userDisplayName(?User $user): string
    {
        if (! $user) {
            return '';
        }

        return trim(($user->name ?? '') . ' ' . ($user->last_name ?? ''));
    }

    private function formatUserDocument(?User $user): string
    {
        if (! $user) {
            return '—';
        }
        $t = trim((string) ($user->document_type ?? ''));
        $n = trim((string) ($user->document_number ?? ''));
        if ($t !== '' && $n !== '') {
            return $t . ' ' . $n;
        }

        return $n !== '' ? $n : '—';
    }

    private function formatUserContact(?User $user): string
    {
        if (! $user) {
            return '—';
        }
        $parts = array_filter([
            trim((string) ($user->phone ?? '')),
            trim((string) ($user->email ?? '')),
        ]);

        return $parts !== [] ? implode(' · ', $parts) : '—';
    }

    private function formatUserAddress(?User $user): string
    {
        if (! $user) {
            return '—';
        }
        $a = trim((string) ($user->address ?? ''));

        return $a !== '' ? $a : '—';
    }
}

