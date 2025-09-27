<?php
/**
 * Database connection helper for Kid Store project.
 */
declare(strict_types=1);

if (!defined('KIDSTORE_DB_SETTINGS')) {
    define('KIDSTORE_DB_SETTINGS', [
        'host' => getenv('KIDSTORE_DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('KIDSTORE_DB_PORT') ?: 3306),
        'name' => getenv('KIDSTORE_DB_NAME') ?: 'kidstore',
        'user' => getenv('KIDSTORE_DB_USER') ?: 'root',
        'pass' => getenv('KIDSTORE_DB_PASS') ?: '**Admin@123',
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
