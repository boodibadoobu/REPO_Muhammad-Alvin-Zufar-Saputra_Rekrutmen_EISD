<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

// Only disposable Laravel caches live in /tmp. Photos use object storage.
$temporaryStorage = sys_get_temp_dir().'/pleasefix';
foreach (['framework/views', 'framework/cache/data', 'framework/sessions', 'logs', 'bootstrap/cache'] as $directory) {
    $path = $temporaryStorage.'/'.$directory;
    if (! is_dir($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
        throw new RuntimeException('Cannot initialize temporary Laravel storage.');
    }
}

foreach ([
    'APP_CONFIG_CACHE' => 'config.php',
    'APP_SERVICES_CACHE' => 'services.php',
    'APP_PACKAGES_CACHE' => 'packages.php',
    'APP_ROUTES_CACHE' => 'routes.php',
    'APP_EVENTS_CACHE' => 'events.php',
] as $key => $file) {
    $value = $temporaryStorage.'/bootstrap/cache/'.$file;
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}

$app = require __DIR__.'/../bootstrap/app.php';
$app->useStoragePath($temporaryStorage);
$app->handleRequest(Request::capture());
