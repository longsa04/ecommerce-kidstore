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
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $prefix = '';
    if (($pos = strpos($scriptName, '/backend/')) !== false) {
        $relative = substr($scriptName, $pos + strlen('/backend/'));
        $depth = substr_count(trim($relative, '/'), '/');
        if ($depth > 0) {
            $prefix = str_repeat('../', $depth);
        }
    }
    define('KIDSTORE_ADMIN_URL_PREFIX', $prefix);
}

