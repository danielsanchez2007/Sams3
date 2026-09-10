@extends('layouts.admin-layout')

@section('title', 'Formatos - ' . $clase->nombre . ' - SAMS')
@section('header-title', 'Formatos')
@section('header-subtitle', $clase->nombre)

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('formatos.index') }}" class="pw-btn-secondary px-3 py-2 text-sm rounded-lg">
        <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>
        Volver
    </a>
</div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    @if (session('success'))
        <div class="p-4 rounded-2xl border border-green-200 bg-green-50 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 rounded-2xl border border-yellow-200 bg-yellow-50 text-yellow-900 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="pw-card bg-white rounded-2xl shadow-lg p-5 border border-gray-100">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-lg font-semibold text-gray-900">Equipos</div>
                <div class="text-xs text-gray-500">Clase: {{ $clase->nombre }}. Abre un equipo para ver su hoja de vida ya llena.</div>
            </div>
        </div>

        <div class="overflow-x-auto mt-4">
            <table class="min-w-full text-sm">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                    <tr>
                        <th class="px-2 py-3 text-left">Fotos</th>
                        <th class="px-3 py-3 text-left">Código</th>
                        <th class="px-3 py-3 text-left">Equipo</th>
                        <th class="px-3 py-3 text-left">Serial</th>
                        <th class="px-3 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($equipos as $e)
                        @php
                            $imgGeneral = $e->imagenes?->firstWhere('tipo', 'general');
                            $imgEtiqueta = $e->imagenes?->firstWhere('tipo', 'etiqueta');
                        @endphp
                        <tr>
                            <td class="px-2 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-1">
                                    @if($imgGeneral)
                                        <a href="{{ asset('storage/' . $imgGeneral->path) }}" target="_blank" class="block w-8 h-8 rounded overflow-hidden border border-gray-200 bg-gray-100 shrink-0" title="Foto general">
                                            <img src="{{ asset('storage/' . $imgGeneral->path) }}" class="w-8 h-8 object-cover" alt="General">
                                        </a>
                                    @else
                                        <span class="flex w-8 h-8 rounded border border-gray-200 bg-gray-50 items-center justify-center text-gray-400 text-xs shrink-0" title="Foto general">—</span>
                                    @endif
                                    @if($imgEtiqueta)
                                        <a href="{{ asset('storage/' . $imgEtiqueta->path) }}" target="_blank" class="block w-8 h-8 rounded overflow-hidden border border-gray-200 bg-gray-100 shrink-0" title="Foto etiqueta">
                                            <img src="{{ asset('storage/' . $imgEtiqueta->path) }}" class="w-8 h-8 object-cover" alt="Etiqueta">
                                        </a>
                                    @else
                                        <span class="flex w-8 h-8 rounded border border-gray-200 bg-gray-50 items-center justify-center text-gray-400 text-xs shrink-0" title="Foto etiqueta">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-3 text-gray-900 font-semibold">
                                <x-codigo-short :codigo="$e->codigo" :extra="$e->nombre" />
                            </td>
                            <td class="px-3 py-3 text-gray-700">{{ $e->nombre }}</td>
                            <td class="px-3 py-3 text-gray-700">{{ $e->serial }}</td>
                            <td class="px-3 py-3 text-right">
                                <div class="flex items-center justify-end gap-2 flex-wrap">
                                    <a href="{{ route('formatos.show', [$clase, $e]) }}" class="pw-btn-primary px-3 py-2 rounded-lg">Ver hoja</a>
                                    <a href="{{ route('formatos.html', [$clase, $e]) }}" target="_blank" class="pw-btn-secondary px-3 py-2 rounded-lg">Imprimir</a>
                                    <a href="{{ route('formatos.pdf', [$clase, $e]) }}" class="pw-btn-dark px-3 py-2 rounded-lg">PDF</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-gray-500">Sin equipos activos para esta clase.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
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
});
</script>
@endsection
