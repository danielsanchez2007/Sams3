@extends('layouts.admin-layout')

@section('title', 'Inspección - ' . $clase->nombre . ' - SAMS')
@section('header-title', 'Inspección')
@section('header-subtitle', $clase->nombre)

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('inspeccion.index') }}" class="pw-btn-secondary px-3 py-2 text-sm rounded-lg">
        <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>
        Volver
    </a>
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

    <div class="pw-card bg-white rounded-2xl shadow-lg p-5 border border-gray-100">
        <div class="text-lg font-semibold text-gray-900 mb-1">Equipos (filas de 10)</div>
        <div class="text-xs text-gray-500 mb-4">Clase: {{ $clase->nombre }}. Verde = al día con la inspección. Sin verde = pendiente o vencida. Gris = dado de baja (se conservan todas las inspecciones; la última es el reporte de baja).</div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                    <tr>
                        <th class="px-2 py-3 text-left">Fotos</th>
                        <th class="px-3 py-3 text-left">Código</th>
                        <th class="px-3 py-3 text-left">Equipo</th>
                        <th class="px-3 py-3 text-left">Serial</th>
                        <th class="px-3 py-3 text-left">Validez inspección</th>
                        <th class="px-3 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($equipos as $e)
                        @php
                            $ultima = $ultimaInspeccionByEquipo[$e->id] ?? $ultimaInspeccionByEquipo[(string)$e->id] ?? null;
                            $alDia = $ultima && $ultima->validez_hasta && $ultima->validez_hasta->gte($hoy);
                            $deBaja = !$e->activo;
                            $imgGeneral = $e->imagenes?->firstWhere('tipo', 'general');
                            $imgEtiqueta = $e->imagenes?->firstWhere('tipo', 'etiqueta');
                        @endphp
                        <tr class="{{ $deBaja ? 'bg-gray-100' : ($alDia ? 'bg-green-50' : '') }}">
                            <td class="px-2 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-1">
                                    @if($imgGeneral)
                                        <a href="{{ asset('storage/' . $imgGeneral->path) }}" target="_blank" class="block w-8 h-8 rounded overflow-hidden border border-gray-200 bg-gray-100 shrink-0" title="Foto general">
                                            <img src="{{ asset('storage/' . $imgGeneral->path) }}" class="w-8 h-8 object-cover" alt="General">
                                        </a>
                                    @else
                                        <span class="flex w-8 h-8 rounded border border-gray-200 bg-gray-50 items-center justify-center text-gray-400 text-xs shrink-0" title="Foto general">—</span>
                                    @endif
                                    @if($imgEtiqueta)
                                        <a href="{{ asset('storage/' . $imgEtiqueta->path) }}" target="_blank" class="block w-8 h-8 rounded overflow-hidden border border-gray-200 bg-gray-100 shrink-0" title="Foto etiqueta">
                                            <img src="{{ asset('storage/' . $imgEtiqueta->path) }}" class="w-8 h-8 object-cover" alt="Etiqueta">
                                        </a>
                                    @else
                                        <span class="flex w-8 h-8 rounded border border-gray-200 bg-gray-50 items-center justify-center text-gray-400 text-xs shrink-0" title="Foto etiqueta">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-3 font-semibold text-gray-900">
                                <x-codigo-short :codigo="$e->codigo" :extra="$e->nombre" />
                                @if($deBaja)
                                    <span class="ml-1 text-xs font-normal text-red-600">(Dado de baja)</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-gray-700">{{ $e->nombre }}</td>
                            <td class="px-3 py-3 text-gray-700">{{ $e->serial }}</td>
                            <td class="px-3 py-3 text-gray-600">
                                @if($ultima)
                                    {{ $ultima->validez_hasta?->format('d/m/Y') ?? '—' }}
                                    @if($alDia)
                                        <span class="text-green-600 text-xs">(al día)</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right">
                                <div class="flex items-center justify-end gap-2 flex-wrap">
                                    @if($canEditInspeccion ?? true)
                                    @if(!$deBaja)
                                        <a href="{{ route('inspeccion.form', $e) }}" class="pw-btn-apartado-inspeccion px-3 py-2 rounded-lg text-xs">Nueva inspección</a>
                                        <a href="{{ route('inspeccion.dar-de-baja', $e) }}" class="pw-btn-dar-baja px-3 py-2 rounded-lg text-xs">Dar de baja</a>
                                    @endif
                                    @endif
                                    @php
                                        $insps = ($inspeccionesByEquipo[$e->id] ?? $inspeccionesByEquipo[(string)$e->id] ?? collect())->map(fn($i) => ['id'=>$i->id,'fecha'=>$i->fecha_inspeccion?->format('d/m/Y'),'validez'=>$i->validez_hasta?->format('d/m/Y'),'html_url'=>route('inspeccion.html',$i),'download_url'=>route('inspeccion.download',$i)])->values();
                                    @endphp
                                    <button type="button" class="btn-ver-inspecciones pw-btn-apartado-inspeccion px-3 py-2 rounded-lg text-xs" data-equipo-codigo="{{ $e->codigo }}" data-equipo-nombre="{{ $e->nombre }}" data-inspecciones='@json($insps)'>Ver</button>
                                    @if($ultima)
                                        @if(($canEditInspeccion ?? true) && !$deBaja)
                                            <a href="{{ route('inspeccion.edit', $ultima) }}" class="px-3 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-xs">Editar</a>
                                        @endif
                                        <button type="button" class="btn-exportar-pdf pw-btn-dark px-3 py-2 rounded-lg text-xs" data-preview-url="{{ route('inspeccion.html', $ultima) }}" data-download-url="{{ route('inspeccion.download', $ultima) }}">Exportar PDF</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-6 text-center text-gray-500">No hay equipos de este tipo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $equipos->onEachSide(1)->links() }}</div>
    </div>

    {{-- Modal Ver inspecciones --}}
    <div id="modalVerInspecciones" class="fixed inset-0 z-50 hidden" aria-hidden="true">
        <div class="fixed inset-0 bg-black/50" id="modalVerInspeccionesBackdrop"></div>
        <div class="pw-modal-content fixed left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-h-[85vh] bg-white rounded-2xl shadow-2xl flex flex-col z-10">
            <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Inspecciones - <span id="modalVerEquipoNombre"></span></h3>
                <button type="button" id="modalVerInspeccionesCerrar" class="p-2 rounded-lg hover:bg-gray-200 text-gray-600">Cerrar</button>
            </div>
            <div class="flex-1 overflow-auto p-4">
                <div id="modalVerInspeccionesLista" class="hidden overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left">#</th>
                                <th class="px-3 py-2 text-left">Fecha inspección</th>
                                <th class="px-3 py-2 text-left">Validez hasta</th>
                                <th class="px-3 py-2 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="modalVerInspeccionesTableBody" class="divide-y divide-gray-100">
                            <!-- Se llena por JS -->
                        </tbody>
                    </table>
                </div>
                <div id="modalVerInspeccionesVacio" class="text-center py-8 text-gray-500 text-sm">
                    No hay inspecciones registradas para este equipo.
                </div>
            </div>
        </div>
    </div>

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

    var pdfModal = document.getElementById('pdfPreviewModal');
    var pdfIframe = document.getElementById('pdfPreviewIframe');
    var pdfDownload = document.getElementById('pdfPreviewDownload');
    var pdfClose = document.getElementById('pdfPreviewClose');
    var pdfBackdrop = document.getElementById('pdfPreviewModalBackdrop');
    document.querySelectorAll('.btn-exportar-pdf').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var previewUrl = btn.getAttribute('data-preview-url');
            var downloadUrl = btn.getAttribute('data-download-url');
            if (previewUrl && pdfIframe) pdfIframe.src = previewUrl + '?t=' + Date.now();
            if (downloadUrl && pdfDownload) pdfDownload.href = downloadUrl;
            if (pdfModal) {
                pdfModal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        });
    });
    if (pdfClose) pdfClose.addEventListener('click', closePdfModal);
    function closePdfModal() {
        if (pdfModal) {
            pdfModal.classList.add('hidden');
            document.body.style.overflow = '';
            if (pdfIframe) pdfIframe.src = 'about:blank';
        }
    }

    var modalVer = document.getElementById('modalVerInspecciones');
    var modalVerBackdrop = document.getElementById('modalVerInspeccionesBackdrop');
    var modalVerCerrar = document.getElementById('modalVerInspeccionesCerrar');
    var modalVerLista = document.getElementById('modalVerInspeccionesLista');
    var modalVerTableBody = document.getElementById('modalVerInspeccionesTableBody');
    var modalVerVacio = document.getElementById('modalVerInspeccionesVacio');

    document.querySelectorAll('.btn-ver-inspecciones').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var codigo = btn.getAttribute('data-equipo-codigo') || '';
            var nombre = btn.getAttribute('data-equipo-nombre') || '';
            var inspecciones = [];
            try {
                inspecciones = JSON.parse(btn.getAttribute('data-inspecciones') || '[]');
            } catch (e) {}

            document.getElementById('modalVerEquipoNombre').textContent = codigo + ' - ' + nombre;

            if (inspecciones.length === 0) {
                modalVerLista.classList.add('hidden');
                modalVerVacio.classList.remove('hidden');
            } else {
                modalVerVacio.classList.add('hidden');
                modalVerLista.classList.remove('hidden');
                var esc = function(s) { return String(s || '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); };
                modalVerTableBody.innerHTML = inspecciones.map(function(ins, idx) {
                    var htmlUrl = esc(ins.html_url);
                    var dlUrl = esc(ins.download_url);
                    return '<tr class="hover:bg-gray-50">' +
                        '<td class="px-3 py-2 text-gray-900 font-medium">' + (idx + 1) + '</td>' +
                        '<td class="px-3 py-2 text-gray-700">' + esc(ins.fecha) + '</td>' +
                        '<td class="px-3 py-2 text-gray-700">' + esc(ins.validez) + '</td>' +
                        '<td class="px-3 py-2 text-right">' +
                        '<button type="button" class="btn-ver-formato pw-btn-primary px-2 py-1 text-xs rounded-lg mr-1" data-html-url="' + htmlUrl + '" data-download-url="' + dlUrl + '">Ver formato</button>' +
                        '<a href="' + dlUrl + '" target="_blank" class="pw-btn-dark px-2 py-1 text-xs rounded-lg inline-block">Descargar PDF</a>' +
                        '</td></tr>';
                }).join('');

                modalVerLista.querySelectorAll('.btn-ver-formato').forEach(function(b) {
                    b.addEventListener('click', function() {
                        var url = b.getAttribute('data-html-url');
                        var dl = b.getAttribute('data-download-url');
                        if (url && pdfIframe) pdfIframe.src = url + '?t=' + Date.now();
                        if (dl && pdfDownload) pdfDownload.href = dl;
                        if (modalVer) modalVer.classList.add('hidden');
                        if (pdfModal) {
                            pdfModal.classList.remove('hidden');
                            document.body.style.overflow = 'hidden';
                        }
                    });
                });
            }

            if (modalVer) modalVer.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });
    });

    function cerrarModalVer() {
        if (modalVer) modalVer.classList.add('hidden');
        document.body.style.overflow = '';
    }
    if (modalVerCerrar) modalVerCerrar.addEventListener('click', cerrarModalVer);
});
</script>
@endsection
