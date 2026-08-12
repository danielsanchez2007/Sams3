@extends('layouts.admin-layout')

@section('title', 'Vista previa - Hoja de Vida Completa - SAMS')
@section('header-title', 'Hoja de Vida Completa')
@section('header-subtitle', ($equipo->codigo ?? $equipo->id) . ' — ' . ($equipo->nombre ?? ''))

@section('header-actions')
<div class="flex items-center gap-2">
    <a href="{{ route('exportar.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 text-sm font-medium">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Volver a Exportar
    </a>
    <form id="formDescargar" action="{{ route('exportar.hoja-vida-completa.download', $equipo) }}" method="POST" class="inline" target="_blank">
        @csrf
        <input type="hidden" name="edited_html" id="editedHtmlDescargar">
        <button type="submit" class="pw-btn-dark inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold shadow-sm">
            <i data-lucide="file-down" class="w-4 h-4"></i>
            Descargar PDF
        </button>
    </form>
</div>
@endsection

@section('content')
<div class="max-w-5xl mx-auto space-y-4">
    @if(session('success'))
        <div class="p-4 rounded-xl border border-green-200 bg-green-50 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="p-4 rounded-xl border border-red-200 bg-red-50 text-red-800 text-sm">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <p class="text-sm text-gray-600">
            Edita el documento, inserta tablas o usa el <strong>Borrador</strong> (actívalo y haz clic en la línea que quieras eliminar, como en Word). Los cambios se reflejan al guardar.
        </p>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('exportar.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Volver</a>
            <form id="formGuardar" action="{{ route('exportar.hoja-vida-completa.guardar-hoja-vida', $equipo) }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="edited_html" id="editedHtmlGuardar">
                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 text-sm font-semibold">Guardar en Hoja de Vida</button>
            </form>
        </div>
    </div>

    {{-- Barra de herramientas tipo Word --}}
    <div class="bg-white rounded-xl border border-gray-200 p-2 flex flex-wrap gap-1">
        <button type="button" onclick="document.execCommand('bold')" class="p-2 rounded-lg hover:bg-gray-100 border border-gray-200" title="Negrita">
            <strong>B</strong>
        </button>
        <button type="button" onclick="document.execCommand('italic')" class="p-2 rounded-lg hover:bg-gray-100 border border-gray-200" title="Cursiva">
            <em>I</em>
        </button>
        <button type="button" onclick="document.execCommand('underline')" class="p-2 rounded-lg hover:bg-gray-100 border border-gray-200 text-sm underline" title="Subrayado">U</button>
        <span class="w-px bg-gray-300 my-1"></span>
        <button type="button" id="btnBorrador" class="px-2 py-1 rounded-lg border border-amber-300 text-amber-700 text-sm flex items-center gap-1" title="Activa el borrador y haz clic en la línea que quieras eliminar (como en Word)">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 20H7L3 16c-1-1 1-3 2-2l3 3"/><path d="m15 5 4 4-9 9-4-4 9-9z"/></svg>
            Borrador
        </button>
        <button type="button" id="btnInsertarTabla" class="px-2 py-1 rounded-lg hover:bg-gray-100 border border-gray-200 text-sm" title="Insertar tabla">Tabla</button>
        <button type="button" id="btnEliminarTabla" class="px-2 py-1 rounded-lg hover:bg-gray-100 border border-red-200 text-red-600 text-sm" title="Eliminar tabla completa">Eliminar tabla</button>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="doc-preview-wrapper p-4 md:p-6 mx-auto" style="background: #f8fafc; max-width: 210mm;">
            <style>{{ $docCss }}</style>
            <style>.doc-editable.borrador-activo tr:hover,.doc-editable.borrador-activo p:hover{background:rgba(251,191,36,0.3);outline:1px dashed #f59e0b;}</style>
            <div id="editableDoc" contenteditable="true" class="outline-none min-h-[400px] doc-editable" style="font-size: 10px;">
                {!! $previewBody !!}
            </div>
        </div>
    </div>
</div>

{{-- Modal para insertar tabla --}}
<div id="modalTabla" class="fixed inset-0 z-50 hidden items-center justify-center" style="background: rgba(0,0,0,0.4);">
    <div class="bg-white rounded-xl p-6 shadow-xl max-w-sm w-full mx-4">
        <h3 class="font-semibold mb-4">Insertar tabla</h3>
        <div class="flex gap-4 mb-4">
            <label class="flex flex-col">
                <span class="text-xs text-gray-500">Filas</span>
                <input type="number" id="tablaFilas" value="3" min="1" max="20" class="border rounded-lg px-3 py-2">
            </label>
            <label class="flex flex-col">
                <span class="text-xs text-gray-500">Columnas</span>
                <input type="number" id="tablaColumnas" value="3" min="1" max="10" class="border rounded-lg px-3 py-2">
            </label>
        </div>
        <div class="flex gap-2 justify-end">
            <button type="button" id="modalTablaCancelar" class="px-4 py-2 rounded-lg border border-gray-300 hover:bg-gray-50">Cancelar</button>
            <button type="button" id="modalTablaOk" class="pw-btn-primary px-4 py-2 rounded-lg">Insertar</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    const editable = document.getElementById('editableDoc');
    const formGuardar = document.getElementById('formGuardar');
    const formDescargar = document.getElementById('formDescargar');
    const inputGuardar = document.getElementById('editedHtmlGuardar');
    const inputDescargar = document.getElementById('editedHtmlDescargar');

    function getEditedHtml() {
        return editable ? editable.innerHTML : '';
    }

    if (formGuardar && inputGuardar) {
        formGuardar.addEventListener('submit', function() {
            inputGuardar.value = getEditedHtml();
        });
    }
    if (formDescargar && inputDescargar) {
        formDescargar.addEventListener('submit', function() {
            inputDescargar.value = getEditedHtml();
        });
    }

    document.getElementById('btnInsertarTabla').addEventListener('click', function() {
        document.getElementById('modalTabla').classList.remove('hidden');
        document.getElementById('modalTabla').classList.add('flex');
    });
    document.getElementById('modalTablaCancelar').addEventListener('click', function() {
        document.getElementById('modalTabla').classList.add('hidden');
        document.getElementById('modalTabla').classList.remove('flex');
    });
    document.getElementById('modalTablaOk').addEventListener('click', function() {
        const filas = parseInt(document.getElementById('tablaFilas').value) || 3;
        const cols = parseInt(document.getElementById('tablaColumnas').value) || 3;
        let html = '<table class="seccion-tabla" style="width:100%;border-collapse:collapse;margin:8px 0;"><tbody>';
        for (let r = 0; r < filas; r++) {
            html += '<tr>';
            for (let c = 0; c < cols; c++) {
                html += '<td style="border:1px solid #ccc;padding:6px;"> </td>';
            }
            html += '</tr>';
        }
        html += '</tbody></table>';
        document.execCommand('insertHTML', false, html);
        document.getElementById('modalTabla').classList.add('hidden');
        document.getElementById('modalTabla').classList.remove('flex');
    });

    let modoBorrador = false;
    const btnBorrador = document.getElementById('btnBorrador');
    const skipClasses = ['doc-seccion', 'doc-seccion-body', 'doc-seccion-title', 'doc-body', 'doc-header'];

    function getLineToErase(node) {
        if (!node || !editable.contains(node)) return null;
        while (node && node !== editable) {
            const tag = (node.tagName || '').toUpperCase();
            const cls = typeof node.className === 'string' ? node.className : '';
            if (tag === 'TR') return node;
            if (tag === 'TD' || tag === 'TH') {
                const tr = node.parentElement;
                if (tr && (tr.tagName || '').toUpperCase() === 'TR') return tr;
            }
            if (['P', 'DIV', 'LI', 'H1', 'H2', 'H3', 'H4'].includes(tag) && !node.closest('table')) {
                if (!skipClasses.some(s => cls.includes(s))) return node;
            }
            node = node.parentElement;
        }
        return null;
    }

    btnBorrador.addEventListener('click', function(e) {
        e.preventDefault();
        modoBorrador = !modoBorrador;
        btnBorrador.classList.toggle('bg-amber-200', modoBorrador);
        btnBorrador.classList.toggle('ring-2', modoBorrador);
        btnBorrador.classList.toggle('ring-amber-500', modoBorrador);
        editable.style.cursor = modoBorrador ? 'crosshair' : '';
        editable.setAttribute('contenteditable', !modoBorrador);
        editable.classList.toggle('borrador-activo', modoBorrador);
    });

    editable.addEventListener('click', function(e) {
        if (!modoBorrador) return;
        e.preventDefault();
        e.stopPropagation();
        const target = getLineToErase(e.target);
        if (target && target.parentNode) {
            target.remove();
        }
    }, true);

    document.getElementById('btnEliminarTabla').addEventListener('click', function() {
        const sel = window.getSelection();
        let node = sel.anchorNode;
        while (node && node.nodeType !== 1) node = node.parentNode;
        while (node) {
            if (node.tagName === 'TABLE') {
                node.remove();
                return;
            }
            node = node.parentElement;
        }
        alert('Coloca el cursor dentro de una tabla para eliminarla.');
    });
});
</script>
@endsection
