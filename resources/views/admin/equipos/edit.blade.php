@extends('layouts.admin-layout')

@section('title', 'Editar Equipo - SAMS')
@section('header-title', 'Todos los Equipos')
@section('header-subtitle', 'Editar equipo')

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('equipos.index') }}" class="pw-btn-secondary px-4 py-2 rounded-lg">
        <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>
        Volver
    </a>
</div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto">
    <form action="{{ route('equipos.update', $equipo) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Datos principales</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Código *</label>
                    <input id="codigo" name="codigo" value="{{ old('codigo', $equipo->codigo) }}" type="text" autocomplete="off" spellcheck="false" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Solo el prefijo inicial es automático: <strong>{{ $codigoPrefijo }}-</strong>. Puedes editar la parte del tipo.</p>
                    @error('codigo')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <input type="hidden" id="codigo_prefijo" value="{{ $codigoPrefijo }}">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                    <input name="nombre" value="{{ old('nombre', $equipo->nombre) }}" type="text" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('nombre')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                    <textarea name="descripcion" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('descripcion', $equipo->descripcion) }}</textarea>
                </div>
            </div>
        </div>

        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Clasificación</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de item *</label>
                    <select id="tipo_equipo_id" name="tipo_equipo_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione...</option>
                        @foreach($tipos as $t)
                            <option value="{{ $t->id }}" {{ (string)old('tipo_equipo_id', $equipo->tipo_equipo_id) === (string)$t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Clase de items *</label>
                    <select id="clase_equipo_id" name="clase_equipo_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione...</option>
                        @foreach($clases as $c)
                            <option value="{{ $c->id }}" data-tipo="{{ $c->tipo_equipo_id }}" {{ (string)old('clase_equipo_id', $equipo->clase_equipo_id) === (string)$c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Vida útil (años)</label>
                    <input name="vida_util" value="{{ old('vida_util', $equipo->vida_util) }}" type="number" min="0" max="50" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado del item *</label>
                    <select id="estado_item" name="estado_item" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione...</option>
                        <option value="bueno" {{ old('estado_item', $equipo->estado_item) === 'bueno' ? 'selected' : '' }}>Bueno</option>
                        <option value="con_observacion" {{ old('estado_item', $equipo->estado_item) === 'con_observacion' ? 'selected' : '' }}>Con observación</option>
                    </select>
                </div>

                <div id="observacion_wrap" class="md:col-span-2 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observación *</label>
                    <textarea id="observacion" name="observacion" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('observacion', $equipo->observacion) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fabricante</label>
                    <select name="fabricante_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione...</option>
                        @foreach($fabricantes as $f)
                            <option value="{{ $f->id }}" {{ (string)old('fabricante_id', $equipo->fabricante_id) === (string)$f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                        @endforeach
                    </select>
                    @error('fabricante_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Ubicación</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Empresa</label>
                    @php($empresaDefault = old('empresa_id', $equipo->empresa_id))
                    @if(!empty($empresaDefault))
                        <input type="hidden" name="empresa_id" value="{{ $empresaDefault }}">
                    @endif
                    <select id="empresa_id" name="empresa_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" {{ !empty($empresaDefault) ? 'disabled' : '' }}>
                        <option value="">Seleccione...</option>
                        @foreach($empresas as $e)
                            <option value="{{ $e->id }}" {{ (string)$empresaDefault === (string)$e->id ? 'selected' : '' }}>{{ $e->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sede</label>
                    <select id="sede_id" name="sede_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" disabled>
                        <option value="">Seleccione...</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ubicado en</label>
                    <select id="ubicacion_tipo" name="ubicacion_tipo" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" disabled>
                        <option value="">Seleccione...</option>
                        <option value="bodega" {{ old('ubicacion_tipo', $equipo->ubicacion_tipo ?: ($equipo->va_a_bodega ? 'bodega' : '')) === 'bodega' ? 'selected' : '' }}>Bodega</option>
                        <option value="oficina" {{ old('ubicacion_tipo', $equipo->ubicacion_tipo) === 'oficina' ? 'selected' : '' }}>Oficina</option>
                        <option value="espacio" {{ old('ubicacion_tipo', $equipo->ubicacion_tipo) === 'espacio' ? 'selected' : '' }}>Espacio</option>
                    </select>
                    @error('ubicacion_tipo')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div id="wrap_bodega_id" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bodega</label>
                    <select id="bodega_id" name="bodega_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" disabled>
                        <option value="">Seleccione...</option>
                    </select>
                </div>

                <div id="wrap_oficina_id" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Oficina</label>
                    <select id="oficina_id" name="oficina_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" disabled>
                        <option value="">Seleccione...</option>
                    </select>
                </div>

                <div id="wrap_espacio_id" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Espacio</label>
                    <select id="espacio_id" name="espacio_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" disabled>
                        <option value="">Seleccione...</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Fechas y uso</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de fabricación</label>
                    <input name="fecha_fabricacion" value="{{ old('fecha_fabricacion', optional($equipo->fecha_fabricacion)->format('Y-m-d')) }}" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de uso</label>
                    <input name="fecha_uso" value="{{ old('fecha_uso', optional($equipo->fecha_uso)->format('Y-m-d')) }}" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de uso</label>
                    <select id="tipo_uso" name="tipo_uso" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione...</option>
                        @foreach(['Entrenamiento','Rescate','Seguridad','Oficina','Otro'] as $tu)
                            <option value="{{ $tu }}" {{ old('tipo_uso', $equipo->tipo_uso) === $tu ? 'selected' : '' }}>{{ $tu }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="tipo_uso_otro_wrap" class="md:col-span-3 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Describe el tipo de uso *</label>
                    <input id="tipo_uso_otro" name="tipo_uso_otro" value="{{ old('tipo_uso_otro', $equipo->tipo_uso_otro) }}" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('tipo_uso_otro')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Compra y valor</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Valor del equipo</label>
                    <input name="valor_equipo" value="{{ old('valor_equipo', $equipo->valor_equipo) }}" type="number" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Número de factura</label>
                    <input name="numero_factura" value="{{ old('numero_factura', $equipo->numero_factura) }}" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de compra</label>
                    <input name="fecha_compra" value="{{ old('fecha_compra', optional($equipo->fecha_compra)->format('Y-m-d')) }}" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Lote</label>
                    <input name="lote" value="{{ old('lote', $equipo->lote) }}" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Resistencia</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">¿Tiene resistencia/capacidad?</label>
                    <select id="tiene_resistencia" name="tiene_resistencia" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="0" {{ old('tiene_resistencia', $equipo->tiene_resistencia ? '1' : '0') === '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('tiene_resistencia', $equipo->tiene_resistencia ? '1' : '0') === '1' ? 'selected' : '' }}>Sí</option>
                    </select>
                </div>

                <div id="resistencia_wrap" class="hidden md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Describe la resistencia *</label>
                    <input id="resistencia_descripcion" name="resistencia_descripcion" value="{{ old('resistencia_descripcion', $equipo->resistencia_descripcion) }}" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('resistencia_descripcion')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Certificación</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descripción de certificación</label>
                    <textarea name="certificacion_descripcion" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('certificacion_descripcion', $equipo->certificacion_descripcion) }}</textarea>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Especificaciones técnicas</label>
                    <textarea name="especificaciones_tecnicas" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('especificaciones_tecnicas', $equipo->especificaciones_tecnicas) }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">¿Tiene manual del fabricante?</label>
                    <select name="tiene_manual_fabricante" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="0" {{ old('tiene_manual_fabricante', $equipo->tiene_manual_fabricante ? '1' : '0') === '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('tiene_manual_fabricante', $equipo->tiene_manual_fabricante ? '1' : '0') === '1' ? 'selected' : '' }}>Sí</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">¿Tiene certificación del fabricante?</label>
                    <select name="tiene_certificacion_fabricante" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="0" {{ old('tiene_certificacion_fabricante', $equipo->tiene_certificacion_fabricante ? '1' : '0') === '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('tiene_certificacion_fabricante', $equipo->tiene_certificacion_fabricante ? '1' : '0') === '1' ? 'selected' : '' }}>Sí</option>
                    </select>
                </div>

                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Evidencias de certificación (puedes agregar más)</label>
                    <input name="certificacion_evidencias[]" type="file" accept="image/*" multiple class="w-full">
                    @error('certificacion_evidencias')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Kit</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">¿Es un kit?</label>
                    <select id="es_kit" name="es_kit" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="0" {{ old('es_kit', $equipo->es_kit ? '1' : '0') === '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('es_kit', $equipo->es_kit ? '1' : '0') === '1' ? 'selected' : '' }}>Sí</option>
                    </select>
                </div>

                <div id="kit_nombre_wrap" class="hidden md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del kit *</label>
                    <input name="kit_nombre" value="{{ old('kit_nombre', $equipo->kit_nombre) }}" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('kit_nombre')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div id="kit_cantidad_wrap" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad de objetos *</label>
                    <input id="kit_cantidad" name="kit_cantidad" value="{{ old('kit_cantidad', $equipo->kit_cantidad) }}" type="number" min="1" max="50" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('kit_cantidad')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div id="kit_imagen_general_wrap" class="hidden md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Foto del kit completo {{ $equipo->es_kit ? '*' : '' }}</label>
                    <input id="kit_imagen_general" name="kit_imagen_general" type="file" accept="image/*" class="w-full">
                    @error('kit_imagen_general')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror

                    <?php $imgKit = $equipo->imagenes->firstWhere('tipo', 'kit_general'); ?>
                    @if($imgKit)
                        <div class="mt-3">
                            <div class="text-xs text-gray-500 mb-1">Actual</div>
                            <div class="relative inline-block" data-img-card="1">
                                <input type="hidden" name="delete_imagenes[]" value="{{ $imgKit->id }}" disabled>
                                <button type="button" class="pw-btn-dark absolute -top-2 -right-2 w-5 h-5 rounded-full text-xs leading-5 flex items-center justify-center shadow" data-delete-img="1">×</button>
                                <a href="{{ asset('storage/' . $imgKit->path) }}" target="_blank" class="inline-block">
                                    <img src="{{ asset('storage/' . $imgKit->path) }}" class="w-24 h-24 object-cover rounded-lg border" alt="Kit">
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div id="kit_items_container" class="hidden mt-6">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Objetos del kit</h4>
                <div id="kit_items_grid" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
                @error('kit_items')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Imágenes</h3>

            <?php $imgs = $equipo->imagenes; ?>

            @if($imgs->count())
                <div class="mb-4">
                    <div class="text-sm font-semibold text-gray-700 mb-2">Imágenes actuales</div>
                    <div class="flex flex-wrap gap-3">
                        @foreach($imgs as $img)
                            <div class="relative w-24" data-img-card="1">
                                <input type="hidden" name="delete_imagenes[]" value="{{ $img->id }}" disabled>
                                <button type="button" class="pw-btn-dark absolute -top-2 -right-2 w-5 h-5 rounded-full text-xs leading-5 flex items-center justify-center shadow" data-delete-img="1">×</button>
                                <a href="{{ asset('storage/' . $img->path) }}" target="_blank" class="block w-24 h-24 rounded-lg overflow-hidden border bg-gray-50">
                                    <img src="{{ asset('storage/' . $img->path) }}" class="w-24 h-24 object-cover" alt="Imagen">
                                </a>
                                <div class="text-[10px] text-gray-500 mt-1">{{ $img->tipo }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Agregar imagen(es) general</label>
                    <input id="picker_general" type="file" accept="image/*" class="w-full">
                    <input id="imagenes_general" name="imagenes_general[]" type="file" accept="image/*" multiple class="hidden">
                    <div id="preview_general" class="mt-3 flex flex-wrap gap-2"></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Agregar imagen(es) etiqueta</label>
                    <input id="picker_etiqueta" type="file" accept="image/*" class="w-full">
                    <input id="imagenes_etiqueta" name="imagenes_etiqueta[]" type="file" accept="image/*" multiple class="hidden">
                    <div id="preview_etiqueta" class="mt-3 flex flex-wrap gap-2"></div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('equipos.index') }}" class="pw-btn-secondary px-4 py-2 rounded-lg">Cancelar</a>
            <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Guardar cambios</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
(function() {
    const tipoSel = document.getElementById('tipo_equipo_id');
    const codigoInput = document.getElementById('codigo');
    const codigoPrefijo = document.getElementById('codigo_prefijo');
    const claseSel = document.getElementById('clase_equipo_id');
    const estadoSel = document.getElementById('estado_item');
    const obsWrap = document.getElementById('observacion_wrap');
    const obsInput = document.getElementById('observacion');

    const empresaSel = document.getElementById('empresa_id');
    const sedeSel = document.getElementById('sede_id');
    const ubicacionTipoSel = document.getElementById('ubicacion_tipo');
    const bodegaSel = document.getElementById('bodega_id');
    const oficinaSel = document.getElementById('oficina_id');
    const espacioSel = document.getElementById('espacio_id');
    const wrapBodega = document.getElementById('wrap_bodega_id');
    const wrapOficina = document.getElementById('wrap_oficina_id');
    const wrapEspacio = document.getElementById('wrap_espacio_id');

    const resSel = document.getElementById('tiene_resistencia');
    const resWrap = document.getElementById('resistencia_wrap');
    const resInput = document.getElementById('resistencia_descripcion');

    const kitSel = document.getElementById('es_kit');
    const kitNombreWrap = document.getElementById('kit_nombre_wrap');
    const kitCantidadWrap = document.getElementById('kit_cantidad_wrap');
    const kitImagenWrap = document.getElementById('kit_imagen_general_wrap');
    const kitImagenInput = document.getElementById('kit_imagen_general');
    const kitItemsContainer = document.getElementById('kit_items_container');
    const kitItemsGrid = document.getElementById('kit_items_grid');
    const kitCantidadInput = document.getElementById('kit_cantidad');

    const pickerGeneral = document.getElementById('picker_general');
    const inputGeneral = document.getElementById('imagenes_general');
    const previewGeneral = document.getElementById('preview_general');
    let filesGeneral = [];

    const pickerEtiqueta = document.getElementById('picker_etiqueta');
    const inputEtiqueta = document.getElementById('imagenes_etiqueta');
    const previewEtiqueta = document.getElementById('preview_etiqueta');
    let filesEtiqueta = [];

    const kitImageId = @json($equipo->imagenes->firstWhere('tipo', 'kit_general')?->id);

    const tipoUsoSel = document.getElementById('tipo_uso');
    const tipoUsoOtroWrap = document.getElementById('tipo_uso_otro_wrap');
    const tipoUsoOtroInput = document.getElementById('tipo_uso_otro');

    function refreshClases() {
        const tipo = tipoSel.value;
        Array.from(claseSel.options).forEach(opt => {
            const t = opt.getAttribute('data-tipo');
            if (!opt.value) return;
            opt.hidden = tipo ? String(t) !== String(tipo) : false;
        });

        const selected = claseSel.options[claseSel.selectedIndex];
        if (selected && selected.hidden) {
            claseSel.value = '';
        }
    }

    function refreshUbicacionTipo() {
        const hasSede = !!sedeSel.value;
        if (!hasSede) {
            if (ubicacionTipoSel) {
                ubicacionTipoSel.disabled = true;
                if (!ubicacionTipoSel.value) {
                    ubicacionTipoSel.value = '';
                }
            }
            bodegaSel.disabled = true;
            oficinaSel.disabled = true;
            espacioSel.disabled = true;
            bodegaSel.value = '';
            oficinaSel.value = '';
            espacioSel.value = '';
            bodegaSel.removeAttribute('required');
            oficinaSel.removeAttribute('required');
            espacioSel.removeAttribute('required');
            wrapBodega.classList.add('hidden');
            wrapOficina.classList.add('hidden');
            wrapEspacio.classList.add('hidden');
            return;
        }

        if (!ubicacionTipoSel) return;
        ubicacionTipoSel.disabled = false;
        const tipo = ubicacionTipoSel.value;
        wrapBodega.classList.toggle('hidden', tipo !== 'bodega');
        wrapOficina.classList.toggle('hidden', tipo !== 'oficina');
        wrapEspacio.classList.toggle('hidden', tipo !== 'espacio');

        if (tipo === 'bodega') {
            bodegaSel.disabled = false;
            bodegaSel.setAttribute('required', 'required');
            oficinaSel.disabled = true;
            espacioSel.disabled = true;
            oficinaSel.value = '';
            espacioSel.value = '';
            oficinaSel.removeAttribute('required');
            espacioSel.removeAttribute('required');
        } else if (tipo === 'oficina') {
            oficinaSel.disabled = false;
            oficinaSel.setAttribute('required', 'required');
            bodegaSel.disabled = true;
            espacioSel.disabled = true;
            bodegaSel.value = '';
            espacioSel.value = '';
            bodegaSel.removeAttribute('required');
            espacioSel.removeAttribute('required');
        } else if (tipo === 'espacio') {
            espacioSel.disabled = false;
            espacioSel.setAttribute('required', 'required');
            bodegaSel.disabled = true;
            oficinaSel.disabled = true;
            bodegaSel.value = '';
            oficinaSel.value = '';
            bodegaSel.removeAttribute('required');
            oficinaSel.removeAttribute('required');
        } else {
            bodegaSel.disabled = true;
            oficinaSel.disabled = true;
            espacioSel.disabled = true;
            bodegaSel.value = '';
            oficinaSel.value = '';
            espacioSel.value = '';
            bodegaSel.removeAttribute('required');
            oficinaSel.removeAttribute('required');
            espacioSel.removeAttribute('required');
        }
    }

    function refreshTipoUsoOtro() {
        if (!tipoUsoSel || !tipoUsoOtroWrap || !tipoUsoOtroInput) {
            return;
        }
        const isOtro = tipoUsoSel.value === 'Otro';
        tipoUsoOtroWrap.classList.toggle('hidden', !isOtro);
        if (isOtro) {
            tipoUsoOtroInput.setAttribute('required', 'required');
        } else {
            tipoUsoOtroInput.removeAttribute('required');
        }
    }

    function refreshResistencia() {
        if (!resSel || !resWrap || !resInput) return;
        const val = resSel.value;
        if (val === '1') {
            resWrap.classList.remove('hidden');
            resInput.setAttribute('required', 'required');
        } else {
            resWrap.classList.add('hidden');
            resInput.removeAttribute('required');
        }
    }

    function renderKitItems() {
        if (!kitSel || !kitItemsGrid || !kitCantidadInput) return;
        const isKit = kitSel.value === '1';
        if (!isKit) return;

        kitItemsGrid.innerHTML = '';
        const n = parseInt(kitCantidadInput.value || '0', 10);
        if (!n || n < 1) return;

        const oldItems = @json(old('kit_items', $equipo->kitItems->map(fn($i) => ['nombre' => $i->nombre, 'descripcion' => $i->descripcion])->values()));

        for (let i = 0; i < n; i++) {
            const wrap = document.createElement('div');
            wrap.className = 'border border-gray-200 rounded-xl p-4';

            const title = document.createElement('div');
            title.className = 'text-sm font-semibold text-gray-900 mb-2';
            title.textContent = `Objeto #${i + 1}`;
            wrap.appendChild(title);

            const container = document.createElement('div');
            container.className = 'space-y-3';

            const row1 = document.createElement('div');
            const lab1 = document.createElement('label');
            lab1.className = 'block text-xs font-medium text-gray-500 mb-1';
            lab1.textContent = 'Nombre *';
            const inp1 = document.createElement('input');
            inp1.name = `kit_items[${i}][nombre]`;
            inp1.type = 'text';
            inp1.required = true;
            inp1.className = 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500';
            row1.appendChild(lab1);
            row1.appendChild(inp1);

            const row2 = document.createElement('div');
            const lab2 = document.createElement('label');
            lab2.className = 'block text-xs font-medium text-gray-500 mb-1';
            lab2.textContent = 'Descripción';
            const inp2 = document.createElement('input');
            inp2.name = `kit_items[${i}][descripcion]`;
            inp2.type = 'text';
            inp2.className = 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500';
            row2.appendChild(lab2);
            row2.appendChild(inp2);

            const old = (oldItems && oldItems[i]) ? oldItems[i] : null;
            if (old) {
                inp1.value = old.nombre || '';
                inp2.value = old.descripcion || '';
            }

            container.appendChild(row1);
            container.appendChild(row2);
            wrap.appendChild(container);
            kitItemsGrid.appendChild(wrap);
        }
    }

    function refreshKit() {
        if (!kitSel) return;
        const isKit = kitSel.value === '1';
        [kitNombreWrap, kitCantidadWrap, kitImagenWrap, kitItemsContainer].forEach(el => {
            if (!el) return;
            el.classList.toggle('hidden', !isKit);
        });

        if (kitImagenInput) {
            if (!isKit) {
                kitImagenInput.removeAttribute('required');
                kitImagenInput.value = '';
            } else {
                let markedForDelete = false;
                if (kitImageId) {
                    const delInput = document.querySelector(`input[type="hidden"][name="delete_imagenes[]"][value="${kitImageId}"]`);
                    if (delInput && delInput.disabled === false) {
                        markedForDelete = true;
                    }
                }

                if (!kitImageId || markedForDelete) {
                    kitImagenInput.setAttribute('required', 'required');
                } else {
                    kitImagenInput.removeAttribute('required');
                }
            }
        }

        if (!isKit) {
            if (kitItemsGrid) kitItemsGrid.innerHTML = '';
        } else {
            renderKitItems();
        }
    }

    function renderPreview(previewEl, filesArr, onRemove) {
        previewEl.innerHTML = '';
        filesArr.forEach((file, idx) => {
            const wrap = document.createElement('div');
            wrap.className = 'relative w-16 h-16';

            const url = URL.createObjectURL(file);
            const img = document.createElement('img');
            img.src = url;
            img.alt = 'preview';
            img.className = 'w-16 h-16 rounded-lg object-cover border border-gray-200';

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pw-btn-dark absolute -top-2 -right-2 w-5 h-5 rounded-full text-xs leading-5 flex items-center justify-center shadow';
            btn.textContent = '×';
            btn.addEventListener('click', function() {
                onRemove(idx);
            });

            wrap.appendChild(img);
            wrap.appendChild(btn);
            previewEl.appendChild(wrap);
        });
    }

    function syncInputFiles(input, filesArr) {
        const dt = new DataTransfer();
        filesArr.forEach(f => dt.items.add(f));
        input.files = dt.files;
    }

    function bindPicker(picker, input, previewEl, getFiles, setFiles) {
        if (!picker || !input || !previewEl) return;
        picker.addEventListener('change', function() {
            const file = picker.files && picker.files[0] ? picker.files[0] : null;
            if (!file) return;

            const next = getFiles().slice();
            next.push(file);
            setFiles(next);
            syncInputFiles(input, next);
            renderPreview(previewEl, next, function(removeIndex) {
                const updated = getFiles().slice();
                updated.splice(removeIndex, 1);
                setFiles(updated);
                syncInputFiles(input, updated);
                renderPreview(previewEl, updated, arguments.callee);
            });

            picker.value = '';
        });
    }

    function ensureCodigoPrefijo() {
        if (!codigoInput || !codigoPrefijo) return;
        const pref = (codigoPrefijo.value || '').trim().toUpperCase();
        if (!pref) return;
        let val = (codigoInput.value || '').trim().toUpperCase().replace(/\s+/g, '');
        if (!val) return;
        if (!val.startsWith(pref + '-')) {
            val = pref + '-' + val.replace(/^-+/, '');
        }
        codigoInput.value = val;
    }

    function refreshObservacion() {
        const val = estadoSel.value;
        if (val === 'con_observacion') {
            obsWrap.classList.remove('hidden');
            obsInput.setAttribute('required', 'required');
        } else {
            obsWrap.classList.add('hidden');
            obsInput.removeAttribute('required');
        }
    }

    async function loadSedes() {
        sedeSel.innerHTML = '<option value="">Seleccione...</option>';
        bodegaSel.innerHTML = '<option value="">Seleccione...</option>';
        oficinaSel.innerHTML = '<option value="">Seleccione...</option>';
        espacioSel.innerHTML = '<option value="">Seleccione...</option>';
        sedeSel.disabled = true;
        bodegaSel.disabled = true;
        oficinaSel.disabled = true;
        espacioSel.disabled = true;

        const empresaId = empresaSel.value;
        if (!empresaId) return;

        const res = await fetch(appUrl(`/empresa/${empresaId}/sedes`));
        const data = await res.json();
        data.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.nombre;
            sedeSel.appendChild(opt);
        });
        sedeSel.disabled = false;

        if (ubicacionTipoSel) {
            ubicacionTipoSel.disabled = false;
        }
        refreshUbicacionTipo();

        const oldSedeId = @json(old('sede_id', $equipo->sede_id));
        if (oldSedeId) {
            sedeSel.value = oldSedeId;
            refreshUbicacionTipo();
            await loadUbicaciones();
        }
    }

    async function loadUbicaciones() {
        bodegaSel.innerHTML = '<option value="">Seleccione...</option>';
        oficinaSel.innerHTML = '<option value="">Seleccione...</option>';
        espacioSel.innerHTML = '<option value="">Seleccione...</option>';
        bodegaSel.disabled = true;
        oficinaSel.disabled = true;
        espacioSel.disabled = true;

        const sedeId = sedeSel.value;
        if (!sedeId) return;

        const [resB, resO, resE] = await Promise.all([
            fetch(appUrl(`/sede/${sedeId}/bodegas`)),
            fetch(appUrl(`/sede/${sedeId}/oficinas`)),
            fetch(appUrl(`/sede/${sedeId}/espacios`)),
        ]);
        const [dataB, dataO, dataE] = await Promise.all([resB.json(), resO.json(), resE.json()]);

        dataB.forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.id;
            opt.textContent = b.sede_id ? b.nombre : `${b.nombre} (general)`;
            bodegaSel.appendChild(opt);
        });
        dataO.forEach(o => {
            const opt = document.createElement('option');
            opt.value = o.id;
            opt.textContent = o.nombre;
            oficinaSel.appendChild(opt);
        });
        dataE.forEach(e => {
            const opt = document.createElement('option');
            opt.value = e.id;
            opt.textContent = e.nombre;
            espacioSel.appendChild(opt);
        });

        const oldBodegaId = @json(old('bodega_id', $equipo->bodega_id));
        if (oldBodegaId) bodegaSel.value = oldBodegaId;
        const oldOficinaId = @json(old('oficina_id', $equipo->oficina_id));
        if (oldOficinaId) oficinaSel.value = oldOficinaId;
        const oldEspacioId = @json(old('espacio_id', $equipo->espacio_id));
        if (oldEspacioId) espacioSel.value = oldEspacioId;

        refreshUbicacionTipo();
    }

    tipoSel.addEventListener('change', refreshClases);
    estadoSel.addEventListener('change', refreshObservacion);
    empresaSel.addEventListener('change', loadSedes);
    sedeSel.addEventListener('change', async function() {
        refreshUbicacionTipo();
        await loadUbicaciones();
    });
    if (ubicacionTipoSel) {
        ubicacionTipoSel.addEventListener('change', function() {
            refreshUbicacionTipo();
        });
    }
    if (tipoUsoSel) {
        tipoUsoSel.addEventListener('change', refreshTipoUsoOtro);
    }

    if (resSel) {
        resSel.addEventListener('change', refreshResistencia);
    }

    if (kitSel) {
        kitSel.addEventListener('change', refreshKit);
    }
    if (kitCantidadInput) {
        kitCantidadInput.addEventListener('input', renderKitItems);
    }

    bindPicker(pickerGeneral, inputGeneral, previewGeneral, () => filesGeneral, (v) => filesGeneral = v);
    bindPicker(pickerEtiqueta, inputEtiqueta, previewEtiqueta, () => filesEtiqueta, (v) => filesEtiqueta = v);
    if (codigoInput) {
        codigoInput.addEventListener('blur', ensureCodigoPrefijo);
    }

    refreshClases();
    refreshObservacion();
    refreshTipoUsoOtro();
    refreshResistencia();
    refreshKit();
    refreshUbicacionTipo();
    ensureCodigoPrefijo();

    if (empresaSel.value) {
        loadSedes();
    }
})();

document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    document.querySelectorAll('[data-delete-img="1"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const card = btn.closest('[data-img-card="1"]');
            if (!card) return;
            var doDelete = function() {
                const input = card.querySelector('input[type="hidden"][name="delete_imagenes[]"]');
                if (input) input.disabled = false;
                card.style.display = 'none';
                try { refreshKit(); } catch (e) {}
            };
            if (typeof showConfirmModal === 'function') {
                showConfirmModal({
                    title: 'Eliminar imagen',
                    message: '¿Estás seguro de que deseas eliminar esta imagen?',
                    confirmText: 'Sí, eliminar',
                    cancelText: 'Cancelar',
                    danger: true,
                    onConfirm: doDelete
                });
            }
        });
    });
});
</script>
@endsection
