@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
        
        <!-- Card Principal -->
        <div class="bg-white rounded-3xl shadow-xl border border-slate-100 p-10">
            
            <!-- Logo -->
            <div class="flex justify-center mb-8">
                <img src="{{ asset($logoPrimario ?? 'images/logo-instituto.png') }}"
                     alt="Prevention World"
                     class="h-24 w-auto drop-shadow-md"
                     onerror="this.onerror=null;this.src='{{ asset('images/logo-principal.png') }}';">
            </div>

            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-slate-800">Crea tu Contraseña Segura</h2>
                <p class="text-slate-500 mt-3 text-sm">
                    Usa al menos 10 caracteres, con mayúsculas, minúsculas y un número.
                </p>
            </div>

            @if (session('warning'))
                <div class="mb-6 bg-amber-50 border border-amber-200 rounded-2xl p-4 text-amber-700 text-sm">
                    {{ session('warning') }}
                </div>
            @endif

            <form class="space-y-6" method="POST" action="{{ route('password.secure.update') }}">
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

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Nueva Contraseña</label>
                    <input name="password" type="password" required autocomplete="new-password" minlength="10" maxlength="72"
                        class="w-full bg-white border border-slate-300 rounded-2xl px-5 py-3.5 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#003087] focus:ring-2 focus:ring-blue-200 transition-all" data-pw-meter="required">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Confirmar Contraseña</label>
                    <input name="password_confirmation" type="password" required autocomplete="new-password" minlength="10"
                        class="w-full bg-white border border-slate-300 rounded-2xl px-5 py-3.5 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#003087] focus:ring-2 focus:ring-blue-200 transition-all">
                </div>

                <button type="submit"
                    class="w-full py-4 px-6 bg-[#003087] hover:bg-[#00246b] text-white font-semibold rounded-2xl transition-all duration-300 shadow-md hover:shadow-lg">
                    GUARDAR CONTRASEÑA Y CONTINUAR
                </button>
            </form>

            <!-- Cerrar Sesión -->
            <div class="mt-8 text-center">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" 
                        class="text-slate-400 hover:text-red-600 text-sm transition-colors flex items-center gap-2 mx-auto">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </div>
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
    input:focus {
        box-shadow: 0 0 0 4px rgba(0, 48, 135, 0.1);
    }
</style>
@endsection