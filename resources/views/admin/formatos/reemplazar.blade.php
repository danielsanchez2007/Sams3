@extends('layouts.admin-layout')

@section('title', 'Reemplazar formato - SAMS')
@section('header-title', 'Reemplazar formato')
@section('header-subtitle', $plantilla->claseEquipo?->nombre ?? ('Clase #' . $plantilla->clase_equipo_id))

@section('header-actions')
<a href="{{ route('formatos.index') }}" class="pw-btn-secondary px-3 py-2 text-sm rounded-lg">
    <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>
    Volver
</a>
@endsection

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
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

    <div class="pw-card bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
        <p class="text-sm text-gray-600 mb-4">Sube el nuevo archivo Excel para la clase <strong>{{ $plantilla->claseEquipo?->nombre }}</strong>. Se reemplazará la plantilla actual.</p>
        <form action="{{ route('formatos.reemplazar', $plantilla) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nuevo archivo Excel</label>
                <input type="file" name="plantilla_excel" accept=".xlsx,.xls" class="w-full text-sm" required>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-xl">Reemplazar formato</button>
                <a href="{{ route('formatos.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300">Cancelar</a>
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
