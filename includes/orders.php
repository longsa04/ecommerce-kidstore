<?php
/**
 * Order and customer helper functions.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function kidstore_find_user_by_email(string $email): ?array
{
    $pdo = kidstore_get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM tbl_users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function kidstore_upsert_customer(array $data): array
{
    $pdo = kidstore_get_pdo();
    $email = strtolower(trim($data['email'] ?? ''));
    if ($email === '') {
        throw new InvalidArgumentException('Email is required for customer records.');
    }

    $user = kidstore_find_user_by_email($email);
    $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    $payload = [
        'name' => trim((string) ($data['name'] ?? 'Guest Customer')),
        'email' => $email,
        'phone' => trim((string) ($data['phone'] ?? '')),
        'address' => trim((string) ($data['address'] ?? '')),
    ];

    if ($user) {
        $stmt = $pdo->prepare('UPDATE tbl_users SET name = :name, phone = :phone, address = :address, updated_at = :updated_at WHERE user_id = :user_id');
        $stmt->execute([
            'name' => $payload['name'],
            'phone' => $payload['phone'] ?: $user['phone'],
            'address' => $payload['address'] ?: $user['address'],
            'updated_at' => $now,
            'user_id' => $user['user_id'],
        ]);
        return array_merge($user, $payload);
    }

    $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO tbl_users (name, email, password, phone, address, is_admin, created_at, updated_at) VALUES (:name, :email, :password, :phone, :address, 0, :created_at, :updated_at)');
    $stmt->execute([
        'name' => $payload['name'],
        'email' => $payload['email'],
        'password' => $password,
        'phone' => $payload['phone'],
        'address' => $payload['address'],
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $payload['user_id'] = (int) $pdo->lastInsertId();
    return $payload;
}

function kidstore_create_shipping_address(int $userId, array $data): int
{
    $pdo = kidstore_get_pdo();
    $stmt = $pdo->prepare('INSERT INTO tbl_shipping_addresses (user_id, recipient_name, phone, address_line, city, postal_code, country) VALUES (:user_id, :recipient_name, :phone, :address_line, :city, :postal_code, :country)');
    $stmt->execute([
        'user_id' => $userId,
        'recipient_name' => trim((string) ($data['recipient_name'] ?? '')),
        'phone' => trim((string) ($data['phone'] ?? '')),
        'address_line' => trim((string) ($data['address_line'] ?? '')),
        'city' => trim((string) ($data['city'] ?? '')),
        'postal_code' => trim((string) ($data['postal_code'] ?? '')),
        'country' => trim((string) ($data['country'] ?? '')),
    ]);

    return (int) $pdo->lastInsertId();
}

/**
 * Creates an order, order items, payment record, and adjusts inventory.
 *
 * @param array<string, mixed> $payload
 * @return array{order_id:int,total:float}
 */
function kidstore_create_order_with_items(array $payload): array
{
    $pdo = kidstore_get_pdo();
    $items = $payload['items'] ?? [];
    if (empty($items)) {
        throw new InvalidArgumentException('Cannot create an order without items.');
    }

    $userId = (int) ($payload['user_id'] ?? 0);
    if ($userId <= 0) {
        throw new InvalidArgumentException('A valid user ID is required to create an order.');
    }

    $paymentMethod = $payload['payment_method'] ?? 'Cash on Delivery';

    $pdo->beginTransaction();
    try {
        $subtotal = 0.0;
        $validatedItems = [];

        foreach ($items as $productId => $item) {
            $product = kidstore_fetch_product((int) $productId);
            if (!$product) {
                throw new RuntimeException('One of the products could not be found.');
            }

            $available = (int) $product['stock_quantity'];
            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            if ($available < $quantity) {
                throw new RuntimeException(sprintf('Insufficient stock for %s.', $product['product_name']));
            }

            $price = (float) $product['price'];
            $lineTotal = $price * $quantity;
            $subtotal += $lineTotal;
            $validatedItems[] = [
                'product_id' => (int) $productId,
                'price' => $price,
                'quantity' => $quantity,
            ];
        }

        $orderStmt = $pdo->prepare('INSERT INTO tbl_orders (user_id, total_price, status) VALUES (:user_id, :total_price, :status)');
        $orderStmt->execute([
            'user_id' => $userId,
            'total_price' => $subtotal,
            'status' => 'pending',
        ]);
        $orderId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare('INSERT INTO tbl_order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)');
        $stockStmt = $pdo->prepare('UPDATE tbl_products SET stock_quantity = GREATEST(stock_quantity - :quantity, 0) WHERE product_id = :product_id');

        foreach ($validatedItems as $item) {
            $itemStmt->execute([
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ]);
            $stockStmt->execute([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        $paymentStatus = $paymentMethod === 'Credit Card' ? 'completed' : 'pending';
        $paidAt = $paymentStatus === 'completed' ? (new DateTimeImmutable())->format('Y-m-d H:i:s') : null;

        $paymentStmt = $pdo->prepare('INSERT INTO tbl_payments (order_id, payment_method, payment_status, amount, paid_at) VALUES (:order_id, :payment_method, :payment_status, :amount, :paid_at)');
        $paymentStmt->execute([
            'order_id' => $orderId,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'amount' => $subtotal,
            'paid_at' => $paidAt,
        ]);

        $pdo->commit();

        return [
            'order_id' => $orderId,
            'total' => $subtotal,
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}


function kidstore_fetch_order_summary(int $orderId): ?array
{
    $pdo = kidstore_get_pdo();
    $orderStmt = $pdo->prepare('SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone FROM tbl_orders o JOIN tbl_users u ON u.user_id = o.user_id WHERE o.order_id = :order_id LIMIT 1');
    $orderStmt->execute(['order_id' => $orderId]);
    $order = $orderStmt->fetch();
    if (!$order) {
        return null;
    }

    $itemsStmt = $pdo->prepare('SELECT oi.*, p.product_name, p.image_url FROM tbl_order_items oi JOIN tbl_products p ON p.product_id = oi.product_id WHERE oi.order_id = :order_id');
    $itemsStmt->execute(['order_id' => $orderId]);
    $order['items'] = $itemsStmt->fetchAll();

    $paymentStmt = $pdo->prepare('SELECT * FROM tbl_payments WHERE order_id = :order_id ORDER BY payment_id DESC LIMIT 1');
    $paymentStmt->execute(['order_id' => $orderId]);
    $order['payment'] = $paymentStmt->fetch();

    return $order;
}

