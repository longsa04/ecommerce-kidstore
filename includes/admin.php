<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/orders.php';

const KIDSTORE_ADMIN_PRODUCT_MAX_PRICE = 100000;
const KIDSTORE_ADMIN_PRODUCT_MAX_STOCK = 1000000;
const KIDSTORE_ADMIN_CATEGORY_NAME_MAX_LENGTH = 50;
const KIDSTORE_ADMIN_CATEGORY_DESCRIPTION_MAX_LENGTH = 1000;


function kidstore_admin_collect_product_validation_errors(array $data): array
{
    $errors = [];

    $priceRaw = $data['price'] ?? null;
    if ($priceRaw === null || $priceRaw === '') {
        $errors[] = 'Price is required.';
    } elseif (!is_numeric($priceRaw)) {
        $errors[] = 'Price must be a valid number.';
    } else {
        $price = (float) $priceRaw;
        if ($price < 0) {
            $errors[] = 'Price cannot be negative.';
        } elseif ($price > KIDSTORE_ADMIN_PRODUCT_MAX_PRICE) {
            $errors[] = 'Price cannot exceed $' . number_format((float) KIDSTORE_ADMIN_PRODUCT_MAX_PRICE, 2);
        }
    }

    $stockRaw = $data['stock_quantity'] ?? null;
    if ($stockRaw === null || $stockRaw === '') {
        $errors[] = 'Stock quantity is required.';
    } elseif (filter_var($stockRaw, FILTER_VALIDATE_INT) === false) {
        $errors[] = 'Stock quantity must be a whole number.';
    } else {
        $stock = (int) $stockRaw;
        if ($stock < 0) {
            $errors[] = 'Stock quantity cannot be negative.';
        } elseif ($stock > KIDSTORE_ADMIN_PRODUCT_MAX_STOCK) {
            $errors[] = 'Stock quantity cannot exceed ' . number_format((float) KIDSTORE_ADMIN_PRODUCT_MAX_STOCK);
        }
    }

    return $errors;
}

/**
 * Validate incoming category payloads before persistence.
 */
function kidstore_admin_collect_category_validation_errors(array $data): array
{
    $errors = [];

    $name = trim((string)($data['category_name'] ?? ''));
    if ($name === '') {
        $errors[] = 'Category name is required.';
    } else {
        $length = function_exists('mb_strlen') ? mb_strlen($name) : strlen($name);
        if ($length > KIDSTORE_ADMIN_CATEGORY_NAME_MAX_LENGTH) {
            $errors[] = 'Category name cannot exceed ' . KIDSTORE_ADMIN_CATEGORY_NAME_MAX_LENGTH . ' characters.';
        }
    }

    $description = trim((string)($data['description'] ?? ''));
    if ($description !== '') {
        $length = function_exists('mb_strlen') ? mb_strlen($description) : strlen($description);
        if ($length > KIDSTORE_ADMIN_CATEGORY_DESCRIPTION_MAX_LENGTH) {
            $errors[] = 'Description cannot exceed ' . KIDSTORE_ADMIN_CATEGORY_DESCRIPTION_MAX_LENGTH . ' characters.';
        }
    }

    return $errors;
}



function kidstore_admin_fetch_products(array $filters = []): array
{
    $filters['activeOnly'] = $filters['activeOnly'] ?? false;
    return kidstore_fetch_products($filters);
}

function kidstore_admin_create_product(array $data): int
{
    $errors = kidstore_admin_collect_product_validation_errors($data);
    if ($errors) {
        throw new InvalidArgumentException(implode("\n", $errors));
    }

    $pdo = kidstore_get_pdo();
    $imagePath = trim((string)($data['image_path'] ?? $data['image_url'] ?? ''));

    $stmt = $pdo->prepare('INSERT INTO tbl_products (product_name, description, price, stock_quantity, category_id, image_url, status, is_active, created_at, updated_at) VALUES (:name, :description, :price, :stock_quantity, :category_id, :image_url, :status, :is_active, NOW(), NOW())');
    $stmt->execute([
        'name' => trim($data['product_name']),
        'description' => trim($data['description'] ?? ''),
        'price' => (float) $data['price'],
        'stock_quantity' => (int) $data['stock_quantity'],
        'category_id' => $data['category_id'] ? (int) $data['category_id'] : null,
        'image_url' => $imagePath,
        'status' => $data['status'] ?? 'active',
        'is_active' => !empty($data['is_active']) ? 1 : 0,
    ]);

    return (int) $pdo->lastInsertId();
}

function kidstore_admin_update_product(int $productId, array $data): void
{
    $errors = kidstore_admin_collect_product_validation_errors($data);
    if ($errors) {
        throw new InvalidArgumentException(implode("\n", $errors));
    }

    $pdo = kidstore_get_pdo();
    $imagePath = trim((string)($data['image_path'] ?? $data['image_url'] ?? ''));

    $stmt = $pdo->prepare('UPDATE tbl_products SET product_name = :name, description = :description, price = :price, stock_quantity = :stock_quantity, category_id = :category_id, image_url = :image_url, status = :status, is_active = :is_active, updated_at = NOW() WHERE product_id = :product_id');
    $stmt->execute([
        'name' => trim($data['product_name']),
        'description' => trim($data['description'] ?? ''),
        'price' => (float) $data['price'],
        'stock_quantity' => (int) $data['stock_quantity'],
        'category_id' => $data['category_id'] ? (int) $data['category_id'] : null,
        'image_url' => $imagePath,
        'status' => $data['status'] ?? 'active',
        'is_active' => !empty($data['is_active']) ? 1 : 0,
        'product_id' => $productId,
    ]);
}

function kidstore_admin_delete_product(int $productId): void
{
    $pdo = kidstore_get_pdo();
    $stmt = $pdo->prepare('UPDATE tbl_products SET status = :status, is_active = 0 WHERE product_id = :product_id');
    $stmt->execute([
        'status' => 'inactive',
        'product_id' => $productId,
    ]);
}

function kidstore_admin_fetch_orders(array $filters = []): array
{
    $pdo = kidstore_get_pdo();
    $sql = 'SELECT o.*, u.name AS customer_name, u.email AS customer_email FROM tbl_orders o '
         . 'JOIN tbl_users u ON u.user_id = o.user_id WHERE 1=1';

    $params = [];
    if (!empty($filters['status'])) {
        $sql .= ' AND o.status = :status';
        $params['status'] = $filters['status'];
    }

    if (!empty($filters['search'])) {
        $term = trim((string) $filters['search']);
        if ($term !== '') {
            $sql .= ' AND ('
                . 'CAST(o.order_id AS CHAR) LIKE :search_term '
                . 'OR u.name LIKE :search_term '
                . 'OR u.email LIKE :search_term'
                . ')';
            $params['search_term'] = '%' . $term . '%';
        }
    }

    $sql .= ' ORDER BY o.created_at DESC';

    if (!empty($filters['limit'])) {
        $limit = max(1, (int) $filters['limit']);
        $sql .= ' LIMIT ' . $limit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function kidstore_admin_update_order_status(int $orderId, string $status): void
{
    $pdo = kidstore_get_pdo();
    $stmt = $pdo->prepare('UPDATE tbl_orders SET status = :status WHERE order_id = :order_id');
    $stmt->execute([
        'status' => $status,
        'order_id' => $orderId,
    ]);
}

function kidstore_admin_dashboard_metrics(): array
{
    $pdo = kidstore_get_pdo();

    $orderTotals = $pdo->query(
        'SELECT COUNT(*) AS order_count,
                COALESCE(SUM(total_price), 0) AS total_sales,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending_orders,
                SUM(CASE WHEN DATE(created_at) = CURRENT_DATE THEN 1 ELSE 0 END) AS today_orders,
                COALESCE(SUM(CASE WHEN DATE(created_at) = CURRENT_DATE THEN total_price ELSE 0 END), 0) AS today_sales
         FROM tbl_orders'
    )->fetch() ?: [
        'order_count' => 0,
        'total_sales' => 0,
        'pending_orders' => 0,
        'today_orders' => 0,
        'today_sales' => 0,
    ];

    $products = $pdo->query(
        'SELECT COUNT(*) AS product_count,
                COALESCE(SUM(stock_quantity), 0) AS items_in_stock,
                SUM(CASE WHEN stock_quantity <= 5 THEN 1 ELSE 0 END) AS low_stock
         FROM tbl_products'
    )->fetch() ?: [
        'product_count' => 0,
        'items_in_stock' => 0,
        'low_stock' => 0,
    ];

    $categories = $pdo->query(
        'SELECT COUNT(*) AS total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_total
         FROM tbl_categories'
    )->fetch() ?: ['total' => 0, 'active_total' => 0];

    $customers = $pdo->query(
        'SELECT COUNT(*) AS total,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS recent
         FROM tbl_users WHERE is_admin = 0'
    )->fetch() ?: ['total' => 0, 'recent' => 0];

    return [
        'order_count' => (int) $orderTotals['order_count'],
        'total_sales' => (float) $orderTotals['total_sales'],
        'pending_orders' => (int) $orderTotals['pending_orders'],
        'today_orders' => (int) $orderTotals['today_orders'],
        'today_sales' => (float) $orderTotals['today_sales'],
        'product_count' => (int) $products['product_count'],
        'items_in_stock' => (int) $products['items_in_stock'],
        'low_stock' => (int) $products['low_stock'],
        'category_count' => (int) $categories['total'],
        'active_categories' => (int) $categories['active_total'],
        'customer_count' => (int) $customers['total'],
        'recent_customers' => (int) $customers['recent'],
    ];
}

function kidstore_admin_fetch_all_categories(bool $includeInactive = true): array
{
    $pdo = kidstore_get_pdo();
    $sql = 'SELECT category_id, category_name, description, is_active, created_at, updated_at FROM tbl_categories';
    if (!$includeInactive) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY category_name ASC';

    return $pdo->query($sql)->fetchAll();
}

function kidstore_admin_fetch_category(int $categoryId): ?array
{
    $pdo = kidstore_get_pdo();
    $stmt = $pdo->prepare('SELECT category_id, category_name, description, is_active FROM tbl_categories WHERE category_id = :category_id LIMIT 1');
    $stmt->execute(['category_id' => $categoryId]);
    $category = $stmt->fetch();

    return $category ?: null;
}

function kidstore_admin_create_category(array $data): int
{
    $errors = kidstore_admin_collect_category_validation_errors($data);
    if ($errors) {
        throw new InvalidArgumentException(implode("\n", $errors));
    }

    $pdo = kidstore_get_pdo();
    $stmt = $pdo->prepare('INSERT INTO tbl_categories (category_name, description, is_active, created_at, updated_at) VALUES (:name, :description, :is_active, NOW(), NOW())');
    $stmt->execute([
        'name' => trim($data['category_name']),
        'description' => trim((string)($data['description'] ?? '')),
        'is_active' => !empty($data['is_active']) ? 1 : 0,
    ]);

    return (int) $pdo->lastInsertId();
}

function kidstore_admin_update_category(int $categoryId, array $data): void
{
    $errors = kidstore_admin_collect_category_validation_errors($data);
    if ($errors) {
        throw new InvalidArgumentException(implode("\n", $errors));
    }

    $pdo = kidstore_get_pdo();
    $stmt = $pdo->prepare('UPDATE tbl_categories SET category_name = :name, description = :description, is_active = :is_active, updated_at = NOW() WHERE category_id = :category_id');
    $stmt->execute([
        'name' => trim($data['category_name']),
        'description' => trim((string)($data['description'] ?? '')),
        'is_active' => !empty($data['is_active']) ? 1 : 0,
        'category_id' => $categoryId,
    ]);
}

function kidstore_admin_delete_category(int $categoryId): void
{
    $pdo = kidstore_get_pdo();
    $stmt = $pdo->prepare('UPDATE tbl_categories SET is_active = 0, updated_at = NOW() WHERE category_id = :category_id');
    $stmt->execute(['category_id' => $categoryId]);
}

function kidstore_admin_activate_category(int $categoryId): void
{
    $pdo = kidstore_get_pdo();
    $stmt = $pdo->prepare('UPDATE tbl_categories SET is_active = 1, updated_at = NOW() WHERE category_id = :category_id');
    $stmt->execute([
        'category_id' => $categoryId,
    ]);
}

function kidstore_admin_order_status_breakdown(): array
{
    $pdo = kidstore_get_pdo();
    $stmt = $pdo->query('SELECT status, COUNT(*) AS total FROM tbl_orders GROUP BY status');

    $breakdown = [
        'pending' => 0,
        'processing' => 0,
        'shipped' => 0,
        'delivered' => 0,
        'cancelled' => 0,
    ];

    foreach ($stmt->fetchAll() as $row) {
        $status = (string) ($row['status'] ?? '');
        if ($status !== '') {
            $breakdown[$status] = (int) $row['total'];
        }
    }

    return $breakdown;
}

function kidstore_admin_product_overview(): array
{
    $pdo = kidstore_get_pdo();
    $row = $pdo->query(
        'SELECT COUNT(*) AS total,
                SUM(CASE WHEN status = "active" AND is_active = 1 THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN status <> "active" OR is_active = 0 THEN 1 ELSE 0 END) AS inactive,
                SUM(CASE WHEN stock_quantity <= 5 THEN 1 ELSE 0 END) AS low_stock,
                SUM(CASE WHEN stock_quantity <= 0 THEN 1 ELSE 0 END) AS out_of_stock
         FROM tbl_products'
    )->fetch() ?: [
        'total' => 0,
        'active' => 0,
        'inactive' => 0,
        'low_stock' => 0,
        'out_of_stock' => 0,
    ];

    return [
        'total' => (int) $row['total'],
        'active' => (int) $row['active'],
        'inactive' => (int) $row['inactive'],
        'low_stock' => (int) $row['low_stock'],
        'out_of_stock' => (int) $row['out_of_stock'],
    ];
}

function kidstore_admin_customer_glance(): array
{
    $pdo = kidstore_get_pdo();
    $row = $pdo->query(
        'SELECT COUNT(*) AS total,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS new_customers,
                COALESCE(SUM(total_spent), 0) AS gross_revenue
         FROM (
            SELECT u.user_id, u.created_at,
                   COALESCE(SUM(o.total_price), 0) AS total_spent
            FROM tbl_users u
            LEFT JOIN tbl_orders o ON o.user_id = u.user_id
            WHERE u.is_admin = 0
            GROUP BY u.user_id, u.created_at
         ) AS stats'
    )->fetch() ?: [
        'total' => 0,
        'new_customers' => 0,
        'gross_revenue' => 0,
    ];

    return [
        'total' => (int) $row['total'],
        'new_customers' => (int) $row['new_customers'],
        'gross_revenue' => (float) $row['gross_revenue'],
    ];
}

function kidstore_admin_monthly_sales(int $months = 6): array
{
    $months = max(1, $months);

    $end = new DateTimeImmutable('first day of this month');
    $start = $end->modify('-' . ($months - 1) . ' months');

    $pdo = kidstore_get_pdo();
    $stmt = $pdo->prepare(
        'SELECT DATE_FORMAT(created_at, "%Y-%m-01") AS month_key,
                COALESCE(SUM(total_price), 0) AS total
         FROM tbl_orders
         WHERE created_at >= :start_date
         GROUP BY month_key
         ORDER BY month_key ASC'
    );
    $stmt->execute([
        'start_date' => $start->format('Y-m-d H:i:s'),
    ]);

    $results = [];
    foreach ($stmt->fetchAll() as $row) {
        $results[(string) $row['month_key']] = (float) $row['total'];
    }

    $series = [];
    $period = $start;
    for ($i = 0; $i < $months; $i++) {
        $key = $period->format('Y-m-01');
        $series[] = [
            'label' => $period->format('M Y'),
            'total' => $results[$key] ?? 0.0,
        ];
        $period = $period->modify('+1 month');
    }

    return $series;
}

