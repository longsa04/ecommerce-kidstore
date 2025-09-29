<?php
$prefix = defined('KIDSTORE_FRONT_URL_PREFIX') ? KIDSTORE_FRONT_URL_PREFIX : '';
$footerCategories = [];

if (function_exists('kidstore_fetch_categories')) {
    try {
        $footerCategories = kidstore_fetch_categories(true);
    } catch (Throwable $exception) {
        $footerCategories = [];
    }

    $footerCategories = array_values(array_filter(
        $footerCategories,
        static function ($category): bool {
            $categoryId = isset($category['category_id']) ? (int) $category['category_id'] : 0;
            $categoryName = isset($category['category_name']) ? (string) $category['category_name'] : '';

            return $categoryId > 0 && $categoryName !== '';
        }
    ));
}
?>
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Little Stars</h3>
                <p>Creating magical moments through quality children's clothing since 2015.</p>
                <div class="social-links">
                    <a href="https://facebook.com"><i class="fab fa-facebook"></i></a>
                    <a href="https://instagram.com"><i class="fab fa-instagram"></i></a>
                    <a href="https://twitter.com"><i class="fab fa-twitter"></i></a>
                    <a href="https://pinterest.com"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?php echo $prefix; ?>index.php">Home</a></li>
                    <li><a href="<?php echo $prefix; ?>pages/shop.php">Shop</a></li>
                    <li><a href="<?php echo $prefix; ?>pages/about.php">About</a></li>
                    <li><a href="<?php echo $prefix; ?>pages/contact.php">Contact</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Categories</h4>
                <?php if (!empty($footerCategories)): ?>
                    <ul>
                        <?php foreach ($footerCategories as $category): ?>
                            <?php
                                $categoryId = (int) ($category['category_id'] ?? 0);
                                $categoryName = (string) ($category['category_name'] ?? '');

                                if ($categoryId <= 0 || $categoryName === '') {
                                    continue;
                                }
                            ?>
                            <li>
                                <a href="<?php echo $prefix; ?>pages/shop.php?category=<?php echo $categoryId; ?>">
                                    <?php echo htmlspecialchars($categoryName); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="footer-empty">Categories will appear once they are added.</p>
                <?php endif; ?>
            </div>
            
            <div class="footer-section">
                <h4>Customer Service</h4>
                <ul>
                    <li><a href="<?php echo $prefix; ?>pages/size-guide.php">Size Guide</a></li>
                    <li><a href="<?php echo $prefix; ?>pages/shipping.php">Shipping Info</a></li>
                    <li><a href="<?php echo $prefix; ?>pages/returns.php">Returns</a></li>
                    <li><a href="<?php echo $prefix; ?>pages/faq.php">FAQ</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2025 Little Stars. All rights reserved.</p>
        </div>
    </div>
</footer>