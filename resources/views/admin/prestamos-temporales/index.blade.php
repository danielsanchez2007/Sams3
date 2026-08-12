@extends('layouts.admin-layout')

@section('title', 'Prestamos temporales - SAMS')
@section('header-title', 'Prestamos temporales')
@section('header-subtitle', 'Prestar, devolver y revisar prestamos')

@section('content')
@php
    $isGestion = in_array(strtolower(trim(auth()->user()?->role?->name ?? '')), ['administrador', 'adminoficina'], true);
@endphp
<div class="max-w-7xl mx-auto space-y-6">
    <div class="pw-card rounded-xl p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="md:col-span-2">
                <label class="text-xs text-gray-600">Filtro usuario/equipo</label>
                <input type="text" name="q" value="{{ $q ?? '' }}" class="w-full border rounded-lg px-3 py-2" placeholder="Nombre, correo, codigo o equipo">
            </div>
            <div>
                <label class="text-xs text-gray-600">Estado</label>
                <select name="estado" class="w-full border rounded-lg px-3 py-2">
                    <option value="">Todos</option>
                    <option value="activo" {{ ($estado ?? '') === 'activo' ? 'selected' : '' }}>Activo</option>
                    <option value="pendiente_revision" {{ ($estado ?? '') === 'pendiente_revision' ? 'selected' : '' }}>Pendiente revision</option>
                    <option value="finalizado" {{ ($estado ?? '') === 'finalizado' ? 'selected' : '' }}>Finalizado</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Aplicar</button>
                <a href="{{ route('prestamos-temporales.index') }}" class="pw-btn-secondary px-4 py-2 rounded-lg">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="pw-card rounded-xl p-4">
        <div class="flex items-center gap-2">
            <button type="button" class="tab-btn px-3 py-2 rounded-lg text-sm font-medium bg-blue-50 text-blue-700" data-tab="prestar">Prestar</button>
            <button type="button" class="tab-btn px-3 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700" data-tab="prestaron">Me prestaron</button>
            @if($isGestion)
                <button type="button" class="tab-btn px-3 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700" data-tab="revision">Revision devoluciones</button>
            @endif
        </div>
    </div>

    <div id="tab-prestar" class="tab-panel space-y-4">
        @if($isGestion)
        <div class="pw-card rounded-xl p-4">
            <h3 class="font-semibold mb-3">Nuevo prestamo temporal</h3>
            <form method="POST" action="{{ route('prestamos-temporales.preview') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="text-xs text-gray-600">Usuario destino</label>
                        <select name="to_user_id" class="w-full border rounded-lg px-3 py-2" required>
                            <option value="">Seleccione...</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ trim($u->name . ' ' . ($u->last_name ?? '')) }} - {{ $u->email }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Fecha inicio del préstamo</label>
                        <input type="date" name="fecha_salida" value="{{ now()->format('Y-m-d') }}" class="w-full border rounded-lg px-3 py-2" required>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600">Fecha fin prevista (devolución)</label>
                        <input type="date" name="fecha_fin" value="{{ now()->addDays(7)->format('Y-m-d') }}" class="w-full border rounded-lg px-3 py-2" required>
                    </div>
                    <div class="flex items-end">
                        <span class="text-xs text-gray-500">Se usa en el formato CF17 (fechas de envío / recibido). Máximo 21 equipos por remisión.</span>
                    </div>
                </div>

                <div>
                    <label class="text-xs text-gray-600">Equipos disponibles de la empresa (todos)</label>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-2 mb-2">
                        <input id="filtro-equipo-texto" type="text" class="md:col-span-2 border rounded-lg px-3 py-2 text-sm" placeholder="Filtrar por codigo o nombre...">
                        <input id="filtro-equipo-categoria" type="text" class="border rounded-lg px-3 py-2 text-sm" placeholder="Categoria / tipo">
                        <input id="filtro-equipo-clase" type="text" class="border rounded-lg px-3 py-2 text-sm" placeholder="Clase">
                        <select id="filtro-equipo-estado" class="border rounded-lg px-3 py-2 text-sm">
                            <option value="">Estado (todos)</option>
                            <option value="libre">Solo libres</option>
                            <option value="asignado normal">Solo asignados normal</option>
                            <option value="prestado temporal">Solo prestado temporal</option>
                        </select>
                    </div>
                    <div id="equipos-listado" class="max-h-80 overflow-y-auto border rounded-lg p-2 space-y-2">
                        @foreach($equipos as $e)
                            @php
                                $tempActivo = (bool) ($e->tiene_prestamo_temporal_activo ?? false);
                                $imgGeneral = $e->imagenes->firstWhere('tipo', 'general');
                                $imgEtiqueta = $e->imagenes->firstWhere('tipo', 'etiqueta');
                                $img = $imgGeneral ?? $imgEtiqueta;
                                $estadoTxt = $tempActivo ? 'prestado temporal' : ($e->asignacion_exists ? 'asignado normal' : 'libre');
                                $tipoTxt = trim((string) (($e->tipoEquipo->nombre ?? '') ?: ($e->tipoEquipo->alias ?? '')));
                                $claseTxt = trim((string) (($e->claseEquipo->nombre ?? '') ?: ($e->claseEquipo->alias ?? '')));
                            @endphp
                            <label
                                class="equipo-item flex items-center gap-3 p-2 rounded border {{ $tempActivo ? 'opacity-50' : '' }}"
                                data-codigo="{{ strtolower((string) $e->codigo) }}"
                                data-nombre="{{ strtolower((string) $e->nombre) }}"
                                data-tipo="{{ strtolower($tipoTxt) }}"
                                data-clase="{{ strtolower($claseTxt) }}"
                                data-estado="{{ strtolower($estadoTxt) }}"
                            >
                                <input type="checkbox" name="equipo_ids[]" value="{{ $e->id }}" {{ $tempActivo ? 'disabled' : '' }}>
                                <div class="w-11 h-11 rounded overflow-hidden border border-gray-300 bg-gray-50 shrink-0">
                                    @if($img?->path)
                                        <img src="{{ asset('storage/' . $img->path) }}" alt="Imagen equipo" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full grid place-items-center text-[10px] text-gray-500">Sin foto</div>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <span class="text-sm block truncate">
                                        <x-codigo-short :codigo="$e->codigo" :extra="$e->nombre" />
                                    </span>
                                    <span class="text-xs text-gray-500 block truncate">Categoria: {{ $tipoTxt !== '' ? $tipoTxt : 'N/A' }} | Clase: {{ $claseTxt !== '' ? $claseTxt : 'N/A' }}</span>
                                </div>
                                @if($tempActivo)
                                    <span class="text-xs px-2 py-0.5 rounded bg-purple-100 text-purple-700">prestado temporal</span>
                                @elseif($e->asignacion_exists)
                                    <span class="text-xs px-2 py-0.5 rounded bg-yellow-100 text-yellow-700">asignado normal</span>
                                @else
                                    <span class="text-xs px-2 py-0.5 rounded bg-green-100 text-green-700">libre</span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                </div>
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Aceptar y llenar formato</button>
            </form>
        </div>
        @else
        <div class="pw-card rounded-xl p-4 text-sm text-gray-600">No tienes permisos para prestar equipos.</div>
        @endif
    </div>

    <div id="tab-prestaron" class="tab-panel hidden space-y-4">
        <div class="pw-card rounded-xl p-4">
            <h3 class="font-semibold mb-3">Prestamos que me hicieron</h3>
            <div class="space-y-3">
                @forelse($prestamosRecibidos as $p)
                    @php
                        $fin = $p->fecha_fin?->copy()?->startOfDay();
                        $hoyRec = now()->startOfDay();
                        $permiteNormal = !$fin || $hoyRec->gte($fin);
                    @endphp
                    <div class="border rounded-lg p-3 flex items-start justify-between gap-3">
                        <div>
                            <div class="font-medium">{{ trim(($p->creador?->name ?? '') . ' ' . ($p->creador?->last_name ?? '')) }}</div>
                            <div class="text-xs text-gray-500">Inicio: {{ optional($p->fecha_salida)->format('Y-m-d') }}@if($p->fecha_fin) · Fin previsto: {{ $p->fecha_fin->format('Y-m-d') }}@endif</div>
                            <div class="text-xs mt-1">{{ $p->items_count ?? 0 }} equipo(s)</div>
                        </div>
                        <div>
                            @if($p->estado === 'activo')
                                <div class="flex gap-2">
                                    @if($permiteNormal)
                                        <a href="{{ route('prestamos-temporales.devolver.form', $p->id) }}" class="pw-btn-secondary px-3 py-2 rounded-lg inline-flex">Devolver</a>
                                    @else
                                        <button type="button" class="pw-btn-secondary px-3 py-2 rounded-lg inline-flex opacity-70 cursor-not-allowed" disabled>Devolver</button>
                                    @endif
                                    @if(!$permiteNormal)
                                        <a href="{{ route('prestamos-temporales.devolver.form', ['prestamo' => $p->id, 'anticipada' => 1]) }}" class="pw-btn-primary px-3 py-2 rounded-lg inline-flex">
                                            Devolución anticipada
                                        </a>
                                    @endif
                                </div>
                            @else
                                <span class="text-xs px-2 py-1 rounded bg-yellow-100 text-yellow-800">Pendiente revision admin</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">No tienes prestamos temporales activos.</div>
                @endforelse
            </div>
        </div>
    </div>

    @if($isGestion)
    <div id="tab-revision" class="tab-panel hidden space-y-4">
        @forelse($prestamosPendRevision as $p)
            @php
                $fechaFin = $p->fecha_fin?->copy()?->startOfDay();
                $hoy = now()->startOfDay();
                $esFechaHabilitada = !$fechaFin || $hoy->gte($fechaFin);
                $puedeRevisar = $p->estado === 'pendiente_revision' && $esFechaHabilitada;
                $puedeAnticipada = !$esFechaHabilitada || $p->estado === 'activo';
            @endphp
            <div class="pw-card rounded-xl p-4">
                <h3 class="font-semibold mb-2">Revision de devolucion - {{ trim(($p->destinatario?->name ?? '') . ' ' . ($p->destinatario?->last_name ?? '')) }}</h3>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="text-xs text-gray-500">
                        {{ $p->items_count ?? 0 }} equipo(s) | Fin programado: {{ optional($p->fecha_fin)->format('Y-m-d') ?? 'N/A' }}
                        @if(!$esFechaHabilitada)
                            <span class="ml-1 px-2 py-0.5 rounded bg-amber-100 text-amber-800">Aun no es fecha de recepción</span>
                        @elseif($p->estado === 'activo')
                            <span class="ml-1 px-2 py-0.5 rounded bg-blue-100 text-blue-800">Esperando devolución del usuario</span>
                        @else
                            <span class="ml-1 px-2 py-0.5 rounded bg-green-100 text-green-800">Lista para revisar</span>
                        @endif
                    </div>
                    @if($puedeRevisar)
                        <a href="{{ route('prestamos-temporales.revisar.form', $p->id) }}" class="pw-btn-primary px-4 py-2 rounded-lg inline-flex">Revisar</a>
                    @else
                        <div class="flex gap-2">
                            <button type="button" class="pw-btn-secondary px-4 py-2 rounded-lg opacity-70 cursor-not-allowed" disabled>
                                Revisar
                            </button>
                            @if($puedeAnticipada)
                                <a href="{{ route('prestamos-temporales.revisar.form', ['prestamo' => $p->id, 'anticipada' => 1]) }}" class="pw-btn-primary px-4 py-2 rounded-lg inline-flex">
                                    Devolución anticipada
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="pw-card rounded-xl p-4 text-sm text-gray-500">No hay préstamos activos o devoluciones pendientes por revisar.</div>
        @endforelse
    </div>
    @endif
</div>

<div id="novedad-modal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl w-full max-w-lg p-4 space-y-3">
        <h4 class="font-semibold">Registrar novedad</h4>
        <p class="text-sm text-gray-600" id="novedad-modal-equipo"></p>
        <textarea id="novedad-modal-text" class="w-full border rounded-lg px-3 py-2 text-sm" rows="4" placeholder="Describe la novedad..."></textarea>
        <div class="flex justify-end gap-2">
            <button type="button" id="novedad-cancel" class="pw-btn-secondary px-3 py-2 rounded-lg">Cancelar</button>
            <button type="button" id="novedad-save" class="pw-btn-primary px-3 py-2 rounded-lg">Guardar</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.tab-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            document.querySelectorAll('.tab-btn').forEach(function(b){
                b.classList.remove('bg-blue-50','text-blue-700');
                b.classList.add('bg-gray-100','text-gray-700');
            });
            btn.classList.remove('bg-gray-100','text-gray-700');
            btn.classList.add('bg-blue-50','text-blue-700');
            const tab = btn.getAttribute('data-tab');
            document.querySelectorAll('.tab-panel').forEach(function(p){ p.classList.add('hidden'); });
            const panel = document.getElementById('tab-' + tab);
            if (panel) panel.classList.remove('hidden');
        });
    });

    const modal = document.getElementById('novedad-modal');
    const modalEquipo = document.getElementById('novedad-modal-equipo');
    const modalText = document.getElementById('novedad-modal-text');
    const modalCancel = document.getElementById('novedad-cancel');
    const modalSave = document.getElementById('novedad-save');
    let activeItemId = null;

    function openNovedadModal(itemId, label) {
        activeItemId = itemId;
        modalEquipo.textContent = label || '';
        const ta = document.getElementById('novedad-' + itemId);
        modalText.value = ta ? ta.value : '';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeNovedadModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        activeItemId = null;
    }

    modalCancel.addEventListener('click', closeNovedadModal);
    modalSave.addEventListener('click', function () {
        if (!activeItemId) return closeNovedadModal();
        const ta = document.getElementById('novedad-' + activeItemId);
        if (ta) {
            ta.classList.remove('hidden');
            ta.value = modalText.value;
        }
        closeNovedadModal();
    });

    document.querySelectorAll('.novedad-trigger').forEach(function (radio) {
        radio.addEventListener('change', function () {
            openNovedadModal(
                radio.getAttribute('data-item'),
                radio.getAttribute('data-label')
            );
        });
    });

    const fTexto = document.getElementById('filtro-equipo-texto');
    const fCat = document.getElementById('filtro-equipo-categoria');
    const fClase = document.getElementById('filtro-equipo-clase');
    const fEstado = document.getElementById('filtro-equipo-estado');
    const items = Array.from(document.querySelectorAll('.equipo-item'));

    function norm(v) { return (v || '').toString().trim().toLowerCase(); }
    function filtrarEquipos() {
        const texto = norm(fTexto?.value);
        const cat = norm(fCat?.value);
        const clase = norm(fClase?.value);
        const estado = norm(fEstado?.value);

        items.forEach(function (item) {
            const codigo = norm(item.getAttribute('data-codigo'));
            const nombre = norm(item.getAttribute('data-nombre'));
            const tipo = norm(item.getAttribute('data-tipo'));
            const claseItem = norm(item.getAttribute('data-clase'));
            const estadoItem = norm(item.getAttribute('data-estado'));

            const okTexto = !texto || codigo.includes(texto) || nombre.includes(texto);
            const okCat = !cat || tipo.includes(cat);
            const okClase = !clase || claseItem.includes(clase);
            const okEstado = !estado || estadoItem === estado;

            item.style.display = (okTexto && okCat && okClase && okEstado) ? '' : 'none';
        });
    }

    [fTexto, fCat, fClase, fEstado].forEach(function (el) {
        if (el) el.addEventListener('input', filtrarEquipos);
        if (el && el.tagName === 'SELECT') el.addEventListener('change', filtrarEquipos);
    });
});
</script>
@endsection

