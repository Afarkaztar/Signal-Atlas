<?php

/**
 * Entrypoint Serverless Vercel untuk Laravel.
 *
 * Mendukung 2 mode database di Vercel:
 * 1. Database Cloud Permanen (PostgreSQL/MySQL via env DB_CONNECTION / DB_URL / DB_HOST)
 *    -> Semua perubahan dari seluruh anggota kelompok tersimpan permanen lintas perangkat.
 * 2. Fallback SQLite (/tmp/database.sqlite yang disalin dari database/database.sqlite).
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

    $configuredConnection = getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? null);
    $hasCloudDb = ($configuredConnection && $configuredConnection !== 'sqlite')
        || getenv('DB_URL') || getenv('DATABASE_URL') || getenv('DB_HOST');

    $defaults = [
        'APP_CONFIG_CACHE' => '/tmp/config.php',
        'APP_EVENTS_CACHE' => '/tmp/events.php',
        'APP_PACKAGES_CACHE' => '/tmp/packages.php',
        'APP_ROUTES_CACHE' => '/tmp/routes.php',
        'APP_SERVICES_CACHE' => '/tmp/services.php',
        'VIEW_COMPILED_PATH' => "$storage/framework/views",
        'SESSION_DRIVER' => getenv('SESSION_DRIVER') ?: 'cookie',
        'CACHE_STORE' => getenv('CACHE_STORE') ?: 'array',
        'LOG_CHANNEL' => getenv('LOG_CHANNEL') ?: 'stderr',
    ];

    if (! $hasCloudDb) {
        $sqliteTarget = '/tmp/database.sqlite';
        $sqliteSource = __DIR__.'/../database/database.sqlite';
        if (! file_exists($sqliteTarget) && file_exists($sqliteSource)) {
            copy($sqliteSource, $sqliteTarget);
            chmod($sqliteTarget, 0666);
        }
        $defaults['DB_CONNECTION'] = 'sqlite';
        $defaults['DB_DATABASE'] = $sqliteTarget;
    }

    foreach ($defaults as $key => $val) {
        putenv("$key=$val");
        $_ENV[$key] = $_SERVER[$key] = $val;
    }
}

require __DIR__.'/../public/index.php';
