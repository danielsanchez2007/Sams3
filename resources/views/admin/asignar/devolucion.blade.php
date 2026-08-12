@extends('layouts.admin-layout')

@section('title', 'Acta de devolución - SAMS')
@section('header-title', 'Acta de devolución')
@section('header-subtitle', 'Completa y firma el formato para enviarlo al usuario')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <form action="{{ route('asignar.devolucion.store', $solicitud->id) }}" method="POST" id="devolucionForm">
        @csrf
        <input type="hidden" name="edited_html" id="edited_html">
        <input type="hidden" name="firma_admin_confirmada" id="firma_admin_confirmada" value="0">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-8">
                <div class="pw-card rounded-2xl border border-gray-100 p-4">
                    <p class="text-lg font-semibold mb-2">Formato de devolución</p>
                    <div id="editableTemplate" contenteditable="true" class="border border-gray-200 rounded-xl p-3 overflow-auto" style="max-height:70vh;">{!! $renderedHtml !!}</div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Firma administrador (arrastra y suelta)</label>
                        <div id="dropFirmaAdmin" class="min-h-[90px] border-2 border-dashed border-cyan-400 rounded-xl bg-cyan-50 flex items-center justify-center text-cyan-700">
                            Suelta aquí la firma del administrador
                        </div>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-4 space-y-4">
                <div class="pw-card rounded-2xl border border-gray-100 p-4">
                    <p class="text-sm font-semibold">Usuario que recibe acta</p>
                    <p class="text-sm text-gray-700">{{ $solicitud->destinatario?->name }} {{ $solicitud->destinatario?->last_name }}</p>
                    <p class="text-xs text-gray-500">{{ $solicitud->destinatario?->email }}</p>
                </div>
                <div class="pw-card rounded-2xl border border-gray-100 p-4">
                    <p class="text-sm font-semibold mb-2">Selecciona equipos a devolver</p>
                    <div class="space-y-2 max-h-72 overflow-auto">
                        @foreach($equipos as $eq)
                        <label class="flex items-start gap-2 border border-gray-200 rounded-lg p-2">
                            <input type="checkbox" name="equipo_ids[]" value="{{ $eq->id }}" class="mt-1 rounded border-gray-300" checked>
                            <span class="text-xs">
                                <span class="font-medium text-gray-900">{{ $eq->nombre }}</span><br>
                                <span class="text-gray-500">
                                    @if($eq?->codigo)
                                        <x-codigo-short :codigo="$eq->codigo" :extra="$eq->nombre" />
                                    @else
                                        sin código
                                    @endif
                                </span>
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>
                <div class="pw-card rounded-2xl border border-gray-100 p-4">
                    <p class="text-sm font-semibold mb-2">Firma admin</p>
                    @if($firmaAdminUrl)
                    <img id="firmaAdminDrag" src="{{ $firmaAdminUrl }}" draggable="true" class="w-56 max-w-full border border-gray-200 rounded bg-white p-2 cursor-move">
                    @else
                    <p class="text-xs text-red-600">Debes cargar tu firma en perfil.</p>
                    @endif
                </div>
                <button type="submit" id="btnEnviarDevolucion" class="pw-btn-primary w-full px-4 py-2 rounded-lg" disabled>Enviar acta de devolución</button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const drag = document.getElementById('firmaAdminDrag');
    const drop = document.getElementById('dropFirmaAdmin');
    const btn = document.getElementById('btnEnviarDevolucion');
    const form = document.getElementById('devolucionForm');
    const hiddenHtml = document.getElementById('edited_html');
    const editable = document.getElementById('editableTemplate');
    const flag = document.getElementById('firma_admin_confirmada');
    let ok = false;

    drag?.addEventListener('dragstart', function (e) {
        e.dataTransfer.setData('text/plain', drag.src);
    });
    drop?.addEventListener('dragover', function (e) { e.preventDefault(); });
    drop?.addEventListener('drop', function (e) {
        e.preventDefault();
        const src = e.dataTransfer.getData('text/plain');
        if (!src) return;
        drop.innerHTML = '<img src="' + src + '" class="max-h-24 object-contain">';
        if (editable) {
            const existing = editable.querySelector('[data-firma-insertada="admin-devolucion"]');
            const html = '<div data-firma-insertada="admin-devolucion" style="margin-top:10px;"><strong>Firma administrador:</strong><br><img src="' + src + '" style="max-height:90px;"></div>';
            if (existing) {
                existing.outerHTML = html;
            } else {
                editable.insertAdjacentHTML('beforeend', html);
            }
        }
        ok = true;
        btn.disabled = false;
        flag.value = '1';
    });

    form?.addEventListener('submit', function (e) {
        if (!ok) {
            e.preventDefault();
            alert('Debes arrastrar la firma del administrador al formato.');
            return;
        }
        hiddenHtml.value = editable.innerHTML;
    });
});
</script>
@endsection

