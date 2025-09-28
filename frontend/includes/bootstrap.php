<?php
declare(strict_types=1);

require_once __DIR__ . '/init_session.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/cart_functions.php';
require_once __DIR__ . '/../../includes/orders.php';
require_once __DIR__ . '/../../includes/auth.php';
if (!defined('KIDSTORE_FRONT_URL_PREFIX')) {
    // Define the frontend URL prefix using the path helpers
    define('KIDSTORE_FRONT_URL_PREFIX', kidstore_frontend_base_uri());
}

const KIDSTORE_FRONTEND_CSRF_SESSION_KEY = 'kidstore_frontend_csrf_token';
const KIDSTORE_FRONTEND_CSRF_FIELD = 'kidstore_csrf_token';
const KIDSTORE_FRONTEND_CSRF_HEADER = 'X-Kidstore-CSRF';

if (!function_exists('kidstore_frontend_csrf_token')) {
    /**
     * Retrieve or generate a CSRF token for the current frontend session.
     */
    function kidstore_frontend_csrf_token(): string
    {
        $token = $_SESSION[KIDSTORE_FRONTEND_CSRF_SESSION_KEY] ?? null;
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $_SESSION[KIDSTORE_FRONTEND_CSRF_SESSION_KEY] = $token;
        }

        return $token;
    }
}

if (!function_exists('kidstore_frontend_csrf_validate')) {
    /**
     * Validate a CSRF token against the session value.
     */
    function kidstore_frontend_csrf_validate(?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $sessionToken = $_SESSION[KIDSTORE_FRONTEND_CSRF_SESSION_KEY] ?? null;
        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }
}

if (!function_exists('kidstore_frontend_extract_request_csrf_token')) {
    /**
     * Attempt to extract a CSRF token from common request locations.
     */
    function kidstore_frontend_extract_request_csrf_token(): ?string
    {
        $candidates = [
            $_SERVER['HTTP_' . str_replace('-', '_', strtoupper(KIDSTORE_FRONTEND_CSRF_HEADER))] ?? null,
            $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null,
            $_POST[KIDSTORE_FRONTEND_CSRF_FIELD] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }
}

// Ensure a token exists for the current session.
kidstore_frontend_csrf_token();

