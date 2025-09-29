<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$prefix = KIDSTORE_FRONT_URL_PREFIX;
$categories = kidstore_fetch_categories(true);

$categoryHighlights = [];
if (!empty($categories)) {
    $accentThemes = [
        ['start' => '#a5b4fc', 'end' => '#818cf8'],
        ['start' => '#fbcfe8', 'end' => '#f472b6'],
        ['start' => '#bfdbfe', 'end' => '#60a5fa'],
        ['start' => '#bbf7d0', 'end' => '#34d399'],
        ['start' => '#fde68a', 'end' => '#f59e0b'],
    ];

    foreach ($categories as $index => $category) {
        $categoryIdValue = (int) $category['category_id'];
        $productCount = kidstore_count_products([
            'category_id' => $categoryIdValue,
            'activeOnly' => true,
        ]);

        $coverProduct = kidstore_fetch_products([
            'category_id' => $categoryIdValue,
            'activeOnly' => true,
            'limit' => 1,
            'sort' => 'newest',
        ]);

        $theme = $accentThemes[$index % count($accentThemes)];

        $categoryHighlights[] = [
            'id' => $categoryIdValue,
            'name' => $category['category_name'],
            'description' => $category['description'] ?: 'Playful pieces curated for curious little stars.',
            'productCount' => $productCount,
            'image' => kidstore_product_image($coverProduct[0]['image_url'] ?? null),
            'theme' => $theme,
        ];
    }
}

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

$categoryDisplayMap = [];
foreach ($categories as $category) {
    $categoryDisplayMap[(string) $category['category_id']] = $category['category_name'];
}

$activeFilters = [];
if ($activeCategory) {
    $activeFilters[] = [
        'key' => 'category',
        'label' => 'Category',
        'value' => $activeCategory['category_name'],
        'remove' => ['category' => null, 'page' => 1],
    ];
}
if ($availability !== '' && isset($availabilityOptions[$availability])) {
    $activeFilters[] = [
        'key' => 'availability',
        'label' => 'Availability',
        'value' => $availabilityOptions[$availability],
        'remove' => ['availability' => null, 'page' => 1],
    ];
}
if ($searchTerm !== '') {
    $activeFilters[] = [
        'key' => 'search',
        'label' => 'Search',
        'value' => $searchTerm,
        'remove' => ['search' => null, 'page' => 1],
    ];
}

$defaultSubtitle = 'Choose from curated outfits and essentials made for giggles, play, and special memories.';
$heroTitle = $activeCategory['category_name'] ?? 'Shop Our Collection';
$heroSubtitle = $defaultSubtitle;
if ($searchTerm !== '') {
    $heroSubtitle = sprintf('Showing results for “%s”.', $searchTerm);
} elseif ($activeCategory) {
    $heroSubtitle = sprintf('Curated picks from the %s collection.', $activeCategory['category_name']);
} elseif ($availability === 'in_stock') {
    $heroSubtitle = 'Everything here is in stock and ready for adventures right away.';
} elseif ($availability === 'out_of_stock') {
    $heroSubtitle = 'These favorites are currently on our restock radar—check back soon!';
}

$shopConfig = [
    'categories' => $categoryDisplayMap,
    'availabilityLabels' => $availabilityOptions,
    'copy' => [
        'default' => $defaultSubtitle,
        'in_stock' => 'Everything here is in stock and ready for adventures right away.',
        'out_of_stock' => 'These favorites are currently on our restock radar—check back soon!',
    ],
];

$searchResultMode = 'matches';
$searchRecommendationHeadline = '';
$searchRecommendationSubline = '';
$searchRecommendationMessage = '';

$hasProducts = !empty($products);

if ($searchTerm !== '' && !$hasProducts) {
    $searchResultMode = 'recommendations';
    $matchedCategory = kidstore_guess_category_from_search($categories, $searchTerm) ?? null;

    $recommendationFilters = [
        'activeOnly' => true,
        'sort' => 'newest',
        'limit' => $perPage,
    ];

    if ($matchedCategory) {
        $recommendationFilters['category_id'] = (int) $matchedCategory['category_id'];
    }

    $products = kidstore_fetch_products($recommendationFilters);
    $totalProducts = count($products);
    $totalPages = 1;
    $page = 1;
    $hasProducts = !empty($products);

    if ($matchedCategory) {
        $categoryName = (string) $matchedCategory['category_name'];
        $searchRecommendationHeadline = sprintf('No exact matches for “%s”. Try these %s favorites instead.', $searchTerm, $categoryName);
        $searchRecommendationSubline = 'Looking for something else? Adjust your search or browse all categories below.';
        $searchRecommendationMessage = sprintf('Showing handpicked highlights from the %s collection.', $categoryName);
        $heroTitle = $categoryName;
        $heroSubtitle = sprintf('We pulled highlights from the %s collection for you.', $categoryName);
    } else {
        $searchRecommendationHeadline = sprintf('We couldn’t find “%s”. Here are some of our newest arrivals you might love.', $searchTerm);
        $searchRecommendationSubline = 'Looking for something else? Try refining your search or explore a featured collection.';
        $searchRecommendationMessage = 'Showing a curated batch of fresh arrivals.';
        $heroTitle = 'Fresh Picks for You';
        $heroSubtitle = 'These new arrivals are catching eyes right now.';
    }
}

$shopConfig['searchContext'] = [
    'mode' => $searchResultMode,
    'headline' => $searchRecommendationHeadline,
    'subline' => $searchRecommendationSubline,
    'message' => $searchRecommendationMessage,
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
    <link href="<?php echo $prefix; ?>assets/shop.css" rel="stylesheet" />
</head>
<body class="shop-page">
<?php include __DIR__ . '/../partials/header.php'; ?>
<main>
    <section class="shop-hero">
        <div class="container">
            <nav class="shop-breadcrumb" aria-label="Breadcrumb">
                <a href="<?php echo $prefix; ?>index.php">Home</a>
                <span>/</span>
                <span>Shop</span>
                <?php if ($activeCategory): ?>
                    <span>/</span>
                    <span><?= htmlspecialchars($activeCategory['category_name']) ?></span>
                <?php endif; ?>
            </nav>
            <h1 data-hero-title><?= htmlspecialchars($heroTitle) ?></h1>
            <p class="shop-hero__subtitle" data-hero-subtitle><?= htmlspecialchars($heroSubtitle) ?></p>
        </div>
    </section>

    <?php if (!empty($categoryHighlights)): ?>
        <section class="category-spotlights" aria-label="Shop categories">
            <div class="container">
                <div class="spotlights-header">
                    <h2>Shop by vibe</h2>
                    <p>Jump straight into a collection that matches your kiddo&rsquo;s next adventure.</p>
                </div>
                <div class="spotlights-grid">
                    <?php foreach ($categoryHighlights as $highlight): ?>
                        <a href="shop.php?<?= htmlspecialchars(kidstore_query_params(['category' => $highlight['id'], 'page' => 1])) ?>"
                           class="category-card"
                           style="--accent-start: <?= htmlspecialchars($highlight['theme']['start']) ?>; --accent-end: <?= htmlspecialchars($highlight['theme']['end']) ?>;"
                           data-category-card>
                            <div class="category-card__body">
                                <span class="category-card__eyebrow">Collection</span>
                                <h3><?= htmlspecialchars($highlight['name']) ?></h3>
                                <p><?= htmlspecialchars(kidstore_truncate_text($highlight['description'], 120)) ?></p>
                            </div>
                            <div class="category-card__footer">
                                <span class="category-card__count">
                                    <?= (int) $highlight['productCount'] ?> item<?= $highlight['productCount'] === 1 ? '' : 's' ?>
                                </span>
                                <span class="category-card__cta">
                                    Explore
                                    <i class="fas fa-arrow-right"></i>
                                </span>
                            </div>
                            <div class="category-card__media" aria-hidden="true">
                                <img src="<?= htmlspecialchars($highlight['image']) ?>" alt="" loading="lazy" />
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="shop-content">
        <div class="container">
            <div class="shop-layout" data-shop-root
                 data-search-endpoint="<?= htmlspecialchars($prefix . 'actions/search_products.php') ?>"
                 data-product-url="<?= htmlspecialchars($prefix . 'pages/product.php') ?>"
                 data-page-url="<?= htmlspecialchars($prefix . 'pages/shop.php') ?>"
                 data-current-page="<?= (int) $page ?>"
                 data-total-pages="<?= (int) $totalPages ?>"
                 data-total-products="<?= (int) $totalProducts ?>"
                 data-search-mode="<?= htmlspecialchars($searchResultMode) ?>">
                <aside class="shop-sidebar">
                    <div class="filter-section">
                        <h3>Categories</h3>
                        <ul class="filter-list">
                            <li><a href="shop.php" class="<?= $categoryId === null ? 'active' : '' ?>" data-filter-link data-filter-type="category" data-filter-value="">All Categories</a></li>
                            <?php foreach ($categories as $category): ?>
                                <li>
                                    <a href="shop.php?<?= htmlspecialchars(kidstore_query_params(['category' => $category['category_id'], 'page' => 1])) ?>"
                                       class="<?= ((int) $category['category_id'] === $categoryId) ? 'active' : '' ?>"
                                       data-filter-link
                                       data-filter-type="category"
                                       data-filter-value="<?= (int) $category['category_id'] ?>">
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
                                       class="<?= $availability === $value ? 'active' : '' ?>"
                                       data-filter-link
                                       data-filter-type="availability"
                                       data-filter-value="<?= htmlspecialchars($value) ?>">
                                        <?= htmlspecialchars($label) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </aside>

                <div class="shop-products">
                    <div class="shop-controls">
                        <div class="shop-controls__top">
                            <form method="get" class="search-form" data-shop-form>
                                <input type="hidden" name="category" value="<?= $categoryId !== null ? (int) $categoryId : '' ?>" data-filter-input="category" />
                                <input type="hidden" name="availability" value="<?= htmlspecialchars($availability) ?>" data-filter-input="availability" />
                                <input type="hidden" name="page" value="<?= (int) $page ?>" data-filter-input="page" />
                                <input type="search" name="search" placeholder="Search for products" value="<?= htmlspecialchars($searchTerm) ?>" aria-label="Search products" data-search-input />
                                <select name="sort" aria-label="Sort products" data-sort-select>
                                    <?php foreach ($sortOptions as $value => $label): ?>
                                        <option value="<?= $value ?>" <?= $sort === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="button solid">
                                    <i class="fas fa-sliders-h"></i>
                                    Apply filters
                                </button>
                            </form>
                            <div class="shop-controls__actions">
                                <p class="results-count" data-results-count aria-live="polite">
                                    <?php if ($searchResultMode === 'recommendations'): ?>
                                        <strong><?= (int) $totalProducts ?></strong> curated pick<?= $totalProducts === 1 ? '' : 's' ?>
                                    <?php else: ?>
                                        <strong><?= (int) $totalProducts ?></strong> product<?= $totalProducts === 1 ? '' : 's' ?> found
                                    <?php endif; ?>
                                </p>
                                <a class="clear-filters" href="shop.php" data-clear-filters>
                                    <i class="fas fa-rotate"></i>
                                    Reset
                                </a>
                            </div>
                        </div>
                        <div class="filter-pills" data-active-filters <?= empty($activeFilters) ? 'hidden' : '' ?>>
                            <?php foreach ($activeFilters as $filter): ?>
                                <a class="filter-pill" href="shop.php?<?= htmlspecialchars(kidstore_query_params($filter['remove'])) ?>" data-filter-link data-filter-type="<?= htmlspecialchars($filter['key']) ?>" data-filter-value="">
                                    <span><?= htmlspecialchars($filter['label']) ?>:</span>
                                    <strong><?= htmlspecialchars($filter['value']) ?></strong>
                                    <i class="fas fa-times" aria-hidden="true"></i>
                                    <span class="sr-only">Remove <?= htmlspecialchars($filter['label']) ?> filter</span>
                                </a>
                            <?php endforeach; ?>
                            <?php if (!empty($activeFilters)): ?>
                                <a class="filter-pill filter-pill--clear" href="shop.php" data-filter-link data-filter-type="reset" data-filter-value="">
                                    <i class="fas fa-broom"></i>
                                    Clear all
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="shop-feedback" data-feedback aria-live="polite">
                        <?= htmlspecialchars($searchResultMode === 'recommendations' ? $searchRecommendationMessage : '') ?>
                    </div>

                    <div class="shop-recommendation" data-search-recommendation <?= $searchResultMode === 'recommendations' ? '' : 'hidden' ?>>
                        <div class="shop-recommendation__icon" aria-hidden="true">
                            <i class="fas fa-magic"></i>
                        </div>
                        <div class="shop-recommendation__copy">
                            <strong data-search-recommendation-text>
                                <?= htmlspecialchars($searchRecommendationHeadline !== '' ? $searchRecommendationHeadline : 'We picked these for you.') ?>
                            </strong>
                            <p data-search-recommendation-subline <?= $searchRecommendationSubline === '' ? 'hidden' : '' ?>>
                                <?= htmlspecialchars($searchRecommendationSubline !== '' ? $searchRecommendationSubline : 'Browse the featured collections below or try another search to narrow things down.') ?>
                            </p>
                        </div>
                    </div>

                    <div class="shop-loader" data-shop-loader hidden>
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <div class="product-card product-card--skeleton">
                                <div class="skeleton-block"></div>
                                <div class="skeleton-lines">
                                    <div class="skeleton-line full"></div>
                                    <div class="skeleton-line medium"></div>
                                    <div class="skeleton-line short"></div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="products-grid" data-products-grid>
                        <?php foreach ($products as $product): ?>
                            <?php $inStock = (int) $product['stock_quantity'] > 0; ?>
                            <article class="product-card" data-product-id="<?= (int) $product['product_id'] ?>">
                                <?php if (!$inStock): ?>
                                    <span class="badge out-of-stock">Out of Stock</span>
                                <?php elseif (($product['category_name'] ?? '') !== ''): ?>
                                    <span class="badge"><?= htmlspecialchars($product['category_name']) ?></span>
                                <?php endif; ?>
                                <a href="product.php?id=<?= (int) $product['product_id'] ?>" class="product-thumb" data-product-link>
                                    <img src="<?= htmlspecialchars(kidstore_product_image($product['image_url'] ?? null)) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" loading="lazy" />
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
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div data-empty-state <?= $hasProducts ? 'hidden' : '' ?>>
                        <h3>No products found</h3>
                        <p>Try adjusting your filters or search to find something magical.</p>
                    </div>

                    <nav class="pagination" data-pagination aria-label="Product pagination">
                        <?php if ($totalPages > 1): ?>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i === $page): ?>
                                    <span class="current" aria-current="page"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="shop.php?<?= htmlspecialchars(kidstore_query_params(['page' => $i])) ?>"
                                       data-page-link
                                       data-page="<?= $i ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </div>
    </section>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>

<div id="notification"></div>
<script src="<?php echo $prefix; ?>assets/script.js" defer></script>
<script id="shop-config" type="application/json">
    <?= json_encode($shopConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
</script>
<script src="<?php echo $prefix; ?>assets/shop.js" defer></script>
</body>
</html>
