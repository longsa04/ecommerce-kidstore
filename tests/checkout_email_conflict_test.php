<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/orders.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$pdo->exec(
    'CREATE TABLE tbl_users (
        user_id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        phone TEXT DEFAULT NULL,
        address TEXT DEFAULT NULL,
        is_admin INTEGER NOT NULL DEFAULT 0,
        created_at TEXT,
        updated_at TEXT
    )'
);

$now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
$pdo->prepare('INSERT INTO tbl_users (name, email, password, phone, address, is_admin, created_at, updated_at) VALUES (:name, :email, :password, :phone, :address, :is_admin, :created_at, :updated_at)')
    ->execute([
        'name' => 'Original Owner',
        'email' => 'owner@example.com',
        'password' => password_hash('secret', PASSWORD_DEFAULT),
        'phone' => '555-0100',
        'address' => '123 Main St',
        'is_admin' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
$pdo->prepare('INSERT INTO tbl_users (name, email, password, phone, address, is_admin, created_at, updated_at) VALUES (:name, :email, :password, :phone, :address, :is_admin, :created_at, :updated_at)')
    ->execute([
        'name' => 'Logged In Shopper',
        'email' => 'shopper@example.com',
        'password' => password_hash('secret', PASSWORD_DEFAULT),
        'phone' => '555-0123',
        'address' => '456 Side St',
        'is_admin' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

// Simulate an authenticated session for the second user.
$_SESSION['user'] = [
    'user_id' => 2,
    'name' => 'Logged In Shopper',
    'email' => 'shopper@example.com',
    'is_admin' => false,
];

$thrown = false;
try {
    kidstore_upsert_customer([
        'name' => 'Would Be Intruder',
        'email' => 'owner@example.com',
        'phone' => '555-0999',
        'address' => '789 Changed Ave',
    ], $pdo);
} catch (RuntimeException $e) {
    if ($e->getCode() !== KIDSTORE_CUSTOMER_EMAIL_CONFLICT) {
        throw new RuntimeException('Unexpected exception code: ' . $e->getCode());
    }
    $expectedMessage = 'An account with this email already exists. Please sign in to continue.';
    if ($e->getMessage() !== $expectedMessage) {
        throw new RuntimeException('Unexpected exception message: ' . $e->getMessage());
    }
    $thrown = true;
}

if (!$thrown) {
    throw new RuntimeException('Expected checkout rejection was not triggered.');
}

$stmt = $pdo->prepare('SELECT name, phone, address FROM tbl_users WHERE email = :email');
$stmt->execute(['email' => 'owner@example.com']);
$owner = $stmt->fetch();

if (!$owner) {
    throw new RuntimeException('Failed to fetch the original owner.');
}

if ($owner['name'] !== 'Original Owner') {
    throw new RuntimeException('Original customer name was modified.');
}

if ($owner['phone'] !== '555-0100') {
    throw new RuntimeException('Original customer phone was modified.');
}

if ($owner['address'] !== '123 Main St') {
    throw new RuntimeException('Original customer address was modified.');
}

echo "checkout_email_conflict_test passed\n";
