<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$vendorAutoload = __DIR__.'/../vendor/autoload.php';
if (! is_file($vendorAutoload)) {
    http_response_code(500);
    echo '<!DOCTYPE html><meta charset="utf-8"><title>SAMS</title><p>Falta la carpeta <code>vendor</code>. Sube el ZIP generado con <code>php artisan sams:package</code> o ejecuta <code>composer install --no-dev</code>.</p>';
    exit(1);
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $vendorAutoload;
require __DIR__.'/../bootstrap/deploy-prepare.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
