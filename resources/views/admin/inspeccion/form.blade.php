@extends('layouts.admin-layout')

@section('title', 'Nueva inspección - ' . ($equipo->codigo ?: $equipo->id) . ' - SAMS')
@section('header-title', 'Nueva inspección')
@section('header-subtitle')
    <x-codigo-short :codigo="$equipo->codigo" :extra="$equipo->nombre" />
@endsection

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('inspeccion.equipos', $clase) }}" class="pw-btn-secondary px-3 py-2 text-sm rounded-lg">
        <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>
        Volver
    </a>
</div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    @if (session('error'))
        <div class="p-4 rounded-2xl border border-red-200 bg-red-50 text-red-800 text-sm">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="p-4 rounded-2xl border border-red-200 bg-red-50 text-red-800 text-sm">
            @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
        </div>
    @endif

    <form action="{{ route('inspeccion.store', $equipo) }}" method="POST" id="inspeccionForm" class="space-y-6">
        @csrf
        <input type="hidden" name="edited_html" id="editedHtml" value="">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-8 space-y-6">
                <div class="pw-card bg-white rounded-2xl shadow-lg p-5 border border-gray-100">
                    <div class="text-lg font-semibold text-gray-900 mb-4">Datos de la inspección</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Fecha de hoy (automática)</label>
                            <input type="text" value="{{ $fechaHoy }}" readonly class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Validez de la inspección (hasta qué día)</label>
                            <input type="date" name="validez_hasta" required min="{{ $fechaHoy }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Cantidad de usuarios <span class="text-red-500">*</span></label>
                            <select name="cantidad_usuarios" id="cantidadUsuarios" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Inspector (firma y foto) <span class="text-red-500">*</span></label>
                            <select name="inspector_user_id" id="inspectorUserSelect" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                <option value="">Seleccionar inspector</option>
                                @foreach($usuarios ?? [] as $u)
                                    <option value="{{ $u->id }}" {{ (int) old('inspector_user_id', $inspectorUserId ?? auth()->id()) === (int) $u->id ? 'selected' : '' }}>{{ trim($u->name . ' ' . ($u->last_name ?? '')) }}</option>
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
                    <div id="usuariosContainer" class="mt-4 space-y-2">
                        <label class="block text-xs font-medium text-gray-500 mb-2">Usuarios (seleccionar según cantidad)</label>
                        <div id="usuariosSelects"></div>
                    </div>
                    @if($requiereObligatoria)
                        <div class="mt-4 p-4 rounded-xl border border-amber-200 bg-amber-50">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="inspeccion_obligatoria" value="1" id="inspeccionObligatoria" class="rounded border-gray-300">
                                <span class="font-medium text-amber-900">Inspección obligatoria</span>
                            </label>
                            <p class="text-xs text-amber-800 mt-1">Permite hacer la inspección aunque no haya pasado la validez (solo para emergencias). Debes ingresar tu contraseña.</p>
                            <div id="wrapPassword" class="mt-3 hidden">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Tu contraseña</label>
                                <input type="password" name="password_obligatoria" id="passwordObligatoria" class="rounded-lg border border-gray-300 px-3 py-2 text-sm w-full max-w-xs" placeholder="Contraseña">
                            </div>
                        </div>
                    @endif
                    <div class="mt-4">
                        <a href="{{ route('inspeccion.dar-de-baja', $equipo) }}" class="pw-btn-dar-baja inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium">
                            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                            Inspección dada de baja (ir a dar de baja equipo)
                        </a>
                    </div>
                </div>

                <div class="pw-card bg-white rounded-2xl shadow-lg border border-gray-100">
                    <div class="flex items-center justify-between p-4 border-b border-gray-100">
                        <div>
                            <div class="text-lg font-semibold text-gray-900">Formato de inspección</div>
                            <div class="text-xs text-gray-500">Vista previa mejorada del Excel. Rellena el contenido; al guardar se generará el PDF y no podrás hacer otra inspección hasta la fecha de validez.</div>
                        </div>
                        <button type="submit" class="pw-btn-success px-4 py-2 rounded-lg">Guardar inspección</button>
                    </div>
                    <div id="editableContainer" class="overflow-auto border border-gray-200 rounded-b-2xl p-4 bg-white" style="max-height: 65vh;">
                        <div id="editableTemplate" contenteditable="true" class="outline-none inspeccion-excel-view">
                            @safeHtml($renderedHtml)
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-4 space-y-4">
                @include('admin.inspeccion.partials.panel-lateral')
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="pw-btn-success px-4 py-2 rounded-lg">Guardar inspección</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<style>
    .inspeccion-excel-view table{border-collapse:collapse;width:100%;table-layout:fixed;}
    .inspeccion-excel-view td,
    .inspeccion-excel-view th{border:1px solid #d1d5db;padding:4px;font-size:11px;vertical-align:top;word-wrap:break-word;}
    .inspeccion-excel-view th{background:#f3f4f6;font-weight:600;}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    var form = document.getElementById('inspeccionForm');
    var editable = document.getElementById('editableTemplate');
    var container = document.getElementById('editableContainer');
    var hidden = document.getElementById('editedHtml');

    if (form && editable && hidden) {
        form.addEventListener('submit', function() {
            hidden.value = editable.innerHTML;
        });
    }

    var cb = document.getElementById('inspeccionObligatoria');
    var wrap = document.getElementById('wrapPassword');
    if (cb && wrap) {
        cb.addEventListener('change', function() {
            wrap.classList.toggle('hidden', !cb.checked);
            if (cb.checked) {
                document.getElementById('passwordObligatoria').setAttribute('required', 'required');
            } else {
                document.getElementById('passwordObligatoria').removeAttribute('required');
            }
        });
    }

    var usuarios = @json($usuariosJs ?? []);
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
            var div = document.createElement('div');
            div.className = 'flex gap-2 items-center';
            div.innerHTML = '<label class="text-xs text-gray-500 w-24">Usuario ' + i + '</label>' +
                '<select name="selected_user_ids[]" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">' +
                '<option value="">— Seleccionar —</option>' +
                usuarios.map(function(u) { return '<option value="' + u.id + '">' + (u.name || 'Usuario ' + u.id) + '</option>'; }).join('') +
                '</select>';
            usuariosSelectsDiv.appendChild(div);
        }
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
            if (inspectorPhoto) { inspectorPhoto.classList.add('hidden'); }
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
@include('admin.inspeccion.partials.panel-lateral-script')
@endsection
