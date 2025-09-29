<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

try {
    $perPage = 9;
    $sort = $_GET['sort'] ?? 'newest';
    $categoryId = isset($_GET['category']) && $_GET['category'] !== '' ? (int) $_GET['category'] : null;
    $availability = $_GET['availability'] ?? '';
    $searchTerm = trim((string) ($_GET['search'] ?? ''));
    $page = max(1, (int) ($_GET['page'] ?? 1));

    $validSorts = ['newest', 'price_asc', 'price_desc', 'name'];
    if (!in_array($sort, $validSorts, true)) {
        $sort = 'newest';
    }

    if (!in_array($availability, ['in_stock', 'out_of_stock'], true)) {
        $availability = '';
    }

    $filters = [
        'activeOnly' => true,
        'sort' => $sort,
        'limit' => $perPage,
        'offset' => ($page - 1) * $perPage,
    ];

    if ($categoryId) {
        $filters['category_id'] = $categoryId;
    }

    if ($searchTerm !== '') {
        $filters['search'] = $searchTerm;
    }

    if ($availability !== '') {
        $filters['availability'] = $availability;
    }

    $countFilters = $filters;
    unset($countFilters['limit'], $countFilters['offset'], $countFilters['sort']);

    $categories = kidstore_fetch_categories(true);
    $categoryMap = [];
    foreach ($categories as $category) {
        $categoryMap[(int) $category['category_id']] = $category['category_name'];
    }

    $defaultSubtitle = 'Choose from curated outfits and essentials made for giggles, play, and special memories.';
    $heroTitle = $categoryId && isset($categoryMap[$categoryId])
        ? $categoryMap[$categoryId]
        : 'Shop Our Collection';
    $heroSubtitle = $defaultSubtitle;

    $totalProducts = kidstore_count_products($countFilters);
    $totalPages = max(1, (int) ceil($totalProducts / $perPage));

    if ($page > $totalPages) {
        $page = $totalPages;
        $filters['offset'] = ($page - 1) * $perPage;
    }

    $searchContext = [
        'mode' => 'matches',
        'headline' => '',
        'subline' => '',
        'message' => '',
    ];

    if ($searchTerm !== '' && $totalProducts === 0) {
        $searchContext['mode'] = 'recommendations';
        $recommendationFilters = [
            'activeOnly' => true,
            'limit' => $perPage,
            'sort' => 'newest',
        ];

        $matchedCategory = kidstore_guess_category_from_search($categories, $searchTerm);
        if ($matchedCategory) {
            $recommendationFilters['category_id'] = (int) $matchedCategory['category_id'];
            $categoryName = (string) $matchedCategory['category_name'];
            $searchContext['headline'] = sprintf('No exact matches for “%s”. Showing favorites from %s instead.', $searchTerm, $categoryName);
            $searchContext['subline'] = 'Try adjusting your filters or browse another collection for even more ideas.';
            $searchContext['message'] = sprintf('Showing highlights from the %s collection.', $categoryName);
            $heroTitle = $categoryName;
            $heroSubtitle = sprintf('We pulled highlights from the %s collection for you.', $categoryName);
        } else {
            $searchContext['headline'] = sprintf('We couldn’t find “%s”. Here are some of our newest arrivals.', $searchTerm);
            $searchContext['subline'] = 'Refine your search or explore a different collection to discover more gems.';
            $searchContext['message'] = 'Showing a curated batch of fresh arrivals.';
            $heroTitle = 'Fresh Picks for You';
            $heroSubtitle = 'These new arrivals are catching eyes right now.';
        }

        $products = kidstore_fetch_products($recommendationFilters);
        $totalProducts = count($products);
        $totalPages = 1;
        $page = 1;
    } else {
        $products = kidstore_fetch_products($filters);
    }

    if ($searchContext['mode'] !== 'recommendations') {
        if ($searchTerm !== '') {
            $heroSubtitle = sprintf('Showing results for “%s”.', $searchTerm);
        } elseif ($categoryId && isset($categoryMap[$categoryId])) {
            $heroSubtitle = sprintf('Curated picks from the %s collection.', $categoryMap[$categoryId]);
        } elseif ($availability === 'in_stock') {
            $heroSubtitle = 'Everything here is in stock and ready for adventures right away.';
        } elseif ($availability === 'out_of_stock') {
            $heroSubtitle = 'These favorites are currently on our restock radar—check back soon!';
        }
    }

    $prefix = KIDSTORE_FRONT_URL_PREFIX;

    $productsPayload = array_map(static function (array $product) use ($prefix, $categoryMap): array {
        $inStock = (int) ($product['stock_quantity'] ?? 0) > 0;
        $description = trim((string) ($product['description'] ?? ''));
        $fallbackDescription = 'Soft, durable, and ready for every adventure.';

        return [
            'id' => (int) $product['product_id'],
            'name' => (string) $product['product_name'],
            'description' => $description !== ''
                ? kidstore_truncate_text($description, 140)
                : $fallbackDescription,
            'price' => (float) $product['price'],
            'priceFormatted' => '$' . kidstore_format_price((float) $product['price']),
            'stockQuantity' => (int) $product['stock_quantity'],
            'inStock' => $inStock,
            'image' => kidstore_product_image($product['image_url'] ?? null, $prefix),
            'category' => [
                'id' => isset($product['category_id']) ? (int) $product['category_id'] : null,
                'name' => $product['category_name']
                    ?? ($product['category_id'] && isset($categoryMap[(int) $product['category_id']])
                        ? $categoryMap[(int) $product['category_id']]
                        : null),
            ],
            'url' => $prefix . 'pages/product.php?id=' . (int) $product['product_id'],
        ];
    }, $products);

    $response = [
        'success' => true,
        'products' => $productsPayload,
        'meta' => [
            'page' => $page,
            'perPage' => $perPage,
            'totalProducts' => $totalProducts,
            'totalPages' => $totalPages,
        ],
        'filters' => [
            'category' => $categoryId && isset($categoryMap[$categoryId])
                ? ['id' => $categoryId, 'name' => $categoryMap[$categoryId]]
                : null,
            'availability' => $availability,
            'search' => $searchTerm,
            'sort' => $sort,
        ],
        'hero' => [
            'title' => $heroTitle,
            'subtitle' => $heroSubtitle,
        ],
        'searchContext' => $searchContext,
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to fetch products at this time.',
    ]);
}

