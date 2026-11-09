<?php

/**
 * Entrypoint Serverless Vercel untuk Laravel.
 *
 * Karena filesystem Vercel bersifat read-only kecuali /tmp, script ini:
 * 1. Menyiapkan direktori storage & bootstrap cache di /tmp
 * 2. Menyalin database SQLite awal (beserta seed data) ke /tmp/database.sqlite
 *    bila menggunakan SQLite, sehingga operasi tulis (CRUD) tetap berjalan.
 */
if (isset($_ENV['VERCEL']) || isset($_SERVER['VERCEL']) || getenv('VERCEL')) {
    $storage = '/tmp/storage';
    foreach ([
        "$storage/app/public/reports",
        "$storage/framework/cache/data",
        "$storage/framework/sessions",
        "$storage/framework/views",
        "$storage/logs",
    ] as $dir) {
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    $sqliteTarget = '/tmp/database.sqlite';
    $sqliteSource = __DIR__.'/../database/database.sqlite';
    if (! file_exists($sqliteTarget) && file_exists($sqliteSource)) {
        copy($sqliteSource, $sqliteTarget);
        chmod($sqliteTarget, 0666);
    }

    foreach ([
        'APP_CONFIG_CACHE' => '/tmp/config.php',
        'APP_EVENTS_CACHE' => '/tmp/events.php',
        'APP_PACKAGES_CACHE' => '/tmp/packages.php',
        'APP_ROUTES_CACHE' => '/tmp/routes.php',
        'APP_SERVICES_CACHE' => '/tmp/services.php',
        'VIEW_COMPILED_PATH' => "$storage/framework/views",
        'DB_CONNECTION' => getenv('DB_CONNECTION') ?: 'sqlite',
        'DB_DATABASE' => getenv('DB_DATABASE') ?: $sqliteTarget,
        'SESSION_DRIVER' => getenv('SESSION_DRIVER') ?: 'cookie',
        'CACHE_STORE' => getenv('CACHE_STORE') ?: 'array',
        'LOG_CHANNEL' => getenv('LOG_CHANNEL') ?: 'stderr',
    ] as $key => $val) {
        putenv("$key=$val");
        $_ENV[$key] = $_SERVER[$key] = $val;
    }
}

require __DIR__.'/../public/index.php';
