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

    $totals = $pdo->query('SELECT COUNT(*) AS order_count, COALESCE(SUM(total_price), 0) AS total_sales FROM tbl_orders')->fetch() ?: ['order_count' => 0, 'total_sales' => 0];
    $products = $pdo->query('SELECT COUNT(*) AS product_count, COALESCE(SUM(stock_quantity), 0) AS items_in_stock FROM tbl_products')->fetch() ?: ['product_count' => 0, 'items_in_stock' => 0];
    $categories = $pdo->query('SELECT COUNT(*) AS total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_total FROM tbl_categories')->fetch() ?: ['total' => 0, 'active_total' => 0];

    return [
        'order_count' => (int) $totals['order_count'],
        'total_sales' => (float) $totals['total_sales'],
        'product_count' => (int) $products['product_count'],
        'items_in_stock' => (int) $products['items_in_stock'],
        'category_count' => (int) $categories['total'],
        'active_categories' => (int) $categories['active_total'],
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

