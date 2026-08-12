@extends('layouts.admin-layout')

@section('title', 'Vista previa asignación - SAMS')
@section('header-title', 'Formato de asignación / traspaso')
@section('header-subtitle', 'Diligencia el formato antes de enviarlo al usuario destino')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <form action="{{ route('asignar.solicitud') }}" method="POST" id="solicitudForm" class="space-y-6">
        @csrf
        <input type="hidden" name="to_user_id" value="{{ $toUser->id }}">
        @foreach($equipoIds as $id)
        <input type="hidden" name="equipo_ids[]" value="{{ $id }}">
        @endforeach
        <input type="hidden" name="edited_html" id="edited_html">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-8">
                <div class="pw-card rounded-2xl border border-gray-100">
                    <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-lg font-semibold text-gray-900">Vista previa del formato</p>
                            <p class="text-xs text-gray-500">Se enviará al usuario para aceptación con firma.</p>
                        </div>
                        <button type="submit" class="pw-btn-success px-4 py-2 rounded-lg">Enviar solicitud</button>
                    </div>
                    <div class="p-4 overflow-auto" style="max-height:70vh;">
                        <div id="editableTemplate" contenteditable="true" class="outline-none">
                            {!! $renderedHtml !!}
                        </div>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-4 space-y-4">
                <div class="pw-card rounded-2xl p-4 border border-gray-100">
                    <p class="text-sm font-semibold text-gray-900 mb-2">Usuario destino</p>
                    <p class="text-sm text-gray-700">{{ $toUser->name }} {{ $toUser->last_name }}</p>
                    <p class="text-xs text-gray-500">{{ $toUser->email }}</p>
                </div>
                <div class="pw-card rounded-2xl p-4 border border-gray-100">
                    <p class="text-sm font-semibold text-gray-900 mb-2">Equipos seleccionados</p>
                    <div class="space-y-2 max-h-72 overflow-auto">
                        @foreach($equipos as $eq)
                        <div class="border border-gray-200 rounded-lg p-2">
                            <p class="text-sm font-medium text-gray-900">{{ $eq->nombre }}</p>
                            <p class="text-xs text-gray-500">{{ $eq->codigo ?? 'sin código' }}</p>
                            @if($eq->asignacion?->user)
                            <p class="text-xs text-amber-700">Actualmente asignado a: {{ $eq->asignacion->user->name }} {{ $eq->asignacion->user->last_name }}</p>
                            @else
                            <p class="text-xs text-emerald-700">Equipo disponible (sin asignación)</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('solicitudForm');
    const editable = document.getElementById('editableTemplate');
    const hidden = document.getElementById('edited_html');
    form?.addEventListener('submit', function () {
        if (hidden && editable) hidden.value = editable.innerHTML;
    });
});
</script>
@endsection

