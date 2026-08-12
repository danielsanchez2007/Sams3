@extends('layouts.admin-layout')

@section('title', 'Exportar - SAMS')
@section('header-title', 'Exportar')
@section('header-subtitle', 'Descarga hojas de vida, inspecciones y formatos')

@section('header-actions')
<form action="{{ route('exportar.actualizar') }}" method="POST" class="inline">
    @csrf
    <button type="submit" class="pw-btn-apartado-exportar inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold shadow-sm" title="Regenerar con la última inspección, hoja de vida y formato de baja actualizados">
        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
        Actualizar
    </button>
</form>
@endsection

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    @if(session('success'))
        <div class="p-4 rounded-2xl border border-green-200 bg-green-50 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-2xl border border-red-200 bg-red-50 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    <div class="pw-card bg-white rounded-2xl shadow-lg p-5 border border-gray-100">
        <div class="text-lg font-semibold text-gray-900 mb-4">Filtros</div>
        <form method="GET" action="{{ route('exportar.index') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Origen</label>
                <select name="origen" class="px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos</option>
                    <option value="inventario" {{ $filterOrigen === 'inventario' ? 'selected' : '' }}>Inventario (IN-)</option>
                    <option value="baja" {{ $filterOrigen === 'baja' ? 'selected' : '' }}>De baja (DB-)</option>
                    <option value="auditoria" {{ $filterOrigen === 'auditoria' ? 'selected' : '' }}>Auditoría (AUD-)</option>
                    <option value="material-didactico" {{ $filterOrigen === 'material-didactico' ? 'selected' : '' }}>Material didáctico (MD-)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Clase</label>
                <select name="clase" class="px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todas</option>
                    @foreach($clases as $c)
                        <option value="{{ $c->id }}" {{ $filterClase == (string)$c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Buscar (código, nombre, serial, descripción)</label>
                <input type="text" name="buscar" value="{{ old('buscar', $filterBuscar) }}" placeholder="Buscar..." class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <button type="submit" class="pw-btn-apartado-exportar px-4 py-2 rounded-xl text-sm">Filtrar</button>
            </div>
        </form>
    </div>

    <div class="pw-card bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="text-lg font-semibold text-gray-900">Equipos ({{ $equipos->total() }})</div>
            <div class="text-xs text-gray-500">Hoja Vida Completa: vista previa con todos los formatos unidos, luego descarga en un solo PDF.</div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left">Fotos</th>
                        <th class="px-3 py-3 text-left">Código</th>
                        <th class="px-3 py-3 text-left">Nombre</th>
                        <th class="px-3 py-3 text-left">Descripción</th>
                        <th class="px-3 py-3 text-left">Origen</th>
                        <th class="px-3 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($equipos as $e)
                        @php
                            $imgGeneral = $e->imagenes?->firstWhere('tipo', 'general');
                            $imgEtiqueta = $e->imagenes?->firstWhere('tipo', 'etiqueta');
                            $doc = $docsByEquipo[$e->id] ?? null;
                            $ultimaInspeccion = $ultimaInspeccionByEquipo[$e->id] ?? $ultimaInspeccionByEquipo[(string)$e->id] ?? null;
                            $baja = $bajaByEquipo[$e->id] ?? $bajaByEquipo[(string)$e->id] ?? null;
                            $deBaja = !$e->activo || $baja;
                            $tipoOrigen = \App\Http\Controllers\ExportarController::tipoOrigen($e);
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    @if($imgGeneral)
                                        <a href="{{ asset('storage/' . $imgGeneral->path) }}" target="_blank" class="block w-12 h-12 rounded overflow-hidden border border-gray-200 bg-gray-100 shrink-0" title="Foto general">
                                            <img src="{{ asset('storage/' . $imgGeneral->path) }}" class="w-12 h-12 object-cover" alt="General">
                                        </a>
                                    @else
                                        <span class="flex w-12 h-12 rounded border border-gray-200 bg-gray-50 items-center justify-center text-gray-400 text-xs shrink-0">—</span>
                                    @endif
                                    @if($imgEtiqueta)
                                        <a href="{{ asset('storage/' . $imgEtiqueta->path) }}" target="_blank" class="block w-12 h-12 rounded overflow-hidden border border-gray-200 bg-gray-100 shrink-0" title="Foto etiqueta">
                                            <img src="{{ asset('storage/' . $imgEtiqueta->path) }}" class="w-12 h-12 object-cover" alt="Etiqueta">
                                        </a>
                                    @else
                                        <span class="flex w-12 h-12 rounded border border-gray-200 bg-gray-50 items-center justify-center text-gray-400 text-xs shrink-0">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2 font-semibold text-gray-900">{{ $e->codigo ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ $e->nombre ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-600 max-w-xs truncate" title="{{ $e->descripcion ?? '' }}">{{ \Illuminate\Support\Str::limit($e->descripcion ?? '—', 60) }}</td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    {{ $tipoOrigen === 'De baja' ? 'bg-red-100 text-red-700' : '' }}
                                    {{ $tipoOrigen === 'Inventario' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $tipoOrigen === 'Auditoría' ? 'bg-blue-100 text-blue-700' : '' }}
                                    {{ $tipoOrigen === 'Material didáctico' ? 'bg-purple-100 text-purple-700' : '' }}
                                    {{ $tipoOrigen === 'Otro' ? 'bg-gray-100 text-gray-700' : '' }}
                                ">{{ $tipoOrigen }}</span>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="flex items-center justify-end gap-1 flex-wrap">
                                    @if($e->claseEquipo)
                                        <a href="{{ route('exportar.hoja-vida', $e) }}" class="pw-btn-apartado-exportar px-2 py-1.5 rounded-lg text-xs" title="Descargar hoja de vida">Hoja Vida</a>
                                    @endif
                                    @if($ultimaInspeccion)
                                        <a href="{{ route('inspeccion.download', $ultimaInspeccion) }}" class="pw-btn-apartado-exportar px-2 py-1.5 rounded-lg text-xs" title="Descargar inspección">Inspección</a>
                                    @endif
                                    @if($deBaja && $baja)
                                        <a href="{{ route('exportar.baja', $e) }}" class="pw-btn-dar-baja px-2 py-1.5 rounded-lg text-xs" title="Descargar formato de baja">Formato Baja</a>
                                    @endif
                                    <a href="{{ route('exportar.hoja-vida-completa.preview', $e) }}" class="pw-btn-apartado-exportar px-4 py-2 rounded-xl text-sm font-semibold" title="Vista previa y descarga PDF (hoja de vida + fotos + inspección + baja)">Hoja Vida Completa</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">No hay equipos con los filtros aplicados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $equipos->onEachSide(1)->links() }}
        </div>
    </div>
</div>
@endsection
