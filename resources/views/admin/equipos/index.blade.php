@extends('layouts.admin-layout')

@section('title', 'Todos los Equipos - SAMS')
@section('header-title', 'Todos los Equipos')
@section('header-subtitle', 'Inventario general')

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <button type="button" id="btnCodigos" class="pw-btn-dark px-3 py-2 text-sm rounded-lg">
        <i data-lucide="qr-code" class="w-4 h-4 inline mr-2"></i>
        Códigos disponibles
    </button>
    <a href="{{ route('equipos.create') }}" class="pw-btn-primary px-3 py-2 text-sm rounded-lg">
        <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
        Nuevo
    </a>
</div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div id="modalCodigos" class="fixed inset-0 hidden items-center justify-center" style="z-index: 9999;">
        <div class="absolute inset-0 bg-black/50" data-close-codigos="1"></div>
        <div class="pw-modal-content relative w-full bg-white rounded-xl shadow-xl border border-gray-100 p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <div class="text-lg font-semibold text-gray-900">Códigos reutilizables</div>
                    <div class="text-xs text-gray-500">Selecciona un código para usarlo en un nuevo equipo</div>
                </div>
                <button type="button" class="w-9 h-9 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center" data-close-codigos="1">×</button>
            </div>

            @if(($codigosDisponibles ?? collect())->count())
                <div class="max-h-80 overflow-auto space-y-2">
                    @foreach($codigosDisponibles as $c)
                        <div class="flex items-center justify-between gap-3 p-3 rounded-lg border border-gray-200">
                            <div class="text-sm font-semibold text-gray-900">
                                <x-codigo-short :codigo="$c->codigo" />
                            </div>
                            <form action="{{ route('equipos.codigos.usar', $c->codigo) }}" method="POST">
                                @csrf
                                <button type="submit" class="pw-btn-primary px-3 py-2 rounded-lg">Usar</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-sm text-gray-600">No hay códigos disponibles.</div>
            @endif
        </div>
    </div>

    <div class="pw-card bg-white rounded-xl shadow-lg p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-3 items-end">
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-gray-500 mb-1">Buscar</label>
                <input name="q" value="{{ $q ?? '' }}" type="text" placeholder="Código, nombre, lote, factura..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Tipo</label>
                <select name="tipo_equipo_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    @foreach($tipos as $t)
                        <option value="{{ $t->id }}" {{ (string)request('tipo_equipo_id') === (string)$t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Clase</label>
                <select name="clase_equipo_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todas</option>
                    @foreach($clases as $c)
                        <option value="{{ $c->id }}" {{ (string)request('clase_equipo_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Empresa</label>
                <select name="empresa_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todas</option>
                    @foreach($empresas as $e)
                        <option value="{{ $e->id }}" {{ (string)request('empresa_id') === (string)$e->id ? 'selected' : '' }}>{{ $e->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Filas</label>
                <select name="per_page" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @php
                        $pp = request('per_page', 15);
                    @endphp
                    <option value="10" {{ (string)$pp === '10' ? 'selected' : '' }}>10</option>
                    <option value="15" {{ (string)$pp === '15' ? 'selected' : '' }}>15</option>
                    <option value="30" {{ (string)$pp === '30' ? 'selected' : '' }}>30</option>
                    <option value="50" {{ (string)$pp === '50' ? 'selected' : '' }}>50</option>
                    <option value="100" {{ (string)$pp === '100' ? 'selected' : '' }}>100</option>
                    <option value="all" {{ (string)$pp === 'all' ? 'selected' : '' }}>Todas</option>
                </select>
            </div>

            <div>
                <div class="flex gap-2">
                    <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Aplicar</button>
                    <a href="{{ route('equipos.index') }}" class="pw-btn-secondary px-4 py-2 rounded-lg">Limpiar</a>
                </div>
            </div>
        </form>
    </div>

    <div class="pw-card bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Listado</h3>
            <div class="text-sm text-gray-600">Mostrando {{ $equipos->firstItem() ?? 0 }} - {{ $equipos->lastItem() ?? 0 }} de {{ $equipos->total() ?? 0 }}</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fotos</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo / Clase</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-40">Ubicación</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($equipos as $equipo)
                        @php
                            $imgGeneral = $equipo->imagenes->firstWhere('tipo', 'general');
                            $imgEtiqueta = $equipo->imagenes->firstWhere('tipo', 'etiqueta');
                            $imgKit = $equipo->imagenes->firstWhere('tipo', 'kit_general');
                            $tempActivo = $equipo->prestamoTemporalItems->contains(fn ($it) => in_array($it->prestamo?->estado, ['activo', 'pendiente_revision'], true));
                            $normalAsignado = (bool) $equipo->asignacion;
                        @endphp
                        <tr class="hover:bg-gray-50" data-equipo-id="{{ $equipo->id }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-11 h-11 bg-gray-100 overflow-hidden border border-gray-300 shadow-sm">
                                        @if($imgEtiqueta)
                                            <img src="{{ asset('storage/' . $imgEtiqueta->path) }}" class="w-full h-full object-cover" alt="Etiqueta">
                                        @endif
                                    </div>
                                    <div class="w-11 h-11 bg-gray-100 overflow-hidden border border-gray-300 shadow-sm">
                                        @if($imgGeneral)
                                            <img src="{{ asset('storage/' . $imgGeneral->path) }}" class="w-full h-full object-cover" alt="General">
                                        @endif
                                    </div>
                                    @if($equipo->es_kit)
                                    <div class="w-11 h-11 bg-gray-100 overflow-hidden border border-gray-300 shadow-sm">
                                        @if($imgKit)
                                            <img src="{{ asset('storage/' . $imgKit->path) }}" class="w-full h-full object-cover" alt="Kit">
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <x-codigo-short :codigo="$equipo->codigo" :extra="$equipo->nombre" />
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="line-clamp-2">{{ $equipo->descripcion ?: '—' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                <div>{{ $equipo->tipoEquipo?->nombre }}</div>
                                <div class="text-xs text-gray-500">{{ $equipo->claseEquipo?->nombre }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 max-w-[160px]">
                                <div class="truncate">{{ $equipo->sede?->nombre }}</div>
                                @if($equipo->bodega)
                                    <div class="text-xs text-gray-500 truncate">{{ $equipo->bodega->nombre }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex flex-col gap-1">
                                    @if($tempActivo)
                                        <span class="px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-800 w-fit">Préstamo</span>
                                    @elseif($normalAsignado)
                                        <span class="px-2 py-1 rounded-full text-xs bg-amber-100 text-amber-800 w-fit">Asignado</span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs bg-slate-100 text-slate-700 w-fit">Disponible</span>
                                    @endif
                                    @if($equipo->estado_item === 'con_observacion')
                                        <span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800 w-fit">Novedad</span>
                                        <div class="text-xs text-yellow-700 truncate max-w-[220px]">{{ $equipo->observacion }}</div>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800 w-fit">Bueno</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('equipos.show', $equipo) }}" class="pw-btn-primary w-9 h-9 inline-flex items-center justify-center rounded-lg" title="Ver">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    <a href="{{ route('equipos.edit', $equipo) }}" class="pw-btn-secondary w-9 h-9 inline-flex items-center justify-center rounded-lg" title="Editar">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </a>
                                    <form action="{{ route('equipos.destroy', $equipo) }}" method="POST" class="js-equipo-delete" data-confirm="¿Estás seguro de que deseas eliminar este equipo? Esta acción no se puede deshacer." data-confirm-danger="1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="pw-btn-danger w-9 h-9 inline-flex items-center justify-center rounded-lg" title="Eliminar">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-6 py-3 border-t border-gray-200">
            <div class="flex flex-wrap items-center gap-3 mb-2 text-xs">
                <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-700">Disponible</span>
                <span class="px-2 py-1 rounded-full bg-amber-100 text-amber-800">Asignado</span>
                <span class="px-2 py-1 rounded-full bg-purple-100 text-purple-800">Préstamo</span>
                <span class="px-2 py-1 rounded-full bg-green-100 text-green-800">Bueno</span>
                <span class="px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">Novedad</span>
            </div>
            {{ $equipos->onEachSide(1)->links() }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const btn = document.getElementById('btnCodigos');
    const modal = document.getElementById('modalCodigos');
    if (btn && modal) {
        const close = () => modal.classList.add('hidden');
        const open = () => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        btn.addEventListener('click', open);
        modal.querySelectorAll('[data-close-codigos="1"]').forEach(el => el.addEventListener('click', close));
    }

    // Eliminar equipo: quita de la tabla al instante y refresca para confirmar.
    document.addEventListener('submit', async (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (!form.classList.contains('js-equipo-delete')) return;

        e.preventDefault();

        const msg = form.getAttribute('data-confirm') || '¿Eliminar equipo?';
        if (typeof showConfirmModal !== 'function') return;

        showConfirmModal({
            title: 'Eliminar equipo',
            message: msg,
            confirmText: 'Eliminar',
            cancelText: 'Cancelar',
            danger: true,
            onConfirm: async function () {
                const row = form.closest('tr[data-equipo-id]');
                if (row) row.remove();

                const fd = new FormData(form);
                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: fd,
                    });
                    if (res.status !== 404) {
                        const data = await res.json().catch(() => null);
                        if (!res.ok || !data?.success) {
                            throw new Error(data?.message || 'No se pudo eliminar el equipo.');
                        }
                    }
                } catch (err) {
                    if (typeof showNotification === 'function') {
                        showNotification(err?.message || 'Error eliminando el equipo.', 'error');
                    }
                }

                setTimeout(() => window.location.reload(), 350);
            }
        });
    });
});
</script>
@endsection
