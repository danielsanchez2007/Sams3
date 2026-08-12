@extends('layouts.admin-layout')

@section('title', 'Crear Rol - SAMS')
@section('header-title', 'Crear Rol')
@section('header-subtitle', 'Crea un rol y define los apartados que puede ver')

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('roles.complete') }}" class="pw-btn-secondary px-4 py-2 rounded-lg">
        <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>
        Volver
    </a>
</div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="pw-card bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Nuevo rol</h3>
            <p class="text-sm text-gray-500">Completa la información y selecciona los permisos disponibles.</p>
        </div>

        <form action="{{ route('roles.store') }}" method="POST" class="p-6 space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre del Rol *</label>
                <input type="text" name="name" required class="w-full pw-input" value="{{ old('name') }}">
                @if(!empty($empresaPrefijo))
                    <p class="mt-1 text-xs text-gray-500">Se guardará con prefijo de empresa: <strong>{{ $empresaPrefijo }}-</strong></p>
                @endif
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                <textarea name="description" rows="3" class="w-full pw-textarea">{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Permisos / Apartados que puede ver</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto text-sm border border-gray-200 rounded-lg p-3 bg-gray-50">
                    @foreach(($availablePermissions ?? []) as $permKey => $permLabel)
                        <label class="inline-flex items-center gap-2 text-gray-700">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $permKey }}"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                {{ in_array($permKey, (array) old('permissions', []), true) ? 'checked' : '' }}
                            >
                            <span>{{ $permLabel }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="mt-1 text-xs text-gray-500">Solo se muestran los apartados disponibles en la empresa activa.</p>
                @error('permissions')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('roles.complete') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg">
                    <i data-lucide="save" class="w-4 h-4 inline mr-2"></i>
                    Crear Rol
                </button>
            </div>
        </form>
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

