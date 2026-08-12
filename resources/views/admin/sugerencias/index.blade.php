@extends('layouts.admin-layout')

@section('title', 'Sugerencias - SAMS')
@section('header-title', 'Sugerencias y avisos')
@section('header-subtitle', 'Avisos que el administrador deja para tu empresa. Para cumplir un aviso, carga una imagen.')

@section('content')
<div class="max-w-4xl mx-auto">
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

    <div class="pw-card bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <i data-lucide="message-square" class="w-5 h-5 text-teal-600"></i>
            Avisos recibidos
        </h2>

        @if($avisos->isEmpty())
        <div class="text-center py-12 text-gray-500">
            <i data-lucide="inbox" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
            <p>No hay avisos para tu empresa.</p>
        </div>
        @else
        <div class="space-y-4">
            @foreach($avisos as $aviso)
            <div class="p-4 rounded-xl border {{ $aviso->tipo === 'observacion' ? 'border-red-200 bg-red-50' : 'border-green-200 bg-green-50' }}">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 {{ $aviso->tipo === 'observacion' ? 'bg-red-200 text-red-700' : 'bg-green-200 text-green-700' }}">
                        <i data-lucide="{{ $aviso->tipo === 'observacion' ? 'alert-circle' : 'check-circle' }}" class="w-5 h-5"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                            <span class="font-medium {{ $aviso->tipo === 'observacion' ? 'text-red-700' : 'text-green-700' }}">
                                {{ $aviso->tipo === 'observacion' ? 'Pendiente (rojo)' : 'Cumplido (verde)' }}
                            </span>
                            <span>•</span>
                            <span>{{ $aviso->created_at->format('d/m/Y H:i') }}</span>
                            @if($aviso->creador)
                            <span>por {{ $aviso->creador->name }}</span>
                            @endif
                        </div>
                        @if($aviso->mensaje)
                        <p class="text-gray-800 mb-3">{{ $aviso->mensaje }}</p>
                        @endif

                        @if($aviso->tipo === 'observacion')
                        <form action="{{ route('sugerencias.cumplir', $aviso) }}" method="POST" enctype="multipart/form-data" class="flex flex-wrap items-end gap-2 mt-2">
                            @csrf
                            <div class="flex-1 min-w-[200px]">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Cargar imagen para cumplir *</label>
                                <input type="file" name="imagen" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" required class="block w-full text-sm text-gray-600 file:mr-2 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                                <p class="text-xs text-gray-500 mt-0.5">JPG, PNG, GIF o WebP. Máx 5MB.</p>
                            </div>
                            <button type="submit" class="pw-btn-primary px-4 py-2 rounded-lg text-sm inline-flex items-center gap-1">
                                <i data-lucide="upload" class="w-4 h-4"></i>
                                Cumplir aviso
                            </button>
                        </form>
                        @elseif($aviso->imagen_cumplimiento_path)
                        <div class="mt-2">
                            <p class="text-xs text-gray-600 mb-1">Imagen de cumplimiento:</p>
                            <a href="{{ asset('storage/' . $aviso->imagen_cumplimiento_path) }}" target="_blank" class="inline-block">
                                <img src="{{ asset('storage/' . $aviso->imagen_cumplimiento_path) }}" alt="Cumplimiento" class="max-h-24 rounded-lg border border-gray-200 hover:opacity-90">
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
