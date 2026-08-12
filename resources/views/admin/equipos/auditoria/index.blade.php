@extends('layouts.admin-layout')

@section('title', 'Auditoría de Equipos - SAMS')
@section('header-title', 'Auditoría de Equipos')
@section('header-subtitle', 'Traspasar equipo y administrar auditoría')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <div class="pw-card bg-white rounded-2xl shadow-lg p-5 border border-gray-100">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-lg font-semibold text-gray-900">Traspasar equipo</div>
                <div class="text-xs text-gray-500">Selecciona un equipo, revisa el preview y traspásalo a auditoría.</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center">
                <i data-lucide="clipboard-check" class="w-5 h-5 text-gray-700"></i>
            </div>
        </div>

        @if(session('success'))
            <div class="mt-4 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mt-4 p-4 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm">{{ session('error') }}</div>
        @endif

        <form action="{{ route('equipos.traspasar') }}" method="POST" class="mt-4" id="auditoriaForm">
            @csrf
            <input type="hidden" name="equipo_id" id="equipoId">
            <input type="hidden" name="destino" id="destinoInput" value="auditoria">
            <input type="hidden" name="from" value="auditoria">

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                <div class="md:col-span-5">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Equipo</label>
                    <div class="relative">
                        <button type="button" id="equipoPickerBtn" class="w-full flex items-center justify-between gap-3 px-4 py-3 rounded-xl border border-gray-200 bg-white hover:bg-gray-50">
                            <div class="min-w-0 text-left">
                                <div class="text-sm font-semibold text-gray-900 truncate" id="equipoPickerLabel">Seleccionar equipo...</div>
                                <div class="text-xs text-gray-500 truncate" id="equipoPickerSub">Busca por código, nombre o serial</div>
                            </div>
                            <i data-lucide="chevron-down" class="w-5 h-5 text-gray-500"></i>
                        </button>

                        <div id="equipoPickerDrop" class="hidden absolute mt-2 w-full bg-white border border-gray-200 rounded-2xl shadow-xl overflow-hidden" style="z-index: 50;">
                            <div class="p-3 border-b border-gray-100 bg-gray-50">
                                <input type="text" id="equipoPickerSearch" placeholder="Buscar..." class="w-full px-3 py-2 border border-gray-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div id="equipoPickerList" class="max-h-72 overflow-auto"></div>
                        </div>
                    </div>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Traspasar a</label>
                    <select id="destinoSelect" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="auditoria">Auditoría</option>
                        <option value="inventario">Inventario</option>
                        <option value="didactico">Material didáctico</option>
                        <option value="baja">De baja</option>
                    </select>
                </div>
                <div class="md:col-span-4">
                    <button type="submit" id="btnTraspasar" class="pw-btn-primary w-full px-4 py-3 rounded-xl disabled:opacity-50" disabled>Traspasar equipo</button>
                </div>
                <div class="md:col-span-12" id="motivoWrap">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Motivo del traspaso *</label>
                    <textarea name="motivo" id="motivoInput" rows="2" required maxlength="2000" placeholder="Explique por qué se traspasa este equipo..." class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500"></textarea>
                    <p class="mt-1 text-xs text-gray-400">Este motivo quedará registrado en el historial. El código de inventario se liberará para reutilización.</p>
                </div>
            </div>

            <div id="equipoPreview" class="mt-4 hidden">
                <div class="flex items-start gap-4 p-4 rounded-2xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white">
                    <div class="flex items-center gap-2" id="equipoPreviewImgWrap"></div>
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold text-gray-900" id="equipoPreviewTitle"></div>
                        <div class="text-xs text-gray-500" id="equipoPreviewCode"></div>
                        <div class="text-xs text-gray-500" id="equipoPreviewSerial"></div>
                        <div class="mt-2 text-sm text-gray-700" id="equipoPreviewDesc"></div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="pw-card bg-white rounded-2xl shadow-lg p-5 border border-gray-100">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-lg font-semibold text-gray-900">Historial de traspasos</div>
                <div class="text-xs text-gray-500">Últimos equipos traspasados a auditoría.</div>
            </div>
        </div>

        <div class="overflow-x-auto mt-4">
            <table class="min-w-full text-sm">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                    <tr>
                        <th class="px-2 py-3 text-left">Fotos</th>
                        <th class="px-3 py-3 text-left">Fecha</th>
                        <th class="px-3 py-3 text-left">Equipo</th>
                        <th class="px-3 py-3 text-left">Código anterior</th>
                        <th class="px-3 py-3 text-left">Código AUD</th>
                        <th class="px-3 py-3 text-left">Motivo</th>
                        <th class="px-3 py-3 text-left">Usuario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($traspasos as $t)
                        @php
                            $equipo = $t->equipo;
                            $imgGeneral = $equipo?->imagenes?->firstWhere('tipo', 'general');
                            $imgEtiqueta = $equipo?->imagenes?->firstWhere('tipo', 'etiqueta');
                        @endphp
                        <tr>
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
                            <td class="px-3 py-3 text-gray-700">{{ optional($t->traspasado_en)->format('Y-m-d H:i') }}</td>
                            <td class="px-3 py-3 text-gray-900">
                                @if($t->equipo)
                                    <div class="font-semibold">{{ $t->equipo->nombre }}</div>
                                    <div class="text-xs text-gray-500">{{ $t->equipo->serial }}</div>
                                @else
                                    <div class="text-gray-500">(Eliminado)</div>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-gray-700">
                                <x-codigo-short :codigo="$t->codigo_anterior" :extra="$t->equipo?->nombre" />
                            </td>
                            <td class="px-3 py-3">
                                <x-codigo-short :codigo="$t->codigo_aud" />
                            </td>
                            <td class="px-3 py-3 text-gray-700 max-w-xs">{{ \Illuminate\Support\Str::limit((string) ($t->motivo ?? '—'), 90) }}</td>
                            <td class="px-3 py-3 text-gray-700">{{ $t->creador?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-gray-500">Sin traspasos aún.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $traspasos->links() }}
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

    const equipos = @json($equiposJs ?? []);

    const btn = document.getElementById('equipoPickerBtn');
    const drop = document.getElementById('equipoPickerDrop');
    const list = document.getElementById('equipoPickerList');
    const search = document.getElementById('equipoPickerSearch');
    const hiddenId = document.getElementById('equipoId');

    const label = document.getElementById('equipoPickerLabel');
    const sub = document.getElementById('equipoPickerSub');

    const preview = document.getElementById('equipoPreview');
    const pImgWrap = document.getElementById('equipoPreviewImgWrap');
    const pTitle = document.getElementById('equipoPreviewTitle');
    const pCode = document.getElementById('equipoPreviewCode');
    const pSerial = document.getElementById('equipoPreviewSerial');
    const pDesc = document.getElementById('equipoPreviewDesc');

    const btnTraspasar = document.getElementById('btnTraspasar');

    let open = false;
    function openDrop() {
        drop.classList.remove('hidden');
        open = true;
        setTimeout(() => search && search.focus(), 0);
    }
    function closeDrop() {
        drop.classList.add('hidden');
        open = false;
    }

    function renderList(items) {
        list.innerHTML = '';
        if (!items.length) {
            const empty = document.createElement('div');
            empty.className = 'p-4 text-sm text-gray-500';
            empty.textContent = 'No hay resultados.';
            list.appendChild(empty);
            return;
        }

        items.forEach(item => {
            const row = document.createElement('button');
            row.type = 'button';
            row.className = 'w-full text-left flex items-center gap-3 px-3 py-3 hover:bg-gray-50';

            const imgWrap = document.createElement('div');
            imgWrap.className = 'flex items-center gap-1 shrink-0';
            [item.img_general, item.img_etiqueta].forEach(url => {
                const wrap = document.createElement('div');
                wrap.className = 'w-9 h-9 overflow-hidden border border-gray-300 bg-gray-100';
                if (url) {
                    const img = document.createElement('img');
                    img.src = url;
                    img.alt = item.codigo || '';
                    img.className = 'w-full h-full object-cover';
                    wrap.appendChild(img);
                } else {
                    wrap.classList.add('flex', 'items-center', 'justify-center');
                    wrap.innerHTML = '<span class="text-gray-400 text-[10px]">—</span>';
                }
                imgWrap.appendChild(wrap);
            });

            const info = document.createElement('div');
            info.className = 'min-w-0 flex-1';
            const t = document.createElement('div');
            t.className = 'text-sm font-semibold text-gray-900 truncate';
            t.textContent = (item.codigo ? item.codigo + ' - ' : '') + (item.nombre || '');
            const s = document.createElement('div');
            s.className = 'text-xs text-gray-500 truncate';
            s.textContent = item.serial || '';
            info.appendChild(t);
            info.appendChild(s);

            row.appendChild(imgWrap);
            row.appendChild(info);

            row.addEventListener('click', () => {
                hiddenId.value = item.id;
                label.textContent = (item.codigo ? item.codigo + ' - ' : '') + (item.nombre || '');
                sub.textContent = item.serial || '';

                if (preview) {
                    preview.classList.remove('hidden');
                    pImgWrap.innerHTML = '';
                    pImgWrap.className = 'flex items-center gap-2';
                    [item.img_general, item.img_etiqueta].forEach(url => {
                        const wrap = document.createElement('div');
                        wrap.className = 'w-12 h-12 overflow-hidden border border-gray-300 bg-gray-100 shrink-0';
                        if (url) {
                            const img = document.createElement('img');
                            img.src = url;
                            img.alt = item.codigo || '';
                            img.className = 'w-full h-full object-cover';
                            wrap.appendChild(img);
                        } else {
                            wrap.classList.add('flex', 'items-center', 'justify-center');
                            wrap.innerHTML = '<span class="text-gray-400 text-xs">—</span>';
                        }
                        pImgWrap.appendChild(wrap);
                    });
                    pTitle.textContent = item.nombre || '';
                    pCode.textContent = 'Código: ' + (item.codigo || '-');
                    pSerial.textContent = 'Serial: ' + (item.serial || '-');
                    pDesc.textContent = item.descripcion || '';
                }

                btnTraspasar.disabled = false;
                closeDrop();
            });

            list.appendChild(row);
        });
    }

    function filterList() {
        const q = (search.value || '').toLowerCase().trim();
        if (!q) {
            renderList(equipos);
            return;
        }
        const filtered = equipos.filter(e => {
            return (
                (e.codigo || '').toLowerCase().includes(q) ||
                (e.nombre || '').toLowerCase().includes(q) ||
                (e.serial || '').toLowerCase().includes(q)
            );
        });
        renderList(filtered);
    }

    renderList(equipos);

    btn.addEventListener('click', () => {
        open ? closeDrop() : openDrop();
    });

    document.addEventListener('click', (e) => {
        if (!open) return;
        if (btn.contains(e.target) || drop.contains(e.target)) return;
        closeDrop();
    });

    search.addEventListener('input', filterList);

    const destinoSelect = document.getElementById('destinoSelect');
    const destinoInput = document.getElementById('destinoInput');
    const motivoWrap = document.getElementById('motivoWrap');
    const motivoInput = document.getElementById('motivoInput');
    function syncMotivoRequired() {
        const dest = destinoSelect ? destinoSelect.value : (destinoInput?.value || '');
        const needsMotivo = dest !== 'baja';
        if (motivoWrap) motivoWrap.classList.toggle('hidden', !needsMotivo);
        if (motivoInput) {
            if (needsMotivo) motivoInput.setAttribute('required', 'required');
            else motivoInput.removeAttribute('required');
        }
    }
    if (destinoSelect && destinoInput) {
        destinoSelect.addEventListener('change', function() {
            destinoInput.value = destinoSelect.value;
            syncMotivoRequired();
        });
    }
    syncMotivoRequired();
});
</script>
@endsection
