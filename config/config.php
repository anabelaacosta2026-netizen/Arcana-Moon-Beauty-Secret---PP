<?php
/**
 * ARCANA — Global Configuration & Environment Loader
 */

// Define application root directory
define('APP_ROOT', dirname(__DIR__));

// Simple and lightweight .env loader
if (file_exists(APP_ROOT . '/.env')) {
    $lines = file(APP_ROOT . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            // Quitar comillas si las tiene
            if ((str_starts_with($v, '"') && str_ends_with($v, '"')) || (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
                $v = substr($v, 1, -1);
            }
            if (!isset($_ENV[$k])) {
                $_ENV[$k] = $v;
                putenv("{$k}={$v}");
            }
        }
    }
}

/**
 * Obtiene una variable de entorno o su valor por defecto
 */
function env(string $key, $default = null) {
    $val = $_ENV[$key] ?? getenv($key);
    if ($val === false || $val === null || $val === '') {
        return $default;
    }
    if (strtolower($val) === 'true') return true;
    if (strtolower($val) === 'false') return false;
    return $val;
}

// Application Environment: 'development' or 'production'
define('APP_ENV', env('APP_ENV', 'development'));

// Base URL (auto-detected if not specified)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseUrl = rtrim($protocol . $host . $scriptDir, '/');
define('BASE_URL', $baseUrl);

// Database Driver: 'mysql' (conexión directa a la BD de phpMyAdmin)
define('DB_DRIVER', env('DB_DRIVER', 'mysql')); 

// JSON Database Configuration
define('JSON_DB_PATH', APP_ROOT . '/database/arcana_db.json');

// SQLite Configuration
define('SQLITE_PATH', APP_ROOT . '/database/arcana.sqlite');

// MySQL Configuration (Conexión directa: arcanaestetica_db / user: root)
$defaultDbName = (env('DB_NAME') && env('DB_NAME') !== 'arcana_db') ? env('DB_NAME') : 'arcanaestetica_db';
$defaultDbUser = env('DB_USER') ?: 'root';
$defaultDbPass = (env('DB_PASS') !== null) ? (string)env('DB_PASS') : '';

define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_NAME', $defaultDbName);
define('DB_USER', $defaultDbUser);
define('DB_PASS', $defaultDbPass);
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_CHARSET', 'utf8mb4');

// Timezone and Locale
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Error reporting based on environment
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}
