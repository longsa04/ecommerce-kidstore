<?php
declare(strict_types=1);

if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    header('Location: ../index.php');
    exit;
}

$prefix = defined('KIDSTORE_FRONT_URL_PREFIX') ? KIDSTORE_FRONT_URL_PREFIX : '';
$featuredCategories = array_slice(kidstore_fetch_categories(true), 0, 3);
$featuredProducts = kidstore_fetch_featured_products(4);

$categoryImageMap = [
    'boys' => 'https://images.pexels.com/photos/4545967/pexels-photo-4545967.jpeg?auto=compress&cs=tinysrgb&w=600',
    'girls' => 'https://images.pexels.com/photos/1648381/pexels-photo-1648381.jpeg?auto=compress&cs=tinysrgb&w=600',
    'baby' => 'https://images.pexels.com/photos/1648375/pexels-photo-1648375.jpeg?auto=compress&cs=tinysrgb&w=600',
    'default' => 'https://images.pexels.com/photos/1648377/pexels-photo-1648377.jpeg?auto=compress&cs=tinysrgb&w=600',
];

function kidstore_resolve_category_image(array $category, array $imageMap, int $index): string
{
    $name = strtolower((string) ($category['category_name'] ?? ''));
    foreach ($imageMap as $keyword => $url) {
        if ($keyword === 'default') {
            continue;
        }
        if (strpos($name, $keyword) !== false) {
            return $url;
        }
    }

    $alternatives = array_values(array_diff_key($imageMap, ['default' => true]));
    if (!empty($alternatives)) {
        return $alternatives[$index % count($alternatives)];
    }

    return $imageMap['default'] ?? '';
}
?>

<style>
.hero {
    display: flex;
    align-items: center;
    min-height: 70vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 0;
    position: relative;
    overflow: hidden;
}
.hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="stars" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23stars)"/></svg>');
    opacity: 0.25;
}
.hero .container {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 40px;
    position: relative;
    z-index: 2;
}
.hero-content {
    flex: 1 1 320px;
    max-width: 540px;
}
.hero-content h1 {
    font-size: 3.2rem;
    font-weight: 700;
    margin-bottom: 20px;
    line-height: 1.2;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}
.hero-content p {
    font-size: 1.2rem;
    margin-bottom: 30px;
    opacity: 0.95;
    line-height: 1.6;
}
.cta-button {
    display: inline-block;
    background: linear-gradient(135deg, #ff6b6b, #ffa726);
    color: white;
    padding: 16px 36px;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 600;
    font-size: 1.05rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    box-shadow: 0 8px 25px rgba(255, 107, 107, 0.35);
    text-transform: uppercase;
    letter-spacing: 1px;
}
.cta-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(255, 107, 107, 0.4);
}
.hero-image {
    flex: 1 1 320px;
    text-align: center;
}
.hero-image img {
    max-width: 100%;
    height: auto;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
    transition: transform 0.3s ease;
}
.hero-image img:hover {
    transform: scale(1.05);
}

.featured-categories,
.featured-products {
    padding: 80px 0;
}
.featured-categories {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}
.featured-products {
    background: #ffffff;
}
.section-title {
    text-align: center;
    font-size: 2.4rem;
    margin-bottom: 50px;
    color: #333;
    font-weight: 700;
}
.categories-grid,
.products-grid {
    display: grid;
    gap: 30px;
    max-width: 1140px;
    margin: 0 auto;
}
.categories-grid {
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}
.products-grid {
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
}
.category-card,
.product-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    text-align: center;
    position: relative;
}
.category-card:hover,
.product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 18px 45px rgba(0, 0, 0, 0.1);
}
.category-card img,
.product-card img {
    width: 100%;
    height: 240px;
    object-fit: cover;
}
.category-card h3,
.product-card h3 {
    font-size: 1.45rem;
    margin: 20px 0 10px;
    color: #333;
    font-weight: 600;
    padding: 0 20px;
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
.category-card p,
.product-card .description {
    color: #555;
    margin: 0 0 20px;
    padding: 0 20px;
    line-height: 1.55;
}
.category-link {
    display: inline-block;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 12px 30px;
    text-decoration: none;
    border-radius: 25px;
    margin-bottom: 25px;
    font-weight: 500;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.category-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
}
.product-card .price {
    font-size: 1.4rem;
    font-weight: 700;
    color: #2196f3;
    margin-bottom: 15px;
}
.add-to-cart {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: linear-gradient(135deg, #2196f3, #21cbf3);
    color: white;
    border: none;
    padding: 12px 28px;
    cursor: pointer;
    border-radius: 30px;
    font-weight: 600;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    margin-bottom: 25px;
    font-size: 1rem;
}
.add-to-cart:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(33, 150, 243, 0.35);
}
.add-to-cart:disabled {
    background: #9e9e9e;
    cursor: not-allowed;
    box-shadow: none;
}

.features {
    padding: 80px 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 30px;
    max-width: 1000px;
    margin: 0 auto;
}
.feature {
    text-align: center;
    padding: 30px 20px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 18px;
    backdrop-filter: blur(10px);
    transition: transform 0.3s ease, background 0.3s ease;
}
.feature:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.15);
}
.feature i {
    font-size: 2.8rem;
    margin-bottom: 18px;
    color: #ffa726;
}
.feature h3 {
    font-size: 1.4rem;
    margin-bottom: 12px;
    font-weight: 600;
}
.feature p {
    opacity: 0.95;
    line-height: 1.6;
}

#notification {
    position: fixed;
    bottom: 90px;
    right: 20px;
    background: linear-gradient(135deg, #4caf50, #45a049);
    color: white;
    padding: 15px 25px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(76, 175, 80, 0.25);
    z-index: 1000;
    transition: opacity 0.25s ease;
    opacity: 0;
    pointer-events: none;
    font-weight: 500;
}
#notification.show {
    opacity: 1;
}

@media (max-width: 768px) {
    .hero {
        padding: 60px 0 40px;
    }
    .hero-content h1 {
        font-size: 2.4rem;
    }
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Magical Clothing for Little Stars</h1>
            <p>Discover playful outfits crafted for comfort, adventure, and imagination. From everyday essentials to special celebrations, we have styles that sparkle.</p>
            <a href="<?php echo $prefix; ?>pages/shop.php" class="cta-button">Shop Now</a>
        </div>
        <div class="hero-image">
            <img src="https://images.pexels.com/photos/1231365/pexels-photo-1231365.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Joyful children wearing colorful clothes">
        </div>
    </div>
</section>

<?php if (!empty($featuredCategories)): ?>
<section class="featured-categories">
    <div class="container">
        <h2 class="section-title">Shop by Category</h2>
        <div class="categories-grid">
            <?php foreach ($featuredCategories as $index => $category): ?>
                <div class="category-card">
                    <img src="<?= htmlspecialchars(kidstore_resolve_category_image($category, $categoryImageMap, $index)) ?>" alt="<?= htmlspecialchars($category['category_name']) ?>">
                    <h3><?= htmlspecialchars($category['category_name']) ?></h3>
                    <?php if (!empty($category['description'])): ?>
                        <p><?= htmlspecialchars($category['description']) ?></p>
                    <?php else: ?>
                        <p>Explore fresh looks and comfy fits curated especially for <?= htmlspecialchars(strtolower($category['category_name'])) ?>.</p>
                    <?php endif; ?>
                    <a href="<?php echo $prefix; ?>pages/shop.php?category=<?= (int) $category['category_id'] ?>" class="category-link">Browse</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($featuredProducts)): ?>
<section class="featured-products">
    <div class="container">
        <h2 class="section-title">Featured Favorites</h2>
        <div class="products-grid">
            <?php foreach ($featuredProducts as $product): ?>
                <div class="product-card" data-product-id="<?= (int) $product['product_id'] ?>">
                    <a href="product.php?id=<?= (int) $product['product_id'] ?>" class="product-thumb">
                        <img src="<?= htmlspecialchars(kidstore_product_image($product['image_url'] ?? null)) ?>" alt="<?= htmlspecialchars($product['product_name']) ?>">
                    </a>
                    <h3><a href="product.php?id=<?= (int) $product['product_id'] ?>"><?= htmlspecialchars($product['product_name']) ?></a></h3>
                    <?php if (!empty($product['description'])): ?>
                        <p class="description"><?= htmlspecialchars(kidstore_truncate_text($product['description'])) ?></p>
                    <?php endif; ?>
                    <p class="price">$<?= kidstore_format_price((float) $product['price']) ?></p>
                    <button class="add-to-cart" data-product-id="<?= (int) $product['product_id'] ?>">
                        <i class="fas fa-shopping-cart"></i>
                        Add to Cart
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="features">
    <div class="container">
        <div class="features-grid">
            <div class="feature">
                <i class="fas fa-shipping-fast"></i>
                <h3>Free Shipping</h3>
                <p>Enjoy complimentary delivery on orders over $50 anywhere nationwide.</p>
            </div>
            <div class="feature">
                <i class="fas fa-undo"></i>
                <h3>Easy Returns</h3>
                <p>Need a different size? Send it back within 30 days for a quick exchange.</p>
            </div>
            <div class="feature">
                <i class="fas fa-shield-alt"></i>
                <h3>Quality Guaranteed</h3>
                <p>Durable fabrics and thoughtful details that keep up with every adventure.</p>
            </div>
            <div class="feature">
                <i class="fas fa-headset"></i>
                <h3>Friendly Support</h3>
                <p>Questions or styling advice? Our team is here for you day and night.</p>
            </div>
        </div>
    </div>
</section>

<div id="notification"></div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.kidstoreSetupAddToCartButtons) {
        window.kidstoreSetupAddToCartButtons();
    }

    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.category-card, .product-card, .feature').forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(30px)';
        element.style.transition = 'all 0.6s ease';
        observer.observe(element);
    });

    const ctaButton = document.querySelector('.cta-button');
    if (ctaButton) {
        ctaButton.addEventListener('click', event => {
            event.preventDefault();
            window.location.href = '<?php echo $prefix; ?>pages/shop.php';
        });
    }

});
</script>
