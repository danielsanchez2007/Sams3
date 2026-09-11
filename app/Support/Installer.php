<?php

namespace App\Support;

use App\Models\Cargo;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\InstallSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PDO;
use Throwable;

class Installer
{
    /**
     * @return list<array{ok: bool, label: string, detail: string}>
     */
    public static function checks(): array
    {
        $exts = ['mbstring', 'openssl', 'pdo', 'pdo_mysql', 'fileinfo', 'tokenizer', 'json', 'ctype', 'xml'];
        $missing = array_values(array_filter($exts, fn (string $ext) => ! extension_loaded($ext)));

        $writable = [
            'storage' => storage_path(),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];
        $notWritable = [];
        foreach ($writable as $label => $path) {
            if (! is_dir($path) || ! is_writable($path)) {
                $notWritable[] = $label;
            }
        }

        return [
            [
                'ok' => PHP_VERSION_ID >= 80200,
                'label' => 'PHP 8.2 o superior',
                'detail' => 'Detectado: PHP '.PHP_VERSION,
            ],
            [
                'ok' => $missing === [],
                'label' => 'Extensiones PHP',
                'detail' => $missing === [] ? 'pdo_mysql, openssl, mbstring, json, fileinfo' : 'Faltan: '.implode(', ', $missing),
            ],
            [
                'ok' => $notWritable === [],
                'label' => 'Carpetas con permiso de escritura',
                'detail' => $notWritable === [] ? 'storage/ y bootstrap/cache/' : 'Sin escritura: '.implode(', ', $notWritable),
            ],
            [
                'ok' => is_file(base_path('vendor/autoload.php')),
                'label' => 'Dependencias PHP (vendor)',
                'detail' => is_file(base_path('vendor/autoload.php'))
                    ? 'Carpeta vendor lista'
                    : 'Falta vendor. Sube el ZIP de sams:package o ejecuta composer install',
            ],
            [
                'ok' => is_file(public_path('build/manifest.json')) || is_file(public_path('css/sams.css')),
                'label' => 'Estilos y JavaScript compilados',
                'detail' => (is_file(public_path('build/manifest.json')) || is_file(public_path('css/sams.css')))
                    ? 'public/build y/o public/css/sams.css listos'
                    : 'Faltan assets. En el PC local ejecuta npm run build',
            ],
        ];
    }

    public static function allChecksPassed(): bool
    {
        foreach (self::checks() as $check) {
            if (! $check['ok']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public static function testMysql(array $db): array
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $db['host'],
                $db['port'],
                $db['database']
            );
            new PDO($dsn, $db['username'], $db['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            return ['ok' => true, 'message' => 'Conexión MySQL correcta.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'No se pudo conectar a MySQL: '.$e->getMessage()];
        }
    }

    public static function writeEnv(array $values): void
    {
        $path = base_path('.env');
        $content = is_file($path)
            ? (string) file_get_contents($path)
            : (string) file_get_contents(base_path('.env.example'));

        foreach ($values as $key => $value) {
            $line = $key.'='.self::escapeEnv((string) $value);
            if (preg_match("/^{$key}=.*/m", $content) === 1) {
                $content = preg_replace("/^{$key}=.*/m", $line, $content, 1) ?? $content;
            } else {
                $content = rtrim($content)."\n{$line}\n";
            }
        }

        file_put_contents($path, $content);
    }

    public static function applyRuntime(array $values): void
    {
        foreach ($values as $key => $value) {
            $string = (string) $value;
            putenv($key.'='.$string);
            $_ENV[$key] = $string;
            $_SERVER[$key] = $string;
        }

        config([
            'app.env' => $values['APP_ENV'] ?? 'production',
            'app.debug' => ($values['APP_DEBUG'] ?? 'false') === 'true',
            'app.url' => $values['APP_URL'] ?? config('app.url'),
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $values['DB_HOST'],
            'database.connections.mysql.port' => $values['DB_PORT'],
            'database.connections.mysql.database' => $values['DB_DATABASE'],
            'database.connections.mysql.username' => $values['DB_USERNAME'],
            'database.connections.mysql.password' => $values['DB_PASSWORD'],
            'session.driver' => $values['SESSION_DRIVER'] ?? 'file',
            'cache.default' => $values['CACHE_STORE'] ?? 'file',
            'queue.default' => $values['QUEUE_CONNECTION'] ?? 'sync',
        ]);

        DB::purge('mysql');
        DB::setDefaultConnection('mysql');
        DB::reconnect('mysql');
    }

    public static function migrateAndSeed(array $admin): void
    {
        Artisan::call('migrate', ['--force' => true]);

        config([
            'sams.install.admin_name' => $admin['name'],
            'sams.install.admin_email' => $admin['email'],
            'sams.install.admin_password' => $admin['password'],
        ]);

        (new InstallSeeder())->run();
        self::ensureAdmin($admin);
    }

    public static function ensureAdmin(array $admin): void
    {
        $role = Role::query()->where('name', 'administrador')->first();
        $cargo = Cargo::query()->where('name', 'Administrador')->first();
        $photo = self::placeholder('user.png');
        $signature = self::placeholder('signature.png');

        $user = User::query()->where('email', $admin['email'])->first() ?? new User();
        $user->fillAccount([
            'codigo' => $user->codigo ?: 'ADM-0000',
            'name' => $admin['name'],
            'last_name' => $user->last_name ?: 'Admin',
            'email' => $admin['email'],
            'password' => $admin['password'],
            'role_id' => $role?->id,
            'cargo_id' => $cargo?->id,
            'empresa_id' => null,
            'active' => true,
            'must_change_password' => false,
            'document_type' => $user->document_type ?: 'CC',
            'document_number' => $user->document_number ?: '0000000000',
            'photo' => $user->photo ?: $photo,
            'signature' => $user->signature ?: $signature,
        ]);
        $user->save();
    }

    private static function placeholder(string $name): string
    {
        $relative = 'seed/placeholders/'.$name;
        if (! Storage::disk('public')->exists('seed/placeholders')) {
            Storage::disk('public')->makeDirectory('seed/placeholders');
        }
        if (! Storage::disk('public')->exists($relative)) {
            $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMB/axH4i0AAAAASUVORK5CYII=');
            Storage::disk('public')->put($relative, $png ?: '');
        }

        return $relative;
    }

    public static function finish(array $meta = []): void
    {
        DeployEnvironment::ensureStorageLink();
        DeployEnvironment::forgetViteHotFile();
        DeployEnvironment::markInstalled($meta);

        try {
            Artisan::call('optimize:clear');
        } catch (Throwable) {
            // ignore
        }
    }

    private static function escapeEnv(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/[\s#"\'\\\\]/', $value) === 1) {
            return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
        }

        return $value;
    }

    public static function suggestedUrl(?Request $request = null): string
    {
        try {
            $http = $request ?? request();

            return rtrim($http->getSchemeAndHttpHost().$http->getBasePath(), '/');
        } catch (Throwable) {
            return rtrim((string) config('app.url', 'https://temporal.preventionworld.org'), '/');
        }
    }
}
