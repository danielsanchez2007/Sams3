@extends('layouts.admin-layout')

@section('title', 'Aceptar solicitud - SAMS')
@section('header-title', 'Aceptar solicitud con firma')
@section('header-subtitle', 'Arrastra tu firma al campo y confirma la recepción')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    @if(!$firmaUrl)
    <div class="p-4 rounded-xl border border-red-200 bg-red-50 text-red-800">
        No tienes firma cargada en tu perfil. Debes subirla para poder aceptar.
        <a href="{{ route('profile.show') }}" class="underline font-semibold">Ir a mi perfil</a>
    </div>
    @endif

    <form method="POST" action="{{ route('asignar.solicitud.aceptar.store', $solicitud->id) }}" id="aceptarForm">
        @csrf
        <input type="hidden" name="signed_html" id="signed_html">
        <input type="hidden" name="firma_confirmada" id="firma_confirmada" value="0">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-8">
                <div class="pw-card rounded-2xl border border-gray-100 p-4">
                    <p class="text-lg font-semibold mb-2">Formulario recibido</p>
                    <p class="text-xs text-gray-500 mb-2">Puedes terminar de diligenciar este formato antes de aceptarlo.</p>
                    <div id="editableTemplate" contenteditable="true" class="border border-gray-200 rounded-xl p-3 overflow-auto bg-white" style="max-height:70vh;">
                        @safeHtml($solicitud->html_formulario)
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Campo de firma (arrastra tu firma aquí)</label>
                        <div id="dropFirma" class="min-h-[100px] border-2 border-dashed border-emerald-400 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-700">
                            Suelta aquí tu firma
                        </div>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-4 space-y-4">
                <div class="pw-card rounded-2xl p-4 border border-gray-100">
                    <p class="text-sm font-semibold text-gray-900">Tu firma</p>
                    @if($firmaUrl)
                    <img id="firmaDrag" src="{{ $firmaUrl }}" draggable="true" class="mt-2 w-56 max-w-full border border-gray-200 rounded bg-white p-2 cursor-move" alt="Firma">
                    <p class="text-xs text-gray-500 mt-2">Arrástrala al campo de firma para habilitar Aceptar.</p>
                    @endif
                </div>
                <div class="pw-card rounded-2xl p-4 border border-gray-100">
                    <p class="text-sm font-semibold mb-2">Equipos en esta solicitud</p>
                    @foreach($solicitud->items as $item)
                        <div class="text-sm text-gray-700">{{ $item->equipo?->nombre }} ({{ $item->equipo?->codigo ?? 'sin código' }})</div>
                    @endforeach
                </div>
                <button type="submit" id="btnAceptar" class="pw-btn-success px-4 py-2 rounded-lg w-full" {{ $firmaUrl ? 'disabled' : 'disabled' }}>Aceptar solicitud</button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const firma = document.getElementById('firmaDrag');
    const drop = document.getElementById('dropFirma');
    const btn = document.getElementById('btnAceptar');
    const form = document.getElementById('aceptarForm');
    const signed = document.getElementById('signed_html');
    const editable = document.getElementById('editableTemplate');
    const flag = document.getElementById('firma_confirmada');
    let hasDrop = false;

    if (firma) {
        firma.addEventListener('dragstart', function (e) {
            e.dataTransfer.setData('text/plain', firma.src);
        });
    }
    if (drop) {
        drop.addEventListener('dragover', function (e) { e.preventDefault(); });
        drop.addEventListener('drop', function (e) {
            e.preventDefault();
            const src = e.dataTransfer.getData('text/plain');
            if (!src) return;
            drop.innerHTML = '<img src="' + src + '" class="max-h-24 object-contain">';
            if (editable) {
                const existing = editable.querySelector('[data-firma-insertada="usuario-acepta"]');
                const html = '<div data-firma-insertada="usuario-acepta" style="margin-top:10px;"><strong>Firma usuario:</strong><br><img src="' + src + '" style="max-height:90px;"></div>';
                if (existing) {
                    existing.outerHTML = html;
                } else {
                    editable.insertAdjacentHTML('beforeend', html);
                }
            }
            hasDrop = true;
            if (btn) btn.disabled = false;
            if (flag) flag.value = '1';
        });
    }

    form?.addEventListener('submit', function (e) {
        if (!hasDrop) {
            e.preventDefault();
            alert('Debes arrastrar tu firma al campo antes de aceptar.');
            return;
        }
        if (signed && editable) signed.value = editable.innerHTML;
    });
});
</script>
@endsection

