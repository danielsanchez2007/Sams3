@extends('layouts.app')

@section('content')
@php
    $logosCfg = $logosCfg ?? ['primario' => 'images/logo-instituto.png'];
    $logoPrimario = $logosCfg['primario'] ?? 'images/logo-instituto.png';
    $logoFallback = 'images/logo-principal.png';
@endphp

<div class="min-h-screen bg-slate-50 flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">

        <div class="mb-8 flex justify-center">
            <a href="{{ route('sistema.info') }}"
                class="inline-flex items-center gap-2 text-slate-500 hover:text-slate-700 transition-colors text-sm font-medium">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Volver a información del sistema</span>
            </a>
        </div>

        <div class="bg-white rounded-3xl shadow-xl border border-slate-100 p-10">

            <div class="flex justify-center mb-10">
                <img src="{{ asset($logoPrimario) }}"
                     alt="Prevention World"
                     class="h-28 w-auto drop-shadow-md"
                     onerror="this.onerror=null;this.src='{{ asset($logoFallback) }}';">
            </div>

            <div class="text-center mb-9">
                <h2 class="text-3xl font-bold text-slate-800">Iniciar Sesión</h2>
                <p class="text-slate-500 mt-2">Bienvenido a Prevention World</p>
            </div>

            <form id="login-form" class="space-y-6" action="{{ url()->current() }}" method="POST">
                @csrf

                @if (session('success'))
                    <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 text-emerald-700 text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('warning'))
                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-amber-700 text-sm">
                        {{ session('warning') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 rounded-2xl p-4 text-red-700 text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="space-y-5">
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-600 mb-2 tracking-wider">CORREO ELECTRÓNICO</label>
                        <input id="email" name="email" type="email" autocomplete="username" required value="{{ old('email') }}"
                            class="w-full bg-white border border-slate-300 rounded-2xl px-5 py-3.5 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#003087] focus:ring-2 focus:ring-blue-200 transition-all">
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-semibold text-slate-600 mb-2 tracking-wider">CONTRASEÑA</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="w-full bg-white border border-slate-300 rounded-2xl px-5 py-3.5 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#003087] focus:ring-2 focus:ring-blue-200 transition-all">
                    </div>
                </div>

                <button id="login-submit" type="submit"
                    class="w-full py-4 px-6 bg-[#003087] hover:bg-[#00246b] text-white font-semibold rounded-2xl transition-all duration-300 flex items-center justify-center gap-3 shadow-md hover:shadow-lg disabled:opacity-70 disabled:cursor-wait">
                    <i data-lucide="log-in" class="w-5 h-5"></i>
                    <span>INICIAR SESIÓN</span>
                </button>

                @if (config('sams.allow_registration', false))
                    <p class="text-center text-sm text-slate-500 pt-2">
                        ¿No tienes cuenta?
                        <a href="{{ route('register') }}" class="font-semibold text-[#003087] hover:underline">Regístrate</a>
                    </p>
                @endif
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        const form = document.getElementById('login-form');
        const btn = document.getElementById('login-submit');
        if (!form || !btn) return;

        form.addEventListener('submit', function (event) {
            if (btn.dataset.loading === '1') {
                event.preventDefault();
                return;
            }
            btn.dataset.loading = '1';
            btn.disabled = true;
            const label = btn.querySelector('span');
            if (label) label.textContent = 'INGRESANDO…';
        });
    })();
</script>
@endsection
