<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/orders.php';
require_once __DIR__ . '/../includes/admin.php';
if (!defined('KIDSTORE_ADMIN_URL_PREFIX')) {
    // $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    // $prefix = '';
    // if (($pos = strpos($scriptName, '/backend/')) !== false) {
    //     $relative = substr($scriptName, $pos + strlen('/backend/'));
    //     $depth = substr_count(trim($relative, '/'), '/');
    //     if ($depth > 0) {
    //         $prefix = str_repeat('../', $depth);
    //     }
    // }
    // define('KIDSTORE_ADMIN_URL_PREFIX', $prefix);
    define('KIDSTORE_ADMIN_URL_PREFIX', kidstore_backend_base_uri());
}

const KIDSTORE_CSRF_SESSION_KEY = 'kidstore_admin_csrf_token';

/**
 * Retrieve or generate the CSRF token for the current session.
 */
function kidstore_csrf_token(): string
{
    $token = $_SESSION[KIDSTORE_CSRF_SESSION_KEY] ?? null;
    if (!is_string($token) || $token === '') {
        $token = bin2hex(random_bytes(32));
        $_SESSION[KIDSTORE_CSRF_SESSION_KEY] = $token;
    }

    return $token;
}

/**
 * Validate a provided CSRF token against the current session value.
 */
function kidstore_csrf_validate(?string $token): bool
{
    if (!is_string($token) || $token === '') {
        return false;
    }

    $sessionToken = $_SESSION[KIDSTORE_CSRF_SESSION_KEY] ?? null;
    if (!is_string($sessionToken) || $sessionToken === '') {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

