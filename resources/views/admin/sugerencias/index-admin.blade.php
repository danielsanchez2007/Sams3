@extends('layouts.admin-layout')

@section('title', 'Sugerencias - SAMS')
@section('header-title', 'Sugerencias y avisos')
@section('header-subtitle', 'Los avisos aparecen en rojo. Cuando la empresa cargue una imagen de cumplimiento, pasan a verde.')

@section('content')
<div class="max-w-3xl mx-auto">
    @if(session('success'))
    <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm flex items-center gap-2">
        <i data-lucide="check-circle" class="w-5 h-5 shrink-0"></i>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm flex items-center gap-2">
        <i data-lucide="alert-circle" class="w-5 h-5 shrink-0"></i>
        {{ session('error') }}
    </div>
    @endif

    <div class="pw-card bg-white rounded-xl shadow-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <i data-lucide="plus-circle" class="w-5 h-5 text-teal-600"></i>
            Nuevo aviso
        </h2>
        <form action="{{ route('sugerencias.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                @if($empresaActiva ?? null)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Empresa</label>
                    <div class="px-3 py-2 bg-gray-100 rounded-lg text-gray-800 font-medium">{{ $empresaActiva->nombre }}</div>
                    <input type="hidden" name="empresa_id" value="{{ $empresaActiva->id }}">
                </div>
                @else
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Empresa *</label>
                    <select name="empresa_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        <option value="">Seleccione empresa...</option>
                        @foreach($empresas as $e)
                        <option value="{{ $e->id }}">{{ $e->nombre }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">O entra a una empresa para que el aviso vaya directamente a esa empresa.</p>
                </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mensaje (opcional)</label>
                    <textarea name="mensaje" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500" placeholder="Texto del aviso..."></textarea>
                </div>
                <p class="text-xs text-gray-600 flex items-center gap-1">
                    <i data-lucide="info" class="w-4 h-4 text-teal-600"></i>
                    El aviso aparecerá en <span class="font-semibold text-red-600">rojo</span>. La empresa deberá cargar una imagen para cumplirlo y pasará a <span class="font-semibold text-green-600">verde</span>.
                </p>
                <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg inline-flex items-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Enviar aviso
                </button>
            </div>
        </form>
    </div>

    <div class="pw-card bg-white rounded-xl shadow-lg p-4">
        <h3 class="text-sm font-semibold text-gray-800 mb-3 flex items-center gap-2">
            <i data-lucide="history" class="w-4 h-4 text-teal-600"></i>
            Historial de avisos
        </h3>
        @if($avisos->isEmpty())
        <p class="text-gray-500 text-sm">No hay avisos.</p>
        @else
        <div class="space-y-2 max-h-64 overflow-y-auto">
            @foreach($avisos as $aviso)
            <div class="p-2 rounded-lg border {{ $aviso->tipo === 'observacion' ? 'border-red-200 bg-red-50' : 'border-green-200 bg-green-50' }}">
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <span class="font-medium text-sm">{{ $aviso->empresa?->nombre }}</span>
                    <span class="text-xs font-semibold {{ $aviso->tipo === 'observacion' ? 'text-red-700' : 'text-green-700' }}">
                        {{ $aviso->tipo === 'observacion' ? 'Pendiente (rojo)' : 'Cumplido (verde)' }}
                    </span>
                </div>
                @if($aviso->mensaje)
                <p class="text-xs text-gray-600 mt-0.5">{{ Str::limit($aviso->mensaje, 100) }}</p>
                @endif
                <p class="text-xs text-gray-500 mt-0.5">{{ $aviso->created_at->format('d/m/Y H:i') }} • {{ $aviso->creador?->name ?? 'Sistema' }}</p>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
