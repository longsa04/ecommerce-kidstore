<?php
/**
 * URL and path helpers that keep frontend and backend links working
 * regardless of the installation directory.
 */
declare(strict_types=1);

if (!defined('KIDSTORE_APP_BASE_URI')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = '/';

    foreach (['/frontend/', '/backend/'] as $needle) {
        if ($scriptName !== '' && ($pos = strpos($scriptName, $needle)) !== false) {
            $base = substr($scriptName, 0, $pos + 1);
            break;
        }
    }

    if ($base === '' || $base[0] !== '/') {
        $base = '/' . ltrim($base, '/');
    }

    if (substr($base, -1) !== '/') {
        $base .= '/';
    }

    define('KIDSTORE_APP_BASE_URI', $base);
}

if (!function_exists('kidstore_trailing_slash')) {
    function kidstore_trailing_slash(string $path): string
    {
        return rtrim($path, '/') . '/';
    }
}

if (!function_exists('kidstore_app_base_uri')) {
    function kidstore_app_base_uri(): string
    {
        return KIDSTORE_APP_BASE_URI;
    }
}

if (!function_exists('kidstore_frontend_base_uri')) {
    function kidstore_frontend_base_uri(): string
    {
        $base = kidstore_app_base_uri();
        return kidstore_trailing_slash($base === '/' ? '/frontend' : $base . 'frontend');
    }
}

if (!function_exists('kidstore_backend_base_uri')) {
    function kidstore_backend_base_uri(): string
    {
        $base = kidstore_app_base_uri();
        return kidstore_trailing_slash($base === '/' ? '/backend' : $base . 'backend');
    }
}

if (!function_exists('kidstore_frontend_url')) {
    function kidstore_frontend_url(string $path = ''): string
    {
        $base = kidstore_frontend_base_uri();
        if ($path === '' || $path === '/') {
            return $base;
        }

        return $base . ltrim($path, '/');
    }
}

if (!function_exists('kidstore_backend_url')) {
    function kidstore_backend_url(string $path = ''): string
    {
        $base = kidstore_backend_base_uri();
        if ($path === '' || $path === '/') {
            return $base;
        }

        return $base . ltrim($path, '/');
    }
}