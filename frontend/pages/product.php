<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($productId <= 0) {
    header('Location: shop.php');
    exit;
}

$prefix = KIDSTORE_FRONT_URL_PREFIX;
$product = kidstore_fetch_product($productId);
if (!$product || ($product['status'] ?? 'inactive') !== 'active' || (int) ($product['is_active'] ?? 0) !== 1) {
    header('Location: shop.php');
    exit;
}

$image = kidstore_product_image($product['image_url'] ?? null);
$inStock = (int) $product['stock_quantity'] > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($product['product_name']) ?> - Little Stars</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="<?php echo $prefix; ?>assets/styles.css" rel="stylesheet" />
    <style>
        .product-page {
            padding: 60px 0;
        }
        .product-layout {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            gap: 40px;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        }
        .product-gallery img {
            width: 100%;
            border-radius: 24px;
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.12);
        }
        .product-info h1 {
            font-size: 2.2rem;
            margin-bottom: 15px;
        }
        .product-price {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 20px;
        }
        .product-meta {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-bottom: 20px;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(99, 102, 241, 0.1);
            color: #4c51bf;
            font-size: 0.9rem;
        }
        .product-description {
            color: #475569;
            line-height: 1.7;
            margin-bottom: 25px;
        }
        .add-to-cart {
            padding: 14px 28px;
            font-size: 1rem;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 25px;
            text-decoration: none;
            color: #6366f1;
            font-weight: 600;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../partials/header.php'; ?>
<main>
    <section class="product-page">
        <div class="container">
            <a href="<?php echo $prefix; ?>pages/shop.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to shop</a>
            <div class="product-layout">
                <div class="product-gallery">
                    <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" />
                </div>
                <div class="product-info">
                    <h1><?= htmlspecialchars($product['product_name']) ?></h1>
                    <div class="product-meta">
                        <?php if ($product['category_name']): ?>
                            <span class="badge"><i class="fas fa-tag"></i> <?= htmlspecialchars($product['category_name']) ?></span>
                        <?php endif; ?>
                        <span class="badge" style="background: <?= $inStock ? 'rgba(34,197,94,0.15);color:#16a34a;' : 'rgba(248,113,113,0.15);color:#ef4444;' ?>">
                            <i class="fas <?= $inStock ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                            <?= $inStock ? 'In stock' : 'Out of stock' ?>
                        </span>
                    </div>
                    <div class="product-price">$<?= kidstore_format_price((float) $product['price']) ?></div>
                    <div class="product-description">
                        <?= nl2br(htmlspecialchars($product['description'] ?: 'This adorable outfit is crafted with soft fabrics and playful details?perfect for everyday adventures.')) ?>
                    </div>
                    <button class="add-to-cart" data-product-id="<?= (int) $product['product_id'] ?>" <?= $inStock ? '' : 'disabled' ?>>
                        <i class="fas fa-shopping-cart"></i>
                        <?= $inStock ? 'Add to Cart' : 'Notify Me' ?>
                    </button>
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
