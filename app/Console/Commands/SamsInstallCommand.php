<?php

namespace App\Console\Commands;

use App\Support\DeployEnvironment;
use App\Support\Installer;
use Illuminate\Console\Command;

class SamsInstallCommand extends Command
{
    protected $signature = 'sams:install
        {--url= : URL pública del sitio}
        {--host=127.0.0.1 : Host MySQL}
        {--port=3306 : Puerto MySQL}
        {--database= : Nombre de la base}
        {--username= : Usuario MySQL}
        {--password= : Contraseña MySQL}
        {--mode=import : import (SQL ya cargado) o create (migrar y sembrar)}
        {--admin-name=Administrador : Nombre del admin si mode=create}
        {--admin-email= : Correo del admin si mode=create}
        {--admin-password= : Contraseña del admin si mode=create}';

    protected $description = 'Conectar SAMS a MySQL para un despliegue sin Node en el servidor';

    public function handle(): int
    {
        foreach (Installer::checks() as $check) {
            $this->{$check['ok'] ? 'info' : 'error'}(($check['ok'] ? 'OK' : 'FALTA').' '.$check['label'].' — '.$check['detail']);
            if (! $check['ok']) {
                return self::FAILURE;
            }
        }

        $database = (string) ($this->option('database') ?: $this->ask('Nombre de la base MySQL'));
        $username = (string) ($this->option('username') ?: $this->ask('Usuario MySQL'));
        $password = (string) ($this->option('password') ?: $this->secret('Contraseña MySQL'));
        $host = (string) $this->option('host');
        $port = (string) $this->option('port');
        $mode = (string) $this->option('mode');
        $url = (string) ($this->option('url') ?: config('app.url'));

        $test = Installer::testMysql(compact('host', 'port', 'database', 'username', 'password'));
        if (! $test['ok']) {
            $this->error($test['message']);

            return self::FAILURE;
        }

        $envValues = [
            'APP_NAME' => 'SAMS',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => rtrim($url, '/'),
            'APP_TIMEZONE' => 'America/Bogota',
            'APP_LOCALE' => 'es',
            'APP_FALLBACK_LOCALE' => 'es',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $host,
            'DB_PORT' => $port,
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password,
            'SESSION_DRIVER' => 'file',
            'SESSION_SECURE_COOKIE' => str_starts_with($url, 'https://') ? 'true' : 'false',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'sync',
            'LOG_LEVEL' => 'error',
            'SAMS_ALLOW_REGISTRATION' => 'false',
        ];

        Installer::writeEnv($envValues);
        Installer::applyRuntime($envValues);

        if ($mode === 'create') {
            $adminEmail = (string) ($this->option('admin-email') ?: $this->ask('Correo del administrador'));
            $adminPassword = (string) ($this->option('admin-password') ?: $this->secret('Contraseña del administrador'));
            Installer::migrateAndSeed([
                'name' => (string) $this->option('admin-name'),
                'email' => $adminEmail,
                'password' => $adminPassword,
            ]);
        } elseif (! DeployEnvironment::databaseLooksReady()) {
            $this->error('La base no tiene las tablas de SAMS. Importa el SQL o usa --mode=create.');

            return self::FAILURE;
        }

        Installer::finish(['mode' => $mode, 'via' => 'artisan']);
        $this->info('SAMS quedó conectado. Abre '.$url.'/inicio');

        return self::SUCCESS;
    }
}
