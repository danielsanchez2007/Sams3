@extends('layouts.admin-layout')

@section('title', 'Ver Equipo - SAMS')
@section('header-title', 'Todos los Equipos')
@section('header-subtitle', 'Detalle del equipo')

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('equipos.index') }}" class="pw-btn-secondary px-4 py-2 rounded-lg">
        <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>
        Volver
    </a>
    <a href="{{ route('equipos.edit', $equipo) }}" class="pw-btn-dark px-4 py-2 rounded-lg">
        <i data-lucide="edit" class="w-4 h-4 inline mr-2"></i>
        Editar
    </a>
    <form id="deleteEquipoForm" action="{{ route('equipos.destroy', $equipo) }}" method="POST" class="inline" data-confirm="¿Eliminar este equipo? Esta acción no se puede deshacer." data-confirm-danger="1">
        @csrf
        @method('DELETE')
        <button type="submit" class="pw-btn-danger px-4 py-2 rounded-lg">
            <i data-lucide="trash-2" class="w-4 h-4 inline mr-2"></i>
            Eliminar
        </button>
    </form>
</div>
@endsection

@section('content')
@php
    $logo = $equipo->empresa?->logo;
    $imgsGeneral = $equipo->imagenes->where('tipo', 'general');
    $imgsEtiqueta = $equipo->imagenes->where('tipo', 'etiqueta');
    $imgsCert = $equipo->imagenes->where('tipo', 'certificacion_evidencia');
    $imgKit = $equipo->imagenes->firstWhere('tipo', 'kit_general');
@endphp

<div class="max-w-7xl mx-auto space-y-6">
    <div class="pw-card bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-start justify-between gap-6">
            <div class="min-w-0">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center overflow-hidden">
                        @if($logo)
                            <img src="{{ asset('storage/' . $logo) }}" alt="Logo" class="w-12 h-12 object-contain">
                        @else
                            <i data-lucide="package" class="w-6 h-6 text-blue-600"></i>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs text-gray-500">Código</div>
                        <div class="text-xl font-bold text-gray-900 truncate">{{ $equipo->codigo }}</div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs text-gray-500">Nombre</div>
                        <div class="text-base font-semibold text-gray-900">{{ $equipo->nombre }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Estado</div>
                        <div class="text-sm">
                            @if($equipo->estado_item === 'con_observacion')
                                <span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">Con observación</span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">Bueno</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Tipo / Clase</div>
                        <div class="text-sm text-gray-900">{{ $equipo->tipoEquipo?->nombre }} / {{ $equipo->claseEquipo?->nombre }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Ubicación</div>
                        <div class="text-sm text-gray-900">
                            {{ $equipo->empresa?->nombre }}
                            @if($equipo->sede)
                                <span class="text-gray-500">/</span> {{ $equipo->sede->nombre }}
                            @endif
                            @if($equipo->bodega)
                                <span class="text-gray-500">/</span> {{ $equipo->bodega->nombre }}
                            @endif
                        </div>
                    </div>
                </div>

                @if($equipo->descripcion)
                    <div class="mt-4">
                        <div class="text-xs text-gray-500">Descripción</div>
                        <div class="text-sm text-gray-800 whitespace-pre-line">{{ $equipo->descripcion }}</div>
                    </div>
                @endif

                @if($equipo->estado_item === 'con_observacion' && $equipo->observacion)
                    <div class="mt-4 p-3 rounded-lg bg-yellow-50 border border-yellow-100">
                        <div class="text-xs font-semibold text-yellow-800">Observación</div>
                        <div class="text-sm text-yellow-900 whitespace-pre-line">{{ $equipo->observacion }}</div>
                    </div>
                @endif
            </div>

            <div class="flex-shrink-0 w-56">
                <div class="text-xs font-semibold text-gray-500 mb-2">Fotos</div>
                <div class="grid grid-cols-3 gap-2">
                    @foreach($imgsEtiqueta->take(3) as $img)
                        <a href="{{ asset('storage/' . $img->path) }}" target="_blank" class="block w-full aspect-square rounded-lg overflow-hidden border bg-gray-50">
                            <img src="{{ asset('storage/' . $img->path) }}" class="w-full h-full object-cover" alt="Etiqueta">
                        </a>
                    @endforeach
                    @foreach($imgsGeneral->take(3) as $img)
                        <a href="{{ asset('storage/' . $img->path) }}" target="_blank" class="block w-full aspect-square rounded-lg overflow-hidden border bg-gray-50">
                            <img src="{{ asset('storage/' . $img->path) }}" class="w-full h-full object-cover" alt="General">
                        </a>
                    @endforeach
                    @if($equipo->es_kit && $imgKit)
                        <a href="{{ asset('storage/' . $imgKit->path) }}" target="_blank" class="block w-full aspect-square rounded-lg overflow-hidden border bg-gray-50">
                            <img src="{{ asset('storage/' . $imgKit->path) }}" class="w-full h-full object-cover" alt="Kit">
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="pw-card bg-white rounded-xl shadow-lg p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Fechas y uso</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="text-xs text-gray-500">Fabricación</div>
                    <div class="text-sm text-gray-900">{{ $equipo->fecha_fabricacion ? $equipo->fecha_fabricacion->format('d/m/Y') : '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Uso</div>
                    <div class="text-sm text-gray-900">{{ $equipo->fecha_uso ? $equipo->fecha_uso->format('d/m/Y') : '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Tipo de uso</div>
                    <div class="text-sm text-gray-900">
                        {{ $equipo->tipo_uso ?: '—' }}
                        @if($equipo->tipo_uso === 'Otro' && $equipo->tipo_uso_otro)
                            <span class="text-gray-500">({{ $equipo->tipo_uso_otro }})</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Vida útil</div>
                    <div class="text-sm text-gray-900">{{ $equipo->vida_util !== null ? $equipo->vida_util . ' años' : '—' }}</div>
                </div>
            </div>
        </div>

        <div class="pw-card bg-white rounded-xl shadow-lg p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Compra y valor</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="text-xs text-gray-500">Número de factura</div>
                    <div class="text-sm text-gray-900">{{ $equipo->numero_factura ?: '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Fecha de compra</div>
                    <div class="text-sm text-gray-900">{{ $equipo->fecha_compra ? $equipo->fecha_compra->format('d/m/Y') : '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Lote</div>
                    <div class="text-sm text-gray-900">{{ $equipo->lote ?: '—' }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Valor</div>
                    <div class="text-sm text-gray-900">{{ $equipo->valor_equipo !== null ? number_format((float)$equipo->valor_equipo, 0, ',', '.') : '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="pw-card bg-white rounded-xl shadow-lg p-6 space-y-4">
        <div class="flex items-center justify-between gap-4">
            <h3 class="text-lg font-semibold text-gray-900">Certificación y soporte</h3>
            <form action="{{ route('equipos.archivos.store', $equipo) }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                @csrf
                <input name="archivo_nombre" type="text" placeholder="Nombre del archivo" class="w-48 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <input name="archivo_file" type="file" class="w-56" required>
                <button type="submit" class="pw-btn-primary px-3 py-2 rounded-lg">Agregar</button>
            </form>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="space-y-3">
                <div>
                    <div class="text-xs text-gray-500">Descripción</div>
                    <div class="text-sm text-gray-900 whitespace-pre-line">{{ $equipo->certificacion_descripcion ?: '—' }}</div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs text-gray-500">Manual fabricante</div>
                        <div class="text-sm text-gray-900">{{ $equipo->tiene_manual_fabricante ? 'Sí' : 'No' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Certificación fabricante</div>
                        <div class="text-sm text-gray-900">{{ $equipo->tiene_certificacion_fabricante ? 'Sí' : 'No' }}</div>
                    </div>
                </div>
                <div>
                    <div class="text-xs text-gray-500">Especificaciones técnicas</div>
                    <div class="text-sm text-gray-900 whitespace-pre-line">{{ $equipo->especificaciones_tecnicas ?: '—' }}</div>
                </div>
                @if($equipo->tiene_resistencia)
                    <div class="p-3 rounded-lg bg-blue-50 border border-blue-100">
                        <div class="text-xs font-semibold text-blue-800">Resistencia / Capacidad</div>
                        <div class="text-sm text-blue-900 whitespace-pre-line">{{ $equipo->resistencia_descripcion }}</div>
                    </div>
                @endif

                <div>
                    <div class="text-xs font-semibold text-gray-500 mb-2">Archivos</div>
                    @if($equipo->archivos->count())
                        <div class="space-y-2">
                            @foreach($equipo->archivos as $a)
                                <div class="flex items-center justify-between gap-3 p-3 rounded-lg border border-gray-200">
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-gray-900 truncate">{{ $a->nombre }}</div>
                                        <div class="text-xs text-gray-500 truncate">{{ $a->original_name }}</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('equipos.archivos.download', $a) }}" class="pw-btn-dark px-3 py-2 rounded-lg">Descargar</a>
                                        <form action="{{ route('equipos.archivos.destroy', [$equipo, $a]) }}" method="POST" data-confirm="¿Estás seguro de que deseas eliminar este archivo?" data-confirm-danger="1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="pw-btn-danger px-3 py-2 rounded-lg">Eliminar</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-sm text-gray-500">—</div>
                    @endif
                </div>
            </div>

            <div>
                <div class="text-xs font-semibold text-gray-500 mb-2">Evidencias</div>
                @if($imgsCert->count())
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach($imgsCert as $img)
                            <div class="relative">
                                <a href="{{ asset('storage/' . $img->path) }}" target="_blank" class="block w-full aspect-square rounded-lg overflow-hidden border bg-gray-50">
                                    <img src="{{ asset('storage/' . $img->path) }}" class="w-full h-full object-cover" alt="Evidencia">
                                </a>
                                <form action="{{ route('equipos.evidencias.destroy', [$equipo, $img]) }}" method="POST" class="absolute top-2 right-2" data-confirm="¿Estás seguro de que deseas eliminar esta evidencia?" data-confirm-danger="1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="pw-btn-danger w-8 h-8 rounded-full flex items-center justify-center shadow">×</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-sm text-gray-500">—</div>
                @endif
            </div>
        </div>
    </div>

    @if($equipo->es_kit)
        <div class="pw-card bg-white rounded-xl shadow-lg p-6 space-y-4">
            <h3 class="text-lg font-semibold text-gray-900">Kit</h3>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <div>
                        <div class="text-xs text-gray-500">Nombre del kit</div>
                        <div class="text-sm text-gray-900">{{ $equipo->kit_nombre ?: '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500">Cantidad de objetos</div>
                        <div class="text-sm text-gray-900">{{ $equipo->kit_cantidad ?: '—' }}</div>
                    </div>

                    <div class="mt-3">
                        <div class="text-xs font-semibold text-gray-500 mb-2">Objetos</div>
                        <div class="space-y-2">
                            @foreach($equipo->kitItems as $it)
                                <div class="p-3 rounded-lg border border-gray-200">
                                    <div class="text-sm font-semibold text-gray-900">{{ $it->nombre ?: '—' }}</div>
                                    @if($it->descripcion)
                                        <div class="text-sm text-gray-700">{{ $it->descripcion }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div>
                    <div class="text-xs font-semibold text-gray-500 mb-2">Foto del kit</div>
                    @if($imgKit)
                        <a href="{{ asset('storage/' . $imgKit->path) }}" target="_blank" class="block w-full rounded-xl overflow-hidden border bg-gray-50">
                            <img src="{{ asset('storage/' . $imgKit->path) }}" class="w-full h-64 object-cover" alt="Kit">
                        </a>
                    @else
                        <div class="text-sm text-gray-500">—</div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Eliminar desde el detalle sin caer en 404 ni recargar lento
    const form = document.getElementById('deleteEquipoForm');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
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
                    const data = await res.json().catch(() => null);
                    if (!res.ok || !data?.success) {
                        throw new Error(data?.message || 'No se pudo eliminar el equipo.');
                    }
                    window.location.href = data.redirect || "{{ route('equipos.index') }}";
                } catch (err) {
                    if (typeof showNotification === 'function') {
                        showNotification(err?.message || 'Error eliminando el equipo.', 'error');
                    }
                }
            }
        });
    });
});
</script>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
@endsection
