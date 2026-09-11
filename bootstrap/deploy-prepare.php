<?php

/**
 * Preparación mínima antes de arrancar Laravel.
 * Permite subir el sistema a un hosting PHP sin pasos extra de Node.
 */
$base = dirname(__DIR__);
$public = $base . DIRECTORY_SEPARATOR . 'public';
$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$isLocalHost = $host === ''
    || str_contains($host, 'localhost')
    || str_contains($host, '127.0.0.1')
    || str_ends_with($host, '.test')
    || str_ends_with($host, '.local');

$hot = $public . DIRECTORY_SEPARATOR . 'hot';
if (! $isLocalHost && is_file($hot)) {
    @unlink($hot);
}

$envPath = $base . DIRECTORY_SEPARATOR . '.env';
$examplePath = $base . DIRECTORY_SEPARATOR . '.env.example';
if (! is_file($envPath) && is_file($examplePath)) {
    @copy($examplePath, $envPath);
}

if (is_file($envPath)) {
    $env = (string) file_get_contents($envPath);
    if (preg_match('/^APP_KEY=\s*$/m', $env) === 1) {
        $key = 'base64:' . base64_encode(random_bytes(32));
        $env = preg_replace('/^APP_KEY=\s*$/m', 'APP_KEY=' . $key, $env, 1) ?? $env;
        @file_put_contents($envPath, $env);
    }
}

$dirs = [
    $base . '/storage/app/public',
    $base . '/storage/app/private',
    $base . '/storage/app/private/exports',
    $base . '/storage/framework/cache/data',
    $base . '/storage/framework/sessions',
    $base . '/storage/framework/views',
    $base . '/storage/logs',
    $base . '/bootstrap/cache',
];
foreach ($dirs as $dir) {
    if (! is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}
