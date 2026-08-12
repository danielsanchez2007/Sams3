@extends('layouts.admin-layout')

@section('title', 'Hoja de Vida - SAMS')
@section('header-title', 'Hoja de Vida')
@section('header-subtitle', 'Selecciona una clase de equipo')

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
                <div class="text-lg font-semibold text-gray-900">Clases de equipo</div>
                <div class="text-xs text-gray-500">Verde = tiene formato Excel asignado. Rojo = no tiene.</div>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($clases as $c)
                @php
                    $has = isset($plantillasByClase[$c->id]);
                @endphp

                @if($has)
                    <a href="{{ route('hoja-vida.clase', $c) }}" class="p-4 rounded-2xl border border-green-200 bg-green-50 hover:bg-green-100 transition">
                        <div class="font-semibold text-green-900">{{ $c->nombre }}</div>
                        <div class="text-xs text-green-700">{{ $c->tipoEquipo?->nombre ?? 'Sin tipo' }}</div>
                        <div class="text-xs text-green-700">Formato asignado</div>
                    </a>
                @else
                    <div class="p-4 rounded-2xl border border-red-200 bg-red-50">
                        <div class="font-semibold text-red-900">{{ $c->nombre }}</div>
                        <div class="text-xs text-red-700">{{ $c->tipoEquipo?->nombre ?? 'Sin tipo' }}</div>
                        <div class="text-xs text-red-700">Sin formato asignado</div>
                    </div>
                @endif
            @endforeach
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
