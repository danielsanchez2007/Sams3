@extends('layouts.admin-layout')

@section('title', 'Códigos por empresa')
@section('header-title', 'Códigos por empresa')
@section('header-subtitle', 'Editar prefijos/tags globales de generación')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="pw-card rounded-xl p-5">
        <p class="text-sm text-gray-200">Los cambios aplican globalmente a la generación de nuevos códigos en cada empresa.</p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        @foreach($empresas as $empresa)
            @php
                $cfg = is_array($empresa->code_settings) ? $empresa->code_settings : [];
                $tiposCodigos = $empresa->tiposEquipo
                    ->map(fn($t) => strtoupper((string) ($t->alias ?: '')))
                    ->filter(fn($v) => $v !== '')
                    ->unique()
                    ->values()
                    ->all();
            @endphp
            <form method="POST" action="{{ route('empresa.codigos.update', $empresa) }}" class="pw-card rounded-xl p-5 space-y-4">
                @csrf
                @method('PUT')
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-white">{{ $empresa->nombre }}</h3>
                        <p class="text-xs text-gray-300">Prefijo base: {{ $empresa->prefijo ?: 'N/A' }}</p>
                    </div>
                    <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Guardar</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <label class="text-sm text-white">Prefijo base (global)
                        <input type="text" name="prefijo" value="{{ $empresa->prefijo ?? '' }}" class="w-full pw-input mt-1" maxlength="20">
                    </label>
                    <label class="text-sm text-white">Roles
                        <input type="text" name="role_tag" value="{{ $cfg['role_tag'] ?? 'ROL' }}" class="w-full pw-input mt-1" maxlength="10">
                    </label>
                    <label class="text-sm text-white">Usuarios
                        <input type="text" name="user_tag" value="{{ $cfg['user_tag'] ?? 'USR' }}" class="w-full pw-input mt-1" maxlength="10">
                    </label>
                    <label class="text-sm text-white">Inventario
                        <input type="text" name="inventory_tag" value="{{ $cfg['inventory_tag'] ?? 'IN' }}" class="w-full pw-input mt-1" maxlength="10">
                    </label>
                    <label class="text-sm text-white">Baja
                        <input type="text" name="baja_tag" value="{{ $cfg['baja_tag'] ?? 'DB' }}" class="w-full pw-input mt-1" maxlength="10">
                    </label>
                    <label class="text-sm text-white">Auditoría
                        <input type="text" name="auditoria_tag" value="{{ $cfg['auditoria_tag'] ?? 'AUD' }}" class="w-full pw-input mt-1" maxlength="10">
                    </label>
                    <label class="text-sm text-white">Material didáctico
                        <input type="text" name="material_tag" value="{{ $cfg['material_tag'] ?? 'MD' }}" class="w-full pw-input mt-1" maxlength="10">
                    </label>
                    <label class="text-sm text-white">Tag global de tipos
                        <input type="text" name="tipo_tag" value="{{ $cfg['tipo_tag'] ?? 'TIP' }}" class="w-full pw-input mt-1" maxlength="10">
                    </label>
                </div>

                <div class="pt-2 border-t border-white/20">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm font-semibold text-white">Tipos de equipo (nombre + código)</p>
                        <button type="button" class="add-tipo-row px-2 py-1 rounded border border-gray-300 text-xs">+ Agregar</button>
                    </div>
                    <p class="text-xs text-gray-300 mb-2">Códigos actuales: {{ !empty($tiposCodigos) ? implode(', ', $tiposCodigos) : 'Sin códigos aún' }}</p>
                    <div class="space-y-2 tipo-rows">
                        @forelse($empresa->tiposEquipo as $tipo)
                            <div class="grid grid-cols-12 gap-2">
                                <input type="hidden" name="tipo_ids[]" value="{{ $tipo->id }}">
                                <input type="text" name="tipo_nombres[]" value="{{ $tipo->nombre }}" class="col-span-8 pw-input" placeholder="Nombre del tipo">
                                <input type="text" name="tipo_aliases[]" value="{{ $tipo->alias ?? '' }}" class="col-span-4 pw-input" placeholder="Código">
                            </div>
                        @empty
                            <div class="grid grid-cols-12 gap-2">
                                <input type="hidden" name="tipo_ids[]" value="">
                                <input type="text" name="tipo_nombres[]" value="" class="col-span-8 pw-input" placeholder="Nombre del tipo">
                                <input type="text" name="tipo_aliases[]" value="" class="col-span-4 pw-input" placeholder="Código">
                            </div>
                        @endforelse
                    </div>
                </div>
            </form>
        @endforeach
    </div>
</div>

<script>
document.querySelectorAll('form').forEach(function(form) {
    var addBtn = form.querySelector('.add-tipo-row');
    var rows = form.querySelector('.tipo-rows');
    if (!addBtn || !rows) return;
    addBtn.addEventListener('click', function () {
        var div = document.createElement('div');
        div.className = 'grid grid-cols-12 gap-2';
        div.innerHTML = '<input type="hidden" name="tipo_ids[]" value="">' +
            '<input type="text" name="tipo_nombres[]" value="" class="col-span-8 pw-input" placeholder="Nombre del tipo">' +
            '<input type="text" name="tipo_aliases[]" value="" class="col-span-4 pw-input" placeholder="Código">';
        rows.appendChild(div);
    });
});
</script>
@endsection
