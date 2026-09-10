@extends('layouts.admin-layout')

@section('title', 'Hoja de Vida - SAMS')
@section('header-title', 'Hoja de Vida')
@section('header-subtitle', ($equipo->codigo ?: $equipo->id) . ' - ' . $equipo->nombre)

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('hoja-vida.clase', $clase) }}" class="pw-btn-secondary px-3 py-2 text-sm rounded-lg">
        <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>
        Volver
    </a>
    <button type="button" id="btnExportarPdf" class="pw-btn-dark px-3 py-2 text-sm rounded-lg" data-preview-url="{{ route('hoja-vida.html', [$clase, $equipo]) }}" data-download-url="{{ route('hoja-vida.pdf', [$clase, $equipo]) }}?async=0&download=1">Exportar PDF</button>
</div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    @if (session('success'))
        <div class="p-4 rounded-2xl border border-green-200 bg-green-50 text-green-800 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 rounded-2xl border border-yellow-200 bg-yellow-50 text-yellow-900 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @if (session('info'))
        <div class="p-4 rounded-2xl border border-blue-200 bg-blue-50 text-blue-800 text-sm">
            {{ session('info') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 rounded-2xl border border-red-200 bg-red-50 text-red-800 text-sm">
            @foreach ($errors->all() as $e)
                <div>{{ $e }}</div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('hoja-vida.store', [$clase, $equipo]) }}" method="POST" class="space-y-6" id="hvForm">
        @csrf
        <input type="hidden" name="edited_html" id="editedHtml" value="">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-8 space-y-4">
                <div class="pw-card bg-white rounded-2xl shadow-lg border border-gray-100">
                    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                        <div>
                            <div class="text-lg font-semibold text-gray-900">Formato</div>
                            <div class="text-xs text-gray-500">Edita directamente y guarda para generar el Excel final.</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('formatos.index') }}" class="px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                Reemplazar formato
                            </a>
                            <button type="submit" class="pw-btn-success px-4 py-2 rounded-lg">Guardar</button>
                        </div>
                    </div>

                    <div id="editableContainer" class="overflow-auto border border-gray-200 rounded-lg p-3 bg-white" style="max-height: 70vh;">
                        <div id="editableTemplate" contenteditable="true" class="outline-none hv-excel-view">
                            @safeHtml($renderedHtml)
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4 space-y-4">
                <div class="pw-card bg-white rounded-2xl shadow-lg p-4 border border-gray-100">
                    <div class="text-sm font-semibold text-gray-900 mb-2">Datos del equipo</div>
                    <div class="text-xs text-gray-500 mb-3">Información rápida que también se refleja en los tokens del formato.</div>
                    <div class="space-y-2 text-sm">
                        <div><span class="text-gray-500 text-xs">Código:</span> <span class="font-semibold text-gray-900">{{ $equipo->codigo }}</span></div>
                        <div><span class="text-gray-500 text-xs">Nombre:</span> <span class="text-gray-900">{{ $equipo->nombre }}</span></div>
                        <div><span class="text-gray-500 text-xs">Clase:</span> <span class="text-gray-900">{{ $equipo->claseEquipo?->nombre }}</span></div>
                        <div><span class="text-gray-500 text-xs">Tipo:</span> <span class="text-gray-900">{{ $equipo->tipoEquipo?->nombre }}</span></div>
                        <div><span class="text-gray-500 text-xs">Serial:</span> <span class="text-gray-900">{{ $equipo->serial }}</span></div>
                        <div><span class="text-gray-500 text-xs">Ubicación:</span>
                            <span class="text-gray-900">
                                {{ $equipo->empresa?->nombre }}
                                @if($equipo->sede)
                                    / {{ $equipo->sede->nombre }}
                                @endif
                                @if($equipo->bodega)
                                    / {{ $equipo->bodega->nombre }}
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="pw-btn-success px-4 py-2 rounded-lg">Guardar</button>
        </div>
    </form>

    {{-- Modal vista previa PDF --}}
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
<style>
    .hv-img-wrap{position:relative;display:inline-block;max-width:240px;max-height:240px;}
    .hv-img-wrap img{max-width:240px;max-height:240px;display:block;}
    .hv-img-remove{position:absolute;top:4px;right:4px;width:22px;height:22px;line-height:20px;border-radius:9999px;border:1px solid rgba(0,0,0,.25);background:rgba(255,255,255,.9);cursor:pointer;font-weight:700;font-size:14px;text-align:center;display:none;}
    .hv-img-wrap:hover .hv-img-remove{display:block;}
    .hv-excel-view table{border-collapse:collapse;width:100%;table-layout:fixed;}
    .hv-excel-view td,
    .hv-excel-view th{border:1px solid #d1d5db;padding:4px;font-size:11px;vertical-align:top;word-wrap:break-word;}
    .hv-excel-view td:empty,
    .hv-excel-view th:empty{border:none;}
    .hv-excel-view th{background:#f3f4f6;font-weight:600;}
    .hv-img-thumb{width:100%;aspect-ratio:1/1;overflow:hidden;cursor:grab;}
    .hv-img-thumb img:active{cursor:grabbing;}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const form = document.getElementById('hvForm');
    const editable = document.getElementById('editableTemplate');
    const container = document.getElementById('editableContainer');
    const hidden = document.getElementById('editedHtml');

    if (form && editable && hidden) {
        form.addEventListener('submit', function() {
            hidden.value = editable.innerHTML;
        });
    }

    function insertTextAtCursor(text) {
        editable.focus();
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) {
            editable.innerHTML += text;
            return;
        }
        const range = sel.getRangeAt(0);
        range.deleteContents();
        const node = document.createTextNode(text);
        range.insertNode(node);
        range.setStartAfter(node);
        range.setEndAfter(node);
        sel.removeAllRanges();
        sel.addRange(range);
    }

    function insertHtmlAtCursor(html) {
        editable.focus();
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) {
            editable.insertAdjacentHTML('beforeend', html);
            return;
        }
        const range = sel.getRangeAt(0);
        range.deleteContents();
        const frag = range.createContextualFragment(html);
        const lastNode = frag.lastChild;
        range.insertNode(frag);
        if (lastNode) {
            range.setStartAfter(lastNode);
            range.setEndAfter(lastNode);
            sel.removeAllRanges();
            sel.addRange(range);
        }
    }

    function buildRemovableImageHtml(src) {
        return '<span class="hv-img-wrap" contenteditable="false">'
            + '<button type="button" class="hv-img-remove" title="Quitar">×</button>'
            + '<img src="' + src + '" alt="Imagen">'
            + '</span>';
    }

    function getCaretRangeFromPoint(x, y) {
        if (document.caretRangeFromPoint) {
            return document.caretRangeFromPoint(x, y);
        }
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

    function insertTextAtPoint(text, x, y) {
        editable.focus();
        const range = getCaretRangeFromPoint(x, y);
        const sel = window.getSelection();

        if (range && sel) {
            sel.removeAllRanges();
            sel.addRange(range);
        }

        insertTextAtCursor(text);
    }

    function insertHtmlAtPoint(html, x, y) {
        editable.focus();
        const range = getCaretRangeFromPoint(x, y);
        const sel = window.getSelection();

        if (range && sel) {
            sel.removeAllRanges();
            sel.addRange(range);
        }

        insertHtmlAtCursor(html);
    }

    function autoScrollOnDrag(evt) {
        if (!container) return;
        const rect = container.getBoundingClientRect();
        const margin = 40;
        const speed = 18;

        if (evt.clientY < rect.top + margin) {
            container.scrollTop -= speed;
        } else if (evt.clientY > rect.bottom - margin) {
            container.scrollTop += speed;
        }
    }

    document.querySelectorAll('[data-insert-token]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            insertTextAtCursor(btn.getAttribute('data-insert-token'));
        });
    });

    document.querySelectorAll('[data-drag-token]').forEach(function(el) {
        el.addEventListener('dragstart', function(e) {
            if (el && el.tagName && el.tagName.toLowerCase() === 'img' && el.getAttribute('src')) {
                const src = el.getAttribute('src');
                const html = buildRemovableImageHtml(src);
                e.dataTransfer.setData('text/html', html);
                e.dataTransfer.setData('text/plain', '');
                e.dataTransfer.effectAllowed = 'copy';
                return;
            }

            const token = el.getAttribute('data-drag-token');
            if (!token) return;
            e.dataTransfer.setData('text/plain', token);
            e.dataTransfer.effectAllowed = 'copy';
        });
    });

    if (container && editable) {
        container.addEventListener('dragover', function(e) {
            e.preventDefault();
            autoScrollOnDrag(e);
        });

        container.addEventListener('drop', function(e) {
            e.preventDefault();
            const html = e.dataTransfer.getData('text/html');
            if (html) {
                insertHtmlAtPoint(html, e.clientX, e.clientY);
                return;
            }

            const token = e.dataTransfer.getData('text/plain');
            if (!token) return;
            insertTextAtPoint(token, e.clientX, e.clientY);
        });
    }

    if (editable) {
        editable.addEventListener('click', function(e) {
            const btn = e.target && e.target.closest ? e.target.closest('.hv-img-remove') : null;
            if (btn) {
                const wrap = btn.closest('.hv-img-wrap');
                if (wrap && wrap.parentNode) {
                    wrap.parentNode.removeChild(wrap);
                }
            }
        });
    }

    const users = @json($usuariosJs ?? []);

    const select = document.getElementById('signatureUserSelect');
    const imgPhoto = document.getElementById('signatureUserPhoto');
    const imgSign = document.getElementById('signatureUserSignature');
    const photoPlaceholder = document.getElementById('signatureUserPhotoPlaceholder');
    const signPlaceholder = document.getElementById('signatureUserSignPlaceholder');

    function renderUserMedia() {
        const userId = select ? parseInt(select.value || '0', 10) : 0;
        const u = users.find(x => parseInt(x.id, 10) === userId);

        if (imgPhoto) {
            if (u && u.photo) {
                imgPhoto.src = u.photo;
                imgPhoto.classList.remove('hidden');
                if (photoPlaceholder) photoPlaceholder.classList.add('hidden');
            } else {
                imgPhoto.removeAttribute('src');
                imgPhoto.classList.add('hidden');
                if (photoPlaceholder) photoPlaceholder.classList.remove('hidden');
            }
        }

        if (imgSign) {
            if (u && u.signature) {
                imgSign.src = u.signature;
                imgSign.classList.remove('hidden');
                if (signPlaceholder) signPlaceholder.classList.add('hidden');
            } else {
                imgSign.removeAttribute('src');
                imgSign.classList.add('hidden');
                if (signPlaceholder) signPlaceholder.classList.remove('hidden');
            }
        }
    }

    if (select) {
        select.addEventListener('change', renderUserMedia);
    }
    renderUserMedia();

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
    function closePdfModal() {
        if (pdfModal) {
            pdfModal.classList.add('hidden');
            document.body.style.overflow = '';
            if (pdfIframe) pdfIframe.src = 'about:blank';
        }
    }
});
</script>
@endsection
