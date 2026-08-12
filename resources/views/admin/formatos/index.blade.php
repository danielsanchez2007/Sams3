@extends('layouts.admin-layout')

@section('title', 'Formatos - SAMS')
@section('header-title', 'Formatos')
@section('header-subtitle', 'Cargar plantilla Excel por Clase de Equipo')

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

    @if ($errors->any())
        <div class="p-4 rounded-2xl border border-red-200 bg-red-50 text-red-800 text-sm">
            @foreach ($errors->all() as $e)
                <div>{{ $e }}</div>
            @endforeach
        </div>
    @endif

    <div class="pw-card bg-white rounded-2xl shadow-lg p-5 border border-gray-100">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-lg font-semibold text-gray-900">Cargar formato (Excel)</div>
                <div class="text-xs text-gray-500">Sube la hoja de vida en blanco y asígnala a una Clase de Equipo.</div>
            </div>
            <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center">
                <i data-lucide="file-spreadsheet" class="w-5 h-5 text-gray-700"></i>
            </div>
        </div>

        <div class="mt-5 text-xs text-gray-500">
            Tokens automáticos sugeridos:
            <div class="mt-1 grid grid-cols-2 gap-1">
                <div>@{{CODIGO}}</div>
                <div>@{{CODIGO_IN}}</div>
                <div>@{{NOMBRE}}</div>
                <div>@{{NOMBRE_EQUIPO}}</div>
                <div>@{{SERIAL}}</div>
                <div>@{{SERIE}}</div>
                <div>@{{DESCRIPCION}}</div>
                <div>@{{DESCRIPCION_GENERAL}}</div>
                <div>@{{TIPO_EQUIPO}}</div>
                <div>@{{CLASE_EQUIPO}}</div>
                <div>@{{EMPRESA}}</div>
                <div>@{{SEDE}}</div>
                <div>@{{BODEGA}}</div>
                <div>@{{UBICACION}}</div>
                <div>@{{FABRICANTE}}</div>
                <div>@{{FECHA_COMPRA}}</div>
                <div>@{{FACTURA}}</div>
                <div>@{{LOTE}}</div>
                <div>@{{USO}}</div>
                <div>@{{TIPO_USO}}</div>
                <div>@{{TIPO_USO_OTRO}}</div>
                <div>@{{FECHA_FABRICACION}}</div>
                <div>@{{FECHA_USO}}</div>
                <div>@{{VIDA_UTIL}}</div>
                <div>@{{ESTADO_ITEM}}</div>
                <div>@{{OBSERVACION}}</div>
                <div>@{{CERTIFICACION}}</div>
                <div>@{{ESPECIFICACIONES_TECNICAS}}</div>
                <div>@{{CUMPLE_NORMAS}}</div>
                <div>@{{ESTADO_FISICO}}</div>
                <div>@{{ESTADO_FUNCIONAL}}</div>
                <div>@{{PUNTUACION}}</div>
                <div>@{{FECHA_AUDITORIA}}</div>
                <div>@{{FECHA_HOY}}</div>
                <div>@{{HORA_HOY}}</div>
                <div>@{{USUARIO}}</div>
            </div>
        </div>

        @php
            // Clases que ya tienen formato asignado
            $clasesConPlantilla = $plantillas->pluck('id', 'clase_equipo_id')->all();
        @endphp

        <form action="{{ route('formatos.store') }}" method="POST" enctype="multipart/form-data" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            @csrf
            <div class="md:col-span-1">
                <label class="block text-xs font-medium text-gray-500 mb-1">Clase de Equipo</label>
                <select name="clase_equipo_id" class="w-full px-3 py-2 border border-gray-300 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    <option value="">Selecciona una clase...</option>
                    @foreach($clases as $c)
                        @php
                            $yaTiene = array_key_exists($c->id, $clasesConPlantilla);
                        @endphp
                        <option value="{{ $c->id }}"
                                {{ (string)old('clase_equipo_id') === (string)$c->id ? 'selected' : '' }}
                                {{ $yaTiene ? 'disabled' : '' }}>
                            {{ $c->nombre }} ({{ $c->tipoEquipo?->nombre ?? 'Sin tipo' }}){{ $yaTiene ? ' - YA TIENE FORMATO' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-1">
                <label class="block text-xs font-medium text-gray-500 mb-1">Excel</label>
                <input type="file" name="plantilla_excel" accept=".xlsx,.xls" class="w-full" required>
            </div>
            <div class="md:col-span-1">
                <button type="submit" class="pw-btn-apartado-formatos w-full px-4 py-3 rounded-xl">Guardar formato</button>
            </div>
        </form>
    </div>

    <div class="pw-card bg-white rounded-2xl shadow-lg p-5 border border-gray-100">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-lg font-semibold text-gray-900">Plantillas asignadas</div>
                <div class="text-xs text-gray-500">Listado de clases con su plantilla cargada.</div>
            </div>
        </div>

        <div class="overflow-x-auto mt-4">
            <table class="min-w-full text-sm">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left">Clase</th>
                        <th class="px-3 py-3 text-left">Fecha</th>
                        <th class="px-3 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($plantillas as $p)
                        <tr>
                            <td class="px-3 py-3 text-gray-900 font-semibold">{{ $p->claseEquipo?->nombre ?? ('Clase #' . ($p->clase_equipo_id ?? '-')) }} <span class="text-xs text-gray-500">({{ $p->tipoEquipo?->nombre ?? ('Tipo #' . $p->tipo_equipo_id) }})</span></td>
                            <td class="px-3 py-3 text-gray-700">{{ optional($p->updated_at)->format('Y-m-d H:i') }}</td>
                            <td class="px-3 py-3 text-right">
                                <div class="flex flex-wrap gap-2 justify-end">
                                    <a href="{{ route('formatos.reemplazar.form', $p) }}" class="pw-btn-reemplazar px-3 py-2 rounded-lg">Reemplazar formato</a>
                                    <a href="{{ route('formatos.download', $p) }}" class="pw-btn-apartado-formatos px-3 py-2 rounded-lg">Descargar</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-3 py-6 text-center text-gray-500">No hay plantillas asignadas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
});
</script>
@endsection
