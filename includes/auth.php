<?php
/**
 * Authentication helpers for customers and admins.
 */
declare(strict_types=1);

require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function kidstore_current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function kidstore_is_admin(): bool
{
    $user = kidstore_current_user();
    return $user ? (bool) ($user['is_admin'] ?? false) : false;
}

function kidstore_login(string $email, string $password): bool
{
    $pdo = kidstore_get_pdo();
    $stmt = $pdo->prepare('SELECT user_id, name, email, password, is_admin FROM tbl_users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return false;
    }

    // Regenerate the session ID upon successful authentication to prevent fixation.
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'user_id' => (int) $user['user_id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'is_admin' => (bool) $user['is_admin'],
    ];

    if (!empty($user['is_admin'])) {
        $_SESSION['admin_id'] = (int) $user['user_id'];
        $_SESSION['admin_name'] = $user['name'];
    }

    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
        $rehash = $pdo->prepare('UPDATE tbl_users SET password = :password WHERE user_id = :user_id');
        $rehash->execute([
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'user_id' => $user['user_id'],
        ]);
    }

    return true;
}

function kidstore_logout(): void
{
    unset($_SESSION['user']);
    unset($_SESSION['admin_id'], $_SESSION['admin_name']);

    // Regenerate after logout to invalidate the previous identifier.
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function kidstore_register(array $data): array
{
    $name = trim((string) ($data['name'] ?? ''));
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $password = (string) ($data['password'] ?? '');
    $confirm = (string) ($data['confirm_password'] ?? '');

    $errors = [];
    if ($name === '') {
        $errors[] = 'Please enter your name.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($password === '') {
        $errors[] = 'Please choose a password.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if ($errors) {
        return ['success' => false, 'errors' => $errors];
    }

    $pdo = kidstore_get_pdo();
    $check = $pdo->prepare('SELECT COUNT(*) FROM tbl_users WHERE email = :email');
    $check->execute(['email' => $email]);
    if ((int) $check->fetchColumn() > 0) {
        return ['success' => false, 'errors' => ['That email address is already registered.']];
    }

    $stmt = $pdo->prepare('INSERT INTO tbl_users (name, email, password, is_admin, created_at, updated_at) VALUES (:name, :email, :password, 0, NOW(), NOW())');
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    kidstore_login($email, $password);

    return ['success' => true];
}

function kidstore_require_auth(): void
{
    if (!kidstore_current_user()) {
        $loginUrl = function_exists('kidstore_frontend_url')
            ? kidstore_frontend_url('pages/auth/login.php')
            : 'login.php';
        header('Location: ' . $loginUrl);
        exit;
    }
}
