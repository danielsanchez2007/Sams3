@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 pw-card p-8 rounded-2xl">
        <div>
            <h2 class="mt-2 text-center text-3xl font-extrabold text-gray-900">
                Restablecer Contraseña
            </h2>
        </div>

        <form class="mt-8 space-y-6" action="{{ route('password.update') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="space-y-1">
                <label for="email" class="block text-sm font-medium text-gray-700">Correo Electrónico</label>
                <input id="email" name="email" type="email" required value="{{ $email ?? old('email') }}"
                    class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="space-y-1">
                <label for="password" class="block text-sm font-medium text-gray-700">Nueva Contraseña</label>
                <input id="password" name="password" type="password" required
                    class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div class="space-y-1">
                <label for="password-confirm" class="block text-sm font-medium text-gray-700">Confirmar Contraseña</label>
                <input id="password-confirm" name="password_confirmation" type="password" required
                    class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <button type="submit" class="pw-btn-login group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Guardar contraseña
            </button>
        </form>
    </div>
</div>
@endsection
