<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$prefix = KIDSTORE_FRONT_URL_PREFIX;
$categories = kidstore_fetch_categories(true);

$categoryId = isset($_GET['category']) && $_GET['category'] !== '' ? (int) $_GET['category'] : null;
$searchTerm = trim((string) ($_GET['search'] ?? ''));
$sort = $_GET['sort'] ?? 'newest';
$availability = $_GET['availability'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;

$filters = [
    'activeOnly' => true,
    'sort' => $sort,
    'limit' => $perPage,
    'offset' => ($page - 1) * $perPage,
];

$countFilters = $filters;
unset($countFilters['limit'], $countFilters['offset']);

if ($categoryId) {
    $filters['category_id'] = $categoryId;
    $countFilters['category_id'] = $categoryId;
}
if ($searchTerm !== '') {
    $filters['search'] = $searchTerm;
    $countFilters['search'] = $searchTerm;
}
if (in_array($availability, ['in_stock', 'out_of_stock'], true)) {
    $filters['availability'] = $availability;
    $countFilters['availability'] = $availability;
}

$products = kidstore_fetch_products($filters);
$totalProducts = kidstore_count_products($countFilters);
$totalPages = max(1, (int) ceil($totalProducts / $perPage));
$page = min($page, $totalPages);

$activeCategory = null;
if ($categoryId) {
    foreach ($categories as $category) {
        if ((int) $category['category_id'] === $categoryId) {
            $activeCategory = $category;
            break;
        }
    }
}

$sortOptions = [
    'newest' => 'Newest',
    'price_asc' => 'Price: Low to High',
    'price_desc' => 'Price: High to Low',
    'name' => 'Name A-Z',
];

$availabilityOptions = [
    '' => 'All Stock',
    'in_stock' => 'In Stock',
    'out_of_stock' => 'Out of Stock',
];

function kidstore_query_params(array $overrides = []): string
{
    $params = array_merge($_GET, $overrides);
    return http_build_query(array_filter($params, static function ($value) {
        return $value !== null && $value !== '';
    }));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Shop - Little Stars</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="<?php echo $prefix; ?>assets/styles.css" rel="stylesheet" />
    <style>
        .shop-hero {
            background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);
            color: #333;
            padding: 80px 0;
            text-align: center;
        }
        .shop-hero h1 {
            font-size: 2.8rem;
            margin-bottom: 10px;
        }
        .shop-hero p {
            font-size: 1.1rem;
            max-width: 640px;
            margin: 0 auto;
        }
        .shop-content {
            padding: 60px 0;
        }
        .shop-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 30px;
        }
        .shop-sidebar {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            padding: 25px;
            height: fit-content;
            position: sticky;
            top: 120px;
        }
        .filter-section + .filter-section {
            margin-top: 30px;
        }
        .filter-section h3 {
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: #333;
        }
        .filter-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 12px;
        }
        .filter-list a {
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-decoration: none;
            color: #444;
            padding: 10px 14px;
            border-radius: 12px;
            transition: background 0.2s ease, transform 0.2s ease;
        }
        .filter-list a.active,
        .filter-list a:hover {
            background: rgba(102, 126, 234, 0.12);
            color: #312e81;
            transform: translateX(4px);
        }
        .shop-products {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        .shop-controls {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }
        .shop-controls form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .shop-controls input[type="search"],
        .shop-controls select {
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid #ddd;
            font-size: 0.95rem;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
        }
        .product-card {
            background: #fff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            text-align: center;
            position: relative;
        }
        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.12);
        }
        .product-card img {
            width: 100%;
            height: 240px;
            object-fit: cover;
        }
        .product-card h3 {
            font-size: 1.2rem;
            margin: 18px 18px 8px;
        }
        .product-thumb {
            display: block;
        }
        .product-card h3 a {
            text-decoration: none;
            color: inherit;
        }
        .product-card h3 a:hover {
            color: #6366f1;
        }
        .product-card p {
            margin: 0 18px 20px;
            color: #555;
            font-size: 0.95rem;
            min-height: 48px;
        }
        .product-card .price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2196f3;
            margin-bottom: 15px;
        }
        .product-meta {
            display: flex;
            justify-content: center;
            gap: 12px;
            font-size: 0.85rem;
            color: #777;
            margin-bottom: 15px;
        }
        .add-to-cart {
            margin-bottom: 20px;
        }
        .badge {
            position: absolute;
            top: 16px;
            left: 16px;
            background: rgba(0, 0, 0, 0.65);
            color: #fff;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge.out-of-stock {
            background: rgba(244, 67, 54, 0.85);
        }
        .pagination {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }
        .pagination a,
        .pagination span {
            padding: 8px 14px;
            border-radius: 10px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
            font-size: 0.95rem;
        }
        .pagination .current {
            background: #667eea;
            color: #fff;
            border-color: #667eea;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        .empty-state p {
            color: #666;
        }
        @media (max-width: 1024px) {
            .shop-layout {
                grid-template-columns: 1fr;
            }
            .shop-sidebar {
                position: static;
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../partials/header.php'; ?>
<main>
    <section class="shop-hero">
        <div class="container">
            <h1><?= htmlspecialchars($activeCategory['category_name'] ?? 'Shop Our Collection') ?></h1>
            <p>
                <?= $searchTerm !== ''
                    ? 'Showing results for ?' . htmlspecialchars($searchTerm) . '?.'
                    : 'Choose from curated outfits and essentials made for giggles, play, and special memories.'
                ?>
            </p>
        </div>
    </section>

    <section class="shop-content">
        <div class="container">
            <div class="shop-layout">
                <aside class="shop-sidebar">
                    <div class="filter-section">
                        <h3>Categories</h3>
                        <ul class="filter-list">
                            <li><a href="shop.php" class="<?= $categoryId === null ? 'active' : '' ?>">All Categories</a></li>
                            <?php foreach ($categories as $category): ?>
                                <li>
                                    <a href="shop.php?<?= htmlspecialchars(kidstore_query_params(['category' => $category['category_id'], 'page' => 1])) ?>"
                                       class="<?= ((int) $category['category_id'] === $categoryId) ? 'active' : '' ?>">
                                        <?= htmlspecialchars($category['category_name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="filter-section">
                        <h3>Availability</h3>
                        <ul class="filter-list">
                            <?php foreach ($availabilityOptions as $value => $label): ?>
                                <li>
                                    <a href="shop.php?<?= htmlspecialchars(kidstore_query_params(['availability' => $value, 'page' => 1])) ?>"
                                       class="<?= $availability === $value ? 'active' : '' ?>">
                                        <?= htmlspecialchars($label) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </aside>

                <div class="shop-products">
                    <div class="shop-controls">
                        <form method="get" class="search-form">
                            <?php if ($categoryId): ?>
                                <input type="hidden" name="category" value="<?= (int) $categoryId ?>" />
                            <?php endif; ?>
                            <?php if ($availability !== '' && isset($availabilityOptions[$availability])): ?>
                                <input type="hidden" name="availability" value="<?= htmlspecialchars($availability) ?>" />
                            <?php endif; ?>
                            <input type="search" name="search" placeholder="Search for products?" value="<?= htmlspecialchars($searchTerm) ?>" />
                            <select name="sort">
                                <?php foreach ($sortOptions as $value => $label): ?>
                                    <option value="<?= $value ?>" <?= $sort === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="add-to-cart" style="padding: 10px 18px; background: #667eea;">
                                <i class="fas fa-filter"></i>
                                Apply
                            </button>
                        </form>
                        <div class="results-count">
                            <?= $totalProducts ?> product<?= $totalProducts === 1 ? '' : 's' ?> found
                        </div>
                    </div>

                    <?php if (!empty($products)): ?>
                        <div class="products-grid">
                            <?php foreach ($products as $product): ?>
                                <?php $inStock = (int) $product['stock_quantity'] > 0; ?>
                                <div class="product-card" data-product-id="<?= (int) $product['product_id'] ?>">
                                    <?php if (!$inStock): ?>
                                        <span class="badge out-of-stock">Out of Stock</span>
                                    <?php elseif (($product['category_name'] ?? '') !== ''): ?>
                                        <span class="badge"><?= htmlspecialchars($product['category_name']) ?></span>
                                    <?php endif; ?>
                                    <a href="product.php?id=<?= (int) $product['product_id'] ?>" class="product-thumb">
                                        <img src="<?= htmlspecialchars(kidstore_product_image($product['image_url'] ?? null)) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>">
                                    </a>
                                    <h3><a href="product.php?id=<?= (int) $product['product_id'] ?>"><?= htmlspecialchars($product['product_name']) ?></a></h3>
                                    <?php if (!empty($product['description'])): ?>
                                        <p><?= htmlspecialchars(kidstore_truncate_text($product['description'], 140)) ?></p>
                                    <?php else: ?>
                                        <p>Soft, durable, and ready for every adventure.</p>
                                    <?php endif; ?>
                                    <div class="product-meta">
                                        <span>$<?= kidstore_format_price((float) $product['price']) ?></span>
                                        <span><?= $inStock ? 'In stock' : 'Sold out' ?></span>
                                    </div>
                                    <button class="add-to-cart" data-product-id="<?= (int) $product['product_id'] ?>" <?= $inStock ? '' : 'disabled' ?>>
                                        <i class="fas fa-shopping-cart"></i>
                                        <?= $inStock ? 'Add to Cart' : 'Notify Me' ?>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <div class="pagination">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <?php if ($i === $page): ?>
                                        <span class="current"><?= $i ?></span>
                                    <?php else: ?>
                                        <a href="shop.php?<?= htmlspecialchars(kidstore_query_params(['page' => $i])) ?>"><?= $i ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <h3>No products found</h3>
                            <p>Try adjusting your filters or search to find something magical.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>

<div id="notification"></div>
<script src="<?php echo $prefix; ?>assets/script.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.kidstoreSetupAddToCartButtons) {
            window.kidstoreSetupAddToCartButtons();
        }
    });
</script>
</body>
</html>
