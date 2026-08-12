@extends('layouts.admin-layout')

@section('title', 'Editar Tipo de Equipo - SAMS')
@section('header-title', 'Editar Tipo de Equipo')
@section('header-subtitle', 'Modificar información del tipo de equipo')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white shadow-md rounded-lg p-6">
        <form action="{{ route('equipos.update-tipo', $tipo->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div>
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">Nombre del Tipo *</label>
                <input type="text" id="nombre" name="nombre" value="{{ $tipo->nombre }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
            </div>

            <div>
                <label for="alias" class="block text-sm font-medium text-gray-700 mb-2">Alias (manual)</label>
                <input type="text" id="alias" name="alias" value="{{ $tipo->alias ?? '' }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
            </div>

            <div>
                <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">{{ $tipo->descripcion ?? '' }}</textarea>
            </div>

            <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                <a href="{{ route('equipos.gestion') }}" 
                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit" 
                    class="pw-btn-success px-6 py-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Actualizar Tipo
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
