@extends('layouts.admin-layout')

@section('title', 'Gestión de Empresa - SAMS')
@section('header-title', 'Gestión de Empresa')
@section('header-subtitle', 'Administración de Empresa, Sedes y Bodegas')

@section('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css">
<style>
    #localizacionMap { z-index: 0; }
    .loc-map-info { min-width: 180px; }
    .loc-map-info-title { font-weight: 700; color: #0f172a; margin-bottom: 4px; }
    .loc-map-info-meta, .loc-map-info-address { font-size: 12px; color: #475569; }
    .loc-map-info-link a { font-size: 12px; color: #2563eb; }

    .empresa-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        padding: 0.3rem;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }
    .empresa-tabs .empresa-tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        margin: 0;
        padding: 0.55rem 0.95rem;
        border: 0;
        border-radius: 0.55rem;
        background: transparent;
        color: #334155;
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.1;
        box-shadow: none;
        cursor: pointer;
        transition: background-color .15s ease, color .15s ease;
    }
    .empresa-tabs .empresa-tab-btn i,
    .empresa-tabs .empresa-tab-btn svg {
        width: 1rem;
        height: 1rem;
        color: currentColor;
        stroke: currentColor;
    }
    .empresa-tabs .empresa-tab-btn:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: transparent;
        box-shadow: none;
    }
    .empresa-tabs .empresa-tab-btn.is-active {
        background: var(--sams-btn-primary-bg, #1e3a8a);
        color: #ffffff;
        border-color: transparent;
        box-shadow: none;
    }
</style>
@endsection

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    @if(!($soloMiEmpresa ?? false))
    <button onclick="showCreateEmpresaModal()" class="empresa-header-btn empresa-header-btn--primary">
        <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
        Nueva Empresa
    </button>
    @endif
    @if(!($soloMiEmpresa ?? false) || ($canEditSede ?? true))
    <button onclick="showCreateSedeModal()" class="empresa-header-btn empresa-header-btn--accent">
        <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
        Nueva Sede
    </button>
    @endif
    @if(!($soloMiEmpresa ?? false) || ($canEditBodega ?? true))
    <button onclick="showCreateBodegaModal()" class="empresa-header-btn">
        <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
        Nueva Bodega
    </button>
    @endif
    @if(($mostrarPestanaOficinas ?? false) && ($canEditOficina ?? false))
    <button type="button" onclick="showCreateOficinaModal()" class="empresa-header-btn empresa-header-btn--primary">
        <i data-lucide="briefcase" class="w-4 h-4 inline mr-2"></i>
        Nueva oficina
    </button>
    @endif
    @if($canEditEspacio ?? false)
    <button type="button" onclick="showCreateEspacioModal()" class="empresa-header-btn empresa-header-btn--accent">
        <i data-lucide="armchair" class="w-4 h-4 inline mr-2"></i>
        Nuevo espacio
    </button>
    @endif
</div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-8">
    </div>

    @if($errors->any())
    <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
        <div class="flex items-start gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 mt-0.5 shrink-0"></i>
            <div>
                <p class="font-semibold text-red-800">No se pudo guardar. Revise los siguientes datos:</p>
                <ul class="mt-2 list-disc list-inside space-y-1 text-sm text-red-700">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <nav class="empresa-tabs mb-6" role="tablist" aria-label="Secciones de empresa">
            <button type="button" id="tabLocalizacion" onclick="openTab('localizacion')" class="empresa-tab-btn">
                <i data-lucide="map-pinned" class="w-4 h-4"></i> Localización
            </button>
            <button type="button" id="tabEmpresa" onclick="openTab('empresa')" class="empresa-tab-btn">
                <i data-lucide="building-2" class="w-4 h-4"></i> Empresa
            </button>
            <button type="button" id="tabSede" onclick="openTab('sede')" class="empresa-tab-btn">
                <i data-lucide="map-pin" class="w-4 h-4"></i> Sede
            </button>
            <button type="button" id="tabBodega" onclick="openTab('bodega')" class="empresa-tab-btn">
                <i data-lucide="warehouse" class="w-4 h-4"></i> Bodegas
            </button>
            @if($mostrarPestanaOficinas ?? false)
            <button type="button" id="tabOficina" onclick="openTab('oficina')" class="empresa-tab-btn">
                <i data-lucide="briefcase" class="w-4 h-4"></i> Oficina
            </button>
            @endif
            <button type="button" id="tabEspacio" onclick="openTab('espacio')" class="empresa-tab-btn">
                <i data-lucide="armchair" class="w-4 h-4"></i> Espacio
            </button>
    </nav>

    <div id="contentLocalizacion">
        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div class="lg:col-span-3">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                            <select id="locFilterTipo" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <option value="all">Todos</option>
                                <option value="empresa">Empresas</option>
                                <option value="sede">Sedes</option>
                                <option value="bodega">Bodegas</option>
                                <option value="oficina">Oficinas</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Empresa</label>
                            <select id="locFilterEmpresa" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <option value="all">Todas</option>
                                @foreach($empresas as $empresa)
                                    <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sede</label>
                            <select id="locFilterSede" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <option value="all">Todas</option>
                                @foreach($sedes as $sede)
                                    <option value="{{ $sede->id }}" data-empresa="{{ $sede->empresa_id }}">{{ $sede->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Bodega</label>
                            <select id="locFilterBodega" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <option value="all">Todas</option>
                                @foreach($bodegas as $bodega)
                                    <option value="{{ $bodega->id }}" data-empresa="{{ $bodega->empresa_id }}" data-sede="{{ $bodega->sede_id }}">{{ $bodega->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Oficina</label>
                            <select id="locFilterOficina" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <option value="all">Todas</option>
                                @foreach($oficinasMap ?? [] as $oficinaM)
                                    <option value="{{ $oficinaM->id }}" data-empresa="{{ $oficinaM->empresa_id }}" data-sede="{{ $oficinaM->sede_id }}">{{ $oficinaM->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                            <input id="locSearch" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500" placeholder="Nombre, ciudad, dirección...">
                        </div>

                        <div class="flex gap-2">
                            <button id="locBtnAplicar" type="button" class="flex-1 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700">Aplicar</button>
                            <button id="locBtnReset" type="button" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Limpiar</button>
                        </div>

                        <div class="text-sm text-gray-500">
                            <span id="locCount">0</span> ubicaciones
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-9">
                    <div id="localizacionMap" class="w-full h-[56vh] min-h-[320px] rounded-xl border border-gray-200 bg-white">
                        <div class="h-full flex items-center justify-center text-sm text-gray-500">Cargando mapa...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="contentEmpresa">
        <div class="space-y-3">
            @foreach($empresas as $empresa)
            @php $nSedes = $empresa->sedes->count(); @endphp
            <div class="pw-card empresa-card bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="flex flex-wrap items-center gap-2 p-4 border-b border-gray-100">
                    <button type="button" class="p-1.5 rounded-lg text-gray-600 hover:bg-gray-100 shrink-0" onclick="toggleEmpresaArbol({{ $empresa->id }})" title="Desplegar sedes" aria-expanded="false" id="emp-arbol-btn-{{ $empresa->id }}">
                        <span id="emp-ch-wrap-{{ $empresa->id }}" class="emp-arbol-chevron">
                            <i data-lucide="chevron-down" class="w-5 h-5"></i>
                        </span>
                    </button>
                    <div class="empresa-icon shrink-0">
                        <i data-lucide="building" class="w-6 h-6 text-white"></i>
                    </div>
                    <button type="button" class="flex-1 min-w-[10rem] text-left" onclick="toggleEmpresaArbol({{ $empresa->id }})">
                        <span class="text-lg font-semibold text-gray-900">{{ $empresa->nombre }}</span>
                        <span class="block text-xs text-gray-500 mt-0.5">
                            <span class="font-medium text-gray-700">{{ $empresa->equipos_count ?? 0 }} equipos</span>
                            <span class="text-gray-400"> · </span>
                            <span class="font-semibold text-cyan-700">{{ $nSedes }} {{ $nSedes === 1 ? 'sede' : 'sedes' }}</span>
                        </span>
                    </button>
                    <span class="px-2 py-1 rounded-full text-xs shrink-0 {{ $empresa->activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $empresa->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                    <div class="empresa-actions items-center flex flex-wrap gap-1 justify-end w-full sm:w-auto sm:ml-auto">
                        @if(!($soloMiEmpresa ?? false))
                        <form action="{{ route('empresa.entrar', $empresa) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit"
                                    class="empresa-action-btn empresa-action-btn--brand"
                                    style="{{ $empresa->color_primario ? 'border-color:' . e($empresa->color_primario) . '; color:' . e($empresa->color_primario) : '' }}"
                                    title="Entrar a esta empresa (misma interfaz, datos de otra empresa)">
                                <i data-lucide="log-in" class="w-4 h-4 mr-1"></i>
                                Entrar a empresa
                            </button>
                        </form>
                        <button type="button"
                                onclick="openEmpresaModulos({{ $empresa->id }})"
                                class="empresa-action-btn empresa-action-btn--indigo"
                                title="Asignar qué módulos puede usar esta empresa">
                            <i data-lucide="sliders" class="w-4 h-4 mr-1"></i>
                            Asignar módulos
                        </button>
                        <form action="{{ route('empresa.modulos-todo', $empresa) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit"
                                    class="empresa-action-btn empresa-action-btn--ok"
                                    title="Dar acceso a todos los módulos">
                                <i data-lucide="unlock" class="w-4 h-4 mr-1"></i>
                                Dar acceso a todo
                            </button>
                        </form>
                        <button type="button"
                                onclick="openAsignarUsuarioEmpresa({{ $empresa->id }}, {{ json_encode($empresa->nombre) }})"
                                class="empresa-action-btn empresa-action-btn--info"
                                title="Crear usuario para esta empresa (solo verá lo configurado para la empresa)">
                            <i data-lucide="user-plus" class="w-4 h-4 mr-1"></i>
                            Asignar usuario
                        </button>
                        @endif
                        @if($canEditEmpresa ?? true)
                        <button type="button" onclick="editEmpresa({{ $empresa->id }})" class="pw-btn-icon-edit inline-flex items-center justify-center rounded-lg" title="Editar">
                            <i data-lucide="edit"></i>
                        </button>
                        <button type="button" onclick="deleteEmpresa({{ $empresa->id }})" class="pw-btn-icon-delete inline-flex items-center justify-center rounded-lg" title="Eliminar">
                            <i data-lucide="trash-2"></i>
                        </button>
                        <button type="button" onclick="toggleEmpresaStatus({{ $empresa->id }})" class="inline-flex items-center justify-center rounded-lg {{ $empresa->activo ? 'pw-btn-icon-toggle-active' : 'pw-btn-icon-toggle-inactive' }}" title="{{ $empresa->activo ? 'Desactivar' : 'Activar' }}">
                            <i data-lucide="{{ $empresa->activo ? 'toggle-right' : 'toggle-left' }}"></i>
                        </button>
                        @endif
                    </div>
                </div>

                <div id="emp-arbol-{{ $empresa->id }}" class="hidden">
                    <div class="p-4 bg-white border-b border-gray-100 space-y-2 text-sm text-gray-600">
                        <div class="flex flex-wrap items-center gap-2 text-gray-500">
                            <i data-lucide="info" class="w-4 h-4 shrink-0"></i>
                            <span>Detalle de empresa</span>
                        </div>
                        @if($empresa->pais)<div class="flex items-center gap-2 text-gray-600"><i data-lucide="globe" class="w-4 h-4"></i>{{ $empresa->pais }}</div>@endif
                        @if($empresa->direccion)<div class="flex items-center gap-2 text-gray-600"><i data-lucide="home" class="w-4 h-4"></i>{{ $empresa->direccion }}</div>@endif
                        @if($empresa->ciudad)<div class="flex items-center gap-2 text-gray-600"><i data-lucide="building-2" class="w-4 h-4"></i>{{ $empresa->ciudad }}</div>@endif
                        @include('admin.empresa.partials.ubicacion-preview', [
                            'mapsUrl' => $empresa->google_maps_url,
                            'lat' => $empresa->latitud,
                            'lng' => $empresa->longitud,
                            'query' => trim(implode(', ', array_filter([
                                $empresa->direccion,
                                $empresa->ciudad,
                                $empresa->municipio,
                                $empresa->departamento,
                                $empresa->pais,
                            ]))),
                            'heightClass' => 'h-28',
                        ])
                    </div>
                    <div class="p-3 space-y-2 bg-slate-50/70">
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-500 px-1">Sedes</p>
                        @forelse($empresa->sedes as $sede)
                        <div class="rounded-lg border border-gray-200 bg-white overflow-hidden shadow-sm">
                            <div class="flex flex-wrap items-center gap-2 px-3 py-2.5">
                                <button type="button" class="p-1 rounded hover:bg-gray-100 shrink-0" onclick="toggleEmpNestedSede({{ $sede->id }})" title="Bodegas y oficinas" aria-expanded="false" id="emp-sede-arbol-btn-{{ $sede->id }}">
                                    <span id="emp-sede-ch-wrap-{{ $sede->id }}" class="emp-arbol-chevron">
                                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-600"></i>
                                    </span>
                                </button>
                                <button type="button" class="flex-1 text-left min-w-0" onclick="toggleEmpNestedSede({{ $sede->id }})">
                                    <span class="font-medium text-gray-900">{{ $sede->nombre }}</span>
                                    <span class="block text-xs text-gray-500">
                                        {{ (int) $sede->bodegas_count }} {{ (int) $sede->bodegas_count === 1 ? 'bodega' : 'bodegas' }}
                                        · {{ (int) $sede->oficinas_count }} {{ (int) $sede->oficinas_count === 1 ? 'oficina' : 'oficinas' }}
                                    </span>
                                </button>
                                @if($canEditSede ?? true)
                                <div class="flex gap-1 shrink-0">
                                    <button type="button" onclick="event.stopPropagation(); editSede({{ $sede->id }})" class="pw-btn-icon-edit inline-flex items-center justify-center rounded-lg p-2" title="Editar"><i data-lucide="edit" class="w-4 h-4"></i></button>
                                    <button type="button" onclick="event.stopPropagation(); deleteSede({{ $sede->id }})" class="pw-btn-icon-delete inline-flex items-center justify-center rounded-lg p-2" title="Eliminar"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                    <button type="button" onclick="event.stopPropagation(); toggleSedeStatus({{ $sede->id }})" class="inline-flex items-center justify-center rounded-lg p-2 {{ $sede->activo ? 'pw-btn-icon-toggle-active' : 'pw-btn-icon-toggle-inactive' }}" title="{{ $sede->activo ? 'Desactivar' : 'Activar' }}"><i data-lucide="{{ $sede->activo ? 'toggle-right' : 'toggle-left' }}" class="w-4 h-4"></i></button>
                                </div>
                                @endif
                            </div>
                            <div id="emp-sede-arbol-{{ $sede->id }}" class="hidden border-t border-gray-100 emp-arbol-sub mx-3 mb-3 py-3 space-y-4">
                                <div>
                                    <h4 class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-2 flex items-center gap-1"><i data-lucide="warehouse" class="w-3.5 h-3.5"></i> Bodegas</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                                        @forelse($sede->bodegas as $bodega)
                                        <div class="oficina-mini-card bg-white p-4 rounded-xl">
                                            <div class="flex justify-between items-start mb-2">
                                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center shrink-0">
                                                    <i data-lucide="warehouse" class="w-5 h-5 text-white"></i>
                                                </div>
                                                @if($canEditBodega ?? true)
                                                <div class="flex gap-1">
                                                    <button type="button" onclick="editBodega({{ $bodega->id }})" class="pw-btn-icon-edit inline-flex p-1.5 rounded-lg" title="Editar"><i data-lucide="edit" class="w-4 h-4"></i></button>
                                                    <button type="button" onclick="deleteBodega({{ $bodega->id }})" class="pw-btn-icon-delete inline-flex p-1.5 rounded-lg" title="Eliminar"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                                    <button type="button" onclick="toggleBodegaStatus({{ $bodega->id }})" class="inline-flex p-1.5 rounded-lg {{ $bodega->activo ? 'pw-btn-icon-toggle-active' : 'pw-btn-icon-toggle-inactive' }}" title="Estado"><i data-lucide="{{ $bodega->activo ? 'toggle-right' : 'toggle-left' }}" class="w-4 h-4"></i></button>
                                                </div>
                                                @endif
                                            </div>
                                            <h5 class="font-semibold text-gray-900 text-sm">{{ $bodega->nombre }}</h5>
                                            <p class="text-xs text-gray-500 mt-1">{{ $bodega->equipos_count ?? 0 }} equipos</p>
                                            <span class="inline-block mt-2 px-2 py-0.5 text-xs rounded-full {{ $bodega->activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">{{ $bodega->activo ? 'Activo' : 'Inactivo' }}</span>
                                        </div>
                                        @empty
                                        <p class="text-sm text-gray-500 col-span-full">No hay bodegas en esta sede.</p>
                                        @endforelse
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-2 flex items-center gap-1"><i data-lucide="briefcase" class="w-3.5 h-3.5"></i> Oficinas</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                                        @forelse($sede->oficinas as $oficina)
                                        <div class="oficina-mini-card empresa-card bg-white p-4 rounded-xl">
                                            <div class="flex justify-between items-start mb-2">
                                                <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 text-white empresa-gradient-icon">
                                                    <i data-lucide="briefcase" class="w-5 h-5"></i>
                                                </div>
                                                @if(($mostrarPestanaOficinas ?? false) && ($canEditOficina ?? false))
                                                <div class="flex gap-1">
                                                    <button type="button" onclick="editOficina({{ $oficina->id }})" class="pw-btn-icon-edit inline-flex p-1.5 rounded-lg" title="Editar"><i data-lucide="edit" class="w-4 h-4"></i></button>
                                                    <button type="button" onclick="deleteOficina({{ $oficina->id }})" class="pw-btn-icon-delete inline-flex p-1.5 rounded-lg" title="Eliminar"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                                </div>
                                                @endif
                                            </div>
                                            <h5 class="font-semibold text-gray-900">{{ $oficina->nombre }}</h5>
                                            <p class="text-xs text-gray-500 mt-1">{{ $oficina->empresa?->nombre ?? '—' }}</p>
                                            <p class="text-xs text-cyan-800 mt-0.5"><i data-lucide="map-pin" class="w-3 h-3 inline"></i> {{ $sede->nombre }}</p>
                                        </div>
                                        @empty
                                        <p class="text-sm text-gray-500 col-span-full">No hay oficinas en esta sede.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <p class="text-sm text-gray-500 px-2 py-2">Esta empresa no tiene sedes. Use «Nueva Sede» arriba.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if($empresas->isEmpty())
        <div class="text-center py-12">
            <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="building" class="w-8 h-8 text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No hay empresas registradas</h3>
            <p class="text-gray-500 mb-4">Comienza registrando una empresa</p>
            <button onclick="showCreateEmpresaModal()" class="pw-btn-success px-4 py-2 rounded-lg">
                <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
                Registrar Primera Empresa
            </button>
        </div>
        @endif
    </div>

    <div id="contentSede" class="hidden">
        <div class="space-y-3">
            @foreach($sedes as $sede)
            <div class="pw-card bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
                <div class="flex flex-wrap items-center gap-2 p-4 border-b border-gray-100">
                    <button type="button" class="p-1.5 rounded-lg text-gray-600 hover:bg-gray-100 shrink-0" onclick="toggleSedeArbolTab({{ $sede->id }})" title="Desplegar bodegas y oficinas" aria-expanded="false" id="sede-tab-arbol-btn-{{ $sede->id }}">
                        <span id="sede-tab-ch-wrap-{{ $sede->id }}" class="emp-arbol-chevron">
                            <i data-lucide="chevron-down" class="w-5 h-5"></i>
                        </span>
                    </button>
                    <div class="w-11 h-11 bg-blue-500 rounded-lg flex items-center justify-center shrink-0">
                        <i data-lucide="map-pin" class="w-6 h-6 text-white"></i>
                    </div>
                    <button type="button" class="flex-1 min-w-[10rem] text-left" onclick="toggleSedeArbolTab({{ $sede->id }})">
                        <span class="text-lg font-semibold text-gray-900">{{ $sede->nombre }}</span>
                        <span class="block text-xs text-gray-500 mt-0.5">{{ $sede->empresa?->nombre }}</span>
                        <span class="block text-xs text-cyan-800 mt-0.5 font-medium">
                            {{ (int) $sede->bodegas_count }} {{ (int) $sede->bodegas_count === 1 ? 'bodega' : 'bodegas' }}
                            · {{ (int) $sede->oficinas_count }} {{ (int) $sede->oficinas_count === 1 ? 'oficina' : 'oficinas' }}
                            · {{ $sede->equipos_count ?? 0 }} equipos
                        </span>
                    </button>
                    <span class="px-2 py-1 rounded-full text-xs shrink-0 {{ $sede->activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $sede->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                    @if($canEditSede ?? true)
                    <div class="flex gap-1 w-full sm:w-auto sm:ml-auto justify-end">
                        <button type="button" onclick="event.stopPropagation(); editSede({{ $sede->id }})" class="pw-btn-icon-edit inline-flex items-center justify-center rounded-lg p-2" title="Editar"><i data-lucide="edit"></i></button>
                        <button type="button" onclick="event.stopPropagation(); deleteSede({{ $sede->id }})" class="pw-btn-icon-delete inline-flex items-center justify-center rounded-lg p-2" title="Eliminar"><i data-lucide="trash-2"></i></button>
                        <button type="button" onclick="event.stopPropagation(); toggleSedeStatus({{ $sede->id }})" class="inline-flex items-center justify-center rounded-lg p-2 {{ $sede->activo ? 'pw-btn-icon-toggle-active' : 'pw-btn-icon-toggle-inactive' }}" title="{{ $sede->activo ? 'Desactivar' : 'Activar' }}"><i data-lucide="{{ $sede->activo ? 'toggle-right' : 'toggle-left' }}"></i></button>
                    </div>
                    @endif
                </div>
                <div id="sede-tab-arbol-{{ $sede->id }}" class="hidden bg-slate-50/70 p-4 space-y-4">
                    <div class="bg-white rounded-lg p-3 border border-gray-100 text-sm text-gray-600 space-y-1">
                        @if($sede->pais)<div class="flex items-center gap-2"><i data-lucide="globe" class="w-4 h-4"></i>{{ $sede->pais }}</div>@endif
                        @if($sede->direccion)<div class="flex items-center gap-2"><i data-lucide="home" class="w-4 h-4"></i>{{ $sede->direccion }}</div>@endif
                        @include('admin.empresa.partials.ubicacion-preview', [
                            'mapsUrl' => $sede->google_maps_url,
                            'query' => trim(implode(', ', array_filter([
                                $sede->direccion,
                                $sede->ciudad,
                                $sede->municipio,
                                $sede->departamento,
                                $sede->pais,
                            ]))),
                            'heightClass' => 'h-24',
                        ])
                    </div>
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-2 flex items-center gap-1"><i data-lucide="warehouse" class="w-3.5 h-3.5"></i> Bodegas</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                            @forelse($sede->bodegas as $bodega)
                            <div class="oficina-mini-card bg-white p-4 rounded-xl">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center shrink-0">
                                        <i data-lucide="warehouse" class="w-5 h-5 text-white"></i>
                                    </div>
                                    @if($canEditBodega ?? true)
                                    <div class="flex gap-1">
                                        <button type="button" onclick="editBodega({{ $bodega->id }})" class="pw-btn-icon-edit inline-flex p-1.5 rounded-lg" title="Editar"><i data-lucide="edit" class="w-4 h-4"></i></button>
                                        <button type="button" onclick="deleteBodega({{ $bodega->id }})" class="pw-btn-icon-delete inline-flex p-1.5 rounded-lg" title="Eliminar"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                        <button type="button" onclick="toggleBodegaStatus({{ $bodega->id }})" class="inline-flex p-1.5 rounded-lg {{ $bodega->activo ? 'pw-btn-icon-toggle-active' : 'pw-btn-icon-toggle-inactive' }}"><i data-lucide="{{ $bodega->activo ? 'toggle-right' : 'toggle-left' }}" class="w-4 h-4"></i></button>
                                    </div>
                                    @endif
                                </div>
                                <h5 class="font-semibold text-gray-900 text-sm">{{ $bodega->nombre }}</h5>
                                <p class="text-xs text-gray-500 mt-1">{{ $bodega->equipos_count ?? 0 }} equipos</p>
                                <span class="inline-block mt-2 px-2 py-0.5 text-xs rounded-full {{ $bodega->activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">{{ $bodega->activo ? 'Activo' : 'Inactivo' }}</span>
                            </div>
                            @empty
                            <p class="text-sm text-gray-500 col-span-full">No hay bodegas.</p>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wide text-gray-500 mb-2 flex items-center gap-1"><i data-lucide="briefcase" class="w-3.5 h-3.5"></i> Oficinas</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                            @forelse($sede->oficinas as $oficina)
                            <div class="oficina-mini-card empresa-card bg-white p-4 rounded-xl">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 text-white empresa-gradient-icon">
                                        <i data-lucide="briefcase" class="w-5 h-5"></i>
                                    </div>
                                    @if(($mostrarPestanaOficinas ?? false) && ($canEditOficina ?? false))
                                    <div class="flex gap-1">
                                        <button type="button" onclick="editOficina({{ $oficina->id }})" class="pw-btn-icon-edit inline-flex p-1.5 rounded-lg"><i data-lucide="edit" class="w-4 h-4"></i></button>
                                        <button type="button" onclick="deleteOficina({{ $oficina->id }})" class="pw-btn-icon-delete inline-flex p-1.5 rounded-lg"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                    </div>
                                    @endif
                                </div>
                                <h5 class="font-semibold text-gray-900">{{ $oficina->nombre }}</h5>
                                <p class="text-xs text-gray-500 mt-1">{{ $oficina->empresa?->nombre ?? '—' }}</p>
                            </div>
                            @empty
                            <p class="text-sm text-gray-500 col-span-full">No hay oficinas en esta sede.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if($sedes->isEmpty())
        <div class="text-center py-12">
            <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="map-pin" class="w-8 h-8 text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No hay sedes registradas</h3>
            <p class="text-gray-500 mb-4">Comienza registrando una sede</p>
            @if($canEditSede ?? true)
            <button onclick="showCreateSedeModal()" class="pw-btn-primary px-4 py-2 rounded-lg">
                <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
                Registrar Primera Sede
            </button>
            @endif
        </div>
        @endif
    </div>

    <div id="contentBodega" class="hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($bodegas as $bodega)
            <div class="pw-card bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                <div class="flex justify-between items-start mb-4">
                    <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                        <i data-lucide="warehouse" class="w-6 h-6 text-white"></i>
                    </div>
                    @if($canEditBodega ?? true)
                    <div class="flex space-x-2">
                        <button onclick="editBodega({{ $bodega->id }})" class="pw-btn-icon-edit inline-flex items-center justify-center rounded-lg" title="Editar">
                            <i data-lucide="edit"></i>
                        </button>
                        <button onclick="deleteBodega({{ $bodega->id }})" class="pw-btn-icon-delete inline-flex items-center justify-center rounded-lg" title="Eliminar">
                            <i data-lucide="trash-2"></i>
                        </button>
                        <button onclick="toggleBodegaStatus({{ $bodega->id }})" class="inline-flex items-center justify-center rounded-lg {{ $bodega->activo ? 'pw-btn-icon-toggle-active' : 'pw-btn-icon-toggle-inactive' }}" title="{{ $bodega->activo ? 'Desactivar' : 'Activar' }}">
                            <i data-lucide="{{ $bodega->activo ? 'toggle-right' : 'toggle-left' }}"></i>
                        </button>
                    </div>
                    @endif
                </div>

                <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ $bodega->nombre }}</h3>
                <p class="text-sm text-gray-500 mb-4">{{ $bodega->empresa?->nombre }} • {{ $bodega->sede?->nombre }} • {{ $bodega->equipos_count ?? 0 }} equipos</p>

                <div class="text-sm text-gray-600 mb-2">
                    <span class="font-semibold">Equipos:</span> {{ $bodega->equipos_count ?? 0 }}
                </div>
                <p class="text-xs text-gray-400 mb-4">{{ $bodega->sede?->nombre ?? 'Sin sede' }}</p>

                <div class="space-y-2 mb-4">
                    @if($bodega->direccion)
                    <div class="flex items-center text-sm text-gray-500">
                        <i data-lucide="home" class="w-4 h-4 mr-2"></i>
                        <span>{{ $bodega->direccion }}</span>
                    </div>
                    @endif
                    @include('admin.empresa.partials.ubicacion-preview', [
                        'mapsUrl' => $bodega->google_maps_url,
                        'query' => trim(implode(', ', array_filter([
                            $bodega->direccion,
                            $bodega->ciudad,
                            $bodega->municipio,
                            $bodega->departamento,
                            $bodega->pais,
                        ]))),
                        'heightClass' => 'h-28',
                    ])
                </div>

                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center text-gray-500">
                        <i data-lucide="info" class="w-4 h-4 mr-1"></i>
                        <span>Bodega</span>
                    </div>
                    <div class="px-2 py-1 rounded-full text-xs {{ $bodega->activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $bodega->activo ? 'Activo' : 'Inactivo' }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if($bodegas->isEmpty())
        <div class="text-center py-12">
            <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="warehouse" class="w-8 h-8 text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No hay bodegas registradas</h3>
            <p class="text-gray-500 mb-4">Comienza registrando una bodega</p>
            @if($canEditBodega ?? true)
            <button onclick="showCreateBodegaModal()" class="pw-btn-apartado-empresa px-4 py-2 rounded-lg">
                <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
                Registrar Primera Bodega
            </button>
            @endif
        </div>
        @endif
    </div>

    @if($mostrarPestanaOficinas ?? false)
    <div id="contentOficina" class="hidden">
        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <p class="text-sm text-gray-600 mb-4">
                Oficinas de <strong>Prevention World</strong>: asocie cada una a una sede y un nombre descriptivo.
            </p>
            @if($canEditOficina ?? false)
            <div class="mb-4">
                <button type="button" onclick="showCreateOficinaModal()" class="pw-btn-primary px-4 py-2 rounded-lg inline-flex items-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Registrar oficina
                </button>
            </div>
            @endif
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse(($oficinas ?? []) as $oficina)
                <div class="pw-card empresa-card oficina-mini-card bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div class="empresa-icon">
                            <i data-lucide="briefcase" class="w-6 h-6 text-white"></i>
                        </div>
                        @if($canEditOficina ?? false)
                        <div class="flex gap-2">
                            <button type="button" onclick="editOficina({{ $oficina->id }})" class="pw-btn-icon-edit inline-flex items-center justify-center rounded-lg p-2" title="Editar">
                                <i data-lucide="edit"></i>
                            </button>
                            <button type="button" onclick="deleteOficina({{ $oficina->id }})" class="pw-btn-icon-delete inline-flex items-center justify-center rounded-lg p-2" title="Eliminar">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </div>
                        @endif
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ $oficina->nombre }}</h3>
                    <div class="space-y-2 text-sm text-gray-600 mb-4">
                        <div class="flex items-center text-gray-500">
                            <i data-lucide="building-2" class="w-4 h-4 mr-2 shrink-0"></i>
                            <span>{{ $oficina->empresa?->nombre ?? '—' }}</span>
                        </div>
                        <div class="flex items-center text-gray-500">
                            <i data-lucide="map-pin" class="w-4 h-4 mr-2 shrink-0"></i>
                            <span>{{ $oficina->sede?->nombre ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-sm pt-2 border-t border-gray-100">
                        <div class="flex items-center text-gray-500">
                            <i data-lucide="briefcase" class="w-4 h-4 mr-1"></i>
                            <span>Oficina</span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full text-center py-12 text-gray-500">
                    No hay oficinas registradas.
                    @if($canEditOficina ?? false)
                    <p class="mt-2">Use «Registrar oficina» o el botón del encabezado.</p>
                    @endif
                </div>
                @endforelse
            </div>
        </div>
    </div>
    @endif

    <div id="contentEspacio" class="hidden">
        <div class="pw-card bg-white rounded-xl shadow-lg p-6">
            <p class="text-sm text-gray-600 mb-4">
                Espacios de la empresa: baños, cocina, lugar de descanso y otros.
            </p>
            @if($canEditEspacio ?? false)
            <div class="mb-4">
                <button type="button" onclick="showCreateEspacioModal()" class="pw-btn-primary px-4 py-2 rounded-lg inline-flex items-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Registrar espacio
                </button>
            </div>
            @endif
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse(($espacios ?? []) as $espacio)
                <div class="pw-card empresa-card oficina-mini-card bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex justify-between items-start mb-4">
                        <div class="empresa-icon">
                            <i data-lucide="armchair" class="w-6 h-6 text-white"></i>
                        </div>
                        @if($canEditEspacio ?? false)
                        <div class="flex gap-2">
                            <button type="button" onclick="editEspacio({{ $espacio->id }})" class="pw-btn-icon-edit inline-flex items-center justify-center rounded-lg p-2" title="Editar">
                                <i data-lucide="edit"></i>
                            </button>
                            <button type="button" onclick="deleteEspacio({{ $espacio->id }})" class="pw-btn-icon-delete inline-flex items-center justify-center rounded-lg p-2" title="Eliminar">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </div>
                        @endif
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ $espacio->nombre }}</h3>
                    <div class="space-y-2 text-sm text-gray-600 mb-4">
                        <div class="flex items-center text-gray-500">
                            <i data-lucide="building-2" class="w-4 h-4 mr-2 shrink-0"></i>
                            <span>{{ $espacio->empresa?->nombre ?? '—' }}</span>
                        </div>
                        <div class="flex items-center text-gray-500">
                            <i data-lucide="map-pin" class="w-4 h-4 mr-2 shrink-0"></i>
                            <span>{{ $espacio->sede?->nombre ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-sm pt-2 border-t border-gray-100">
                        <div class="flex items-center text-gray-500">
                            <i data-lucide="armchair" class="w-4 h-4 mr-1"></i>
                            <span>Espacio</span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full text-center py-12 text-gray-500">
                    No hay espacios registrados.
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<datalist id="paisesList">
</datalist>

<!-- Create Empresa Modal -->
<div id="createEmpresaModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="pw-modal-content pw-modal-lg bg-white rounded-xl p-6 w-full max-h-[80vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Registrar Nueva Empresa</h3>
        <form action="{{ route('empresa.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">NIT *</label>
                    <input type="text" name="nit" value="{{ old('nit') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Prefijo (automático)</label>
                    <input type="text" value="Se genera automáticamente desde el nombre" readonly class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Color (paleta)</label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="createEmpresaColorPicker" value="{{ old('color_primario', '#F97316') }}" class="h-10 w-16 p-1 border border-gray-300 rounded-lg bg-white cursor-pointer">
                        <input type="text" id="createEmpresaColorHex" name="color_primario" value="{{ old('color_primario', '#F97316') }}" maxlength="20" placeholder="#F97316" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div class="empresa-color-dots">
                        <span id="createDotPrimary" class="empresa-color-dot empresa-dot-primary"></span>
                        <span id="createDotSec1" class="empresa-color-dot empresa-dot-sec1"></span>
                        <span id="createDotSec2" class="empresa-color-dot empresa-dot-sec2"></span>
                        <span class="empresa-color-dot empresa-dot-white"></span>
                        <span class="empresa-color-dot empresa-dot-black"></span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">País</label>
                    <input list="paisesList" autocomplete="off" type="text" id="createEmpresaPais" name="pais" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Departamento</label>
                    <input list="createEmpresaDepartamentosList" autocomplete="off" type="text" id="createEmpresaDepartamento" name="departamento" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <datalist id="createEmpresaDepartamentosList"></datalist>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Municipio</label>
                    <input list="createEmpresaMunicipiosList" autocomplete="off" type="text" id="createEmpresaMunicipio" name="municipio" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <datalist id="createEmpresaMunicipiosList"></datalist>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ciudad</label>
                    <input list="createEmpresaCiudadesList" autocomplete="off" type="text" id="createEmpresaCiudad" name="ciudad" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <datalist id="createEmpresaCiudadesList"></datalist>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                    <input type="text" name="direccion" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Enlace de Google Maps (opcional si pones latitud y longitud)</label>
                    <input type="text" id="createEmpresaMapsUrl" name="google_maps_url" placeholder="https://maps.app.goo.gl/..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <p class="text-xs text-gray-500 mt-1">Puedes pegar solo el enlace de Maps <strong>o</strong> escribir latitud y longitud. La altitud no es obligatoria.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Latitud (opcional si hay enlace)</label>
                    <input type="number" step="0.0000001" id="createEmpresaLatitud" name="latitud" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Longitud (opcional si hay enlace)</label>
                    <input type="number" step="0.0000001" id="createEmpresaLongitud" name="longitud" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Altitud (opcional)</label>
                    <input type="number" step="0.01" name="altitud" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Color secundario 1 *</label>
                    <input type="color" id="createEmpresaColorSec1" name="color_secundario_1" value="#67E8F9" class="h-10 w-24 p-1 border border-gray-300 rounded-lg bg-white cursor-pointer">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Color secundario 2 *</label>
                    <input type="color" id="createEmpresaColorSec2" name="color_secundario_2" value="#075479" class="h-10 w-24 p-1 border border-gray-300 rounded-lg bg-white cursor-pointer">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Color 4 (auto)</label>
                    <input type="text" value="#FFFFFF" readonly class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Color 5 (auto)</label>
                    <input type="text" value="#000000" readonly class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-gray-500">
                </div>
                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Logo principal (elige uno)</label>
                        <input type="file" id="createLogoPrincipalFile" name="logo_principal_file" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Logo secundario (elige uno)</label>
                        <input type="file" id="createLogoSecundarioFile" name="logo_secundario_file" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Foto de la empresa (opcional)</label>
                    <input type="file" name="foto_empresa" accept="image/jpeg,image/png,image/webp" class="js-empresa-image w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <p class="text-xs text-gray-500 mt-1">JPG, PNG o WebP. Máximo 10 MB; si pesa mucho, el sistema la optimizará al guardar.</p>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideCreateEmpresaModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="pw-btn-success px-4 py-2 rounded-lg">Registrar Empresa</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Empresa Modal -->
<div id="editEmpresaModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="pw-modal-content pw-modal-lg bg-white rounded-xl p-6 w-full max-h-[80vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Editar Empresa</h3>
        <form id="editEmpresaForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="empresa_id" id="editEmpresaId" value="{{ old('empresa_id') }}">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                    <input type="text" id="editEmpresaNombre" name="nombre" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">NIT *</label>
                    <input type="text" id="editEmpresaNit" name="nit" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Prefijo (automático)</label>
                    <input type="text" id="editEmpresaPrefijo" readonly class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Color (paleta)</label>
                    <div class="flex items-center gap-2">
                        <input type="color" id="editEmpresaColorPicker" value="#f97316" class="h-10 w-16 p-1 border border-gray-300 rounded-lg bg-white cursor-pointer">
                        <input type="text" id="editEmpresaColor" name="color_primario" maxlength="20" placeholder="#F97316" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div class="empresa-color-dots">
                        <span id="editDotPrimary" class="empresa-color-dot empresa-dot-primary"></span>
                        <span id="editDotSec1" class="empresa-color-dot empresa-dot-sec1"></span>
                        <span id="editDotSec2" class="empresa-color-dot empresa-dot-sec2"></span>
                        <span class="empresa-color-dot empresa-dot-white"></span>
                        <span class="empresa-color-dot empresa-dot-black"></span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">País</label>
                    <input list="paisesList" autocomplete="off" type="text" id="editEmpresaPais" name="pais" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Departamento</label>
                    <input list="editEmpresaDepartamentosList" autocomplete="off" type="text" id="editEmpresaDepartamento" name="departamento" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <datalist id="editEmpresaDepartamentosList"></datalist>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Municipio</label>
                    <input list="editEmpresaMunicipiosList" autocomplete="off" type="text" id="editEmpresaMunicipio" name="municipio" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <datalist id="editEmpresaMunicipiosList"></datalist>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ciudad</label>
                    <input list="editEmpresaCiudadesList" autocomplete="off" type="text" id="editEmpresaCiudad" name="ciudad" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <datalist id="editEmpresaCiudadesList"></datalist>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                    <input type="text" id="editEmpresaDireccion" name="direccion" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Enlace de Google Maps (opcional si pones latitud y longitud)</label>
                    <input type="text" id="editEmpresaMaps" name="google_maps_url" placeholder="https://maps.app.goo.gl/..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <p class="text-xs text-gray-500 mt-1">Puedes pegar solo el enlace de Maps <strong>o</strong> escribir latitud y longitud. La altitud no es obligatoria.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Latitud (opcional si hay enlace)</label>
                    <input type="number" step="0.0000001" id="editEmpresaLatitud" name="latitud" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Longitud (opcional si hay enlace)</label>
                    <input type="number" step="0.0000001" id="editEmpresaLongitud" name="longitud" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Altitud (opcional)</label>
                    <input type="number" step="0.01" id="editEmpresaAltitud" name="altitud" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Color secundario 1 *</label>
                    <input type="color" id="editEmpresaColorSec1" name="color_secundario_1" value="#67E8F9" class="h-10 w-24 p-1 border border-gray-300 rounded-lg bg-white cursor-pointer">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Color secundario 2 *</label>
                    <input type="color" id="editEmpresaColorSec2" name="color_secundario_2" value="#075479" class="h-10 w-24 p-1 border border-gray-300 rounded-lg bg-white cursor-pointer">
                </div>
                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Logo principal (elige uno)</label>
                        <input type="file" id="editLogoPrincipalFile" name="logo_principal_file" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Logo secundario (elige uno)</label>
                        <input type="file" id="editLogoSecundarioFile" name="logo_secundario_file" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Foto de la empresa (opcional)</label>
                    <input type="file" name="foto_empresa" accept="image/jpeg,image/png,image/webp" class="js-empresa-image w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <p class="text-xs text-gray-500 mt-1">JPG, PNG o WebP. Máximo 10 MB; si pesa mucho, el sistema la optimizará al guardar.</p>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideEditEmpresaModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Actualizar Empresa</button>
            </div>
        </form>
    </div>
</div>

<!-- Asignar Módulos Empresa Modal -->
<div id="modulosEmpresaModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="pw-modal-content pw-modal-md bg-white rounded-xl p-6 w-full max-h-[80vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Asignar módulos a empresa</h3>
        <form id="modulosEmpresaForm" method="POST">
            @csrf
            <div class="space-y-3">
                <p class="text-sm text-gray-600">Para cada apartado elige el nivel de acceso: <strong>Sin acceso</strong>, <strong>Solo vista</strong> o <strong>Editar / eliminar</strong>. Si no configuras nada, la empresa tendrá acceso completo a todos los módulos.</p>
                <div class="mt-3 space-y-3 text-sm">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mt-2 mb-1">Gestión principal</div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Usuarios</div>
                            <div class="text-xs text-gray-500">Gestión de usuarios del sistema.</div>
                        </div>
                        <select name="modulos[users]" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="none">Sin acceso</option>
                            <option value="view">Solo vista</option>
                            <option value="edit">Editar / eliminar</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Roles</div>
                            <div class="text-xs text-gray-500">Gestión de roles y permisos.</div>
                        </div>
                        <select name="modulos[roles]" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="none">Sin acceso</option>
                            <option value="view">Solo vista</option>
                            <option value="edit">Editar / eliminar</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Cargos</div>
                            <div class="text-xs text-gray-500">Gestión de cargos.</div>
                        </div>
                        <select name="modulos[cargos]" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="none">Sin acceso</option>
                            <option value="view">Solo vista</option>
                            <option value="edit">Editar / eliminar</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Grupos</div>
                            <div class="text-xs text-gray-500">Gestión de grupos.</div>
                        </div>
                        <select name="modulos[grupos]" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="none">Sin acceso</option>
                            <option value="view">Solo vista</option>
                            <option value="edit">Editar / eliminar</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Fabricantes</div>
                            <div class="text-xs text-gray-500">Gestión de fabricantes.</div>
                        </div>
                        <select name="modulos[fabricantes]" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="none">Sin acceso</option>
                            <option value="view">Solo vista</option>
                            <option value="edit">Editar / eliminar</option>
                        </select>
                    </div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mt-2 mb-1">Gestión de equipos y operativos</div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Inventario / Todos los equipos</div>
                            <div class="text-xs text-gray-500">Ver y gestionar equipos de inventario.</div>
                        </div>
                        <select name="modulos[equipos]" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="none">Sin acceso</option>
                            <option value="view">Solo vista</option>
                            <option value="edit">Editar / eliminar</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Equipos de baja</div>
                            <div class="text-xs text-gray-500">Dar de baja y consultar historiales.</div>
                        </div>
                        <select name="modulos[equipos_baja]" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="none">Sin acceso</option>
                            <option value="view">Solo vista</option>
                            <option value="edit">Editar / eliminar</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Hoja de vida</div>
                            <div class="text-xs text-gray-500">Ver y gestionar hojas de vida.</div>
                        </div>
                        <select name="modulos[hoja_vida]" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="none">Sin acceso</option>
                            <option value="view">Solo vista</option>
                            <option value="edit">Editar / eliminar</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Inspecciones</div>
                            <div class="text-xs text-gray-500">Programar y registrar inspecciones.</div>
                        </div>
                        <select name="modulos[inspeccion]" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="none">Sin acceso</option>
                            <option value="view">Solo vista</option>
                            <option value="edit">Editar / eliminar</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Exportar</div>
                            <div class="text-xs text-gray-500">Exportar listados y reportes.</div>
                        </div>
                        <select name="modulos[exportar]" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="none">Sin acceso</option>
                            <option value="view">Solo vista</option>
                            <option value="edit">Editar / eliminar</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Material didáctico</div>
                            <div class="text-xs text-gray-500">Gestionar material de capacitación.</div>
                        </div>
                        <select name="modulos[material_didactico]" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="none">Sin acceso</option>
                            <option value="view">Solo vista</option>
                            <option value="edit">Editar / eliminar</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Auditoría de equipos</div>
                            <div class="text-xs text-gray-500">Historial y auditoría de movimientos.</div>
                        </div>
                            <select name="modulos[auditoria]" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="none">Sin acceso</option>
                            <option value="view">Solo vista</option>
                            <option value="edit">Editar / eliminar</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Asignar</div>
                            <div class="text-xs text-gray-500">Módulo de asignación de recursos.</div>
                        </div>
                        <select name="modulos[asignar]" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="none">Sin acceso</option>
                            <option value="view">Solo vista</option>
                            <option value="edit">Editar / eliminar</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Préstamos temporales</div>
                            <div class="text-xs text-gray-500">Prestar, recibir y devolver equipos temporalmente.</div>
                        </div>
                        <select name="modulos[prestamos_temporales]" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="none">Sin acceso</option>
                            <option value="view">Solo vista</option>
                            <option value="edit">Editar / eliminar</option>
                        </select>
                    </div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mt-2 mb-1">Empresa, sede y bodega</div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Empresa</div>
                            <div class="text-xs text-gray-500">Ver y gestionar datos de su empresa (solo sus datos).</div>
                        </div>
                        <select name="modulos[empresa]" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="none">Sin acceso</option>
                            <option value="view">Solo vista</option>
                            <option value="edit">Editar / eliminar</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Sede</div>
                            <div class="text-xs text-gray-500">Ver y gestionar sedes de su empresa.</div>
                        </div>
                        <select name="modulos[sede]" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="none">Sin acceso</option>
                            <option value="view">Solo vista</option>
                            <option value="edit">Editar / eliminar</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <div class="font-semibold">Bodega</div>
                            <div class="text-xs text-gray-500">Ver y gestionar bodegas de su empresa.</div>
                        </div>
                        <select name="modulos[bodega]" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                            <option value="none">Sin acceso</option>
                            <option value="view">Solo vista</option>
                            <option value="edit">Editar / eliminar</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideModulosEmpresaModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Guardar módulos</button>
            </div>
        </form>
    </div>
</div>

<!-- Asignar usuario a empresa Modal -->
<div id="asignarUsuarioEmpresaModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="pw-modal-content pw-modal-sm bg-white rounded-xl p-6 w-full max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Asignar usuario a empresa</h3>
        <p id="asignarUsuarioEmpresaNombre" class="text-sm text-gray-600 mb-4"></p>
        <form id="asignarUsuarioEmpresaForm" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" name="name" required maxlength="255" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="Ej. Juan">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Apellido</label>
                    <input type="text" name="last_name" maxlength="255" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="Ej. Pérez">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo *</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="usuario@ejemplo.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña *</label>
                    <input type="password" name="password" required minlength="10" maxlength="72" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500" placeholder="Mínimo 10 caracteres" data-pw-meter="required">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña *</label>
                    <input type="password" name="password_confirmation" required minlength="10" maxlength="72" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-3">
                El usuario tendrá código con las iniciales de la empresa y su nombre.
                Se le asignará automáticamente el rol exclusivo de esa empresa y solo verá
                los apartados configurados para ella.
            </p>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideAsignarUsuarioEmpresaModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Crear usuario</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Sede Modal -->
<div id="createSedeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="pw-modal-content pw-modal-lg bg-white rounded-xl p-6 w-full max-h-[80vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Registrar Nueva Sede</h3>
        <form action="{{ route('sedes.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Empresa *</label>
                    <select id="createSedeEmpresa" name="empresa_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione...</option>
                        @foreach($empresas as $empresa)
                        <option value="{{ $empresa->id }}" data-pais="{{ $empresa->pais }}" data-departamento="{{ $empresa->departamento }}" data-municipio="{{ $empresa->municipio }}" data-ciudad="{{ $empresa->ciudad }}" data-direccion="{{ $empresa->direccion }}" data-maps="{{ $empresa->google_maps_url }}">{{ $empresa->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                    <input type="text" name="nombre" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">País</label>
                    <input list="paisesList" autocomplete="off" type="text" id="createSedePais" name="pais" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Departamento</label>
                    <input list="createSedeDepartamentosList" autocomplete="off" type="text" id="createSedeDepartamento" name="departamento" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <datalist id="createSedeDepartamentosList"></datalist>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Municipio</label>
                    <input list="createSedeMunicipiosList" autocomplete="off" type="text" id="createSedeMunicipio" name="municipio" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <datalist id="createSedeMunicipiosList"></datalist>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ciudad</label>
                    <input list="createSedeCiudadesList" autocomplete="off" type="text" id="createSedeCiudad" name="ciudad" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <datalist id="createSedeCiudadesList"></datalist>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                    <input type="text" id="createSedeDireccion" name="direccion" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">URL Google Maps (opcional)</label>
                    <input type="url" id="createSedeMaps" name="google_maps_url" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideCreateSedeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Registrar Sede</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Sede Modal -->
<div id="editSedeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="pw-modal-content pw-modal-lg bg-white rounded-xl p-6 w-full max-h-[80vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Editar Sede</h3>
        <form id="editSedeForm" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Empresa *</label>
                    <select id="editSedeEmpresa" name="empresa_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccione...</option>
                        @foreach($empresas as $empresa)
                        <option value="{{ $empresa->id }}" data-pais="{{ $empresa->pais }}" data-departamento="{{ $empresa->departamento }}" data-municipio="{{ $empresa->municipio }}" data-ciudad="{{ $empresa->ciudad }}" data-direccion="{{ $empresa->direccion }}" data-maps="{{ $empresa->google_maps_url }}">{{ $empresa->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                    <input type="text" id="editSedeNombre" name="nombre" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">País</label>
                    <input list="paisesList" autocomplete="off" type="text" id="editSedePais" name="pais" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Departamento</label>
                    <input list="editSedeDepartamentosList" autocomplete="off" type="text" id="editSedeDepartamento" name="departamento" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <datalist id="editSedeDepartamentosList"></datalist>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Municipio</label>
                    <input list="editSedeMunicipiosList" autocomplete="off" type="text" id="editSedeMunicipio" name="municipio" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <datalist id="editSedeMunicipiosList"></datalist>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ciudad</label>
                    <input list="editSedeCiudadesList" autocomplete="off" type="text" id="editSedeCiudad" name="ciudad" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <datalist id="editSedeCiudadesList"></datalist>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                    <input type="text" id="editSedeDireccion" name="direccion" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">URL Google Maps (opcional)</label>
                    <input type="url" id="editSedeMaps" name="google_maps_url" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideEditSedeModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Actualizar Sede</button>
            </div>
        </form>
    </div>
</div>

<!-- Create Bodega Modal -->
<div id="createBodegaModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="pw-modal-content pw-modal-lg bg-white rounded-xl p-6 w-full max-h-[80vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Registrar Nueva Bodega</h3>
        <form action="{{ route('bodegas.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Empresa *</label>
                    <select id="createBodegaEmpresa" name="empresa_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Seleccione...</option>
                        @foreach($empresas as $empresa)
                        <option value="{{ $empresa->id }}" data-pais="{{ $empresa->pais }}" data-departamento="{{ $empresa->departamento }}" data-municipio="{{ $empresa->municipio }}" data-ciudad="{{ $empresa->ciudad }}" data-direccion="{{ $empresa->direccion }}" data-maps="{{ $empresa->google_maps_url }}">{{ $empresa->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sede</label>
                    <select id="createBodegaSede" name="sede_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Seleccione...</option>
                        @foreach($sedes as $sedeOpt)
                        <option value="{{ $sedeOpt->id }}" data-empresa="{{ $sedeOpt->empresa_id }}">{{ $sedeOpt->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                    <input type="text" name="nombre" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">País</label>
                    <input list="paisesList" autocomplete="off" type="text" id="createBodegaPais" name="pais" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Departamento</label>
                    <input list="createBodegaDepartamentosList" autocomplete="off" type="text" id="createBodegaDepartamento" name="departamento" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <datalist id="createBodegaDepartamentosList"></datalist>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Municipio</label>
                    <input list="createBodegaMunicipiosList" autocomplete="off" type="text" id="createBodegaMunicipio" name="municipio" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <datalist id="createBodegaMunicipiosList"></datalist>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ciudad</label>
                    <input list="createBodegaCiudadesList" autocomplete="off" type="text" id="createBodegaCiudad" name="ciudad" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <datalist id="createBodegaCiudadesList"></datalist>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                    <input type="text" id="createBodegaDireccion" name="direccion" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">URL Google Maps (opcional)</label>
                    <input type="url" id="createBodegaMaps" name="google_maps_url" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideCreateBodegaModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="pw-btn-apartado-empresa px-4 py-2 rounded-lg">Registrar Bodega</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Bodega Modal -->
<div id="editBodegaModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="pw-modal-content pw-modal-lg bg-white rounded-xl p-6 w-full max-h-[80vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Editar Bodega</h3>
        <form id="editBodegaForm" method="POST">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Empresa *</label>
                    <select id="editBodegaEmpresa" name="empresa_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Seleccione...</option>
                        @foreach($empresas as $empresa)
                        <option value="{{ $empresa->id }}" data-pais="{{ $empresa->pais }}" data-departamento="{{ $empresa->departamento }}" data-municipio="{{ $empresa->municipio }}" data-ciudad="{{ $empresa->ciudad }}" data-direccion="{{ $empresa->direccion }}" data-maps="{{ $empresa->google_maps_url }}">{{ $empresa->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sede</label>
                    <select id="editBodegaSede" name="sede_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Seleccione...</option>
                        @foreach($sedes as $sedeOpt)
                        <option value="{{ $sedeOpt->id }}" data-empresa="{{ $sedeOpt->empresa_id }}">{{ $sedeOpt->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                    <input type="text" id="editBodegaNombre" name="nombre" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">País</label>
                    <input list="paisesList" autocomplete="off" type="text" id="editBodegaPais" name="pais" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Departamento</label>
                    <input list="editBodegaDepartamentosList" autocomplete="off" type="text" id="editBodegaDepartamento" name="departamento" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <datalist id="editBodegaDepartamentosList"></datalist>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Municipio</label>
                    <input list="editBodegaMunicipiosList" autocomplete="off" type="text" id="editBodegaMunicipio" name="municipio" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <datalist id="editBodegaMunicipiosList"></datalist>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ciudad</label>
                    <input list="editBodegaCiudadesList" autocomplete="off" type="text" id="editBodegaCiudad" name="ciudad" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <datalist id="editBodegaCiudadesList"></datalist>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                    <input type="text" id="editBodegaDireccion" name="direccion" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">URL Google Maps (opcional)</label>
                    <input type="url" id="editBodegaMaps" name="google_maps_url" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideEditBodegaModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Actualizar Bodega</button>
            </div>
        </form>
    </div>
</div>

@if($mostrarPestanaOficinas ?? false)
<!-- Create Oficina Modal -->
<div id="createOficinaModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="pw-modal-content pw-modal-md bg-white rounded-xl p-6 w-full max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Nueva oficina</h3>
        <form action="{{ route('oficinas.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Empresa *</label>
                <select id="createOficinaEmpresa" name="empresa_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500">
                    @foreach($empresas as $empresa)
                    <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sede *</label>
                <select id="createOficinaSede" name="sede_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500">
                    <option value="">Seleccione...</option>
                    @foreach($sedes as $sedeOpt)
                    <option value="{{ $sedeOpt->id }}" data-empresa="{{ $sedeOpt->empresa_id }}">{{ $sedeOpt->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de la oficina *</label>
                <input type="text" name="nombre" required maxlength="120" placeholder="Ej. Oficina Bogotá Norte" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="hideCreateOficinaModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Oficina Modal -->
<div id="editOficinaModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="pw-modal-content pw-modal-md bg-white rounded-xl p-6 w-full max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Editar oficina</h3>
        <form id="editOficinaForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Empresa *</label>
                <select id="editOficinaEmpresa" name="empresa_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500">
                    @foreach($empresas as $empresa)
                    <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sede *</label>
                <select id="editOficinaSede" name="sede_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500">
                    <option value="">Seleccione...</option>
                    @foreach($sedes as $sedeOpt)
                    <option value="{{ $sedeOpt->id }}" data-empresa="{{ $sedeOpt->empresa_id }}">{{ $sedeOpt->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de la oficina *</label>
                <input type="text" id="editOficinaNombre" name="nombre" required maxlength="120" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="hideEditOficinaModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Actualizar</button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- Create Espacio Modal -->
<div id="createEspacioModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="pw-modal-content pw-modal-md bg-white rounded-xl p-6 w-full max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Nuevo espacio</h3>
        <form action="{{ route('espacios.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Empresa *</label>
                <select id="createEspacioEmpresa" name="empresa_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500">
                    @foreach($empresas as $empresa)
                    <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sede *</label>
                <select id="createEspacioSede" name="sede_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500">
                    <option value="">Seleccione...</option>
                    @foreach($sedes as $sedeOpt)
                    <option value="{{ $sedeOpt->id }}" data-empresa="{{ $sedeOpt->empresa_id }}">{{ $sedeOpt->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del espacio *</label>
                <input type="text" name="nombre" required maxlength="120" placeholder="Ej. Cocina, Baños, Sala de descanso" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="hideCreateEspacioModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Espacio Modal -->
<div id="editEspacioModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="pw-modal-content pw-modal-md bg-white rounded-xl p-6 w-full max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Editar espacio</h3>
        <form id="editEspacioForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Empresa *</label>
                <select id="editEspacioEmpresa" name="empresa_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500">
                    @foreach($empresas as $empresa)
                    <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sede *</label>
                <select id="editEspacioSede" name="sede_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500">
                    <option value="">Seleccione...</option>
                    @foreach($sedes as $sedeOpt)
                    <option value="{{ $sedeOpt->id }}" data-empresa="{{ $sedeOpt->empresa_id }}">{{ $sedeOpt->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del espacio *</label>
                <input type="text" id="editEspacioNombre" name="nombre" required maxlength="120" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="hideEditEspacioModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">Actualizar</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const __GEO_COUNTRY_STATES_CACHE_KEY = 'sams3_country_states_v1';
const __GEO_CITIES_CACHE_PREFIX = 'sams3_cities_v1:';
let __GEO = null;
const __UBI_WIRES = {};

function __setDatalistOptions(datalistId, items) {
    const dl = document.getElementById(datalistId);
    if (!dl) return;
    dl.innerHTML = '';
    items.forEach(v => {
        const opt = document.createElement('option');
        opt.value = v;
        dl.appendChild(opt);
    });
}

async function __loadCountryStatesIndex() {
    if (__GEO) return __GEO;

    try {
        const cached = localStorage.getItem(__GEO_COUNTRY_STATES_CACHE_KEY);
        if (cached) {
            __GEO = JSON.parse(cached);
            __fillCountriesDatalist(__GEO.countries);
            return __GEO;
        }
    } catch (e) {}

    const res = await fetch('https://countriesnow.space/api/v0.1/countries/states');
    const payload = await res.json();
    const data = Array.isArray(payload?.data) ? payload.data : [];

    const countries = [];
    const statesByCountry = {};

    data.forEach(c => {
        const name = (c?.name || '').trim();
        if (!name) return;
        countries.push(name);
        const states = Array.isArray(c?.states) ? c.states.map(s => (s?.name || '').trim()).filter(Boolean) : [];
        statesByCountry[name] = states.sort((a,b)=>a.localeCompare(b));
    });

    const normalized = {
        countries: countries.sort((a,b)=>a.localeCompare(b)),
        statesByCountry
    };

    try {
        localStorage.setItem(__GEO_COUNTRY_STATES_CACHE_KEY, JSON.stringify(normalized));
    } catch (e) {}

    __GEO = normalized;
    __fillCountriesDatalist(__GEO.countries);
    return __GEO;
}

function __fillCountriesDatalist(countries) {
    __setDatalistOptions('paisesList', Array.isArray(countries) ? countries : []);
}

async function __loadCities(country, state) {
    const c = (country || '').trim();
    const s = (state || '').trim();
    if (!c || !s) return [];

    const key = __GEO_CITIES_CACHE_PREFIX + c + '|' + s;
    try {
        const cached = localStorage.getItem(key);
        if (cached) return JSON.parse(cached);
    } catch (e) {}

    const res = await fetch('https://countriesnow.space/api/v0.1/countries/state/cities', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ country: c, state: s })
    });
    const payload = await res.json();
    const cities = Array.isArray(payload?.data) ? payload.data.map(x => (x || '').trim()).filter(Boolean) : [];
    const normalized = cities.sort((a,b)=>a.localeCompare(b));

    try {
        localStorage.setItem(key, JSON.stringify(normalized));
    } catch (e) {}

    return normalized;
}

function __wireUbicacion(prefix, dlDept, dlMun, dlCity) {
    const pais = document.getElementById(prefix + 'Pais');
    const dept = document.getElementById(prefix + 'Departamento');
    const mun = document.getElementById(prefix + 'Municipio');
    const city = document.getElementById(prefix + 'Ciudad');

    if (!pais || !dept || !mun || !city) return;

    const refreshDepartamentos = async () => {
        const country = (pais.value || '').trim();
        if (!country) {
            __setDatalistOptions(dlDept, []);
            __setDatalistOptions(dlMun, []);
            __setDatalistOptions(dlCity, []);
            return;
        }

        const geo = await __loadCountryStatesIndex();
        const states = geo.statesByCountry[country] || [];
        __setDatalistOptions(dlDept, states);
        __setDatalistOptions(dlMun, []);
        __setDatalistOptions(dlCity, []);
    };

    const refreshMunicipios = async () => {
        const country = (pais.value || '').trim();
        const state = (dept.value || '').trim();
        if (!country || !state) {
            __setDatalistOptions(dlMun, []);
            __setDatalistOptions(dlCity, []);
            return;
        }

        const cities = await __loadCities(country, state);
        __setDatalistOptions(dlMun, cities);
        __setDatalistOptions(dlCity, []);
    };

    const refreshCiudades = async () => {
        const m = (mun.value || '').trim();
        if (!m) {
            __setDatalistOptions(dlCity, []);
            return;
        }
        __setDatalistOptions(dlCity, [m]);
        if (!city.value) city.value = m;
    };

    pais.addEventListener('input', function() {
        refreshDepartamentos();
    });

    dept.addEventListener('input', function() {
        refreshMunicipios();
    });

    mun.addEventListener('input', function() {
        refreshCiudades();
    });

    __UBI_WIRES[prefix] = {
        refreshDepartamentos,
        refreshMunicipios,
        refreshCiudades,
        dlDept,
        dlMun,
        dlCity
    };

    refreshDepartamentos();
}

async function __refreshUbicacionChain(prefix) {
    const wire = __UBI_WIRES[prefix];
    if (!wire) return;

    await wire.refreshDepartamentos();
    await wire.refreshMunicipios();
    await wire.refreshCiudades();
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    __loadCountryStatesIndex();
});

function openTab(tab) {
    const tabs = ['localizacion', 'empresa', 'sede', 'bodega' @if($mostrarPestanaOficinas ?? false), 'oficina' @endif, 'espacio'];
    tabs.forEach(t => {
        const pane = document.getElementById('content' + capitalize(t));
        if (pane) pane.classList.add('hidden');
        const btn = document.getElementById('tab' + capitalize(t));
        if (btn) btn.classList.remove('is-active');
    });

    const pane = document.getElementById('content' + capitalize(tab));
    if (pane) pane.classList.remove('hidden');
    const activeBtn = document.getElementById('tab' + capitalize(tab));
    if (activeBtn) activeBtn.classList.add('is-active');

    if (tab === 'localizacion') {
        initLocalizacionMap();
        if (__LOC_MAP && typeof __LOC_MAP.invalidateSize === 'function') {
            setTimeout(function () { __LOC_MAP.invalidateSize(); }, 80);
        }
    }
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function capitalize(s) {
    return s.charAt(0).toUpperCase() + s.slice(1);
}

function toggleEmpresaArbol(id) {
    const p = document.getElementById('emp-arbol-' + id);
    const w = document.getElementById('emp-ch-wrap-' + id);
    const b = document.getElementById('emp-arbol-btn-' + id);
    if (!p) return;
    p.classList.toggle('hidden');
    const open = !p.classList.contains('hidden');
    if (w) w.classList.toggle('rotate-180', open);
    if (b) b.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function toggleEmpNestedSede(id) {
    const p = document.getElementById('emp-sede-arbol-' + id);
    const w = document.getElementById('emp-sede-ch-wrap-' + id);
    const b = document.getElementById('emp-sede-arbol-btn-' + id);
    if (!p) return;
    p.classList.toggle('hidden');
    const open = !p.classList.contains('hidden');
    if (w) w.classList.toggle('rotate-180', open);
    if (b) b.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function toggleSedeArbolTab(id) {
    const p = document.getElementById('sede-tab-arbol-' + id);
    const w = document.getElementById('sede-tab-ch-wrap-' + id);
    const b = document.getElementById('sede-tab-arbol-btn-' + id);
    if (!p) return;
    p.classList.toggle('hidden');
    const open = !p.classList.contains('hidden');
    if (w) w.classList.toggle('rotate-180', open);
    if (b) b.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function showCreateEmpresaModal() { document.getElementById('createEmpresaModal').classList.remove('hidden'); }
function hideCreateEmpresaModal() { document.getElementById('createEmpresaModal').classList.add('hidden'); }
function showEditEmpresaModal() { document.getElementById('editEmpresaModal').classList.remove('hidden'); }
/** Base de la app: soporta instalaciones bajo /public (Laragon). */
function appUrl(path) {
    const base = @json(rtrim(request()->getBasePath(), '/'));
    const p = String(path || '');
    return base + (p.startsWith('/') ? p : '/' + p);
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function parseCoordsFromMapsUrl(url) {
    const s = String(url || '');
    let m = s.match(/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/);
    if (m) return { lat: m[1], lng: m[2] };
    m = s.match(/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/);
    if (m) return { lat: m[1], lng: m[2] };
    m = s.match(/[?&](?:q|query|ll)=(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/i);
    if (m) return { lat: m[1], lng: m[2] };
    return null;
}

async function fillCoordsFromMapsInput(mapsId, latId, lngId) {
    const maps = typeof mapsId === 'string' ? document.getElementById(mapsId) : mapsId;
    const lat = typeof latId === 'string' ? document.getElementById(latId) : latId;
    const lng = typeof lngId === 'string' ? document.getElementById(lngId) : lngId;
    if (!maps || !lat || !lng) return;
    const url = (maps.value || '').trim();
    if (!url) return;
    if ((lat.value || '').trim() !== '' && (lng.value || '').trim() !== '') return;

    let coords = parseCoordsFromMapsUrl(url);
    if (!coords) {
        try {
            const res = await fetch(@json(route('empresa.geocode')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken()
                },
                body: JSON.stringify({ maps_url: url })
            });
            const json = await res.json();
            if (json && json.success && json.data) {
                coords = { lat: json.data.lat, lng: json.data.lng };
            }
        } catch (e) {}
    }
    if (!coords) return;
    lat.value = coords.lat;
    lng.value = coords.lng;
}

async function parseJsonResponse(response) {
    const ct = response.headers.get('content-type') || '';
    if (ct.includes('application/json')) return await response.json().catch(() => null);
    return null;
}

/** GET JSON con manejo de errores uniforme. */
async function apiGet(path) {
    const response = await fetch(appUrl(path), {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    });
    const data = await parseJsonResponse(response);
    if (!response.ok || !data) {
        throw new Error(data?.message || `No se pudo cargar la información (${response.status}).`);
    }
    return data;
}

/** DELETE/POST JSON con manejo de errores uniforme. */
async function apiSend(path, method) {
    const response = await fetch(appUrl(path), {
        method: method,
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    });
    const data = await parseJsonResponse(response);
    if (!response.ok || !data?.success) {
        throw new Error(data?.message || `La operación no se pudo completar (${response.status}).`);
    }
    return data;
}

function hideEditEmpresaModal() { document.getElementById('editEmpresaModal').classList.add('hidden'); }
function showModulosEmpresaModal() { document.getElementById('modulosEmpresaModal').classList.remove('hidden'); }
function hideModulosEmpresaModal() { document.getElementById('modulosEmpresaModal').classList.add('hidden'); }

function showAsignarUsuarioEmpresaModal() { document.getElementById('asignarUsuarioEmpresaModal').classList.remove('hidden'); }
function hideAsignarUsuarioEmpresaModal() { document.getElementById('asignarUsuarioEmpresaModal').classList.add('hidden'); }

function openAsignarUsuarioEmpresa(empresaId, empresaNombre) {
    document.getElementById('asignarUsuarioEmpresaNombre').textContent = 'Empresa: ' + (empresaNombre || '');
    const form = document.getElementById('asignarUsuarioEmpresaForm');
    form.action = appUrl('/empresa/' + empresaId + '/usuarios');
    form.reset();
    showAsignarUsuarioEmpresaModal();
}

function showCreateSedeModal() {
    document.getElementById('createSedeModal').classList.remove('hidden');
    @if(!empty($empresaActivaId ?? null))
    document.getElementById('createSedeEmpresa').value = '{{ $empresaActivaId }}';
    fillLocationFromEmpresaSelect('createSedeEmpresa', 'createSede');
    if (typeof __refreshUbicacionChain === 'function') __refreshUbicacionChain('createSede');
    @endif
}
function hideCreateSedeModal() { document.getElementById('createSedeModal').classList.add('hidden'); }
function showEditSedeModal() { document.getElementById('editSedeModal').classList.remove('hidden'); }
function hideEditSedeModal() { document.getElementById('editSedeModal').classList.add('hidden'); }

function showCreateBodegaModal() {
    document.getElementById('createBodegaModal').classList.remove('hidden');
    @if(!empty($empresaActivaId ?? null))
    document.getElementById('createBodegaEmpresa').value = '{{ $empresaActivaId }}';
    if (typeof loadSedesForEmpresa === 'function') loadSedesForEmpresa('createBodegaEmpresa', 'createBodegaSede');
    fillLocationFromEmpresaSelect('createBodegaEmpresa', 'createBodega');
    if (typeof __refreshUbicacionChain === 'function') __refreshUbicacionChain('createBodega');
    @endif
}
function hideCreateBodegaModal() { document.getElementById('createBodegaModal').classList.add('hidden'); }
function showEditBodegaModal() { document.getElementById('editBodegaModal').classList.remove('hidden'); }
function hideEditBodegaModal() { document.getElementById('editBodegaModal').classList.add('hidden'); }

@if($mostrarPestanaOficinas ?? false)
function showCreateOficinaModal() {
    const m = document.getElementById('createOficinaModal');
    if (!m) return;
    m.classList.remove('hidden');
    const ce = document.getElementById('createOficinaEmpresa');
    if (ce) {
        @if(!empty($empresaActivaId ?? null))
        ce.value = '{{ $empresaActivaId }}';
        @endif
        if (!ce.value && ce.options.length) ce.selectedIndex = 0;
        loadSedesForEmpresa('createOficinaEmpresa', 'createOficinaSede');
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
function hideCreateOficinaModal() {
    const m = document.getElementById('createOficinaModal');
    if (m) m.classList.add('hidden');
}
function showEditOficinaModal() {
    const m = document.getElementById('editOficinaModal');
    if (m) m.classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
function hideEditOficinaModal() {
    const m = document.getElementById('editOficinaModal');
    if (m) m.classList.add('hidden');
}
function editOficina(id) {
    apiGet(`/oficinas/${id}/edit`)
        .then(data => {
            const ee = document.getElementById('editOficinaEmpresa');
            if (ee) ee.value = data.empresa_id || '';
            document.getElementById('editOficinaNombre').value = data.nombre || '';
            document.getElementById('editOficinaForm').action = appUrl(`/oficinas/${id}`);
            loadSedesForEmpresa('editOficinaEmpresa', 'editOficinaSede', data.sede_id);
            showEditOficinaModal();
        })
        .catch(e => alert(e?.message || 'Error al cargar la oficina.'));
}
function deleteOficina(id) {
    if (!confirm('¿Eliminar esta oficina?')) return;
    apiSend(`/oficinas/${id}`, 'DELETE')
        .then(() => location.reload())
        .catch(e => alert(e?.message || 'Error al eliminar la oficina.'));
}
@endif

function showCreateEspacioModal() {
    const m = document.getElementById('createEspacioModal');
    if (!m) return;
    m.classList.remove('hidden');
    const ce = document.getElementById('createEspacioEmpresa');
    if (ce) {
        @if(!empty($empresaActivaId ?? null))
        ce.value = '{{ $empresaActivaId }}';
        @endif
        if (!ce.value && ce.options.length) ce.selectedIndex = 0;
        loadSedesForEmpresa('createEspacioEmpresa', 'createEspacioSede');
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
function hideCreateEspacioModal() {
    const m = document.getElementById('createEspacioModal');
    if (m) m.classList.add('hidden');
}
function showEditEspacioModal() {
    const m = document.getElementById('editEspacioModal');
    if (m) m.classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}
function hideEditEspacioModal() {
    const m = document.getElementById('editEspacioModal');
    if (m) m.classList.add('hidden');
}
function editEspacio(id) {
    apiGet(`/espacios/${id}/edit`)
        .then(data => {
            const ee = document.getElementById('editEspacioEmpresa');
            if (ee) ee.value = data.empresa_id || '';
            document.getElementById('editEspacioNombre').value = data.nombre || '';
            document.getElementById('editEspacioForm').action = appUrl(`/espacios/${id}`);
            loadSedesForEmpresa('editEspacioEmpresa', 'editEspacioSede', data.sede_id);
            showEditEspacioModal();
        })
        .catch(e => alert(e?.message || 'Error al cargar el espacio.'));
}
function deleteEspacio(id) {
    if (!confirm('¿Eliminar este espacio?')) return;
    apiSend(`/espacios/${id}`, 'DELETE')
        .then(() => location.reload())
        .catch(e => alert(e?.message || 'Error al eliminar el espacio.'));
}

function editEmpresa(id) {
    apiGet(`/empresa/${id}/edit`)
        .then(data => {
            document.getElementById('editEmpresaNombre').value = data.nombre || '';
            document.getElementById('editEmpresaNit').value = data.nit || '';
            document.getElementById('editEmpresaPrefijo').value = data.prefijo || '';
            const primaryColor = normalizeHexColor(data.color_primario || '#f97316');
            document.getElementById('editEmpresaColor').value = primaryColor;
            const editColorPicker = document.getElementById('editEmpresaColorPicker');
            if (editColorPicker) {
                editColorPicker.value = primaryColor;
            }
            const editEmpresaId = document.getElementById('editEmpresaId');
            if (editEmpresaId) editEmpresaId.value = id;
            document.getElementById('editEmpresaPais').value = data.pais || '';
            document.getElementById('editEmpresaDepartamento').value = data.departamento || '';
            document.getElementById('editEmpresaMunicipio').value = data.municipio || '';
            document.getElementById('editEmpresaCiudad').value = data.ciudad || '';
            document.getElementById('editEmpresaDireccion').value = data.direccion || '';
            document.getElementById('editEmpresaMaps').value = data.google_maps_url || '';
            document.getElementById('editEmpresaLatitud').value = data.latitud || '';
            document.getElementById('editEmpresaLongitud').value = data.longitud || '';
            document.getElementById('editEmpresaAltitud').value = data.altitud || '';
            fillCoordsFromMapsInput('editEmpresaMaps', 'editEmpresaLatitud', 'editEmpresaLongitud');
            document.getElementById('editEmpresaColorSec1').value = normalizeHexColor(data.color_secundario_1 || '#67e8f9');
            document.getElementById('editEmpresaColorSec2').value = normalizeHexColor(data.color_secundario_2 || '#075479');
            syncEmpresaPaletteDots('edit');
            document.getElementById('editEmpresaForm').action = appUrl(`/empresa/${id}`);
            __refreshUbicacionChain('editEmpresa');
            showEditEmpresaModal();
        })
        .catch(e => alert(e?.message || 'Error al cargar la empresa.'));
}

function normalizeHexColor(value) {
    const v = (value || '').toString().trim();
    if (/^#([0-9a-fA-F]{6})$/.test(v)) return v;
    if (/^#([0-9a-fA-F]{3})$/.test(v)) {
        return '#' + v[1] + v[1] + v[2] + v[2] + v[3] + v[3];
    }
    return '#f97316';
}

function bindColorPair(pickerId, textId) {
    const picker = document.getElementById(pickerId);
    const text = document.getElementById(textId);
    if (!picker || !text) return;

    picker.value = normalizeHexColor(text.value || picker.value);
    text.value = picker.value;

    picker.addEventListener('input', function() {
        text.value = picker.value;
    });

    text.addEventListener('input', function() {
        const normalized = normalizeHexColor(text.value);
        picker.value = normalized;
        if (!text.value.startsWith('#') || text.value.length < 4) return;
        text.value = normalized;
    });
}

function syncEmpresaPaletteDots(prefix) {
    const primaryInput = document.getElementById(prefix + 'EmpresaColorPicker');
    const sec1Input = document.getElementById(prefix + 'EmpresaColorSec1');
    const sec2Input = document.getElementById(prefix + 'EmpresaColorSec2');
    const dotPrimary = document.getElementById(prefix + 'DotPrimary');
    const dotSec1 = document.getElementById(prefix + 'DotSec1');
    const dotSec2 = document.getElementById(prefix + 'DotSec2');
    if (!primaryInput || !sec1Input || !sec2Input || !dotPrimary || !dotSec1 || !dotSec2) return;

    dotPrimary.style.background = normalizeHexColor(primaryInput.value || '#f97316');
    dotSec1.style.background = normalizeHexColor(sec1Input.value || '#67e8f9');
    dotSec2.style.background = normalizeHexColor(sec2Input.value || '#075479');
}

function bindEmpresaPalette(prefix) {
    const primaryPicker = document.getElementById(prefix + 'EmpresaColorPicker');
    const sec1Input = document.getElementById(prefix + 'EmpresaColorSec1');
    const sec2Input = document.getElementById(prefix + 'EmpresaColorSec2');
    if (!primaryPicker || !sec1Input || !sec2Input) return;

    const refresh = () => syncEmpresaPaletteDots(prefix);
    primaryPicker.addEventListener('input', refresh);
    sec1Input.addEventListener('input', refresh);
    sec2Input.addEventListener('input', refresh);
    refresh();
}

function openEmpresaModulos(id) {
    apiGet(`/empresa/${id}/edit`)
        .then(data => {
            const form = document.getElementById('modulosEmpresaForm');
            form.action = appUrl(`/empresa/${id}/modulos`);
            const selects = form.querySelectorAll('select[name^="modulos["]');
            let current = data.modulos || {};

            // Compatibilidad: si existe gestion_principal (formato antiguo), aplicarlo a users, roles, cargos, grupos, fabricantes
            if (current && typeof current === 'object' && !Array.isArray(current) && current.gestion_principal) {
                const v = current.gestion_principal;
                ['users', 'roles', 'cargos', 'grupos', 'fabricantes'].forEach(k => { if (current[k] === undefined) current[k] = v; });
            }

            selects.forEach(sel => {
                const key = sel.name.replace(/^modulos\[(.+)]$/, '$1');
                let value = 'edit';

                if (Array.isArray(current)) {
                    value = current.includes(key) ? 'edit' : 'none';
                } else if (current && typeof current === 'object') {
                    if (current[key] === 'none' || current[key] === 'view' || current[key] === 'edit') {
                        value = current[key];
                    } else if (current[key]) {
                        value = 'edit';
                    } else {
                        value = 'none';
                    }
                }

                sel.value = value;
            });
            showModulosEmpresaModal();
        })
        .catch(e => alert(e?.message || 'Error al cargar los módulos.'));
}

function deleteEmpresa(id) {
    if (!confirm('¿Estás seguro de eliminar esta empresa? Esta acción no se puede deshacer.')) return;

    apiSend(`/empresa/${id}`, 'DELETE')
        .then(() => location.reload())
        .catch(e => alert(e?.message || 'Error al eliminar la empresa.'));
}

function toggleEmpresaStatus(id) {
    apiSend(`/empresa/${id}/toggle-status`, 'POST')
        .then(() => location.reload())
        .catch(e => alert(e?.message || 'Error al cambiar el estado.'));
}

function editSede(id) {
    apiGet(`/sedes/${id}/edit`)
        .then(data => {
            document.getElementById('editSedeEmpresa').value = data.empresa_id || '';
            document.getElementById('editSedeNombre').value = data.nombre || '';
            document.getElementById('editSedePais').value = data.pais || '';
            document.getElementById('editSedeDepartamento').value = data.departamento || '';
            document.getElementById('editSedeMunicipio').value = data.municipio || '';
            document.getElementById('editSedeCiudad').value = data.ciudad || '';
            document.getElementById('editSedeDireccion').value = data.direccion || '';
            document.getElementById('editSedeMaps').value = data.google_maps_url || '';
            document.getElementById('editSedeForm').action = appUrl(`/sedes/${id}`);
            __refreshUbicacionChain('editSede');
            showEditSedeModal();
        })
        .catch(e => alert(e?.message || 'Error al cargar la sede.'));
}

function deleteSede(id) {
    if (!confirm('¿Estás seguro de eliminar esta sede?')) return;

    apiSend(`/sedes/${id}`, 'DELETE')
        .then(() => location.reload())
        .catch(e => alert(e?.message || 'Error al eliminar la sede.'));
}

function toggleSedeStatus(id) {
    apiSend(`/sedes/${id}/toggle-status`, 'POST')
        .then(() => location.reload())
        .catch(e => alert(e?.message || 'Error al cambiar el estado.'));
}

function editBodega(id) {
    apiGet(`/bodegas/${id}/edit`)
        .then(data => {
            document.getElementById('editBodegaEmpresa').value = data.empresa_id || '';
            document.getElementById('editBodegaNombre').value = data.nombre || '';
            document.getElementById('editBodegaPais').value = data.pais || '';
            document.getElementById('editBodegaDepartamento').value = data.departamento || '';
            document.getElementById('editBodegaMunicipio').value = data.municipio || '';
            document.getElementById('editBodegaCiudad').value = data.ciudad || '';
            document.getElementById('editBodegaDireccion').value = data.direccion || '';
            document.getElementById('editBodegaMaps').value = data.google_maps_url || '';
            document.getElementById('editBodegaForm').action = appUrl(`/bodegas/${id}`);

            loadSedesForEmpresa('editBodegaEmpresa', 'editBodegaSede', data.sede_id);

            __refreshUbicacionChain('editBodega');
            showEditBodegaModal();
        })
        .catch(e => alert(e?.message || 'Error al cargar la bodega.'));
}

function deleteBodega(id) {
    if (!confirm('¿Estás seguro de eliminar esta bodega?')) return;

    apiSend(`/bodegas/${id}`, 'DELETE')
        .then(() => location.reload())
        .catch(e => alert(e?.message || 'Error al eliminar la bodega.'));
}

function toggleBodegaStatus(id) {
    apiSend(`/bodegas/${id}/toggle-status`, 'POST')
        .then(() => location.reload())
        .catch(e => alert(e?.message || 'Error al cambiar el estado.'));
}

function fillLocationFromEmpresaSelect(selectId, prefix) {
    const select = document.getElementById(selectId);
    const opt = select.options[select.selectedIndex];
    if (!opt) return;

    document.getElementById(prefix + 'Pais').value = opt.getAttribute('data-pais') || '';
    document.getElementById(prefix + 'Departamento').value = opt.getAttribute('data-departamento') || '';
    document.getElementById(prefix + 'Municipio').value = opt.getAttribute('data-municipio') || '';
    document.getElementById(prefix + 'Ciudad').value = opt.getAttribute('data-ciudad') || '';
    document.getElementById(prefix + 'Direccion').value = opt.getAttribute('data-direccion') || '';
    document.getElementById(prefix + 'Maps').value = opt.getAttribute('data-maps') || '';
}

/** Todas las sedes visibles, embebidas para no depender de una petición al servidor. */
const __SEDES = @json($sedes->map(fn ($s) => ['id' => (int) $s->id, 'empresa_id' => (int) $s->empresa_id, 'nombre' => (string) $s->nombre])->values());

function pintarSedes(sedeSelect, lista, selectedSedeId) {
    if (!lista.length) {
        sedeSelect.innerHTML = '<option value="">Esta empresa no tiene sedes registradas</option>';
        return;
    }
    sedeSelect.innerHTML = '<option value="">Seleccione...</option>';
    lista.forEach(s => {
        const o = document.createElement('option');
        o.value = s.id;
        o.textContent = s.nombre;
        if (selectedSedeId && parseInt(selectedSedeId, 10) === parseInt(s.id, 10)) {
            o.selected = true;
        }
        sedeSelect.appendChild(o);
    });
}

function loadSedesForEmpresa(empresaSelectId, sedeSelectId, selectedSedeId) {
    const empresaSelect = document.getElementById(empresaSelectId);
    const sedeSelect = document.getElementById(sedeSelectId);
    if (!empresaSelect || !sedeSelect) return Promise.resolve();

    const empresaId = parseInt(empresaSelect.value, 10);
    if (!empresaId) {
        sedeSelect.innerHTML = '<option value="">Seleccione empresa primero...</option>';
        return Promise.resolve();
    }

    const locales = __SEDES.filter(s => s.empresa_id === empresaId);
    pintarSedes(sedeSelect, locales, selectedSedeId);

    // Si esta empresa no venía en el listado embebido, se consulta al servidor.
    if (locales.length) return Promise.resolve();

    return apiGet(`/empresa/${empresaId}/sedes`)
        .then(sedes => pintarSedes(sedeSelect, Array.isArray(sedes) ? sedes : (sedes?.data ?? []), selectedSedeId))
        .catch(() => {
            sedeSelect.innerHTML = '<option value="">Esta empresa no tiene sedes registradas</option>';
        });
}

document.addEventListener('DOMContentLoaded', function() {
    __wireUbicacion('createEmpresa', 'createEmpresaDepartamentosList', 'createEmpresaMunicipiosList', 'createEmpresaCiudadesList');
    __wireUbicacion('editEmpresa', 'editEmpresaDepartamentosList', 'editEmpresaMunicipiosList', 'editEmpresaCiudadesList');
    __wireUbicacion('createSede', 'createSedeDepartamentosList', 'createSedeMunicipiosList', 'createSedeCiudadesList');
    __wireUbicacion('editSede', 'editSedeDepartamentosList', 'editSedeMunicipiosList', 'editSedeCiudadesList');
    __wireUbicacion('createBodega', 'createBodegaDepartamentosList', 'createBodegaMunicipiosList', 'createBodegaCiudadesList');
    __wireUbicacion('editBodega', 'editBodegaDepartamentosList', 'editBodegaMunicipiosList', 'editBodegaCiudadesList');

    const createSedeEmpresa = document.getElementById('createSedeEmpresa');
    if (createSedeEmpresa) {
        createSedeEmpresa.addEventListener('change', function() {
            fillLocationFromEmpresaSelect('createSedeEmpresa', 'createSede');
            __refreshUbicacionChain('createSede');
        });
    }

    const editSedeEmpresa = document.getElementById('editSedeEmpresa');
    if (editSedeEmpresa) {
        editSedeEmpresa.addEventListener('change', function() {
            fillLocationFromEmpresaSelect('editSedeEmpresa', 'editSede');
            __refreshUbicacionChain('editSede');
        });
    }

    const createBodegaEmpresa = document.getElementById('createBodegaEmpresa');
    if (createBodegaEmpresa) {
        createBodegaEmpresa.addEventListener('change', function() {
            fillLocationFromEmpresaSelect('createBodegaEmpresa', 'createBodega');
            loadSedesForEmpresa('createBodegaEmpresa', 'createBodegaSede');
            __refreshUbicacionChain('createBodega');
        });
    }

    const editBodegaEmpresa = document.getElementById('editBodegaEmpresa');
    if (editBodegaEmpresa) {
        editBodegaEmpresa.addEventListener('change', function() {
            fillLocationFromEmpresaSelect('editBodegaEmpresa', 'editBodega');
            loadSedesForEmpresa('editBodegaEmpresa', 'editBodegaSede');
            __refreshUbicacionChain('editBodega');
        });
    }

    @if($mostrarPestanaOficinas ?? false)
    const createOficinaEmpresa = document.getElementById('createOficinaEmpresa');
    if (createOficinaEmpresa) {
        createOficinaEmpresa.addEventListener('change', function() {
            loadSedesForEmpresa('createOficinaEmpresa', 'createOficinaSede');
        });
    }
    const editOficinaEmpresa = document.getElementById('editOficinaEmpresa');
    if (editOficinaEmpresa) {
        editOficinaEmpresa.addEventListener('change', function() {
            loadSedesForEmpresa('editOficinaEmpresa', 'editOficinaSede');
        });
    }
    @endif

    const createEspacioEmpresa = document.getElementById('createEspacioEmpresa');
    if (createEspacioEmpresa) {
        createEspacioEmpresa.addEventListener('change', function() {
            loadSedesForEmpresa('createEspacioEmpresa', 'createEspacioSede');
        });
    }
    const editEspacioEmpresa = document.getElementById('editEspacioEmpresa');
    if (editEspacioEmpresa) {
        editEspacioEmpresa.addEventListener('change', function() {
            loadSedesForEmpresa('editEspacioEmpresa', 'editEspacioSede');
        });
    }

    bindColorPair('createEmpresaColorPicker', 'createEmpresaColorHex');
    bindColorPair('editEmpresaColorPicker', 'editEmpresaColor');
    bindEmpresaPalette('create');
    bindEmpresaPalette('edit');

    function syncColorPairBeforeSubmit(form, pickerId, textId) {
        if (!form) return;
        form.addEventListener('submit', function () {
            const picker = document.getElementById(pickerId);
            const text = document.getElementById(textId);
            if (!picker || !text) return;
            if (!(text.value || '').trim()) {
                text.value = normalizeHexColor(picker.value || '#f97316');
            }
        });
    }
    syncColorPairBeforeSubmit(document.querySelector('#createEmpresaModal form'), 'createEmpresaColorPicker', 'createEmpresaColorHex');
    syncColorPairBeforeSubmit(document.getElementById('editEmpresaForm'), 'editEmpresaColorPicker', 'editEmpresaColor');

    const EMPRESA_IMAGE_MAX_BYTES = 10 * 1024 * 1024;
    document.querySelectorAll('.js-empresa-image').forEach(function (input) {
        input.addEventListener('change', function () {
            const file = input.files && input.files[0];
            if (!file) return;
            if (file.size > EMPRESA_IMAGE_MAX_BYTES) {
                if (typeof showNotification === 'function') {
                    showNotification('La imagen supera 10 MB. Elige un archivo más liviano.', 'error');
                } else {
                    alert('La imagen supera 10 MB. Elige un archivo más liviano.');
                }
                input.value = '';
            }
        });
    });

    function bindMapsVsCoords(mapsId, latId, lngId) {
        const maps = document.getElementById(mapsId);
        const lat = document.getElementById(latId);
        const lng = document.getElementById(lngId);
        if (!maps || !lat || !lng) return;
        const apply = () => {
            const hasMaps = (maps.value || '').trim().length > 0;
            lat.required = false;
            lng.required = false;
            lat.removeAttribute('required');
            lng.removeAttribute('required');
            if (hasMaps) {
                fillCoordsFromMapsInput(maps, lat, lng);
            }
        };
        maps.addEventListener('input', apply);
        maps.addEventListener('paste', function () { setTimeout(apply, 50); });
        maps.addEventListener('blur', apply);
        apply();
    }
    bindMapsVsCoords('createEmpresaMapsUrl', 'createEmpresaLatitud', 'createEmpresaLongitud');
    bindMapsVsCoords('editEmpresaMaps', 'editEmpresaLatitud', 'editEmpresaLongitud');

    function bindSingleLogoRule(firstId, secondId) {
        const a = document.getElementById(firstId);
        const b = document.getElementById(secondId);
        if (!a || !b) return;
        a.addEventListener('change', () => { if (a.files && a.files.length) b.value = ''; });
        b.addEventListener('change', () => { if (b.files && b.files.length) a.value = ''; });
    }
    bindSingleLogoRule('createLogoPrincipalFile', 'createLogoSecundarioFile');
    bindSingleLogoRule('editLogoPrincipalFile', 'editLogoSecundarioFile');

    @if(session('empresa_nueva_id'))
    openAsignarUsuarioEmpresa(@json(session('empresa_nueva_id')), @json(session('empresa_nueva_nombre')));
    @endif

    @php
        $empresaFormErrorKeys = ['nombre','nit','color_primario','foto_empresa','pais','departamento','ciudad','direccion','logo_principal_file','logo_secundario_file','latitud','longitud','google_maps_url','color_secundario_1','color_secundario_2','telefono','email','sitio_web'];
        $reopenEmpresaForm = $errors->hasAny($empresaFormErrorKeys);
    @endphp
    @if($reopenEmpresaForm)
    openTab('empresa');
    @if(old('_method') === 'PUT' && old('empresa_id'))
    (function () {
        const id = @json((string) old('empresa_id'));
        document.getElementById('editEmpresaNombre').value = @json(old('nombre', ''));
        document.getElementById('editEmpresaNit').value = @json(old('nit', ''));
        const primaryColor = normalizeHexColor(@json(old('color_primario', '#f97316')));
        document.getElementById('editEmpresaColor').value = primaryColor;
        const editColorPicker = document.getElementById('editEmpresaColorPicker');
        if (editColorPicker) editColorPicker.value = primaryColor;
        document.getElementById('editEmpresaPais').value = @json(old('pais', ''));
        document.getElementById('editEmpresaDepartamento').value = @json(old('departamento', ''));
        document.getElementById('editEmpresaMunicipio').value = @json(old('municipio', ''));
        document.getElementById('editEmpresaCiudad').value = @json(old('ciudad', ''));
        document.getElementById('editEmpresaDireccion').value = @json(old('direccion', ''));
        document.getElementById('editEmpresaMaps').value = @json(old('google_maps_url', ''));
        document.getElementById('editEmpresaLatitud').value = @json(old('latitud', ''));
        document.getElementById('editEmpresaLongitud').value = @json(old('longitud', ''));
        document.getElementById('editEmpresaAltitud').value = @json(old('altitud', ''));
        document.getElementById('editEmpresaColorSec1').value = normalizeHexColor(@json(old('color_secundario_1', '#67e8f9')));
        document.getElementById('editEmpresaColorSec2').value = normalizeHexColor(@json(old('color_secundario_2', '#075479')));
        document.getElementById('editEmpresaId').value = id;
        document.getElementById('editEmpresaForm').action = appUrl('/empresa/' + id);
        syncEmpresaPaletteDots('edit');
        showEditEmpresaModal();
    })();
    @else
    showCreateEmpresaModal();
    @endif
    @else
    openTab('localizacion');
    @endif
    initLocalizacionMap();
});

let __LOC_MAP = null;
let __LOC_MARKERS = [];
let __LOC_INFO = null;
let __LOC_INITED = false;
let __LOC_COUNTS = null;

function __setLocalizacionMapState(message) {
    const el = document.getElementById('localizacionMap');
    if (!el || __LOC_MAP) return;
    el.innerHTML = '<div class="h-full flex items-center justify-center text-sm text-gray-500">' + String(message || '') + '</div>';
}

function __clearLocalizacionMapState() {
    const el = document.getElementById('localizacionMap');
    if (!el || __LOC_MAP) return;
    el.innerHTML = '';
}

function __locCoords(it) {
    const lat = Number(it.latitud);
    const lng = Number(it.longitud);
    if (isFinite(lat) && isFinite(lng) && !(lat === 0 && lng === 0)) {
        return { lat: lat, lng: lng };
    }
    const fromUrl = parseCoordsFromMapsUrl(it.google_maps_url || '');
    if (fromUrl) {
        return { lat: Number(fromUrl.lat), lng: Number(fromUrl.lng) };
    }
    return null;
}

window.__onGoogleMapsReady = function () {};

function __locFullAddress(item) {
    const parts = [item.direccion, item.ciudad, item.municipio, item.departamento, item.pais].filter(Boolean);
    return parts.join(', ');
}

async function __locGeocode(address) {
    const cacheKey = 'loc_geocode_v1:' + address.toLowerCase();
    try {
        const cached = localStorage.getItem(cacheKey);
        if (cached) return JSON.parse(cached);
    } catch (e) {}

    const res = await fetch("{{ route('empresa.geocode') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ address })
    });

    const json = await res.json();
    if (!json || !json.success || !json.data) return null;

    try {
        localStorage.setItem(cacheKey, JSON.stringify(json.data));
    } catch (e) {}

    return json.data;
}

function initLocalizacionMap() {
    if (__LOC_INITED) return;
    const el = document.getElementById('localizacionMap');
    if (!el) return;
    if (typeof L === 'undefined') {
        __setLocalizacionMapState('No se pudo cargar el mapa.');
        return;
    }

    __LOC_INITED = true;
    __clearLocalizacionMapState();

    __LOC_MAP = L.map(el, {
        zoomControl: true,
    }).setView([4.5709, -74.2973], 6);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
    }).addTo(__LOC_MAP);

    setTimeout(function () { __LOC_MAP.invalidateSize(); }, 50);

    const baseItems = [];

    @foreach($empresas as $e)
        baseItems.push({
            tipo: 'empresa',
            id: {{ $e->id }},
            empresa_id: {{ $e->id }},
            nombre: @json($e->nombre),
            pais: @json($e->pais),
            departamento: @json($e->departamento),
            municipio: @json($e->municipio),
            ciudad: @json($e->ciudad),
            direccion: @json($e->direccion),
            google_maps_url: @json($e->google_maps_url),
            latitud: @json($e->latitud),
            longitud: @json($e->longitud),
        });
    @endforeach

    @foreach($sedes as $s)
        baseItems.push({
            tipo: 'sede',
            id: {{ $s->id }},
            empresa_id: {{ $s->empresa_id }},
            sede_id: {{ $s->id }},
            nombre: @json($s->nombre),
            empresa_nombre: @json(optional($s->empresa)->nombre),
            pais: @json($s->pais),
            departamento: @json($s->departamento),
            municipio: @json($s->municipio),
            ciudad: @json($s->ciudad),
            direccion: @json($s->direccion),
            google_maps_url: @json($s->google_maps_url),
        });
    @endforeach

    @foreach($bodegas as $b)
        baseItems.push({
            tipo: 'bodega',
            id: {{ $b->id }},
            empresa_id: {{ $b->empresa_id }},
            sede_id: @json($b->sede_id),
            nombre: @json($b->nombre),
            empresa_nombre: @json(optional($b->empresa)->nombre),
            sede_nombre: @json(optional($b->sede)->nombre),
            pais: @json($b->pais),
            departamento: @json($b->departamento),
            municipio: @json($b->municipio),
            ciudad: @json($b->ciudad),
            direccion: @json($b->direccion),
            google_maps_url: @json($b->google_maps_url),
        });
    @endforeach

    @foreach($oficinasMap ?? [] as $om)
    @php
        $omSede = $om->sede;
        $omEmp = $om->empresa;
        $omGeo = $omSede ?: $omEmp;
    @endphp
        baseItems.push({
            tipo: 'oficina',
            id: {{ $om->id }},
            empresa_id: {{ $om->empresa_id }},
            sede_id: {{ $om->sede_id }},
            nombre: @json($om->nombre),
            empresa_nombre: @json(optional($omEmp)->nombre),
            sede_nombre: @json(optional($omSede)->nombre),
            pais: @json($omGeo?->pais),
            departamento: @json($omGeo?->departamento),
            municipio: @json($omGeo?->municipio),
            ciudad: @json($omGeo?->ciudad),
            direccion: @json($omGeo?->direccion),
            google_maps_url: @json($omGeo?->google_maps_url),
        });
    @endforeach

    __LOC_COUNTS = (function() {
        const empresaToSedes = {};
        const empresaToBodegas = {};
        const sedeToBodegas = {};
        const empresaToOficinas = {};
        const sedeToOficinas = {};

        baseItems.forEach(it => {
            if (it.tipo === 'sede') {
                empresaToSedes[it.empresa_id] = (empresaToSedes[it.empresa_id] || 0) + 1;
            }
            if (it.tipo === 'bodega') {
                empresaToBodegas[it.empresa_id] = (empresaToBodegas[it.empresa_id] || 0) + 1;
                if (it.sede_id) {
                    sedeToBodegas[it.sede_id] = (sedeToBodegas[it.sede_id] || 0) + 1;
                }
            }
            if (it.tipo === 'oficina') {
                empresaToOficinas[it.empresa_id] = (empresaToOficinas[it.empresa_id] || 0) + 1;
                if (it.sede_id) {
                    sedeToOficinas[it.sede_id] = (sedeToOficinas[it.sede_id] || 0) + 1;
                }
            }
        });

        return { empresaToSedes, empresaToBodegas, sedeToBodegas, empresaToOficinas, sedeToOficinas };
    })();

    const selEmpresa = document.getElementById('locFilterEmpresa');
    const selSede = document.getElementById('locFilterSede');
    const selBodega = document.getElementById('locFilterBodega');
    const selOficina = document.getElementById('locFilterOficina');

    function __updateSedeBodegaOptionsByEmpresa() {
        const empresaId = selEmpresa?.value || 'all';
        if (!selSede || !selBodega) return;

        Array.from(selSede.options).forEach((opt, idx) => {
            if (idx === 0) return;
            const optEmp = opt.getAttribute('data-empresa');
            opt.hidden = (empresaId !== 'all' && String(optEmp) !== String(empresaId));
        });

        Array.from(selBodega.options).forEach((opt, idx) => {
            if (idx === 0) return;
            const optEmp = opt.getAttribute('data-empresa');
            opt.hidden = (empresaId !== 'all' && String(optEmp) !== String(empresaId));
        });
    }

    function __updateBodegaOptionsBySede() {
        const sedeId = selSede?.value || 'all';
        if (!selBodega) return;

        Array.from(selBodega.options).forEach((opt, idx) => {
            if (idx === 0) return;

            const optSede = opt.getAttribute('data-sede');
            if (sedeId === 'all') {
                const empresaId = selEmpresa?.value || 'all';
                if (empresaId === 'all') return;
                const optEmp = opt.getAttribute('data-empresa');
                opt.hidden = (String(optEmp) !== String(empresaId));
                return;
            }

            opt.hidden = (String(optSede) !== String(sedeId));
        });
    }

    function __updateOficinaOptionsBySede() {
        if (!selOficina) return;
        const sedeId = selSede?.value || 'all';
        const empresaId = selEmpresa?.value || 'all';

        Array.from(selOficina.options).forEach((opt, idx) => {
            if (idx === 0) return;
            const optEmp = opt.getAttribute('data-empresa');
            const optSede = opt.getAttribute('data-sede');
            if (empresaId !== 'all' && String(optEmp) !== String(empresaId)) {
                opt.hidden = true;
                return;
            }
            if (sedeId === 'all') {
                opt.hidden = false;
                return;
            }
            opt.hidden = (String(optSede) !== String(sedeId));
        });
    }

    const selTipo = document.getElementById('locFilterTipo');
    const inputBuscar = document.getElementById('locSearch');
    const aplicar = () => renderLocalizacionMarkers(baseItems);

    selEmpresa?.addEventListener('change', function() {
        if (selSede) selSede.value = 'all';
        if (selBodega) selBodega.value = 'all';
        if (selOficina) selOficina.value = 'all';
        __updateSedeBodegaOptionsByEmpresa();
        __updateBodegaOptionsBySede();
        __updateOficinaOptionsBySede();
        aplicar();
    });

    selSede?.addEventListener('change', function() {
        if (selBodega) selBodega.value = 'all';
        if (selOficina) selOficina.value = 'all';
        __updateBodegaOptionsBySede();
        __updateOficinaOptionsBySede();
        aplicar();
    });

    // Bodega y oficina son mutuamente excluyentes: elegir una limpia la otra.
    selBodega?.addEventListener('change', function() {
        if (selBodega.value !== 'all') {
            if (selOficina) selOficina.value = 'all';
            if (selTipo) selTipo.value = 'all';
        }
        aplicar();
    });

    selOficina?.addEventListener('change', function() {
        if (selOficina.value !== 'all') {
            if (selBodega) selBodega.value = 'all';
            if (selTipo) selTipo.value = 'all';
        }
        aplicar();
    });

    selTipo?.addEventListener('change', function() {
        if (selTipo.value !== 'all' && selTipo.value !== 'bodega' && selBodega) selBodega.value = 'all';
        if (selTipo.value !== 'all' && selTipo.value !== 'oficina' && selOficina) selOficina.value = 'all';
        aplicar();
    });

    let __locSearchTimer = null;
    inputBuscar?.addEventListener('input', function() {
        clearTimeout(__locSearchTimer);
        __locSearchTimer = setTimeout(aplicar, 300);
    });
    inputBuscar?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(__locSearchTimer);
            aplicar();
        }
    });

    __updateSedeBodegaOptionsByEmpresa();
    __updateBodegaOptionsBySede();
    __updateOficinaOptionsBySede();

    document.getElementById('locBtnAplicar')?.addEventListener('click', aplicar);

    document.getElementById('locBtnReset')?.addEventListener('click', function() {
        if (selTipo) selTipo.value = 'all';
        if (selEmpresa) selEmpresa.value = 'all';
        if (selSede) selSede.value = 'all';
        if (selBodega) selBodega.value = 'all';
        if (selOficina) selOficina.value = 'all';
        if (inputBuscar) inputBuscar.value = '';
        __updateSedeBodegaOptionsByEmpresa();
        __updateBodegaOptionsBySede();
        __updateOficinaOptionsBySede();
        aplicar();
    });

    aplicar();
}

function renderLocalizacionMarkers(items) {
    if (!__LOC_MAP) return;

    __LOC_MARKERS.forEach(function (m) { __LOC_MAP.removeLayer(m); });
    __LOC_MARKERS = [];

    const tipo = document.getElementById('locFilterTipo')?.value || 'all';
    const empresaId = document.getElementById('locFilterEmpresa')?.value || 'all';
    const sedeId = document.getElementById('locFilterSede')?.value || 'all';
    const bodegaId = document.getElementById('locFilterBodega')?.value || 'all';
    const oficinaId = document.getElementById('locFilterOficina')?.value || 'all';
    const q = (document.getElementById('locSearch')?.value || '').trim().toLowerCase();

    const filtered = items.filter(it => {
        if (tipo !== 'all' && it.tipo !== tipo) return false;
        if (empresaId !== 'all' && String(it.empresa_id) !== String(empresaId)) return false;

        if (sedeId !== 'all') {
            if (it.tipo === 'sede' && String(it.id) !== String(sedeId)) return false;
            if (it.tipo === 'bodega' && String(it.sede_id) !== String(sedeId)) return false;
            if (it.tipo === 'oficina' && String(it.sede_id) !== String(sedeId)) return false;
            if (it.tipo === 'empresa') return false;
        }

        if (bodegaId !== 'all') {
            if (it.tipo !== 'bodega') return false;
            if (String(it.id) !== String(bodegaId)) return false;
        }

        if (oficinaId !== 'all') {
            if (it.tipo !== 'oficina') return false;
            if (String(it.id) !== String(oficinaId)) return false;
        }

        if (!q) return true;
        const hay = [it.nombre, it.empresa_nombre, it.sede_nombre, it.tipo, it.pais, it.departamento, it.municipio, it.ciudad, it.direccion]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();
        return hay.includes(q);
    });

    const colors = {
        empresa: '#ea580c',
        sede: '#2563eb',
        oficina: '#16a34a',
        bodega: '#7c3aed',
    };
    const bounds = [];
    let count = 0;

    for (const it of filtered) {
        const coords = __locCoords(it);
        if (!coords) continue;

        const address = __locFullAddress(it);
        const color = colors[it.tipo] || '#334155';
        const marker = L.circleMarker([coords.lat, coords.lng], {
            radius: 9,
            color: color,
            weight: 2,
            fillColor: color,
            fillOpacity: 0.85,
        }).addTo(__LOC_MAP);

        const sedesCount = it.tipo === 'empresa' ? (__LOC_COUNTS?.empresaToSedes?.[it.empresa_id] || 0) : null;
        const bodegasCountEmpresa = it.tipo === 'empresa' ? (__LOC_COUNTS?.empresaToBodegas?.[it.empresa_id] || 0) : null;
        const oficinasCountEmpresa = it.tipo === 'empresa' ? (__LOC_COUNTS?.empresaToOficinas?.[it.empresa_id] || 0) : null;
        const bodegasCountSede = it.tipo === 'sede' ? (__LOC_COUNTS?.sedeToBodegas?.[it.id] || 0) : null;
        const oficinasCountSede = it.tipo === 'sede' ? (__LOC_COUNTS?.sedeToOficinas?.[it.id] || 0) : null;

        const html = `
            <div class="loc-map-info">
                <div class="loc-map-info-title">${it.nombre || ''}</div>
                ${it.tipo === 'empresa' ? `<div class="loc-map-info-meta">Sedes: <b>${sedesCount}</b> · Bodegas: <b>${bodegasCountEmpresa}</b> · Oficinas: <b>${oficinasCountEmpresa}</b></div>` : ''}
                ${it.tipo === 'sede' ? `<div class="loc-map-info-meta">Bodegas: <b>${bodegasCountSede}</b> · Oficinas: <b>${oficinasCountSede}</b></div>` : ''}
                ${it.tipo === 'oficina' ? `<div class="loc-map-info-meta">Oficina</div>` : ''}
                ${it.empresa_nombre ? `<div class="loc-map-info-meta">${it.empresa_nombre}</div>` : ''}
                ${it.sede_nombre ? `<div class="loc-map-info-meta">${it.sede_nombre}</div>` : ''}
                ${address ? `<div class="loc-map-info-address">${address}</div>` : ''}
                ${it.google_maps_url ? `<div class="loc-map-info-link"><a href="${it.google_maps_url}" target="_blank">Ver en Google Maps</a></div>` : ''}
            </div>
        `;
        marker.bindPopup(html);
        __LOC_MARKERS.push(marker);
        bounds.push([coords.lat, coords.lng]);
        count++;
    }

    const countEl = document.getElementById('locCount');
    if (countEl) countEl.textContent = String(count);

    if (count > 0) {
        __LOC_MAP.fitBounds(bounds, { padding: [28, 28], maxZoom: 14 });
    } else {
        __LOC_MAP.setView([4.5709, -74.2973], 6);
    }
}
</script>
@endsection
