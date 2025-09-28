<?php
/**
 * Database connection helper for Kid Store project.
 */
declare(strict_types=1);

require_once __DIR__ . '/env.php';

if (!defined('KIDSTORE_DB_SETTINGS')) {
    $host = kidstore_env('KIDSTORE_DB_HOST', '127.0.0.1');
    $portValue = kidstore_env('KIDSTORE_DB_PORT', '3306');
    $dbName = kidstore_env('KIDSTORE_DB_NAME', 'kidstore');
    $user = kidstore_env('KIDSTORE_DB_USER', 'root');
    $password = kidstore_env('KIDSTORE_DB_PASS', '**Admin@123');

    $port = filter_var($portValue, FILTER_VALIDATE_INT);
    if ($port === false || $port <= 0) {
        throw new RuntimeException('KIDSTORE_DB_PORT must be a positive integer.');
    }

    define('KIDSTORE_DB_SETTINGS', [
        'host' => $host,
        'port' => $port,
        'name' => $dbName,
        'user' => $user,
        'pass' => $password,
        'charset' => 'utf8mb4',
    ]);
}

/**
 * Returns a shared PDO instance configured for MySQL.
 */
function kidstore_get_pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $settings = KIDSTORE_DB_SETTINGS;
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $settings['host'],
        $settings['port'],
        $settings['name'],
        $settings['charset']
    );

    try {
        $pdo = new PDO($dsn, $settings['user'], $settings['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
    }

    return $pdo;
}
