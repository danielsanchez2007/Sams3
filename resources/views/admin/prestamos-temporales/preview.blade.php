@extends('layouts.admin-layout')

@section('title', 'Previsualizar prestamo - SAMS')
@section('header-title', 'Previsualizar prestamo temporal')
@section('header-subtitle', 'Completa el formato CF17 y confirma envio')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <form method="POST" action="{{ route('prestamos-temporales.store') }}" id="prestamoTemporalForm" class="space-y-6">
        @csrf
        <input type="hidden" name="to_user_id" value="{{ $toUser->id }}">
        <input type="hidden" name="fecha_salida" value="{{ $fechaSalida }}">
        <input type="hidden" name="fecha_fin" value="{{ $fechaFin }}">
        <input type="hidden" name="edited_html" id="edited_html">
        @foreach($equipoIds as $id)
            <input type="hidden" name="equipo_ids[]" value="{{ $id }}">
        @endforeach

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-8">
                <div class="pw-card rounded-2xl border border-gray-100">
                    <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-lg font-semibold text-gray-900">Vista previa del formato</p>
                            <p class="text-xs text-gray-500">Puede escribir y ajustar directamente antes de enviar.</p>
                        </div>
                        <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Aceptar y enviar</button>
                    </div>
                    <div class="p-4 overflow-auto bg-white" style="max-height:70vh;">
                        <div id="editableTemplate" contenteditable="true" class="outline-none">
                            {!! $renderedHtml !!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-4 space-y-4">
                <div class="pw-card rounded-2xl p-4 border border-gray-100">
                    <p class="text-sm font-semibold text-gray-900 mb-2">Datos del préstamo</p>
                    <p class="text-sm text-gray-700"><strong>Recibe:</strong> {{ trim(($toUser->name ?? '') . ' ' . ($toUser->last_name ?? '')) }}</p>
                    <p class="text-xs text-gray-500">{{ $toUser->email }}</p>
                    <p class="text-sm text-gray-700 mt-2"><strong>Entrega:</strong> {{ trim(($prestador->name ?? '') . ' ' . ($prestador->last_name ?? '')) }}</p>
                    <p class="text-xs text-gray-600 mt-2">Inicio: <strong>{{ $fechaSalidaFmt }}</strong> | Fin: <strong>{{ $fechaFinFmt }}</strong></p>
                </div>
                <div class="pw-card rounded-2xl p-4 border border-gray-100">
                    <p class="text-sm font-semibold text-gray-900 mb-2">Equipos seleccionados</p>
                    <div class="space-y-2 max-h-72 overflow-auto">
                        @foreach($equipos as $eq)
                            <div class="border border-gray-200 rounded-lg p-2">
                                <p class="text-sm font-medium text-gray-900">{{ $eq->nombre }}</p>
                                <p class="text-xs text-gray-500">
                                    @if($eq?->codigo)
                                        <x-codigo-short :codigo="$eq->codigo" :extra="$eq->nombre" />
                                    @else
                                        sin código
                                    @endif
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="rounded-lg border border-amber-100 bg-amber-50/80 px-3 py-2 text-xs text-amber-900">
                    <strong>Firmas:</strong> el bloque de firmas viene al final del formato; puede moverlo dentro del documento para alinearlo donde corresponda.
                </div>
                <a href="{{ route('prestamos-temporales.index') }}" class="inline-flex pw-btn-secondary px-4 py-2 rounded-lg">Cancelar</a>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('prestamoTemporalForm');
    const editable = document.getElementById('editableTemplate');
    const hidden = document.getElementById('edited_html');
    form?.addEventListener('submit', function () {
        if (hidden && editable) hidden.value = editable.innerHTML;
    });
});
</script>
@endsection
