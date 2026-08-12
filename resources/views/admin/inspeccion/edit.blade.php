@extends('layouts.admin-layout')

@section('title', 'Editar inspección - ' . ($equipo->codigo ?: $equipo->id) . ' - SAMS')
@section('header-title', 'Editar inspección')
@section('header-subtitle')
    <x-codigo-short :codigo="$equipo->codigo" :extra="$equipo->nombre" />
@endsection

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('inspeccion.equipos', $clase) }}" class="pw-btn-secondary px-3 py-2 text-sm rounded-lg">
        <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>
        Volver
    </a>
    <button type="button" id="btnExportarPdf" class="pw-btn-dark px-3 py-2 text-sm rounded-lg" data-preview-url="{{ route('inspeccion.html', $inspeccion) }}" data-download-url="{{ route('inspeccion.download', $inspeccion) }}">Exportar PDF</button>
</div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    @if (session('success'))
        <div class="p-4 rounded-2xl border border-green-200 bg-green-50 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="p-4 rounded-2xl border border-yellow-200 bg-yellow-50 text-yellow-900 text-sm">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="p-4 rounded-2xl border border-red-200 bg-red-50 text-red-800 text-sm">
            @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    @if($inspeccion->dado_de_baja && $inspeccion->equipoBaja)
        <div class="bg-amber-50 rounded-2xl border border-amber-200 p-5">
            <div class="text-lg font-semibold text-amber-900 mb-2">Inspección dada de baja</div>
            <p class="text-sm text-amber-800 mb-3">En esta inspección se dio de baja el equipo. Es la última inspección antes de la baja.</p>
            <a href="{{ route('equipos.bajas.pdf.show', $inspeccion->equipoBaja) }}" target="_blank" class="pw-btn-reemplazar inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                Ver formato de baja
            </a>
        </div>
    @endif

    <div class="pw-card bg-white rounded-2xl shadow-lg p-5 border border-gray-100">
        <div class="text-lg font-semibold text-gray-900 mb-2">Datos de la inspección</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500">Fecha inspección:</span>
                <span class="font-medium">{{ $inspeccion->fecha_inspeccion?->format('d/m/Y') ?? '—' }}</span>
            </div>
            <div>
                <span class="text-gray-500">Validez hasta:</span>
                <span class="font-medium">{{ $inspeccion->validez_hasta?->format('d/m/Y') ?? '—' }}</span>
                <span class="text-gray-400 text-xs">(no se puede modificar)</span>
            </div>
        </div>
    </div>

    <form action="{{ route('inspeccion.update', $inspeccion) }}" method="POST" id="editForm" class="space-y-6">
        @csrf
        @method('PUT')
        <input type="hidden" name="edited_html" id="editedHtml" value="">

        <div class="pw-card bg-white rounded-2xl shadow-lg p-5 border border-gray-100">
            <div class="text-sm font-semibold text-gray-900 mb-4">Inspector y usuarios</div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Cantidad de usuarios <span class="text-red-500">*</span></label>
                    <select name="cantidad_usuarios" id="cantidadUsuarios" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @for($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}" {{ (count($inspeccion->selected_user_ids ?? []) ?: 1) == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Inspector <span class="text-red-500">*</span></label>
                    <select name="inspector_user_id" id="inspectorUserSelect" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="">— Seleccionar inspector —</option>
                        @foreach($usuarios ?? [] as $u)
                            <option value="{{ $u->id }}" {{ $inspeccion->inspector_user_id == $u->id ? 'selected' : '' }}>{{ trim($u->name . ' ' . ($u->last_name ?? '')) }}</option>
                        @endforeach
                    </select>
                    <div id="inspectorPreview" class="mt-2 flex gap-2 items-start flex-wrap hidden">
                        <div class="text-center">
                            <div class="text-[10px] text-gray-500 mb-1">Foto</div>
                            <img id="inspectorPhoto" src="" alt="" class="w-16 h-16 object-cover rounded-lg border border-gray-200 hidden">
                            <div id="inspectorPhotoPlaceholder" class="w-16 h-16 rounded-lg border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center text-[10px] text-gray-400">—</div>
                        </div>
                        <div class="text-center">
                            <div class="text-[10px] text-gray-500 mb-1">Firma</div>
                            <img id="inspectorSignature" src="" alt="" class="w-24 h-12 object-contain border border-gray-200 hidden">
                            <div id="inspectorSignPlaceholder" class="w-24 h-12 rounded border border-dashed border-gray-300 bg-gray-50 flex items-center justify-center text-[10px] text-gray-400">—</div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="usuariosContainer" class="mt-4">
                <label class="block text-xs font-medium text-gray-500 mb-2">Usuarios</label>
                <div id="usuariosSelects"></div>
            </div>
        </div>

        <div class="pw-card bg-white rounded-2xl shadow-lg border border-gray-100">
            <div class="flex items-center justify-between p-4 border-b border-gray-100">
                <div>
                    <div class="text-lg font-semibold text-gray-900">Contenido</div>
                    <div class="text-xs text-gray-500">Puedes editar solo el contenido. La fecha de validez no se modifica.</div>
                </div>
                <button type="submit" class="pw-btn-success px-4 py-2 rounded-lg">Guardar cambios</button>
            </div>
            <div id="editableContainer" class="overflow-auto border border-gray-200 rounded-b-2xl p-4 bg-white" style="max-height: 65vh;">
                <div id="editableTemplate" contenteditable="true" class="outline-none">
                    {!! $inspeccion->edited_html !!}
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center">
            <a href="{{ route('inspeccion.dar-de-baja', $equipo) }}?inspeccion_id={{ $inspeccion->id }}" class="pw-btn-dar-baja inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium">
                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                Inspección dada de baja (ir a dar de baja equipo)
            </a>
            <button type="submit" class="pw-btn-success px-4 py-2 rounded-lg">Guardar cambios</button>
        </div>
    </form>

    {{-- Modal vista previa PDF (igual que Hoja de Vida) --}}
    <div id="pdfPreviewModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="fixed inset-0 bg-black/50" id="pdfPreviewModalBackdrop"></div>
        <div class="fixed inset-4 md:inset-8 lg:inset-12 flex flex-col bg-white rounded-2xl shadow-2xl overflow-hidden z-10">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50">
                <h3 class="text-lg font-semibold text-gray-900">Vista previa del PDF</h3>
                <div class="flex items-center gap-2">
                    <a id="pdfPreviewDownload" href="#" target="_blank" class="pw-btn-primary px-4 py-2 rounded-lg text-sm font-medium">Descargar PDF</a>
                    <button type="button" id="pdfPreviewClose" class="p-2 rounded-lg hover:bg-gray-200 text-gray-600">Cerrar</button>
                </div>
            </div>
            <div class="flex-1 min-h-0 p-2">
                <iframe id="pdfPreviewIframe" class="w-full h-full border border-gray-200 rounded-lg bg-white" title="Vista previa"></iframe>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    var form = document.getElementById('editForm');
    var editable = document.getElementById('editableTemplate');
    var hidden = document.getElementById('editedHtml');
    if (form && editable && hidden) {
        form.addEventListener('submit', function() {
            hidden.value = editable.innerHTML;
        });
    }

    var pdfModal = document.getElementById('pdfPreviewModal');
    var pdfIframe = document.getElementById('pdfPreviewIframe');
    var pdfDownload = document.getElementById('pdfPreviewDownload');
    var pdfClose = document.getElementById('pdfPreviewClose');
    var pdfBackdrop = document.getElementById('pdfPreviewModalBackdrop');
    var btnExportar = document.getElementById('btnExportarPdf');
    if (btnExportar && pdfModal) {
        btnExportar.addEventListener('click', function() {
            var previewUrl = btnExportar.getAttribute('data-preview-url');
            var downloadUrl = btnExportar.getAttribute('data-download-url');
            if (previewUrl && pdfIframe) pdfIframe.src = previewUrl + '?t=' + Date.now();
            if (downloadUrl && pdfDownload) pdfDownload.href = downloadUrl;
            pdfModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });
    }
    if (pdfClose) pdfClose.addEventListener('click', closePdfModal);
    if (pdfBackdrop) pdfBackdrop.addEventListener('click', closePdfModal);
    function closePdfModal() {
        if (pdfModal) {
            pdfModal.classList.add('hidden');
            document.body.style.overflow = '';
            if (pdfIframe) pdfIframe.src = 'about:blank';
        }
    }

    var usuarios = @json($usuariosJs ?? []);
    var initialSelectedIds = @json($inspeccion->selected_user_ids ?? []);
    var cantidadSelect = document.getElementById('cantidadUsuarios');
    var usuariosSelectsDiv = document.getElementById('usuariosSelects');
    var inspectorSelect = document.getElementById('inspectorUserSelect');
    var inspectorPhoto = document.getElementById('inspectorPhoto');
    var inspectorSignature = document.getElementById('inspectorSignature');
    var inspectorPhotoPlaceholder = document.getElementById('inspectorPhotoPlaceholder');
    var inspectorSignPlaceholder = document.getElementById('inspectorSignPlaceholder');
    var inspectorPreview = document.getElementById('inspectorPreview');

    function renderUsuariosSelects() {
        var n = parseInt(cantidadSelect ? cantidadSelect.value : 1, 10) || 1;
        usuariosSelectsDiv.innerHTML = '';
        for (var i = 1; i <= n; i++) {
            var selId = initialSelectedIds[i - 1] || '';
            var div = document.createElement('div');
            div.className = 'flex gap-2 items-center mb-2';
            div.innerHTML = '<label class="text-xs text-gray-500 w-24">Usuario ' + i + '</label>' +
                '<select name="selected_user_ids[]" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">' +
                '<option value="">— Seleccionar —</option>' +
                usuarios.map(function(u) { return '<option value="' + u.id + '"' + (parseInt(selId,10)===parseInt(u.id,10)?' selected':'') + '>' + (u.name || 'Usuario ' + u.id) + '</option>'; }).join('') +
                '</select>';
            usuariosSelectsDiv.appendChild(div);
        }
        initialSelectedIds = [];
    }
    function renderInspectorPreview() {
        var userId = inspectorSelect ? parseInt(inspectorSelect.value || '0', 10) : 0;
        var u = usuarios.find(function(x) { return parseInt(x.id, 10) === userId; });
        if (inspectorPreview) inspectorPreview.classList.remove('hidden');
        if (u) {
            if (u.photo && inspectorPhoto) {
                inspectorPhoto.src = u.photo;
                inspectorPhoto.classList.remove('hidden');
                if (inspectorPhotoPlaceholder) inspectorPhotoPlaceholder.classList.add('hidden');
            } else {
                if (inspectorPhoto) { inspectorPhoto.classList.add('hidden'); inspectorPhoto.removeAttribute('src'); }
                if (inspectorPhotoPlaceholder) inspectorPhotoPlaceholder.classList.remove('hidden');
            }
            if (u.signature && inspectorSignature) {
                inspectorSignature.src = u.signature;
                inspectorSignature.classList.remove('hidden');
                if (inspectorSignPlaceholder) inspectorSignPlaceholder.classList.add('hidden');
            } else {
                if (inspectorSignature) { inspectorSignature.classList.add('hidden'); inspectorSignature.removeAttribute('src'); }
                if (inspectorSignPlaceholder) inspectorSignPlaceholder.classList.remove('hidden');
            }
        } else {
            if (inspectorPhoto) inspectorPhoto.classList.add('hidden');
            if (inspectorPhotoPlaceholder) inspectorPhotoPlaceholder.classList.remove('hidden');
            if (inspectorSignature) inspectorSignature.classList.add('hidden');
            if (inspectorSignPlaceholder) inspectorSignPlaceholder.classList.remove('hidden');
        }
    }
    if (cantidadSelect) cantidadSelect.addEventListener('change', renderUsuariosSelects);
    if (inspectorSelect) inspectorSelect.addEventListener('change', renderInspectorPreview);
    renderUsuariosSelects();
    renderInspectorPreview();
});
</script>
@endsection
