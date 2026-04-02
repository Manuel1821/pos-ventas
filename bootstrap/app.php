<?php

declare(strict_types=1);

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/env.php';

$projectRoot = dirname(__DIR__);

app_load_env($projectRoot);

$appEnv = (string) ($_ENV['APP_ENV'] ?? 'development');
if (!in_array($appEnv, ['development', 'production'], true)) {
    $appEnv = 'development';
}

$envConfigPath = $projectRoot . '/config/environments/' . $appEnv . '.php';
$envConfig = is_file($envConfigPath) ? require $envConfigPath : [];

$baseConfigPath = $projectRoot . '/config/app.php';
$baseConfig = is_file($baseConfigPath) ? require $baseConfigPath : [];

$config = array_replace_recursive($baseConfig, $envConfig);

// Config adicional (sin acoplar el MVP a múltiples archivos).
$menuConfigPath = $projectRoot . '/config/menu.php';
$menuConfig = is_file($menuConfigPath) ? require $menuConfigPath : [];
$config = array_replace_recursive($config, $menuConfig);

// Base URL para que el panel funcione en subcarpetas (ej: https://dominio.com/manuel).
// Si en `.env` no se definió `APP_URL`, la inferimos desde `SCRIPT_NAME`.
if (php_sapi_name() !== 'cli') {
    $configuredUrl = (string) ($config['app']['url'] ?? '');
    if ($configuredUrl === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = rtrim(str_replace('\\', '/', (string) dirname($scriptName)), '/');
        if ($basePath === '.' || $basePath === '\\' || $basePath === '') {
            $basePath = '';
        }
        if ($host !== '') {
            $config['app']['url'] = $scheme . '://' . $host . $basePath;
        }
    }
}

// Tiempo
$timezone = (string) ($config['app']['timezone'] ?? 'UTC');
date_default_timezone_set($timezone);

// Manejo de errores
$debug = (bool) ($config['app']['debug'] ?? false);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', $projectRoot . '/storage/logs/php-error.log');
error_reporting($debug ? E_ALL : E_ALL & ~E_NOTICE & ~E_STRICT);

// Sesiones
if (php_sapi_name() !== 'cli') {
    session_name((string) ($config['session']['name'] ?? 'POSSESSION'));
    $cookieParams = [
        'lifetime' => (int) ($config['session']['lifetime'] ?? 0),
        'path' => (string) ($config['session']['path'] ?? '/'),
        'domain' => (string) ($config['session']['domain'] ?? ''),
        'secure' => (bool) ($config['session']['secure'] ?? false),
        'httponly' => true,
        'samesite' => (string) ($config['session']['samesite'] ?? 'Lax'),
    ];
    session_set_cookie_params($cookieParams);

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

// Exponer config para plantillas simples.
// (Evita dependencias extra y mantiene el MVP)
$GLOBALS['config'] = $config;
$GLOBALS['app_base_path'] = $projectRoot;

