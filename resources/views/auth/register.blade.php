@extends('layouts.app')

@section('content')
@php
    $logosCfg = $logosCfg ?? ['primario' => 'images/logo-instituto.png'];
    $logoPrimario = $logosCfg['primario'] ?? 'images/logo-instituto.png';
@endphp

<div class="min-h-screen bg-slate-50 flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
        
        <!-- Botón Volver -->
        <div class="mb-8 flex justify-center">
            <a href="{{ route('sistema.info') }}" 
                class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-700 transition-colors text-sm font-medium">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Volver a información del sistema</span>
            </a>
        </div>

        <!-- Card Principal -->
        <div class="bg-white rounded-3xl shadow-xl border border-slate-100 p-10">
            
            <!-- Logo -->
            <div class="flex justify-center mb-8">
                <img src="{{ asset($logoPrimario) }}" 
                     alt="Prevention World" 
                     class="h-24 w-auto drop-shadow-md">
            </div>

            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-slate-800">Crear Cuenta</h2>
                <p class="text-slate-500 mt-2">Únete a Prevention World</p>
            </div>

            <form class="space-y-5" action="{{ route('register') }}" method="POST">
                @csrf

                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 rounded-2xl p-4 text-red-700 text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-2">Nombre</label>
                        <input name="name" value="{{ old('name') }}" required maxlength="255"
                            class="w-full bg-white border border-slate-300 rounded-2xl px-5 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#003087] focus:ring-2 focus:ring-blue-200 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-2">Apellido</label>
                        <input name="last_name" value="{{ old('last_name') }}" maxlength="255"
                            class="w-full bg-white border border-slate-300 rounded-2xl px-5 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#003087] focus:ring-2 focus:ring-blue-200 transition-all">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Correo Electrónico</label>
                    <input name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                        class="w-full bg-white border border-slate-300 rounded-2xl px-5 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#003087] focus:ring-2 focus:ring-blue-200 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Empresa</label>
                    <select name="empresa_id" required
                        class="w-full bg-white border border-slate-300 rounded-2xl px-5 py-3 text-slate-800 focus:outline-none focus:border-[#003087] focus:ring-2 focus:ring-blue-200 transition-all">
                        <option value="" disabled selected>— Selecciona una empresa —</option>
                        @foreach ($empresas as $e)
                            <option value="{{ $e->id }}" {{ (string) old('empresa_id') === (string) $e->id ? 'selected' : '' }}>
                                {{ $e->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Contraseña Provisional</label>
                    <input name="password" type="password" required autocomplete="new-password" minlength="10" maxlength="72"
                        class="w-full bg-white border border-slate-300 rounded-2xl px-5 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#003087] focus:ring-2 focus:ring-blue-200 transition-all" data-pw-meter="required">
                    <p class="pw-hint mt-2">Mínimo 10 caracteres, con mayúscula, minúscula y un número.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Confirmar Contraseña</label>
                    <input name="password_confirmation" type="password" required autocomplete="new-password" minlength="10" maxlength="72"
                        class="w-full bg-white border border-slate-300 rounded-2xl px-5 py-3 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#003087] focus:ring-2 focus:ring-blue-200 transition-all">
                </div>

                <button type="submit"
                    class="w-full mt-4 py-4 px-6 bg-[#003087] hover:bg-[#00246b] text-white font-semibold rounded-2xl transition-all duration-300 flex items-center justify-center gap-3 shadow-md hover:shadow-lg">
                    <span>CREAR CUENTA</span>
                </button>
            </form>

            <p class="text-center mt-6 text-sm">
                <a href="{{ route('login') }}" 
                   class="text-[#003087] hover:text-blue-700 font-medium transition-colors">
                    ¿Ya tienes cuenta? Inicia sesión
                </a>
            </p>
        </div>

        <!-- Footer -->
        <p class="text-center text-slate-400 text-xs mt-10">
            © {{ date('Y') }} Instituto Prevention World QHSE S.A.S.<br>
            Todos los derechos reservados
        </p>
    </div>
</div>

<script>
    lucide.createIcons();
</script>

<style>
    input:focus, select:focus {
        box-shadow: 0 0 0 4px rgba(0, 48, 135, 0.1);
    }
</style>
@endsection