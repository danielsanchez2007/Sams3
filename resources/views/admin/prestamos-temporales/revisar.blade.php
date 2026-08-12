@extends('layouts.admin-layout')

@section('title', 'Revisar devolución - SAMS')
@section('header-title', 'Revisar devolución de préstamo temporal')
@section('header-subtitle', 'Valide el formato y el estado de cada equipo')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <form method="POST" action="{{ route('prestamos-temporales.revisar', $prestamo->id) }}" id="revisionPrestamoForm" class="space-y-6">
        @csrf
        <input type="hidden" name="reviewed_html" id="reviewed_html">
        <input type="hidden" name="anticipada" value="{{ !empty($anticipada) ? 1 : 0 }}">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-8">
                <div class="pw-card rounded-2xl border border-gray-100">
                    <div class="p-4 border-b border-gray-100">
                        <p class="text-lg font-semibold text-gray-900">Formato de devolución</p>
                        <p class="text-xs text-gray-500">Puede terminar de diligenciar el formato antes de guardar la revisión.</p>
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
                    <p class="text-sm font-semibold text-gray-900 mb-2">Datos</p>
                    <p class="text-sm text-gray-700"><strong>Usuario:</strong> {{ trim(($prestamo->destinatario?->name ?? '') . ' ' . ($prestamo->destinatario?->last_name ?? '')) }}</p>
                    <p class="text-xs text-gray-500">{{ $prestamo->destinatario?->email }}</p>
                    <p class="text-xs text-gray-600 mt-2">Inicio: {{ optional($prestamo->fecha_salida)->format('Y-m-d') }}</p>
                    <p class="text-xs text-gray-600">Fin previsto: {{ optional($prestamo->fecha_fin)->format('Y-m-d') }}</p>
                </div>
                @if(!empty($anticipada))
                    <div class="pw-card rounded-2xl p-4 border border-amber-200 bg-amber-50/70 space-y-2">
                        <p class="text-sm font-semibold text-amber-900">Autorización de devolución anticipada</p>
                        <p class="text-xs text-amber-800">Para recibir antes de la fecha programada, valide con su contraseña o con la del usuario destinatario.</p>
                        <div>
                            <label class="text-xs text-amber-900">Validar con</label>
                            <select name="credential_user_id" class="w-full border rounded-lg px-3 py-2 text-sm" required>
                                <option value="{{ auth()->id() }}">Mi contraseña ({{ trim((auth()->user()->name ?? '') . ' ' . (auth()->user()->last_name ?? '')) }})</option>
                                <option value="{{ $prestamo->to_user_id }}">Contraseña del destinatario ({{ trim(($prestamo->destinatario?->name ?? '') . ' ' . ($prestamo->destinatario?->last_name ?? '')) }})</option>
                            </select>
                            @error('credential_user_id')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="text-xs text-amber-900">Contraseña</label>
                            <input type="password" name="credential_password" class="w-full border rounded-lg px-3 py-2 text-sm" required placeholder="Escriba la contraseña de autorización">
                            @error('credential_password')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endif
                <a href="{{ route('prestamos-temporales.index') }}" class="inline-flex pw-btn-secondary px-4 py-2 rounded-lg">Volver</a>
            </div>
        </div>

        <div class="pw-card rounded-2xl p-4 border border-gray-100">
            <p class="text-base font-semibold text-gray-900 mb-3">Equipos devueltos</p>
            <div class="overflow-auto">
                <table class="w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
                    <thead class="bg-gray-50 text-gray-700">
                        <tr>
                            <th class="text-left px-3 py-2 border-b">Imagen</th>
                            <th class="text-left px-3 py-2 border-b">Código</th>
                            <th class="text-left px-3 py-2 border-b">Nombre</th>
                            <th class="text-left px-3 py-2 border-b">Estado</th>
                            <th class="text-left px-3 py-2 border-b">Novedad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prestamo->items as $it)
                            @php
                                $imgGeneral = $it->equipo?->imagenes?->firstWhere('tipo', 'general');
                                $imgEtiqueta = $it->equipo?->imagenes?->firstWhere('tipo', 'etiqueta');
                                $img = $imgGeneral ?? $imgEtiqueta;
                            @endphp
                            <tr class="align-top border-b">
                                <td class="px-3 py-2">
                                    <div class="w-14 h-14 rounded overflow-hidden border border-gray-300 bg-gray-50">
                                        @if($img?->path)
                                            <img src="{{ asset('storage/' . $img->path) }}" alt="Equipo" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full grid place-items-center text-[10px] text-gray-500">Sin foto</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2 font-mono text-xs">{{ $it->equipo?->codigo ?? 'N/A' }}</td>
                                <td class="px-3 py-2">{{ $it->equipo?->nombre ?? 'Equipo' }}</td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap gap-2">
                                        <label class="px-2 py-1 rounded border">
                                            <input type="radio" name="revision[{{ $it->id }}][estado]" value="si" required checked>
                                            Bueno
                                        </label>
                                        <label class="px-2 py-1 rounded border">
                                            <input type="radio" name="revision[{{ $it->id }}][estado]" value="no" required>
                                            Malo
                                        </label>
                                        <label class="px-2 py-1 rounded border">
                                            <input type="radio" name="revision[{{ $it->id }}][estado]" value="novedad" required class="novedad-trigger" data-item="{{ $it->id }}">
                                            Con novedad
                                        </label>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <textarea
                                        name="revision[{{ $it->id }}][novedad]"
                                        rows="2"
                                        class="w-full border rounded-lg px-2 py-1 text-sm hidden novedad-textarea"
                                        id="novedad-{{ $it->id }}"
                                        placeholder="Describe la novedad"></textarea>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 flex gap-2">
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Guardar revisión</button>
                <a href="{{ route('prestamos-temporales.index') }}" class="pw-btn-secondary px-4 py-2 rounded-lg">Cancelar</a>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('revisionPrestamoForm');
    const editable = document.getElementById('editableTemplate');
    const hidden = document.getElementById('reviewed_html');

    form?.addEventListener('submit', function () {
        if (hidden && editable) hidden.value = editable.innerHTML;
    });

    document.querySelectorAll('.novedad-trigger').forEach(function (radio) {
        radio.addEventListener('change', function () {
            const itemId = radio.getAttribute('data-item');
            const ta = document.getElementById('novedad-' + itemId);
            if (ta) ta.classList.remove('hidden');
        });
    });

    document.querySelectorAll('input[type="radio"][name^="revision"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            const name = radio.getAttribute('name');
            const m = name.match(/revision\[(\d+)\]\[estado\]/);
            if (!m) return;
            const itemId = m[1];
            const ta = document.getElementById('novedad-' + itemId);
            if (!ta) return;
            if (radio.value === 'novedad' && radio.checked) {
                ta.classList.remove('hidden');
            } else if (radio.checked) {
                ta.classList.add('hidden');
                ta.value = '';
            }
        });
    });
});
</script>
@endsection
