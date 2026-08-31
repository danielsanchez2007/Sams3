@extends('layouts.admin-layout')

@section('title', 'Devolver prestamo - SAMS')
@section('header-title', 'Devolver prestamo temporal')
@section('header-subtitle', 'Firma y confirma devolucion')

@section('content')
<div class="max-w-6xl mx-auto space-y-4">
    <div class="pw-card rounded-xl p-4">
        <h3 class="font-semibold">Prestamo de {{ trim(($prestamo->creador?->name ?? '') . ' ' . ($prestamo->creador?->last_name ?? '')) }}</h3>
        <p class="text-xs text-gray-500 mt-1">{{ $prestamo->items->count() }} equipo(s)</p>
    </div>

    <form method="POST" action="{{ route('prestamos-temporales.devolver', $prestamo->id) }}" class="space-y-4">
        @csrf
        <input type="hidden" name="anticipada" value="{{ !empty($anticipada) ? 1 : 0 }}">
        <div class="pw-card rounded-xl p-4 space-y-3">
            <label class="text-sm font-medium">Formato (editable para devolución)</label>
            <div id="editor" contenteditable="true" class="border rounded-lg p-3 min-h-[260px] text-sm bg-white">@safeHtml($prestamo->html_formulario)</div>
            <input type="hidden" name="signed_html" id="signed_html">
            <input type="hidden" name="firma_confirmada" id="firma_confirmada" value="0">
        </div>

        @if(!empty($anticipada))
            <div class="pw-card rounded-xl p-3 text-xs border border-amber-200 bg-amber-50 text-amber-900">
                Está realizando una <strong>devolución anticipada</strong>. Solo se enviará si la firma se carga correctamente.
            </div>
        @endif

        <div class="pw-card rounded-xl p-4">
            <label class="text-sm font-medium">Firma obligatoria</label>
            @if($firmaUrl)
                <div class="mt-2 flex gap-3 items-start">
                    <img src="{{ $firmaUrl }}" id="firma-source" draggable="true" class="h-16 w-auto border rounded bg-white p-1 cursor-move" alt="Firma">
                    <div id="firma-drop" class="flex-1 min-h-[90px] border-2 border-dashed border-gray-300 rounded-lg p-3 text-sm text-gray-500">Arrastra aqui tu firma para confirmar.</div>
                </div>
            @else
                <div class="mt-2 text-sm text-red-600">No tienes firma en tu perfil. Debes subirla para poder devolver.</div>
            @endif
        </div>

        <div class="flex gap-2">
            <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Confirmar devolución</button>
            <a href="{{ route('prestamos-temporales.index') }}" class="pw-btn-secondary px-4 py-2 rounded-lg">Cancelar</a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    const editor = document.getElementById('editor');
    const signedInput = document.getElementById('signed_html');
    const firmaConfirm = document.getElementById('firma_confirmada');
    const source = document.getElementById('firma-source');
    const drop = document.getElementById('firma-drop');

    if (source && drop) {
        source.addEventListener('dragstart', function (e) {
            e.dataTransfer.setData('text/plain', source.src);
        });
        drop.addEventListener('dragover', function (e) {
            e.preventDefault();
            drop.classList.add('border-blue-400');
        });
        drop.addEventListener('dragleave', function () {
            drop.classList.remove('border-blue-400');
        });
        drop.addEventListener('drop', function (e) {
            e.preventDefault();
            drop.classList.remove('border-blue-400');
            const src = e.dataTransfer.getData('text/plain');
            if (!src) return;
            drop.innerHTML = '<img src="' + src + '" class="h-14 w-auto" alt="Firma pegada">';
            firmaConfirm.value = '1';
            editor.insertAdjacentHTML('beforeend', '<p><strong>Firma usuario:</strong></p><img src="' + src + '" style="max-height:70px;">');
        });
    }

    form.addEventListener('submit', function (e) {
        signedInput.value = editor.innerHTML;
        if (firmaConfirm.value !== '1') {
            e.preventDefault();
            alert('Debes arrastrar tu firma antes de confirmar.');
        }
    });
});
</script>
@endsection

