@extends('layouts.admin-layout')

@section('title', 'Hoja de vida - ' . ($equipo->codigo ?: $equipo->id) . ' - SAMS')
@section('header-title', 'Hoja de vida')
@section('header-subtitle')
    <x-codigo-short :codigo="$equipo->codigo" :extra="$equipo->nombre" />
@endsection

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('formatos.clase', $clase) }}" class="pw-btn-secondary px-3 py-2 text-sm rounded-lg">
        <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>
        Volver
    </a>
    <a href="{{ route('formatos.html', [$clase, $equipo]) }}" target="_blank" class="pw-btn-secondary px-3 py-2 text-sm rounded-lg">
        Ver
    </a>
    <a href="{{ route('formatos.html', [$clase, $equipo]) }}?print=1" target="_blank" class="pw-btn-secondary px-3 py-2 text-sm rounded-lg">
        Imprimir
    </a>
    <a href="{{ route('formatos.pdf', [$clase, $equipo]) }}" class="pw-btn-dark px-3 py-2 text-sm rounded-lg">
        PDF
    </a>
</div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto space-y-4">
    <div class="p-4 rounded-2xl border border-teal-200 bg-teal-50 text-teal-900 text-sm">
        Documento generado automáticamente con los datos actuales del equipo. No es necesario subir un Excel ni asignar formato.
    </div>
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden" style="height: 75vh;">
        <iframe src="{{ route('formatos.html', [$clase, $equipo]) }}" class="w-full h-full" style="border:0;" title="Hoja de vida"></iframe>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
@endsection
