<?php

namespace App\Http\Controllers;

use App\Support\DeployEnvironment;
use App\Support\Installer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class InstallController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (DeployEnvironment::isInstalled() && ! $request->boolean('reinstall')) {
            return redirect('/inicio');
        }

        return view('install.show', [
            'checks' => Installer::checks(),
            'ready' => Installer::allChecksPassed(),
            'appUrl' => Installer::suggestedUrl($request),
            'secure' => $request->isSecure(),
            'alreadyInstalled' => DeployEnvironment::isInstalled(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (DeployEnvironment::isInstalled() && ! $request->boolean('reinstall')) {
            return redirect('/inicio');
        }

        if (! Installer::allChecksPassed()) {
            return back()->with('error', 'El servidor aún no cumple los requisitos. Revisa la lista en rojo.');
        }

        $data = $request->validate([
            'app_url' => ['required', 'url', 'max:255'],
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'numeric'],
            'db_database' => ['required', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'mode' => ['required', 'in:import,create'],
            'admin_name' => ['required_if:mode,create', 'nullable', 'string', 'max:120'],
            'admin_email' => ['required_if:mode,create', 'nullable', 'email', 'max:255'],
            'admin_password' => ['required_if:mode,create', 'nullable', 'string', 'min:10'],
        ], [
            'admin_name.required_if' => 'Escribe el nombre del administrador.',
            'admin_email.required_if' => 'Escribe el correo del administrador.',
            'admin_password.required_if' => 'La contraseña del administrador es obligatoria (mínimo 10 caracteres).',
        ]);

        $secure = $request->isSecure() || str_starts_with($data['app_url'], 'https://');
        $envValues = [
            'APP_NAME' => 'SAMS',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => rtrim($data['app_url'], '/'),
            'APP_TIMEZONE' => 'America/Bogota',
            'APP_LOCALE' => 'es',
            'APP_FALLBACK_LOCALE' => 'es',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => (string) $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $data['db_password'] ?? '',
            'SESSION_DRIVER' => 'file',
            'SESSION_LIFETIME' => '120',
            'SESSION_ENCRYPT' => 'true',
            'SESSION_SECURE_COOKIE' => $secure ? 'true' : 'false',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'sync',
            'LOG_LEVEL' => 'error',
            'MAIL_MAILER' => 'log',
            'SAMS_ALLOW_REGISTRATION' => 'false',
            'SAMS_LIGHTWEIGHT_UI' => 'true',
        ];

        $test = Installer::testMysql([
            'host' => $envValues['DB_HOST'],
            'port' => $envValues['DB_PORT'],
            'database' => $envValues['DB_DATABASE'],
            'username' => $envValues['DB_USERNAME'],
            'password' => $envValues['DB_PASSWORD'],
        ]);
        if (! $test['ok']) {
            return back()->withInput()->with('error', $test['message']);
        }

        try {
            Installer::writeEnv($envValues);
            Installer::applyRuntime($envValues);

            if ($data['mode'] === 'create') {
                Installer::migrateAndSeed([
                    'name' => $data['admin_name'],
                    'email' => $data['admin_email'],
                    'password' => $data['admin_password'],
                ]);
            } elseif (! DeployEnvironment::databaseLooksReady()) {
                return back()->withInput()->with(
                    'error',
                    'La base de datos no tiene las tablas de SAMS. Impórtala en phpMyAdmin o elige “Crear tablas nuevas”.'
                );
            }

            Installer::finish([
                'mode' => $data['mode'],
                'database' => $envValues['DB_DATABASE'],
            ]);
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'No se pudo terminar la instalación: '.$e->getMessage());
        }

        return redirect('/inicio')->with('success', 'SAMS quedó instalado y conectado a la base de datos.');
    }
}
