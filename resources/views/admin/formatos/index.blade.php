@extends('layouts.admin-layout')

@section('title', 'Formatos - SAMS')
@section('header-title', 'Formatos')
@section('header-subtitle', 'Hoja de vida automática por clase de equipo')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    @if (session('success'))
        <div class="p-4 rounded-2xl border border-green-200 bg-green-50 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session('info'))
        <div class="p-4 rounded-2xl border border-blue-200 bg-blue-50 text-blue-800 text-sm">
            {{ session('info') }}
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
                <div class="text-lg font-semibold text-gray-900">Formato oficial del sistema</div>
                <div class="text-xs text-gray-500">Elige una clase para ver sus equipos. La hoja de vida se llena sola con los datos del inventario.</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center">
                <i data-lucide="file-text" class="w-5 h-5 text-gray-700"></i>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @forelse($clases as $c)
                <a href="{{ route('formatos.clase', $c) }}" class="p-4 rounded-2xl border border-gray-200 bg-white hover:border-teal-300 hover:bg-teal-50/40 transition">
                    <div class="font-semibold text-gray-900">{{ $c->nombre }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">{{ $c->tipoEquipo?->nombre ?? 'Sin tipo' }}</div>
                    <div class="text-xs text-teal-700 mt-2">{{ (int) ($c->equipos_activos_count ?? 0) }} equipo(s)</div>
                </a>
            @empty
                <div class="col-span-full p-6 text-center text-sm text-gray-500 border border-dashed border-gray-200 rounded-2xl">
                    No hay clases de equipo para mostrar.
                </div>
            @endforelse
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
