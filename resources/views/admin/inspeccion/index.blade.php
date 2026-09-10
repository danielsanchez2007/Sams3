@extends('layouts.admin-layout')

@section('title', 'Inspección - SAMS')
@section('header-title', 'Inspección')
@section('header-subtitle', 'Formato Excel por clase de equipo')

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
        <div class="text-lg font-semibold text-gray-900 mb-2">Asignar formato de inspección</div>
        <p class="text-sm text-gray-500 mb-4">Selecciona una clase de equipo (solo las que no tienen formato) y sube un Excel. Para reemplazar un formato existente, usa «Reemplazar formato» en la tarjeta de la clase.</p>

        @if(($clasesSinFormato ?? collect())->isEmpty())
            <p class="text-sm text-amber-700 bg-amber-50 rounded-lg p-3">Todas las clases tienen formato asignado. Usa «Reemplazar formato» en una tarjeta para cambiarlo.</p>
        @else
        <form action="{{ route('inspeccion.plantilla.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Clase de equipo</label>
                <select name="clase_equipo_id" required class="rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">Seleccionar...</option>
                    @foreach($clasesSinFormato ?? $clases as $c)
                        <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Archivo Excel</label>
                <input type="file" name="plantilla_excel" accept=".xlsx,.xls" required class="text-sm">
            </div>
            <button type="submit" class="pw-btn-apartado-inspeccion px-4 py-2 rounded-lg text-sm">Asignar formato</button>
        </form>
        @endif
    </div>

    <div class="pw-card bg-white rounded-2xl shadow-lg p-5 border border-gray-100">
        <div class="text-lg font-semibold text-gray-900 mb-2">Clases de equipo</div>
        <p class="text-sm text-gray-500 mb-4">Verde = tiene formato asignado. Clic en la clase para ver equipos e inspeccionar.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($clases as $c)
                @php $has = isset($plantillasByClase[$c->id]); @endphp
                @if($has)
                    <div class="p-4 rounded-2xl border border-green-200 bg-green-50">
                        <div class="font-semibold text-green-900">{{ $c->nombre }}</div>
                        <div class="text-xs text-green-700">{{ $c->tipoEquipo?->nombre ?? 'Sin tipo' }}</div>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <a href="{{ route('inspeccion.equipos', $c) }}" class="pw-btn-success inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-lg">Ver equipos</a>
                            <button type="button" class="btn-reemplazar-formato pw-btn-reemplazar inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-lg" data-clase-id="{{ $c->id }}" data-clase-nombre="{{ $c->nombre }}">Reemplazar formato</button>
                        </div>
                    </div>
                @else
                    <div class="p-4 rounded-2xl border border-gray-200 bg-gray-50">
                        <div class="font-semibold text-gray-700">{{ $c->nombre }}</div>
                        <div class="text-xs text-gray-500">{{ $c->tipoEquipo?->nombre ?? 'Sin tipo' }}</div>
                        <div class="text-xs text-gray-500">Sin formato · Asigna uno arriba</div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Modal reemplazar formato --}}
    <div id="modalReemplazarFormato" class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="fixed inset-0 bg-black/50" id="modalReemplazarBackdrop"></div>
        <div class="pw-modal-content fixed left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-full bg-white rounded-2xl shadow-2xl p-6 z-10">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Reemplazar formato</h3>
            <p class="text-sm text-gray-500 mb-4">Clase: <span id="modalClaseNombre" class="font-medium text-gray-900"></span></p>
            <form id="formReemplazarFormato" action="{{ route('inspeccion.plantilla.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="clase_equipo_id" id="modalClaseId" value="">
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Nuevo archivo Excel</label>
                    <input type="file" name="plantilla_excel" accept=".xlsx,.xls" required class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
                </div>
                <div class="flex gap-2 justify-end">
                    <button type="button" id="modalReemplazarCerrar" class="pw-btn-secondary px-4 py-2 rounded-lg">Cancelar</button>
                    <button type="submit" class="pw-btn-reemplazar px-4 py-2 rounded-lg">Reemplazar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    var modal = document.getElementById('modalReemplazarFormato');
    var backdrop = document.getElementById('modalReemplazarBackdrop');
    var btnCerrar = document.getElementById('modalReemplazarCerrar');
    var form = document.getElementById('formReemplazarFormato');
    var inputClaseId = document.getElementById('modalClaseId');
    var spanClaseNombre = document.getElementById('modalClaseNombre');

    document.querySelectorAll('.btn-reemplazar-formato').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var claseId = btn.getAttribute('data-clase-id');
            var claseNombre = btn.getAttribute('data-clase-nombre');
            if (spanClaseNombre) spanClaseNombre.textContent = claseNombre || '';
            if (form) form.reset();
            if (inputClaseId) inputClaseId.value = claseId || '';
            if (modal) modal.classList.remove('hidden');
        });
    });

    function cerrarModal() {
        if (modal) modal.classList.add('hidden');
    }
    if (btnCerrar) btnCerrar.addEventListener('click', cerrarModal);
});
</script>
@endsection
