@extends('layouts.admin-layout')

@section('title', 'Nuevo Equipo - SAMS')
@section('header-title', 'Todos los Equipos')
@section('header-subtitle', 'Registrar nuevo equipo')

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
    <form action="{{ route('equipos.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Datos principales</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Código *</label>
                    <input id="codigo" name="codigo" value="{{ old('codigo', $codigo) }}" type="text" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @if($codigoLocked)
                        <p class="text-xs text-gray-500 mt-1">Código reservado automáticamente para este registro.</p>
                    @else
                        <p class="text-xs text-gray-500 mt-1">Se genera automáticamente al seleccionar tipo y clase (formato: <strong>{{ $codigoPrefijo }}-AAC-IN-0001</strong>).</p>
                    @endif
                    @error('codigo')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <input type="hidden" id="codigo_prefijo" value="{{ $codigoPrefijo }}">
                <input type="hidden" id="codigo_locked" value="{{ $codigoLocked ? '1' : '0' }}">

                @if(!empty($codigoReutilizableId))
                    <input type="hidden" name="codigo_reutilizable_id" value="{{ $codigoReutilizableId }}">
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                    <input name="nombre" value="{{ old('nombre') }}" type="text" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('nombre')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                    <textarea name="descripcion" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('descripcion') }}</textarea>
                    @error('descripcion')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
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
                            <option value="{{ $t->id }}" {{ (string)old('tipo_equipo_id') === (string)$t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                        @endforeach
                    </select>
                    @error('tipo_equipo_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Clase de items *</label>
                    <select id="clase_equipo_id" name="clase_equipo_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione...</option>
                        @foreach($clases as $c)
                            <option value="{{ $c->id }}" data-tipo="{{ $c->tipo_equipo_id }}" {{ (string)old('clase_equipo_id') === (string)$c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                    @error('clase_equipo_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Vida útil (años)</label>
                    <input name="vida_util" value="{{ old('vida_util') }}" type="number" min="0" max="50" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('vida_util')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado del item *</label>
                    <select id="estado_item" name="estado_item" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione...</option>
                        <option value="bueno" {{ old('estado_item') === 'bueno' ? 'selected' : '' }}>Bueno</option>
                        <option value="con_observacion" {{ old('estado_item') === 'con_observacion' ? 'selected' : '' }}>Con observación</option>
                    </select>
                    @error('estado_item')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div id="observacion_wrap" class="md:col-span-2 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observación *</label>
                    <textarea id="observacion" name="observacion" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('observacion') }}</textarea>
                    @error('observacion')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Marca / Proveedor y Ubicación</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Proveedor / Marca</label>
                    <select name="fabricante_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione...</option>
                        @foreach($fabricantes as $f)
                            <option value="{{ $f->id }}" {{ (string)old('fabricante_id') === (string)$f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                        @endforeach
                    </select>
                    @error('fabricante_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Empresa</label>
                    @php($empresaDefault = old('empresa_id', $empresaId ?? null))
                    @if(!empty($empresaDefault))
                        <input type="hidden" name="empresa_id" value="{{ $empresaDefault }}">
                    @endif
                    <select id="empresa_id" name="empresa_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" {{ !empty($empresaDefault) ? 'disabled' : '' }}>
                        <option value="">Seleccione...</option>
                        @foreach($empresas as $e)
                            <option value="{{ $e->id }}" {{ (string)$empresaDefault === (string)$e->id ? 'selected' : '' }}>{{ $e->nombre }}</option>
                        @endforeach
                    </select>
                    @error('empresa_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sede</label>
                    <select id="sede_id" name="sede_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" disabled>
                        <option value="">Seleccione...</option>
                    </select>
                    @error('sede_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ubicado en</label>
                    <select id="ubicacion_tipo" name="ubicacion_tipo" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" disabled>
                        <option value="">Seleccione...</option>
                        <option value="bodega" {{ old('ubicacion_tipo') === 'bodega' ? 'selected' : '' }}>Bodega</option>
                        <option value="oficina" {{ old('ubicacion_tipo') === 'oficina' ? 'selected' : '' }}>Oficina</option>
                        <option value="espacio" {{ old('ubicacion_tipo') === 'espacio' ? 'selected' : '' }}>Espacio</option>
                    </select>
                    @error('ubicacion_tipo')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div id="wrap_bodega_id" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bodega</label>
                    <select id="bodega_id" name="bodega_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" disabled>
                        <option value="">Seleccione...</option>
                    </select>
                    @error('bodega_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div id="wrap_oficina_id" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Oficina</label>
                    <select id="oficina_id" name="oficina_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" disabled>
                        <option value="">Seleccione...</option>
                    </select>
                    @error('oficina_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div id="wrap_espacio_id" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Espacio</label>
                    <select id="espacio_id" name="espacio_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" disabled>
                        <option value="">Seleccione...</option>
                    </select>
                    @error('espacio_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Fechas y uso</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de fabricación</label>
                    <input name="fecha_fabricacion" value="{{ old('fecha_fabricacion') }}" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de uso</label>
                    <input name="fecha_uso" value="{{ old('fecha_uso') }}" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de uso</label>
                    <select id="tipo_uso" name="tipo_uso" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione...</option>
                        @foreach(['Entrenamiento','Rescate','Seguridad','Oficina','Otro'] as $tu)
                            <option value="{{ $tu }}" {{ old('tipo_uso') === $tu ? 'selected' : '' }}>{{ $tu }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="tipo_uso_otro_wrap" class="md:col-span-3 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Describe el tipo de uso *</label>
                    <input id="tipo_uso_otro" name="tipo_uso_otro" value="{{ old('tipo_uso_otro') }}" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('tipo_uso_otro')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Kit</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">¿Es un kit?</label>
                    <select id="es_kit" name="es_kit" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="0" {{ old('es_kit', '0') === '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('es_kit') === '1' ? 'selected' : '' }}>Sí</option>
                    </select>
                    @error('es_kit')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div id="kit_nombre_wrap" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del kit *</label>
                    <input id="kit_nombre" name="kit_nombre" value="{{ old('kit_nombre') }}" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('kit_nombre')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div id="kit_cantidad_wrap" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cantidad de objetos *</label>
                    <input id="kit_cantidad" name="kit_cantidad" value="{{ old('kit_cantidad') }}" type="number" min="1" max="50" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('kit_cantidad')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div id="kit_imagen_general_wrap" class="hidden md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Foto obligatoria del kit completo *</label>
                    <input id="kit_imagen_general" name="kit_imagen_general" type="file" accept="image/*" class="w-full">
                    @error('kit_imagen_general')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div id="kit_items_container" class="hidden mt-6">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Objetos del kit</h4>
                <div id="kit_items_grid" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
                @error('kit_items')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Certificación y evidencia fotográfica</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descripción de certificación</label>
                    <textarea name="certificacion_descripcion" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('certificacion_descripcion') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Evidencia fotográfica (opcional)</label>
                    <input name="certificacion_evidencias[]" type="file" accept="image/*" multiple class="w-full">
                    @error('certificacion_evidencias')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Especificaciones técnicas</h3>
            <textarea name="especificaciones_tecnicas" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('especificaciones_tecnicas') }}</textarea>
        </div>

        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Compra y valor</h3>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Valor del equipo</label>
                    <input name="valor_equipo" value="{{ old('valor_equipo') }}" type="number" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Número de factura</label>
                    <input name="numero_factura" value="{{ old('numero_factura') }}" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de compra</label>
                    <input name="fecha_compra" value="{{ old('fecha_compra') }}" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Lote</label>
                    <input name="lote" value="{{ old('lote') }}" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">¿Datos de resistencia?</label>
                    <select id="tiene_resistencia" name="tiene_resistencia" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="0" {{ old('tiene_resistencia', '0') === '0' ? 'selected' : '' }}>No</option>
                        <option value="1" {{ old('tiene_resistencia') === '1' ? 'selected' : '' }}>Sí</option>
                    </select>
                </div>
                <div id="resistencia_wrap" class="md:col-span-2 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descripción de resistencia *</label>
                    <input id="resistencia_descripcion" name="resistencia_descripcion" value="{{ old('resistencia_descripcion') }}" type="text" placeholder="Ej: Resiste 200 kilos" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('resistencia_descripcion')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="flex items-center gap-3">
                    <input id="tiene_manual_fabricante" name="tiene_manual_fabricante" value="1" type="checkbox" {{ old('tiene_manual_fabricante') ? 'checked' : '' }} class="h-4 w-4 text-blue-600">
                    <label for="tiene_manual_fabricante" class="text-sm text-gray-700">Viene con manual del fabricante</label>
                </div>
                <div class="flex items-center gap-3">
                    <input id="tiene_certificacion_fabricante" name="tiene_certificacion_fabricante" value="1" type="checkbox" {{ old('tiene_certificacion_fabricante') ? 'checked' : '' }} class="h-4 w-4 text-blue-600">
                    <label for="tiene_certificacion_fabricante" class="text-sm text-gray-700">Viene con certificación del fabricante</label>
                </div>
            </div>
        </div>

        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Imágenes</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Imagen(es) general</label>
                    <input id="picker_general" type="file" accept="image/*" class="w-full">
                    <input id="imagenes_general" name="imagenes_general[]" type="file" accept="image/*" multiple class="hidden">
                    @error('imagenes_general')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    <div id="preview_general" class="mt-3 flex flex-wrap gap-2"></div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Imagen(es) etiqueta</label>
                    <input id="picker_etiqueta" type="file" accept="image/*" class="w-full">
                    <input id="imagenes_etiqueta" name="imagenes_etiqueta[]" type="file" accept="image/*" multiple class="hidden">
                    @error('imagenes_etiqueta')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    <div id="preview_etiqueta" class="mt-3 flex flex-wrap gap-2"></div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('equipos.index') }}" class="pw-btn-secondary px-4 py-2 rounded-lg">Cancelar</a>
            <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Guardar</button>
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

    const kitSel = document.getElementById('es_kit');
    const kitNombreWrap = document.getElementById('kit_nombre_wrap');
    const kitCantidadWrap = document.getElementById('kit_cantidad_wrap');
    const kitImagenWrap = document.getElementById('kit_imagen_general_wrap');
    const kitImagenInput = document.getElementById('kit_imagen_general');
    const kitItemsContainer = document.getElementById('kit_items_container');
    const kitItemsGrid = document.getElementById('kit_items_grid');
    const kitCantidadInput = document.getElementById('kit_cantidad');

    const resSel = document.getElementById('tiene_resistencia');
    const resWrap = document.getElementById('resistencia_wrap');
    const resInput = document.getElementById('resistencia_descripcion');

    const tipoUsoSel = document.getElementById('tipo_uso');
    const tipoUsoOtroWrap = document.getElementById('tipo_uso_otro_wrap');
    const tipoUsoOtroInput = document.getElementById('tipo_uso_otro');

    const pickerGeneral = document.getElementById('picker_general');
    const inputGeneral = document.getElementById('imagenes_general');
    const previewGeneral = document.getElementById('preview_general');
    let filesGeneral = [];

    const pickerEtiqueta = document.getElementById('picker_etiqueta');
    const inputEtiqueta = document.getElementById('imagenes_etiqueta');
    const previewEtiqueta = document.getElementById('preview_etiqueta');
    let filesEtiqueta = [];

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
            if (!ubicacionTipoSel.value) {
                ubicacionTipoSel.value = '';
            }
        }

        refreshUbicacionTipo();

        const oldSedeId = @json(old('sede_id'));
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

        const oldBodegaId = @json(old('bodega_id'));
        if (oldBodegaId) bodegaSel.value = oldBodegaId;
        const oldOficinaId = @json(old('oficina_id'));
        if (oldOficinaId) oficinaSel.value = oldOficinaId;
        const oldEspacioId = @json(old('espacio_id'));
        if (oldEspacioId) espacioSel.value = oldEspacioId;

        refreshUbicacionTipo();
    }

    function refreshKit() {
        const isKit = kitSel.value === '1';
        [kitNombreWrap, kitCantidadWrap, kitImagenWrap, kitItemsContainer].forEach(el => {
            if (!el) return;
            el.classList.toggle('hidden', !isKit);
        });

        if (kitImagenInput) {
            if (isKit) {
                kitImagenInput.setAttribute('required', 'required');
            } else {
                kitImagenInput.removeAttribute('required');
                kitImagenInput.value = '';
            }
        }

        if (!isKit) {
            kitItemsGrid.innerHTML = '';
        } else {
            renderKitItems();
        }
    }

    function renderKitItems() {
        const isKit = kitSel.value === '1';
        if (!isKit) return;

        const n = parseInt(kitCantidadInput.value || '0', 10);
        kitItemsGrid.innerHTML = '';
        if (!n || n < 1) return;

        for (let i = 0; i < n; i++) {
            const wrap = document.createElement('div');
            wrap.className = 'border border-gray-200 rounded-xl p-4';

            wrap.innerHTML = `
                <div class="text-sm font-semibold text-gray-900 mb-2">Objeto #${i + 1}</div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nombre *</label>
                        <input name="kit_items[${i}][nombre]" type="text" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Descripción</label>
                        <input name="kit_items[${i}][descripcion]" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                </div>
            `;

            kitItemsGrid.appendChild(wrap);
        }
    }

    function refreshResistencia() {
        const val = resSel.value;
        if (val === '1') {
            resWrap.classList.remove('hidden');
            resInput.setAttribute('required', 'required');
        } else {
            resWrap.classList.add('hidden');
            resInput.removeAttribute('required');
        }
    }

    function refreshTipoUsoOtro() {
        const isOtro = tipoUsoSel.value === 'Otro';
        tipoUsoOtroWrap.classList.toggle('hidden', !isOtro);
        if (isOtro) {
            tipoUsoOtroInput.setAttribute('required', 'required');
        } else {
            tipoUsoOtroInput.removeAttribute('required');
            tipoUsoOtroInput.value = '';
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

    let refreshCodigoTimeout = null;
    let refreshCodigoSeq = 0;
    async function refreshCodigoAuto() {
        if (!codigoInput) return;
        const isLocked = document.getElementById('codigo_locked')?.value === '1';
        if (isLocked) return;

        const tipoId = tipoSel?.value || '';
        const claseId = claseSel?.value || '';
        const empresaId = empresaSel?.value || '';
        if (!tipoId || !claseId) {
            codigoInput.value = '';
            codigoInput.placeholder = 'Se genera al elegir tipo y clase';
            return;
        }

        const params = new URLSearchParams();
        params.set('tipo_equipo_id', tipoId);
        params.set('clase_equipo_id', claseId);
        if (empresaId) {
            params.set('empresa_id', empresaId);
        }

        const mySeq = ++refreshCodigoSeq;
        try {
            const res = await fetch(`{{ route('equipos.codigo-sugerido') }}?${params.toString()}`);
            if (!res.ok) return;
            const data = await res.json();
            if (mySeq !== refreshCodigoSeq) return;
            if (data && data.codigo) {
                codigoInput.value = String(data.codigo);
            }
        } catch (e) {
            // keep existing value on fetch errors
        }
    }

    function queueRefreshCodigoAuto() {
        if (refreshCodigoTimeout) {
            clearTimeout(refreshCodigoTimeout);
        }
        refreshCodigoTimeout = setTimeout(refreshCodigoAuto, 120);
    }

    tipoSel.addEventListener('change', function() {
        refreshClases();
        queueRefreshCodigoAuto();
    });
    claseSel.addEventListener('change', queueRefreshCodigoAuto);
    estadoSel.addEventListener('change', refreshObservacion);
    empresaSel.addEventListener('change', function() {
        loadSedes();
        queueRefreshCodigoAuto();
    });
    sedeSel.addEventListener('change', function() {
        refreshUbicacionTipo();
        loadUbicaciones();
    });
    if (ubicacionTipoSel) {
        ubicacionTipoSel.addEventListener('change', function() {
            refreshUbicacionTipo();
        });
    }
    kitSel.addEventListener('change', refreshKit);
    kitCantidadInput.addEventListener('input', renderKitItems);
    resSel.addEventListener('change', refreshResistencia);
    tipoUsoSel.addEventListener('change', refreshTipoUsoOtro);

    bindPicker(pickerGeneral, inputGeneral, previewGeneral, () => filesGeneral, (v) => filesGeneral = v);
    bindPicker(pickerEtiqueta, inputEtiqueta, previewEtiqueta, () => filesEtiqueta, (v) => filesEtiqueta = v);
    refreshClases();
    refreshObservacion();
    refreshKit();
    refreshResistencia();
    refreshTipoUsoOtro();
    refreshUbicacionTipo();
    queueRefreshCodigoAuto();

    if (empresaSel.value) {
        loadSedes();
    }
})();

document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
@endsection
