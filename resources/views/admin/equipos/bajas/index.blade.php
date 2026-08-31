@extends('layouts.admin-layout')

@section('title', 'Equipos de Baja - SAMS')
@section('header-title', 'Equipos de Baja')
@section('header-subtitle', 'Formato global, registro y PDF')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-4">
            <div class="pw-card bg-white rounded-2xl shadow-lg p-5 border border-gray-100">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-lg font-semibold text-gray-900">Formato de bajas</div>
                        <div class="text-xs text-gray-500">Opcional. Si no carga Excel, el sistema usa el acta PDF profesional integrada.</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center">
                        <i data-lucide="file-spreadsheet" class="w-5 h-5 text-gray-700"></i>
                    </div>
                </div>

                <div class="text-sm text-gray-700 mb-3">
                    <div class="font-medium">Estado:</div>
                    @if($plantillaExcelPath)
                        <div class="text-green-700">Excel personalizado cargado</div>
                        <div class="text-xs text-gray-500 break-all">{{ $plantillaExcelPath }}</div>
                    @else
                        <div class="text-teal-700">Acta PDF profesional integrada (lista para usar)</div>
                    @endif
                </div>

                @unless($zipDisponible ?? true)
                    <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                        <div class="font-semibold">Extensión ZIP de PHP desactivada</div>
                        <p class="mt-1">Sin ella no se pueden leer archivos Excel. El sistema usará el acta PDF integrada. Para habilitarla, descomente <code>extension=zip</code> en <code>php.ini</code> y reinicie el servidor.</p>
                    </div>
                @endunless

                <form action="{{ route('equipos.bajas.plantilla.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-3 mt-4">
                    @csrf
                    <div>
                        <input type="file" name="plantilla_excel" accept=".xlsx,.xls" class="w-full" required>
                        @error('plantilla_excel')
                            <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="pw-btn-dark w-full px-4 py-2 rounded-xl">Cargar / Reemplazar</button>
                </form>
                @if($plantillaExcelPath)
                <form action="{{ route('equipos.bajas.plantilla.regenerar') }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2 rounded-xl text-sm border border-gray-300 hover:bg-gray-50 text-gray-700">Regenerar formato (tal cual Excel)</button>
                </form>
                @endif

                <details class="mt-5 rounded-lg border border-gray-200 bg-gray-50 p-3">
                    <summary class="cursor-pointer text-xs font-semibold text-gray-700">Ver campos automáticos del Excel</summary>
                    <div class="mt-2 grid grid-cols-2 gap-1 text-[11px] text-gray-500">
                        <div>@{{CODIGO_IN}}</div>
                        <div>@{{CODIGO_DB}}</div>
                        <div>@{{NOMBRE}}</div>
                        <div>@{{SERIAL}}</div>
                        <div>@{{DESCRIPCION}}</div>
                        <div>@{{EMPRESA}}</div>
                        <div>@{{SEDE}}</div>
                        <div>@{{BODEGA}}</div>
                        <div>@{{OFICINA}}</div>
                        <div>@{{ESPACIO}}</div>
                        <div>@{{UBICACION}}</div>
                        <div>@{{FABRICANTE}}</div>
                        <div>@{{TIPO}}</div>
                        <div>@{{CLASE}}</div>
                        <div>@{{ESTADO_ITEM}}</div>
                        <div>@{{VIDA_UTIL}}</div>
                        <div>@{{FECHA_FABRICACION}}</div>
                        <div>@{{FECHA_USO}}</div>
                        <div>@{{FECHA_COMPRA}}</div>
                        <div>@{{TIPO_USO}}</div>
                        <div>@{{FACTURA}}</div>
                        <div>@{{LOTE}}</div>
                        <div>@{{VALOR}}</div>
                        <div>@{{ESPECIFICACIONES}}</div>
                        <div>@{{CERTIFICACION}}</div>
                        <div>@{{RESISTENCIA}}</div>
                        <div>@{{KIT_NOMBRE}}</div>
                        <div>@{{ASIGNADO_A}}</div>
                        <div>@{{FECHA_BAJA}}</div>
                        <div>@{{MOTIVO_BAJA}}</div>
                        <div>@{{OBSERVACIONES_BAJA}}</div>
                    </div>
                </details>
            </div>
        </div>

        <div class="lg:col-span-8 space-y-6">
            <div class="pw-card bg-white rounded-2xl shadow-lg p-5 border border-gray-100">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-lg font-semibold text-gray-900">Eliminar equipo</div>
                        <div class="text-xs text-gray-500">Selecciona cualquier equipo y elimínalo. La historia (hoja de vida, inspecciones, etc.) se conserva. Requiere contraseña de administrador.</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center">
                        <i data-lucide="trash-2" class="w-5 h-5 text-red-700"></i>
                    </div>
                </div>

                <form action="{{ route('equipos.bajas.eliminar') }}" method="POST" class="mt-4" id="eliminarForm" data-confirm="¿Estás seguro de que deseas eliminar este equipo del sistema? La historia (hoja de vida, inspecciones) se conservará." data-confirm-danger="1">
                    @csrf
                    <input type="hidden" name="equipo_id" id="eliminarEquipoId" value="">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                        <div class="md:col-span-5">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Equipo</label>
                            <div class="relative" id="eliminarEquipoPicker">
                                <button type="button" id="eliminarEquipoPickerBtn" class="w-full px-3 py-2 border border-gray-300 rounded-xl bg-white text-left hover:bg-gray-50">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center gap-1 shrink-0" id="eliminarEquipoPickerThumb">
                                            <i data-lucide="package" class="w-4 h-4 text-gray-500"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-sm font-medium text-gray-900" id="eliminarEquipoPickerLabel">Seleccionar equipo...</div>
                                        </div>
                                        <i data-lucide="chevrons-up-down" class="w-4 h-4 text-gray-500"></i>
                                    </div>
                                </button>
                                <div id="eliminarEquipoPickerDrop" class="baja-picker-drop hidden absolute mt-2 w-full bg-white border border-gray-200 rounded-2xl shadow-xl overflow-hidden">
                                    <div class="p-3 border-b border-gray-100 bg-gray-50">
                                        <input type="text" id="eliminarEquipoPickerSearch" placeholder="Buscar..." class="w-full px-3 py-2 border border-gray-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-red-500">
                                    </div>
                                    <div id="eliminarEquipoPickerList" class="max-h-60 overflow-auto"></div>
                                </div>
                            </div>
                        </div>
                        <div class="md:col-span-4">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Contraseña administrador</label>
                            <input type="password" name="password" required placeholder="Contraseña de un admin" class="w-full px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500" autocomplete="current-password">
                        </div>
                        <div class="md:col-span-3">
                            <button type="submit" class="pw-btn-danger w-full px-4 py-2 rounded-xl">Eliminar</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="pw-card bg-white rounded-2xl shadow-lg p-5 border border-gray-100">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-lg font-semibold text-gray-900">Dar de baja</div>
                        <div class="text-xs text-gray-500">Selecciona un equipo, indica el motivo y genera el acta PDF.</div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                        <i data-lucide="archive" class="w-5 h-5 text-blue-700"></i>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end mt-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Categoría</label>
                        <select id="equipoCategoria" class="w-full mb-3 px-3 py-2 border border-gray-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all">Todos</option>
                            <option value="inventario">Inventario</option>
                            <option value="material">Material didáctico</option>
                            <option value="auditoria">Auditoría</option>
                        </select>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Equipo</label>
                        <input type="hidden" id="equipoId" value="">

                        <div class="relative" id="equipoPicker">
                            <button type="button" id="equipoPickerBtn" class="w-full px-3 py-2 border border-gray-300 rounded-xl bg-white text-left hover:bg-gray-50">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-1 shrink-0" id="equipoPickerThumb">
                                        <i data-lucide="image" class="w-4 h-4 text-gray-500"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-medium text-gray-900" id="equipoPickerLabel">Selecciona un equipo...</div>
                                        <div class="text-xs text-gray-500 truncate" id="equipoPickerSub">Escribe para buscar por código, nombre o serial</div>
                                    </div>
                                    <i data-lucide="chevrons-up-down" class="w-4 h-4 text-gray-500"></i>
                                </div>
                            </button>

                            <div id="equipoPickerDrop" class="baja-picker-drop hidden absolute mt-2 w-full bg-white border border-gray-200 rounded-2xl shadow-xl overflow-hidden">
                                <div class="p-3 border-b border-gray-100 bg-gray-50">
                                    <input type="text" id="equipoPickerSearch" placeholder="Buscar..." class="w-full px-3 py-2 border border-gray-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div id="equipoPickerList" class="max-h-72 overflow-auto"></div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <button type="button" id="btnAbrirFormato" class="pw-btn-primary w-full px-4 py-2 rounded-xl">Abrir acta de baja</button>
                    </div>
                </div>

                <div id="equipoPreview" class="mt-4 hidden">
                    <div class="flex items-start gap-4 p-4 rounded-2xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white">
                        <div class="flex items-center gap-2" id="equipoPreviewImgWrap"></div>
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold text-gray-900" id="equipoPreviewTitle"></div>
                            <div class="text-xs text-gray-500" id="equipoPreviewMeta"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pw-card bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100 mt-6">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <div class="text-lg font-semibold text-gray-900">Historial de bajas</div>
                        <div class="text-xs text-gray-500">PDF disponible por registro</div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fotos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipo (IN)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Motivo</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">PDF</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($bajas as $b)
                                @php
                                    $equipo = $b->equipo;
                                    $imgGeneral = $equipo?->imagenes?->firstWhere('tipo', 'general');
                                    $imgEtiqueta = $equipo?->imagenes?->firstWhere('tipo', 'etiqueta');
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-2 py-2 whitespace-nowrap">
                                        <div class="flex items-center gap-1">
                                            @if($imgGeneral)
                                                <a href="{{ asset('storage/' . $imgGeneral->path) }}" target="_blank" class="block w-9 h-9 overflow-hidden border border-gray-300 bg-gray-100 shrink-0 shadow-sm" title="Foto general">
                                                    <img src="{{ asset('storage/' . $imgGeneral->path) }}" class="w-full h-full object-cover" alt="General">
                                                </a>
                                            @else
                                                <span class="flex w-9 h-9 border border-gray-300 bg-gray-50 items-center justify-center text-gray-400 text-xs shrink-0">—</span>
                                            @endif
                                            @if($imgEtiqueta)
                                                <a href="{{ asset('storage/' . $imgEtiqueta->path) }}" target="_blank" class="block w-9 h-9 overflow-hidden border border-gray-300 bg-gray-100 shrink-0 shadow-sm" title="Foto etiqueta">
                                                    <img src="{{ asset('storage/' . $imgEtiqueta->path) }}" class="w-full h-full object-cover" alt="Etiqueta">
                                                </a>
                                            @else
                                                <span class="flex w-9 h-9 border border-gray-300 bg-gray-50 items-center justify-center text-gray-400 text-xs shrink-0">—</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                        <x-codigo-short :codigo="$b->codigo_db" />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <x-codigo-short :codigo="$b->codigo_in" />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $b->fecha_baja ? $b->fecha_baja->format('Y-m-d') : '—' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ \Illuminate\Support\Str::limit((string)$b->motivo_baja, 80) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('equipos.bajas.edit', $b) }}" class="pw-btn-secondary w-9 h-9 inline-flex items-center justify-center rounded-lg" title="Editar">
                                                <i data-lucide="pencil" class="w-4 h-4"></i>
                                            </a>
                                            <a href="{{ route('equipos.bajas.pdf.show', $b) }}" target="_blank" class="pw-btn-primary w-9 h-9 inline-flex items-center justify-center rounded-lg" title="Ver PDF">
                                                <i data-lucide="eye" class="w-4 h-4"></i>
                                            </a>
                                            <a href="{{ route('equipos.bajas.pdf.download', $b) }}" class="pw-btn-dark w-9 h-9 inline-flex items-center justify-center rounded-lg" title="Descargar">
                                                <i data-lucide="download" class="w-4 h-4"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-3 border-t border-gray-200">
                    {{ $bajas->onEachSide(1)->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const equipos = @json($equiposJs);
    const equiposEliminar = @json($equiposEliminarJs ?? []);

    const hiddenId = document.getElementById('equipoId');
    const picker = document.getElementById('equipoPicker');
    const pickerBtn = document.getElementById('equipoPickerBtn');
    const pickerDrop = document.getElementById('equipoPickerDrop');
    const pickerSearch = document.getElementById('equipoPickerSearch');
    const pickerList = document.getElementById('equipoPickerList');
    const pickerLabel = document.getElementById('equipoPickerLabel');
    const pickerSub = document.getElementById('equipoPickerSub');
    const pickerThumb = document.getElementById('equipoPickerThumb');
    const categoriaSel = document.getElementById('equipoCategoria');

    const preview = document.getElementById('equipoPreview');
    const title = document.getElementById('equipoPreviewTitle');
    const meta = document.getElementById('equipoPreviewMeta');
    const imgWrap = document.getElementById('equipoPreviewImgWrap');

    function openDrop() {
        pickerDrop.classList.remove('hidden');
        pickerSearch.value = '';
        renderList('');
        setTimeout(() => pickerSearch.focus(), 0);
    }

    function closeDrop() {
        pickerDrop.classList.add('hidden');
    }

    function normalize(s) {
        return (s || '').toString().toLowerCase();
    }

    function renderThumbs(container, imgGeneral, imgEtiqueta) {
        container.innerHTML = '';
        container.className = 'flex items-center gap-1 shrink-0';
        [imgGeneral, imgEtiqueta].forEach((url, idx) => {
            const wrap = document.createElement('div');
            wrap.className = 'w-9 h-9 overflow-hidden border border-gray-300 bg-gray-100';
            if (url) {
                const img = document.createElement('img');
                img.src = url;
                img.className = 'w-full h-full object-cover';
                wrap.appendChild(img);
            } else {
                wrap.classList.add('flex', 'items-center', 'justify-center');
                wrap.innerHTML = '<span class="text-gray-400 text-[10px]">—</span>';
            }
            container.appendChild(wrap);
        });
    }

    function renderThumb(container, imgUrl) {
        container.innerHTML = '';
        if (imgUrl) {
            const img = document.createElement('img');
            img.src = imgUrl;
            img.className = 'w-full h-full object-cover';
            container.appendChild(img);
        } else {
            const icon = document.createElement('i');
            icon.setAttribute('data-lucide', 'image');
            icon.className = 'w-4 h-4 text-gray-500';
            container.appendChild(icon);
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }
    }

    function renderList(query) {
        const q = normalize(query);
        const categoria = categoriaSel ? categoriaSel.value : 'all';
        const filtered = equipos.filter(e => {
            const hay = normalize(e.codigo) + ' ' + normalize(e.nombre) + ' ' + normalize(e.serial);
            const catOk = categoria === 'all' || (e.categoria || 'inventario') === categoria;
            return hay.includes(q) && catOk;
        });

        pickerList.innerHTML = '';

        if (!filtered.length) {
            const empty = document.createElement('div');
            empty.className = 'p-4 text-sm text-gray-600';
            empty.textContent = 'No hay resultados.';
            pickerList.appendChild(empty);
            return;
        }

        filtered.forEach(e => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'w-full px-3 py-3 text-left hover:bg-blue-50 flex items-center gap-3';

            const thumb = document.createElement('div');
            renderThumbs(thumb, e.img_general, e.img_etiqueta);

            const info = document.createElement('div');
            info.className = 'min-w-0 flex-1';

            const line1 = document.createElement('div');
            line1.className = 'text-sm font-semibold text-gray-900 truncate';
            line1.textContent = (e.codigo || '') + ' - ' + (e.nombre || '');

            const line2 = document.createElement('div');
            line2.className = 'text-xs text-gray-500 truncate';
            const categoriaTxt = (e.categoria === 'material') ? 'Material didáctico' : ((e.categoria === 'auditoria') ? 'Auditoría' : 'Inventario');
            line2.textContent = (e.serial ? ('Serial: ' + e.serial + ' | ') : '') + categoriaTxt;

            info.appendChild(line1);
            info.appendChild(line2);

            btn.appendChild(thumb);
            btn.appendChild(info);

            btn.addEventListener('click', function() {
                hiddenId.value = e.id;
                pickerLabel.textContent = (e.codigo || '') + ' - ' + (e.nombre || '');
                pickerSub.textContent = e.serial ? ('Serial: ' + e.serial) : '—';
                renderThumbs(pickerThumb, e.img_general, e.img_etiqueta);
                updatePreview();
                closeDrop();
            });

            pickerList.appendChild(btn);
        });
    }

    function updatePreview() {
        const id = parseInt(hiddenId.value || '0', 10);
        const e = equipos.find(x => x.id === id);
        if (!e) {
            preview.classList.add('hidden');
            return;
        }

        title.textContent = e.codigo + ' - ' + e.nombre;
        meta.textContent = (e.serial ? ('Serial: ' + e.serial) : '');
        imgWrap.innerHTML = '';
        imgWrap.className = 'flex items-center gap-2';
        [e.img_general, e.img_etiqueta].forEach((url, idx) => {
            const wrap = document.createElement('div');
            wrap.className = 'w-12 h-12 overflow-hidden border border-gray-300 bg-gray-100 shrink-0';
            if (url) {
                const img = document.createElement('img');
                img.src = url;
                img.className = 'w-full h-full object-cover';
                wrap.appendChild(img);
            } else {
                wrap.classList.add('flex', 'items-center', 'justify-center');
                wrap.innerHTML = '<span class="text-gray-400 text-xs">—</span>';
            }
            imgWrap.appendChild(wrap);
        });
        preview.classList.remove('hidden');
    }

    pickerBtn.addEventListener('click', function() {
        if (pickerDrop.classList.contains('hidden')) {
            openDrop();
        } else {
            closeDrop();
        }
    });

    pickerSearch.addEventListener('input', function() {
        renderList(pickerSearch.value);
    });

    if (categoriaSel) {
        categoriaSel.addEventListener('change', function() {
            renderList(pickerSearch.value || '');
        });
    }

    document.addEventListener('click', function(e) {
        if (!picker.contains(e.target)) {
            closeDrop();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDrop();
        }
    });

    document.getElementById('btnAbrirFormato').addEventListener('click', function() {
        const id = hiddenId.value;
        if (!id) {
            if (typeof showNotification === 'function') {
                showNotification('Selecciona un equipo', 'warning');
            }
            return;
        }
        window.location.href = '{{ url('/equipos/bajas') }}/' + id + '/form';
    });

    (function() {
        const eliminarId = document.getElementById('eliminarEquipoId');
        const eliminarBtn = document.getElementById('eliminarEquipoPickerBtn');
        const eliminarDrop = document.getElementById('eliminarEquipoPickerDrop');
        const eliminarSearch = document.getElementById('eliminarEquipoPickerSearch');
        const eliminarList = document.getElementById('eliminarEquipoPickerList');
        const eliminarLabel = document.getElementById('eliminarEquipoPickerLabel');
        const eliminarThumb = document.getElementById('eliminarEquipoPickerThumb');

        function eliminarOpen() {
            eliminarDrop.classList.remove('hidden');
            eliminarSearch.value = '';
            eliminarRenderList('');
            setTimeout(() => eliminarSearch && eliminarSearch.focus(), 0);
        }
        function eliminarClose() { eliminarDrop.classList.add('hidden'); }
        function eliminarRenderList(query) {
            const q = (query || '').toString().toLowerCase();
            const filtered = equiposEliminar.filter(e => {
                const hay = ((e.codigo || '') + ' ' + (e.nombre || '') + ' ' + (e.serial || '')).toLowerCase();
                return hay.includes(q);
            });
            eliminarList.innerHTML = '';
            if (!filtered.length) {
                eliminarList.innerHTML = '<div class="p-4 text-sm text-gray-600">No hay resultados.</div>';
                return;
            }
            filtered.forEach(e => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-full px-3 py-3 text-left hover:bg-red-50 flex items-center gap-3';
                const thumb = document.createElement('div');
                renderThumbs(thumb, e.img_general, e.img_etiqueta);
                const info = document.createElement('div');
                info.className = 'min-w-0 flex-1';
                const title = document.createElement('div');
                title.className = 'text-sm font-semibold text-gray-900 truncate';
                title.textContent = (e.codigo || '') + ' - ' + (e.nombre || '');
                const sub = document.createElement('div');
                sub.className = 'text-xs text-gray-500 truncate';
                sub.textContent = e.serial ? ('Serial: ' + e.serial) : '';
                info.appendChild(title);
                info.appendChild(sub);
                btn.appendChild(thumb);
                btn.appendChild(info);
                btn.addEventListener('click', function() {
                    eliminarId.value = e.id;
                    eliminarLabel.textContent = (e.codigo || '') + ' - ' + (e.nombre || '');
                    renderThumbs(eliminarThumb, e.img_general, e.img_etiqueta);
                    eliminarClose();
                });
                eliminarList.appendChild(btn);
            });
        }

        eliminarBtn.addEventListener('click', function() {
            if (eliminarDrop.classList.contains('hidden')) eliminarOpen();
            else eliminarClose();
        });
        eliminarSearch.addEventListener('input', function() { eliminarRenderList(eliminarSearch.value); });
        document.addEventListener('click', function(ev) {
            if (!document.getElementById('eliminarEquipoPicker').contains(ev.target)) eliminarClose();
        });
    })();
});
</script>
@endsection
