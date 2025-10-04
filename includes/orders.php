<?php
/**
 * Order and customer helper functions.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

const KIDSTORE_CUSTOMER_EMAIL_CONFLICT = 409;

function kidstore_find_user_by_email(string $email, ?PDO $pdo = null): ?array
{
    $pdo = $pdo ?? kidstore_get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM tbl_users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function kidstore_upsert_customer(array $data, ?PDO $pdo = null): array
{
    $pdo = $pdo ?? kidstore_get_pdo();
    $email = strtolower(trim($data['email'] ?? ''));
    if ($email === '') {
        throw new InvalidArgumentException('Email is required for customer records.');
    }

    $user = kidstore_find_user_by_email($email, $pdo);
    $currentUser = kidstore_current_user();
    $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
    $payload = [
        'name' => trim((string) ($data['name'] ?? 'Guest Customer')),
        'email' => $email,
        'phone' => trim((string) ($data['phone'] ?? '')),
        'address' => trim((string) ($data['address'] ?? '')),
    ];

    if ($user) {
        $userId = (int) $user['user_id'];
        $currentUserId = (int) ($currentUser['user_id'] ?? 0);
        if ($currentUserId !== $userId) {
            throw new RuntimeException(
                'An account with this email already exists. Please sign in to continue.',
                KIDSTORE_CUSTOMER_EMAIL_CONFLICT
            );
        }

        $stmt = $pdo->prepare('UPDATE tbl_users SET name = :name, phone = :phone, address = :address, updated_at = :updated_at WHERE user_id = :user_id');
        $stmt->execute([
            'name' => $payload['name'],
            'phone' => $payload['phone'] !== '' ? $payload['phone'] : ($user['phone'] ?? ''),
            'address' => $payload['address'] !== '' ? $payload['address'] : ($user['address'] ?? ''),
            'updated_at' => $now,
            'user_id' => $user['user_id'],
        ]);
        return array_merge($user, [
            'name' => $payload['name'],
            'phone' => $payload['phone'] !== '' ? $payload['phone'] : ($user['phone'] ?? ''),
            'address' => $payload['address'] !== '' ? $payload['address'] : ($user['address'] ?? ''),
            'updated_at' => $now,
        ]);
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
 * @return array{order_id:int,total:float,shipping_address_id:int}
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
    $shippingAddressId = (int) ($payload['shipping_address_id'] ?? 0);
    if ($shippingAddressId <= 0) {
        throw new InvalidArgumentException('A shipping address is required to create an order.');
    }

    $addressStmt = $pdo->prepare('SELECT address_id FROM tbl_shipping_addresses WHERE address_id = :address_id AND user_id = :user_id LIMIT 1');
    $addressStmt->execute([
        'address_id' => $shippingAddressId,
        'user_id' => $userId,
    ]);
    if ($addressStmt->fetchColumn() === false) {
        throw new RuntimeException('The specified shipping address could not be found for this customer.');
    }

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

        $orderStmt = $pdo->prepare('INSERT INTO tbl_orders (user_id, shipping_address_id, total_price, status) VALUES (:user_id, :shipping_address_id, :total_price, :status)');
        $orderStmt->execute([
            'user_id' => $userId,
            'shipping_address_id' => $shippingAddressId,
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
            'shipping_address_id' => $shippingAddressId,
        ];
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}


/**
 * Update order and payment records after an external payment flow completes.
 * Restores inventory if the payment ultimately fails.
 */
function kidstore_update_payment_outcome(int $orderId, string $paymentStatus): void
{
    $paymentStatus = strtolower($paymentStatus);
    if (!in_array($paymentStatus, ['pending', 'completed', 'failed'], true)) {
        throw new InvalidArgumentException('Unsupported payment status supplied.');
    }

    $pdo = kidstore_get_pdo();
    $pdo->beginTransaction();

    try {
        $orderStmt = $pdo->prepare('SELECT status FROM tbl_orders WHERE order_id = :order_id LIMIT 1');
        $orderStmt->execute(['order_id' => $orderId]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) {
            throw new RuntimeException('Order not found for payment update.');
        }

        $currentStatus = (string) ($order['status'] ?? 'pending');
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        if ($paymentStatus === 'completed') {
            $paymentUpdate = $pdo->prepare(
                'UPDATE tbl_payments SET payment_status = :status, paid_at = :paid_at WHERE order_id = :order_id'
            );
            $paymentUpdate->execute([
                'status' => 'completed',
                'paid_at' => $now,
                'order_id' => $orderId,
            ]);

            if ($currentStatus !== 'processing') {
                $orderUpdate = $pdo->prepare('UPDATE tbl_orders SET status = :status WHERE order_id = :order_id');
                $orderUpdate->execute([
                    'status' => 'processing',
                    'order_id' => $orderId,
                ]);
            }
        } elseif ($paymentStatus === 'failed') {
            $paymentUpdate = $pdo->prepare(
                'UPDATE tbl_payments SET payment_status = :status, paid_at = NULL WHERE order_id = :order_id'
            );
            $paymentUpdate->execute([
                'status' => 'failed',
                'order_id' => $orderId,
            ]);

            if ($currentStatus !== 'cancelled') {
                $itemsStmt = $pdo->prepare('SELECT product_id, quantity FROM tbl_order_items WHERE order_id = :order_id');
                $itemsStmt->execute(['order_id' => $orderId]);
                $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

                if ($items) {
                    $stockStmt = $pdo->prepare(
                        'UPDATE tbl_products SET stock_quantity = stock_quantity + :quantity WHERE product_id = :product_id'
                    );
                    foreach ($items as $item) {
                        $stockStmt->execute([
                            'product_id' => (int) $item['product_id'],
                            'quantity' => (int) $item['quantity'],
                        ]);
                    }
                }

                $orderUpdate = $pdo->prepare('UPDATE tbl_orders SET status = :status WHERE order_id = :order_id');
                $orderUpdate->execute([
                    'status' => 'cancelled',
                    'order_id' => $orderId,
                ]);
            }
        } else {
            $paymentUpdate = $pdo->prepare(
                'UPDATE tbl_payments SET payment_status = :status, paid_at = NULL WHERE order_id = :order_id'
            );
            $paymentUpdate->execute([
                'status' => 'pending',
                'order_id' => $orderId,
            ]);

            if ($currentStatus !== 'pending') {
                $orderUpdate = $pdo->prepare('UPDATE tbl_orders SET status = :status WHERE order_id = :order_id');
                $orderUpdate->execute([
                    'status' => 'pending',
                    'order_id' => $orderId,
                ]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function kidstore_fetch_order_summary(int $orderId): ?array
{
    $pdo = kidstore_get_pdo();
    $orderStmt = $pdo->prepare(
        'SELECT o.*, '
        . 'u.name AS customer_name, '
        . 'u.email AS customer_email, '
        . 'u.phone AS customer_phone, '
        . 'sa.recipient_name AS shipping_recipient_name, '
        . 'sa.phone AS shipping_phone, '
        . 'sa.address_line AS shipping_address_line, '
        . 'sa.city AS shipping_city, '
        . 'sa.postal_code AS shipping_postal_code, '
        . 'sa.country AS shipping_country '
        . 'FROM tbl_orders o '
        . 'JOIN tbl_users u ON u.user_id = o.user_id '
        . 'LEFT JOIN tbl_shipping_addresses sa ON sa.address_id = o.shipping_address_id '
        . 'WHERE o.order_id = :order_id LIMIT 1'
    );
    $orderStmt->execute(['order_id' => $orderId]);
    $order = $orderStmt->fetch();
    if (!$order) {
        return null;
    }

    if (!empty($order['shipping_address_id']) && $order['shipping_recipient_name'] !== null) {
        $order['shipping'] = [
            'address_id' => (int) $order['shipping_address_id'],
            'recipient_name' => $order['shipping_recipient_name'],
            'phone' => $order['shipping_phone'],
            'address_line' => $order['shipping_address_line'],
            'city' => $order['shipping_city'],
            'postal_code' => $order['shipping_postal_code'],
            'country' => $order['shipping_country'],
        ];
    } else {
        $order['shipping'] = null;
    }

    unset(
        $order['shipping_recipient_name'],
        $order['shipping_phone'],
        $order['shipping_address_line'],
        $order['shipping_city'],
        $order['shipping_postal_code'],
        $order['shipping_country']
    );

    $itemsStmt = $pdo->prepare('SELECT oi.*, p.product_name, p.image_url FROM tbl_order_items oi JOIN tbl_products p ON p.product_id = oi.product_id WHERE oi.order_id = :order_id');
    $itemsStmt->execute(['order_id' => $orderId]);
    $order['items'] = $itemsStmt->fetchAll();

    $paymentStmt = $pdo->prepare('SELECT * FROM tbl_payments WHERE order_id = :order_id ORDER BY payment_id DESC LIMIT 1');
    $paymentStmt->execute(['order_id' => $orderId]);
    $order['payment'] = $paymentStmt->fetch();

    return $order;
}

