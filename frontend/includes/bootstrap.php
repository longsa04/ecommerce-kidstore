<?php
declare(strict_types=1);

require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/cart_functions.php';
require_once __DIR__ . '/../../includes/orders.php';
require_once __DIR__ . '/../../includes/auth.php';
if (!defined('KIDSTORE_FRONT_URL_PREFIX')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $prefix = '';
    if (($pos = strpos($scriptName, '/frontend/')) !== false) {
        $relative = substr($scriptName, $pos + strlen('/frontend/'));
        $depth = substr_count(trim($relative, '/'), '/');
        if ($depth > 0) {
            $prefix = str_repeat('../', $depth);
        }
    }
    define('KIDSTORE_FRONT_URL_PREFIX', $prefix);
}

