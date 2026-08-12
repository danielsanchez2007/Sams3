@extends('layouts.admin-layout')

@section('title', 'Ver / editar formato - SAMS')
@section('header-title', 'Ver / editar formato')
@section('header-subtitle', 'Seguimiento de solicitud #' . $solicitud->id)

@section('content')
<div class="max-w-7xl mx-auto space-y-4">
    <form method="POST" action="{{ route('asignar.seguimiento.formato.store', $solicitud->id) }}" id="editFormatoForm">
        @csrf
        <input type="hidden" name="edited_html" id="edited_html">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-8">
                <div class="pw-card rounded-2xl border border-gray-100 p-4">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-lg font-semibold text-gray-900">Formato</p>
                        @php($bloqueadoAdmin = (($solicitud->tipo ?? 'entrega') === 'devolucion' && ($solicitud->workflow_step ?? '') === 'pendiente_revision_admin'))
                        @if($bloqueadoAdmin)
                        <span class="text-xs px-2 py-1 rounded bg-amber-100 text-amber-800">Bloqueado: pendiente revisión</span>
                        @else
                        <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Guardar cambios</button>
                        @endif
                    </div>
                    <div id="editableTemplate" contenteditable="{{ $bloqueadoAdmin ? 'false' : 'true' }}" class="border border-gray-200 rounded-xl p-3 overflow-auto bg-white" style="max-height:70vh;">
                        {!! $solicitud->html_formulario !!}
                    </div>
                </div>
            </div>
            <div class="lg:col-span-4">
                <div class="pw-card rounded-2xl border border-gray-100 p-4 mb-4">
                    <p class="font-semibold text-gray-900">Resumen</p>
                    <p class="text-sm text-gray-700 mt-2">Tipo: {{ strtoupper($solicitud->tipo ?? 'entrega') }}</p>
                    <p class="text-sm text-gray-700">Estado: {{ ucfirst($solicitud->estado) }}</p>
                    <p class="text-sm text-gray-700">Usuario: {{ $solicitud->destinatario?->name }} {{ $solicitud->destinatario?->last_name }}</p>
                    <p class="text-sm text-gray-700">Equipos: {{ $solicitud->items->count() }}</p>
                </div>

                <div class="pw-card rounded-2xl border border-gray-100 p-4">
                    <p class="font-semibold text-gray-900 mb-2">Tu firma (arrastrable)</p>
                    @if($firmaAdminUrl)
                    <img id="firmaDrag" src="{{ $firmaAdminUrl }}" draggable="true" class="w-56 max-w-full border border-gray-200 rounded bg-white p-2 cursor-move" alt="Firma admin">
                    <p class="text-xs text-gray-500 mt-2">Arrástrala y suéltala en el recuadro de firma.</p>
                    @else
                    <p class="text-xs text-red-600">No tienes firma cargada en perfil.</p>
                    @endif
                    <div class="mt-3">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Recuadro de firma</label>
                        <div id="dropFirma" class="min-h-[90px] border-2 border-dashed border-cyan-400 rounded-xl bg-cyan-50 flex items-center justify-center text-cyan-700 text-sm">
                            Suelta aquí tu firma
                        </div>
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
    const form = document.getElementById('editFormatoForm');
    const editable = document.getElementById('editableTemplate');
    const hidden = document.getElementById('edited_html');
    const drag = document.getElementById('firmaDrag');
    const drop = document.getElementById('dropFirma');
    const bloqueado = editable?.getAttribute('contenteditable') === 'false';

    drag?.addEventListener('dragstart', function (e) {
        if (bloqueado) return;
        e.dataTransfer.setData('text/plain', drag.src);
    });
    drop?.addEventListener('dragover', function (e) { if (!bloqueado) e.preventDefault(); });
    drop?.addEventListener('drop', function (e) {
        if (bloqueado) return;
        e.preventDefault();
        const src = e.dataTransfer.getData('text/plain');
        if (!src) return;
        drop.innerHTML = '<img src="' + src + '" class="max-h-24 object-contain">';
        if (editable) {
            const existing = editable.querySelector('[data-firma-insertada="admin-seguimiento"]');
            const html = '<div data-firma-insertada="admin-seguimiento" style="margin-top:10px;"><strong>Firma administrador:</strong><br><img src="' + src + '" style="max-height:90px;"></div>';
            if (existing) {
                existing.outerHTML = html;
            } else {
                editable.insertAdjacentHTML('beforeend', html);
            }
        }
    });

    form?.addEventListener('submit', function (e) {
        if (bloqueado) {
            e.preventDefault();
            return;
        }
        if (hidden && editable) hidden.value = editable.innerHTML;
    });
});
</script>
@endsection

