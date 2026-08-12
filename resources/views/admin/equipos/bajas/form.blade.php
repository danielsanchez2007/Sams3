@extends('layouts.admin-layout')

@section('title', 'Dar de baja - SAMS')
@section('header-title', 'Dar de baja')
@section('header-subtitle', 'Llenar formato y generar PDF')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('equipos.bajas.index') }}" class="pw-btn-secondary px-4 py-2 rounded-lg">Volver</a>
        <div class="text-sm text-gray-600">Equipo: <span class="font-semibold text-gray-900">{{ $equipo->codigo }} - {{ $equipo->nombre }}</span></div>
    </div>

    @php
        $isEdit = isset($baja);
        $saveRoute = $isEdit ? route('equipos.bajas.update', $baja) : route('equipos.bajas.store', $equipo);
        $saveLabel = $isEdit ? 'Actualizar y regenerar PDF' : 'Guardar y generar PDF';
        $resumenValue = old('resumen_baja', $isEdit ? ($baja->motivo_baja ?? '') : '');
    @endphp

    @if($errors->any())
        <div class="p-4 rounded-lg bg-red-50 border border-red-200 text-red-800">
            <div class="font-semibold mb-1">Revisa los campos</div>
            <ul class="list-disc pl-6 text-sm">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $saveRoute }}" method="POST" class="space-y-6" id="bajaForm">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif
        @if($inspeccion ?? null)
            <input type="hidden" name="inspeccion_id" value="{{ $inspeccion->id }}">
            <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm">
                Vinculado a la inspección actual. Al guardar se marcará esta inspección como «dada de baja».
            </div>
        @endif

        <div class="pw-card bg-white rounded-xl shadow-lg p-4 border border-gray-100">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Fecha de baja</label>
                    <input type="text" value="{{ now()->format('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50" readonly>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Resumen de la baja</label>
                    <textarea name="resumen_baja" class="w-full px-3 py-2 border border-gray-300 rounded-lg" rows="3" required>{{ $resumenValue }}</textarea>
                </div>
            </div>
        </div>


        <input type="hidden" name="edited_html" id="editedHtml" value="">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <div class="lg:col-span-9">
                <div class="pw-card bg-white rounded-xl shadow-lg p-4 border border-gray-100">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <div class="text-lg font-semibold text-gray-900">{{ !empty($usaActaIntegrada) ? 'Acta de baja (integrada)' : 'Formato' }}</div>
                            <div class="text-xs text-gray-500">
                                @if(!empty($usaActaIntegrada))
                                    Vista previa del acta profesional. Al guardar se exportará en PDF.
                                @else
                                    Vista editable del Excel importado. El PDF se generará exactamente con este formato.
                                @endif
                            </div>
                        </div>
                        <button type="submit" class="pw-btn-success px-4 py-2 rounded-lg">{{ $saveLabel }}</button>
                    </div>

                    <div id="editableContainer" class="baja-editable-container overflow-auto border border-gray-200 rounded-lg p-4 bg-white">
                        <div id="editableTemplate" contenteditable="true" class="outline-none baja-excel-view">
                            {!! $renderedHtml !!}
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-3 space-y-4">
                <div class="pw-card bg-white rounded-xl shadow-lg p-4 border border-gray-100 space-y-3 sticky top-4 max-h-[85vh] overflow-auto">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">Datos del equipo</div>
                        <div class="text-xs text-gray-500">Ficha completa del equipo seleccionado.</div>
                    </div>

                    @if(!empty($equipoDetalle['imagen']))
                        <div class="w-full aspect-square rounded-lg overflow-hidden border border-gray-200 bg-gray-50">
                            <img src="{{ $equipoDetalle['imagen'] }}" alt="Equipo" class="w-full h-full object-cover">
                        </div>
                    @endif

                    <div class="text-sm font-semibold text-gray-800 break-words">{{ $equipoDetalle['titulo'] ?? ($equipo->codigo . ' — ' . $equipo->nombre) }}</div>

                    <div class="space-y-2">
                        @forelse(($equipoDetalle['campos'] ?? []) as $campo)
                            <div class="rounded-lg border border-gray-100 bg-gray-50 px-2.5 py-2">
                                <div class="text-[10px] uppercase tracking-wide text-gray-500 font-semibold">{{ $campo['label'] }}</div>
                                <div class="text-xs text-gray-900 whitespace-pre-line break-words mt-0.5">{{ $campo['value'] !== '' ? $campo['value'] : '—' }}</div>
                            </div>
                        @empty
                            <div class="text-xs text-gray-500">No hay datos adicionales del equipo.</div>
                        @endforelse
                    </div>
                </div>

                <div class="pw-card bg-white rounded-xl shadow-lg p-4 border border-gray-100 space-y-3">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">Usuario y firma</div>
                        <div class="text-xs text-gray-500">Selecciona un usuario y arrastra su firma al formato.</div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Usuario</label>
                        <select id="bajaUserSelect" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <option value="">Seleccionar usuario</option>
                            @foreach(($usuarios ?? []) as $u)
                                <option value="{{ $u->id }}">{{ trim($u->name . ' ' . ($u->last_name ?? '')) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="text-xs text-gray-600 space-y-1">
                        <div><span class="text-gray-500">Nombre:</span> <span id="bajaUserName">—</span></div>
                        <div><span class="text-gray-500">Documento:</span> <span id="bajaUserDoc">—</span></div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <div class="text-[11px] text-gray-500 mb-1">Foto</div>
                            <div class="w-full aspect-square rounded-lg border border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center">
                                <img id="bajaUserPhoto" src="" alt="Foto" class="w-full h-full object-cover hidden">
                                <span id="bajaUserPhotoPh" class="text-xs text-gray-400">Sin foto</span>
                            </div>
                        </div>
                        <div>
                            <div class="text-[11px] text-gray-500 mb-1">Firma</div>
                            <div class="w-full h-24 rounded-lg border border-gray-200 bg-white overflow-hidden flex items-center justify-center p-1">
                                <img id="bajaUserSignature" src="" alt="Firma" class="max-w-full max-h-full object-contain hidden cursor-grab" draggable="true">
                                <span id="bajaUserSignPh" class="text-xs text-gray-400">Sin firma</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-2">
                        Arrastra la firma y suéltala dentro del formato.
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="pw-btn-success px-4 py-2 rounded-lg">{{ $saveLabel }}</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const form = document.getElementById('bajaForm');
    const editable = document.getElementById('editableTemplate');
    const container = document.getElementById('editableContainer');
    const hidden = document.getElementById('editedHtml');

    if (form && editable && hidden) {
        form.addEventListener('submit', function() {
            hidden.value = editable.innerHTML;
        });
    }

    const users = @json($usuariosJs ?? []);
    const userSelect = document.getElementById('bajaUserSelect');
    const userName = document.getElementById('bajaUserName');
    const userDoc = document.getElementById('bajaUserDoc');
    const userPhoto = document.getElementById('bajaUserPhoto');
    const userPhotoPh = document.getElementById('bajaUserPhotoPh');
    const userSign = document.getElementById('bajaUserSignature');
    const userSignPh = document.getElementById('bajaUserSignPh');

    function renderUser() {
        const id = parseInt((userSelect && userSelect.value) || '0', 10);
        const u = users.find(x => parseInt(x.id, 10) === id);
        if (!u) {
            userName.textContent = '—';
            userDoc.textContent = '—';
            userPhoto.classList.add('hidden');
            userPhoto.removeAttribute('src');
            userPhotoPh.classList.remove('hidden');
            userSign.classList.add('hidden');
            userSign.removeAttribute('src');
            userSignPh.classList.remove('hidden');
            return;
        }

        userName.textContent = u.name || '—';
        userDoc.textContent = u.document_number || '—';

        if (u.photo) {
            userPhoto.src = u.photo;
            userPhoto.classList.remove('hidden');
            userPhotoPh.classList.add('hidden');
        } else {
            userPhoto.classList.add('hidden');
            userPhoto.removeAttribute('src');
            userPhotoPh.classList.remove('hidden');
        }

        if (u.signature) {
            userSign.src = u.signature;
            userSign.classList.remove('hidden');
            userSignPh.classList.add('hidden');
        } else {
            userSign.classList.add('hidden');
            userSign.removeAttribute('src');
            userSignPh.classList.remove('hidden');
        }
    }

    if (userSelect) {
        userSelect.addEventListener('change', renderUser);
        renderUser();
    }

    function getCaretRangeFromPoint(x, y) {
        if (document.caretRangeFromPoint) return document.caretRangeFromPoint(x, y);
        if (document.caretPositionFromPoint) {
            const pos = document.caretPositionFromPoint(x, y);
            if (!pos) return null;
            const range = document.createRange();
            range.setStart(pos.offsetNode, pos.offset);
            range.collapse(true);
            return range;
        }
        return null;
    }

    function insertHtmlAtPoint(html, x, y) {
        editable.focus();
        const range = getCaretRangeFromPoint(x, y);
        const sel = window.getSelection();
        if (range && sel) {
            sel.removeAllRanges();
            sel.addRange(range);
        }
        if (!sel || sel.rangeCount === 0) {
            editable.insertAdjacentHTML('beforeend', html);
            return;
        }
        const r = sel.getRangeAt(0);
        r.deleteContents();
        const frag = r.createContextualFragment(html);
        const lastNode = frag.lastChild;
        r.insertNode(frag);
        if (lastNode) {
            r.setStartAfter(lastNode);
            r.setEndAfter(lastNode);
            sel.removeAllRanges();
            sel.addRange(r);
        }
    }

    if (userSign) {
        userSign.addEventListener('dragstart', function(e) {
            if (!userSign.src) return;
            const html = '<img src="' + userSign.src + '" alt="Firma" class="baja-signature-inline">';
            e.dataTransfer.setData('text/html', html);
            e.dataTransfer.effectAllowed = 'copy';
        });
    }

    if (container && editable) {
        container.addEventListener('dragover', function(e) {
            e.preventDefault();
        });
        container.addEventListener('drop', function(e) {
            e.preventDefault();
            const html = e.dataTransfer.getData('text/html');
            if (!html) return;
            insertHtmlAtPoint(html, e.clientX, e.clientY);
        });
    }

    // Mueve estilos embebidos del HTML importado al <head> para conservar
    // colores, logos, bordes y tipografías del Excel convertido.
    if (editable) {
        const styles = editable.querySelectorAll('style');
        styles.forEach((styleEl, idx) => {
            const id = 'baja-template-style-' + idx;
            if (document.getElementById(id)) return;
            const style = document.createElement('style');
            style.id = id;
            style.textContent = styleEl.textContent || '';
            document.head.appendChild(style);
        });
    }
});
</script>
@endsection
