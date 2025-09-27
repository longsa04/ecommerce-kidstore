<?php
/**
 * Shared helper functions for the Kid Store project.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/**
 * Fetches product categories.
 *
 * @param bool $activeOnly When true, returns only categories flagged as active.
 * @return array<int, array<string, mixed>>
 */
function kidstore_fetch_categories(bool $activeOnly = true): array
{
    $pdo = kidstore_get_pdo();
    $sql = 'SELECT category_id, category_name, description FROM tbl_categories';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY category_name ASC';

    return $pdo->query($sql)->fetchAll();
}

/**
 * Builds the reusable portion of the product query and parameter list.
 *
 * @param array<string, mixed> $filters
 * @return array{0:string,1:array<string, mixed>}
 */
function kidstore_prepare_product_query(array $filters = []): array
{
    $sql = 'FROM tbl_products p '
         . 'LEFT JOIN tbl_categories c ON c.category_id = p.category_id '
         . 'WHERE 1=1';

    $params = [];

    if (!empty($filters['activeOnly'])) {
        $sql .= " AND p.is_active = 1 AND p.status = 'active'";
    }

    if (isset($filters['category_id']) && $filters['category_id'] !== '' && $filters['category_id'] !== null) {
        $sql .= ' AND p.category_id = :category_id';
        $params['category_id'] = (int) $filters['category_id'];
    }

    if (!empty($filters['search'])) {
        $sql .= ' AND (p.product_name LIKE :search OR p.description LIKE :search)';
        $params['search'] = '%' . $filters['search'] . '%';
    }

    if (isset($filters['status']) && $filters['status'] !== '') {
        $sql .= ' AND p.status = :status';
        $params['status'] = $filters['status'];
    }

    if (isset($filters['availability'])) {
        if ($filters['availability'] === 'in_stock') {
            $sql .= ' AND p.stock_quantity > 0';
        } elseif ($filters['availability'] === 'out_of_stock') {
            $sql .= ' AND p.stock_quantity <= 0';
        }
    }

    return [$sql, $params];
}

/**
 * Fetches a collection of products based on optional filters.
 *
 * Supported filters: category_id, search, limit, offset, activeOnly, sort (newest|price_asc|price_desc|name).
 *
 * @param array<string, mixed> $filters
 * @return array<int, array<string, mixed>>
 */
function kidstore_fetch_products(array $filters = []): array
{
    $pdo = kidstore_get_pdo();

    [$whereSql, $params] = kidstore_prepare_product_query($filters);

    $select = 'SELECT p.product_id, p.product_name, p.description, p.price, p.stock_quantity, '
            . 'p.image_url, p.status, p.is_active, c.category_name, p.category_id ' . $whereSql;

    $sort = $filters['sort'] ?? 'newest';
    switch ($sort) {
        case 'price_asc':
            $select .= ' ORDER BY p.price ASC';
            break;
        case 'price_desc':
            $select .= ' ORDER BY p.price DESC';
            break;
        case 'name':
            $select .= ' ORDER BY p.product_name ASC';
            break;
        default:
            $select .= ' ORDER BY COALESCE(p.created_at, NOW()) DESC';
    }

    if (isset($filters['limit'])) {
        $limit = max(1, (int) $filters['limit']);
        $select .= ' LIMIT ' . $limit;
        if (isset($filters['offset'])) {
            $offset = max(0, (int) $filters['offset']);
            $select .= ' OFFSET ' . $offset;
        }
    }

    $stmt = $pdo->prepare($select);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * Counts products matching the provided filters.
 *
 * @param array<string, mixed> $filters
 */
function kidstore_count_products(array $filters = []): int
{
    $pdo = kidstore_get_pdo();
    [$whereSql, $params] = kidstore_prepare_product_query($filters);

    $stmt = $pdo->prepare('SELECT COUNT(*) AS total ' . $whereSql);
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}


/**
 * Truncates long text and appends an ellipsis when needed.
 */
function kidstore_truncate_text(string $text, int $limit = 130): string

{

    $text = trim($text);

    if ($text === '') {

        return '';

    }



    $ellipsis = '...';



    if (function_exists('mb_strlen') && function_exists('mb_substr')) {

        if (mb_strlen($text) <= $limit) {

            return $text;

        }

        return rtrim(mb_substr($text, 0, $limit - 1)) . $ellipsis;

    }



    if (strlen($text) <= $limit) {

        return $text;

    }



    return rtrim(substr($text, 0, $limit - 1)) . $ellipsis;

}



function kidstore_product_image(?string $imageUrl, string $basePrefix = ''): string

{

    if (!empty($imageUrl)) {

        if (preg_match('~^(?:https?:)?//~i', $imageUrl)) {

            return $imageUrl;

        }



        if ($basePrefix === '') {

            if (defined('KIDSTORE_FRONT_URL_PREFIX')) {

                $basePrefix = KIDSTORE_FRONT_URL_PREFIX;

            } elseif (defined('KIDSTORE_ADMIN_URL_PREFIX')) {

                $basePrefix = KIDSTORE_ADMIN_URL_PREFIX;

            }

        }



        return $basePrefix . ltrim($imageUrl, '/');

    }



    return 'https://images.pexels.com/photos/45982/pexels-photo-45982.jpeg?auto=compress&cs=tinysrgb&w=600';

}



function kidstore_fetch_product(int $productId): ?array
{
    $pdo = kidstore_get_pdo();
    $sql = 'SELECT p.*, c.category_name FROM tbl_products p '
         . 'LEFT JOIN tbl_categories c ON c.category_id = p.category_id '
         . 'WHERE p.product_id = :product_id LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['product_id' => $productId]);
    $product = $stmt->fetch();

    return $product ?: null;
}

/**
 * Fetch a small set of featured products (currently newest active items).
 */
function kidstore_fetch_featured_products(int $limit = 4): array
{
    return kidstore_fetch_products([
        'activeOnly' => true,
        'limit' => $limit,
        'sort' => 'newest',
    ]);
}

/**
 * Formats a monetary value for display.
 */
function kidstore_format_price(float $amount): string
{
    return number_format($amount, 2);
}

