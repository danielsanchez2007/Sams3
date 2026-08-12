<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Services\AiChat\AiChatService;
use App\Services\AiChat\SamsLiveDataReporter;
use App\Services\EmpresaContext;
use App\Services\VistaOficina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Throwable;

class GeminiChatController extends Controller
{
    public function __construct(
        private AiChatService $aiChat,
        private SamsLiveDataReporter $liveDataReporter,
    ) {
    }

    private function empresaModuloNivel(string $key): string
    {
        $empresa = EmpresaContext::empresaActiva();
        $raw = $empresa?->modulos ?? null;
        if ($raw === null || ! is_array($raw)) {
            return 'edit';
        }
        if (array_is_list($raw)) {
            return in_array($key, $raw, true) ? 'edit' : 'none';
        }
        $val = $raw[$key] ?? null;
        if ($val === null && in_array($key, ['users', 'roles', 'cargos', 'grupos', 'fabricantes'], true)) {
            $val = $raw['gestion_principal'] ?? null;
        }
        return in_array($val, ['none', 'view', 'edit'], true) ? $val : ($val ? 'edit' : 'none');
    }

    /**
     * Datos en vivo del asistente sin filtro por empresa: administrador global SAMS (sin empresa asignada)
     * o cualquier usuario cuya empresa sea la matriz Prevention World.
     */
    private function assistantUnscopedLiveData(): bool
    {
        $u = auth()->user();
        if (! $u) {
            return false;
        }
        if ($u->empresa_id === null) {
            return true;
        }
        $empresa = $u->empresa ?? Empresa::query()->find($u->empresa_id);

        return $empresa !== null && EmpresaContext::esPreventionWorld($empresa);
    }

    private function isAdminOficina(): bool
    {
        return VistaOficina::mostrarMenuOficina(auth()->user());
    }

    private function getModulosDisponiblesParaUsuario(): array
    {
        if ($this->assistantUnscopedLiveData()) {
            return ['all'];
        }
        $empresa = EmpresaContext::empresaActiva() ?? auth()->user()?->empresa;
        $modulos = $empresa?->modulos ?? null;
        $rolePerms = auth()->user()?->role?->permissions;
        if (! is_array($rolePerms)) {
            $rolePerms = [];
        }

        if ($modulos === null || ! is_array($modulos)) {
            return ! empty($rolePerms) ? array_values(array_unique($rolePerms)) : ['all'];
        }
        if (array_is_list($modulos)) {
            return $modulos;
        }
        $disponibles = [];
        $keys = [
            'users' => 'Usuarios', 'roles' => 'Roles', 'cargos' => 'Cargos', 'grupos' => 'Grupos', 'fabricantes' => 'Fabricantes',
            'equipos' => 'Equipos/Inventario', 'equipos_baja' => 'Equipos de Baja', 'material_didactico' => 'Material Didáctico', 'auditoria' => 'Auditoría',
            'hoja_vida' => 'Hoja de Vida', 'inspeccion' => 'Inspección', 'exportar' => 'Exportar', 'asignar' => 'Asignar',
            'prestamos_temporales' => 'Préstamos temporales',
            'empresa' => 'Empresa', 'sede' => 'Sede', 'bodega' => 'Bodega',
        ];
        foreach ($keys as $k => $label) {
            $lvl = $this->empresaModuloNivel($k);
            if ($lvl !== 'none') {
                $disponibles[$k] = $label;
            }
        }
        if (empty($rolePerms)) {
            return $disponibles;
        }

        $filtered = [];
        foreach ($disponibles as $k => $label) {
            if (in_array($k, $rolePerms, true)) {
                $filtered[$k] = $label;
            }
        }
        return $filtered;
    }

    private function moduleAllowed(string $module): bool
    {
        $mods = $this->getModulosDisponiblesParaUsuario();
        if ($mods === ['all']) {
            return true;
        }
        if (array_is_list($mods)) {
            return in_array($module, $mods, true);
        }
        return array_key_exists($module, $mods);
    }

    private function hasFullSamsAccess(): bool
    {
        $mods = $this->getModulosDisponiblesParaUsuario();
        return $mods === ['all'] || (is_array($mods) && count($mods) > 15);
    }

    private function getSystemInstruction(): string
    {
        $cacheKey = 'ai:sams:instruction:v5:' . (auth()->id() ?? 'guest') . ':' . md5(json_encode($this->getModulosDisponiblesParaUsuario()));

        return Cache::remember($cacheKey, now()->addMinutes(10), function () {
        $modulosDisponibles = $this->getModulosDisponiblesParaUsuario();
        $isFullAccess = $this->hasFullSamsAccess();

        $restriccion = '';
        if (! $this->assistantUnscopedLiveData()) {
            $restriccion .= <<<'REST'

ALCANCE DE DATOS (multi-empresa): Este usuario NO es administrador global de SAMS ni pertenece a la empresa matriz Prevention World.
Los bloques [DATOS EN VIVO DEL SISTEMA] solo incluyen información de SU empresa. Está prohibido inferir, mencionar o inventar cifras, nombres o datos de otras empresas. Si pregunta por otras empresas, responde que solo puede ver información de la empresa a la que está asociado su usuario.
REST;
        }
        if (! $isFullAccess && ! empty($modulosDisponibles)) {
            $lista = implode(', ', array_values($modulosDisponibles));
            $restriccion .= <<<REST

RESTRICCIÓN OBLIGATORIA - Este usuario pertenece a una empresa y solo puede acceder a: {$lista}.

Si pregunta cómo hacer algo que NO está en esa lista (por ejemplo: crear usuarios, gestionar roles, asignar equipos, inspección, exportar, etc.), responde ÚNICAMENTE algo como: "Lo siento, no tienes acceso a esa información en SAMS. Tu empresa solo tiene habilitados: {$lista}. Consulta con el administrador de Prevention World si necesitas más permisos."

NO des instrucciones paso a paso para funciones a las que no tiene acceso. NUNCA expliques cómo crear usuarios, roles, inspecciones, etc., si ese módulo no está en la lista de arriba.
REST;
        }

        $base = <<<TEXT
Eres el asistente de SAMS (Sistema de Administración y Gestión de equipos para Prevention World).
Respondes en español con tono cercano y conversacional: como una persona del equipo que explica con calma, no como un manual ni un formulario.

VOZ Y RITMO (obligatorio):
- Tutea de forma natural ("tú", "te", "tu"); evita frases rígidas tipo "El usuario debe" o "Proceda a".
- Varía cómo empiezas: no uses siempre "En SAMS…" o "Puedes…"; a veces responde directo al dato o con una frase corta de contexto.
- Suena humano: una o dos frases sueltas están bien; no hace falta enumerar todo con viñetas si la pregunta es simple.
- Sé breve cuando baste; si hace falta detalle (pasos, números), organízalo sin volver repetitivo.
- Evita muletillas artificiales ("¡Claro que sí!", "¡Por supuesto!") en cada respuesta; un solo reconocimiento breve cuando encaje está bien.
- No uses mayúsculas para dar énfasis; usa negritas solo si ayudan (p. ej. nombres de menú o cifras clave).
- Personalidad: asistente tipo Jarvis (profesional, elegante, útil), sin exagerar ni actuar como personaje ficticio todo el tiempo.
- Si la pregunta es de conteo directo (ej.: "¿cuántos usuarios hay?", "¿cuántos equipos hay?"), responde SOLO con el total y, como máximo, una frase corta adicional.
- No agregues contexto extra, desglose o recomendaciones si el usuario no lo pidió.

DATOS Y CLARIDAD: Prioriza lo esencial; párrafos cortos. Si [DATOS EN VIVO] trae cifras, intégralas en la respuesta con lenguaje natural ("tenéis X equipos…", "en tu empresa hay…").
INTELIGENCIA DE RESPUESTA: Antes de responder, interpreta la intención real de la pregunta, conecta contexto de mensajes previos y entrega una respuesta útil (no genérica), con el nivel de detalle exacto que piden.
TEXT;
        $base .= $restriccion;

        $base .= "\n\nPuedes responder preguntas de conocimiento general, pero cuando hable de SAMS:";

        if ($isFullAccess) {
            $base .= "\n\nMódulos de SAMS: Dashboard, Gestión Principal (Usuarios, Roles, Cargos, Grupos, Fabricantes), Gestión de Equipos (Inventario IN-XXXX, Tipos, Clases, Material Didáctico, Equipos de Baja, Auditoría), Gestión de Empresa (Empresas, Sedes, Bodegas, Personalización), Configuración (Formatos, Hoja de Vida, Inspección, Exportar), Asignar equipos. Sugerencias siempre visible.";
        } else {
            $base .= " solo los módulos que tiene habilitados.";
        }

        $base .= "\n\nFlujo vigente de ASIGNAR en SAMS: selección de equipos disponibles y/o traspaso, vista previa de formato editable, envío de solicitud al usuario destino, aceptación con firma arrastrada y diligenciamiento final del formato.";
        $base .= "\n\nFlujo vigente de PRÉSTAMOS TEMPORALES: crear préstamo con formato Excel/HTML editable, fecha inicio y fecha fin, devolución con firma obligatoria del destinatario, revisión admin con tabla de equipos e imágenes, devolución anticipada con autorización por contraseña (admin o destinatario), y generación de aviso automático cuando estado de revisión sea Malo o Con novedad.";
        $base .= "\n\nPara adminoficina también existe: Seguimiento formatos, ver/editar formato, devolución con acta y firma, estado de equipos (en uso por usuario o disponibles), secciones Mis equipos y Mis actas.";
        $base .= "\n\nSi te preguntan por esos módulos, debes responder con esas rutas y no con el flujo antiguo.";

        $base .= "\n\nSi explicas algo de SAMS, menciona brevemente la ruta o los botones de acceso rápido si aplica.";

        $base .= "\n\nDATOS EN VIVO: Si el mensaje del usuario incluye un bloque que empieza por [DATOS EN VIVO DEL SISTEMA], esos totales y listas vienen directamente de la base de datos. DEBES responder usando esos números y nombres tal cual (totales, desglose por empresa, por tipo, préstamos, asignaciones). No sustituyas la respuesta por solo decir 've a Inventario'.";
        $base .= "\n\nSi aparece [DATOS EN VIVO DEL SISTEMA], está prohibido afirmar que no tienes acceso a datos del sistema, que el usuario debe buscarlo solo en el menú, o que son datos no disponibles: en ese caso SÍ tienes los datos en el mismo mensaje y debes usarlos.";

        $pk = trim((string) config('sams-assistant.program_knowledge', ''));
        if ($pk !== '') {
            $base .= "\n\nCONOCIMIENTO INTERNO DEL PROGRAMA (úsalo para explicar SAMS sin inventar módulos que el usuario no tiene):\n" . $pk;
        }

        return $base;
        });
    }

    private function getQuickActionsForUser(): array
    {
        $all = [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'modulo' => null],
            ['label' => 'Usuarios', 'route' => 'users.complete', 'modulo' => 'users'],
            ['label' => 'Equipos', 'route' => 'equipos.index', 'modulo' => 'equipos'],
            ['label' => 'Hoja de Vida', 'route' => 'hoja-vida.index', 'modulo' => 'hoja_vida'],
            ['label' => 'Inspección', 'route' => 'inspeccion.index', 'modulo' => 'inspeccion'],
            ['label' => 'Exportar', 'route' => 'exportar.index', 'modulo' => 'exportar'],
            ['label' => 'Asignar', 'route' => 'asignar.index', 'modulo' => 'asignar'],
            ['label' => 'Seguimiento formatos', 'route' => 'asignar.seguimiento', 'modulo' => 'asignar'],
        ];
        $available = [];
        foreach ($all as $a) {
            if (! Route::has($a['route'])) {
                continue;
            }
            $a['url'] = route($a['route']);
            $available[] = $a;
        }
        if ($this->assistantUnscopedLiveData()) {
            return array_map(fn ($a) => ['label' => $a['label'], 'url' => $a['url']], $available);
        }
        $disponibles = $this->getModulosDisponiblesParaUsuario();
        if ($disponibles === ['all'] || (is_array($disponibles) && count($disponibles) > 15)) {
            return array_map(fn ($a) => ['label' => $a['label'], 'url' => $a['url']], $available);
        }
        $keys = array_keys($disponibles);
        $filtrados = [];
        foreach ($available as $a) {
            if ($a['modulo'] === null || in_array($a['modulo'], $keys, true)) {
                $filtrados[] = ['label' => $a['label'], 'url' => $a['url']];
            }
        }
        return $filtrados;
    }

    private function getSuggestionPromptsForUser(): array
    {
        $items = [['text' => '¿Qué es SAMS?', 'modulo' => null]];
        if ($this->moduleAllowed('asignar')) {
            $items[] = ['text' => '¿Cómo asigno equipos?', 'modulo' => 'asignar'];
            $items[] = ['text' => '¿Cómo funciona Seguimiento formatos y devolución?', 'modulo' => 'asignar'];
        }
        if ($this->moduleAllowed('exportar')) {
            $items[] = ['text' => '¿Cómo exporto la hoja de vida?', 'modulo' => 'exportar'];
        }
        if ($this->moduleAllowed('equipos')) {
            $items[] = ['text' => '¿Cómo consulto el inventario?', 'modulo' => 'equipos'];
            $items[] = ['text' => '¿Cuántos equipos tenemos en total y por empresa?', 'modulo' => 'equipos'];
            $items[] = ['text' => '¿Cuántos arneses tenemos y cuántos por empresa?', 'modulo' => 'equipos'];
        }
        if ($this->moduleAllowed('prestamos_temporales')) {
            $items[] = ['text' => '¿Qué préstamos temporales están activos y a quién?', 'modulo' => 'prestamos_temporales'];
        }
        return array_column($items, 'text');
    }

    public function index()
    {
        $quickActions = $this->getQuickActionsForUser();
        return view('admin.gemini.chat', [
            'geminiConfigured' => $this->aiChat->hasAnyProvider(),
            'providers' => $this->aiChat->configuredProviderNames(),
            'quickActions' => $quickActions,
            'suggestionPrompts' => $this->getSuggestionPromptsForUser(),
        ]);
    }

    /**
     * Evita 422 por mensajes vacíos en el historial (p. ej. respuesta IA vacía).
     *
     * @param  array<int, mixed>  $history
     * @return array<int, array{role: string, parts: array<int, array{text: string}>}>
     */
    private function sanitizeChatHistory(array $history): array
    {
        $out = [];
        foreach ($history as $item) {
            if (! is_array($item) || ! isset($item['role'], $item['parts']) || ! is_array($item['parts'])) {
                continue;
            }
            $role = $item['role'] === 'model' ? 'model' : 'user';
            if (! in_array($role, ['user', 'model'], true)) {
                continue;
            }
            $text = trim((string) ($item['parts'][0]['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $text = mb_substr($text, 0, 8000);
            $out[] = ['role' => $role, 'parts' => [['text' => $text]]];
        }

        return array_slice($out, -12);
    }

    /**
     * Une el mensaje actual con varias entradas previas del usuario (historial) para interpretar
     * seguimientos del tipo «sí, en PDF» después de pedir el manual técnico.
     */
    private function buildRecentUserContextForManual(Request $request, string $currentMessage): string
    {
        $chunks = [Str::lower(Str::ascii(trim($currentMessage)))];
        $hist = $request->input('history', []);
        if (! is_array($hist)) {
            return $chunks[0];
        }
        foreach (array_reverse($hist) as $item) {
            if (! is_array($item) || ($item['role'] ?? '') !== 'user') {
                continue;
            }
            $t = trim((string) ($item['parts'][0]['text'] ?? ''));
            if ($t === '') {
                continue;
            }
            $chunks[] = Str::lower(Str::ascii($t));
            if (count($chunks) >= 12) {
                break;
            }
        }

        return implode(' ', $chunks);
    }

    /**
     * Tema «manual técnico SAMS»: requiere la palabra manual y (técnico|técnica|sams|documentación).
     */
    private function wantsTechnicalManualTopic(string $contextLower): bool
    {
        if (! str_contains($contextLower, 'manual')) {
            return false;
        }
        if (str_contains($contextLower, 'tecnico') || str_contains($contextLower, 'tecnica')) {
            return true;
        }
        if (str_contains($contextLower, 'sams')) {
            return true;
        }
        if (str_contains($contextLower, 'documentacion')) {
            return true;
        }

        return false;
    }

    private function wantsTechnicalManualHtmlOnly(string $contextLower): bool
    {
        return str_contains($contextLower, 'solo html')
            || str_contains($contextLower, 'html solamente')
            || str_contains($contextLower, 'solo el html')
            || str_contains($contextLower, 'archivo html');
    }

    /**
     * Administrador global: sin empresa asignada (convención del proyecto para acceso total).
     */
    private function isGlobalAdministratorForTechnicalManual(): bool
    {
        $u = auth()->user();

        return $u !== null && $u->empresa_id === null;
    }

    private function formatLiveDataAsAssistantReply(string $markdownBlock): string
    {
        $t = preg_replace('/^###\s+/m', '', $markdownBlock) ?? $markdownBlock;
        $t = str_replace(['**', '`'], '', $t);
        $t = trim($t);
        $t = preg_replace('/^-\s+/m', '• ', $t) ?? $t;

        return "Respuesta directa (datos en vivo de SAMS):\n" . $t;
    }

    public function chat(Request $request)
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(60);
        }

        $history = $this->sanitizeChatHistory($request->input('history', []));

        $request->merge(['history' => $history]);

        $request->validate([
            'message' => 'required|string|max:8000',
            'history' => 'nullable|array',
            'history.*.role' => 'required|string|in:user,model',
            'history.*.parts' => 'required|array|min:1',
            'history.*.parts.0.text' => 'required|string|max:8000',
        ]);

        $originalMessage = (string) $request->input('message');
        $manualCtx = $this->buildRecentUserContextForManual($request, $originalMessage);

        if ($this->wantsTechnicalManualTopic($manualCtx)) {
            if (! $this->isGlobalAdministratorForTechnicalManual()) {
                return response()->json([
                    'reply' => 'El **manual técnico completo** (PDF/HTML) solo lo puede descargar el **administrador global de SAMS** (usuario sin empresa asignada). Si lo necesitas, pídeselo a Prevention World.',
                ]);
            }

            if ($this->wantsTechnicalManualHtmlOnly($manualCtx)) {
                return response()->json([
                    'reply' => 'De acuerdo. Se descarga el manual en **HTML** (`SAMS-Manual-Tecnico.html`). Para **PDF** generado en servidor, escribe por ejemplo «manual técnico en PDF» o «descargar PDF».',
                    'download_manual' => true,
                    'manual_url' => route('admin.docs.manual-tecnico'),
                ]);
            }

            return response()->json([
                'reply' => 'Listo. Se descarga el **manual técnico en PDF** (`SAMS-Manual-Tecnico.pdf`) generado en el servidor. Si prefieres solo HTML, escribe **«solo html»** en la misma conversación al pedir el manual.',
                'download_manual_pdf' => true,
                'manual_pdf_url' => route('admin.docs.manual-tecnico-pdf'),
            ]);
        }

        $mods = $this->getModulosDisponiblesParaUsuario();

        $liveAppendix = null;
        try {
            $liveAppendix = $this->liveDataReporter->build($originalMessage, [
                'modules' => $mods,
                'isPwGlobalAdmin' => $this->assistantUnscopedLiveData(),
            ]);
        } catch (Throwable $e) {
            Log::error('SamsLiveDataReporter: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'error' => 'No se pudieron leer los datos del sistema en vivo. Si el problema continúa, avisa al administrador.',
            ], 500);
        }

        if (! $this->aiChat->hasAnyProvider()) {
            if ($liveAppendix !== null && $liveAppendix !== '') {
                return response()->json([
                    'reply' => $this->formatLiveDataAsAssistantReply($liveAppendix),
                    'source' => 'sams_live',
                ]);
            }
            return response()->json([
                'error' => 'Para preguntas generales hace falta una API de IA en .env (GEMINI_API_KEY, GROQ_API_KEY o OPENROUTER_API_KEY). Las preguntas de inventario, préstamos o asignaciones sí funcionan con datos del sistema.',
            ], 503);
        }

        $systemInstruction = $this->getSystemInstruction();
        $newUserMessage = $originalMessage;
        if ($liveAppendix !== null && $liveAppendix !== '') {
            $newUserMessage .= "\n\n[DATOS EN VIVO DEL SISTEMA]\n" . $liveAppendix
                . "\n\nInstrucción: responde en tono conversacional usando estos datos; integra números y nombres con frases naturales (no listas secas si no hace falta). Si preguntan varias cosas, responde cada parte con claridad.";
        }

        $contents = [];
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $systemInstruction]],
        ];
        foreach ($history as $item) {
            $role = $item['role'] === 'model' ? 'model' : 'user';
            $text = $item['parts'][0]['text'] ?? '';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $text]],
            ];
        }
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $newUserMessage]],
        ];

        $result = $this->aiChat->chat($contents, $systemInstruction);

        if ($result['reply'] !== null) {
            $payload = ['reply' => $result['reply']];
            if ($result['provider'] !== null) {
                $payload['provider'] = $result['provider'];
            }

            return response()->json($payload);
        }

        $friendlyReply = $this->getFriendlyFallbackReply($originalMessage, null);
        if ($friendlyReply !== null) {
            return response()->json(['reply' => $friendlyReply, 'source' => 'fallback']);
        }

        return response()->json([
            'error' => 'La IA externa no respondió a tiempo. Reintenta; para totales de equipos, préstamos o asignaciones formula la pregunta de forma directa (ej.: «¿cuántos equipos tenemos?»).',
        ], 502);
    }

    /**
     * Cuando Gemini no responde, devolver un mensaje útil para saludos o preguntas frecuentes.
     */
    private function getFriendlyFallbackReply(string $message, ?string $lastError): ?string
    {
        $n = Str::lower(Str::ascii($message));
        if (Str::length(trim($n)) === 0) {
            return null;
        }
        if ($n === 'hola' || $n === 'holi' || Str::startsWith($n, 'hola ')) {
            return '¡Hola! Soy el asistente de SAMS. Te puedo ayudar con datos reales del sistema (totales, arneses, préstamos, asignaciones) y con dudas rápidas de uso. Por ejemplo: «¿cuántos arneses tenemos?» o «¿qué préstamos están activos?».';
        }
        if (Str::contains($n, 'que es sams') || Str::contains($n, 'qué es sams')) {
            return '**SAMS** es el Sistema de Administración y Gestión de equipos del Instituto Prevention World. Gestiona usuarios, roles, inventario (cascos, arneses, eslingas, kits), hoja de vida, inspecciones y más. Más info: https://preventionworld.edu.co/portal/';
        }
        if ((Str::contains($n, 'como asigno') || Str::contains($n, 'cómo asigno') || Str::contains($n, 'asignar equipos')) && $this->moduleAllowed('asignar')) {
            return 'Para **asignar equipos**: ve a **Asignar**. Selecciona usuario y equipos (disponibles o para traspaso), luego abre **Preparar formato y enviar solicitud**. El usuario destino completa/acepta con firma; después queda aplicada la asignación.';
        }
        if ((Str::contains($n, 'seguimiento formatos') || Str::contains($n, 'devolucion') || Str::contains($n, 'devolución') || Str::contains($n, 'mis actas') || Str::contains($n, 'mis equipos')) && $this->moduleAllowed('asignar')) {
            return 'En el módulo **Seguimiento formatos** puedes ver actas, estado y botón de devolución. En **Asignar** también aparecen **Mis equipos** y **Mis actas** para el usuario, con historial de formatos firmados.';
        }
        if (
            (Str::contains($n, 'usuarios') || Str::contains($n, 'roles') || Str::contains($n, 'inspeccion') || Str::contains($n, 'inspección') || Str::contains($n, 'exportar') || Str::contains($n, 'asignar')) &&
            ! $this->hasFullSamsAccess()
        ) {
            $mods = $this->getModulosDisponiblesParaUsuario();
            if (is_array($mods) && ! empty($mods) && $mods !== ['all']) {
                $labels = array_is_list($mods) ? implode(', ', $mods) : implode(', ', array_values($mods));
                return "Lo siento, no tienes acceso a ese módulo en SAMS. Tu perfil solo tiene habilitado: {$labels}.";
            }
        }
        return null;
    }
}
