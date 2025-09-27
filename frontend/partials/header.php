<?php
declare(strict_types=1);

if (!function_exists('getCartItemCount')) {
    require_once __DIR__ . '/../includes/cart_functions.php';
}

$cartItemCount = getCartItemCount();
$current_page = basename($_SERVER['PHP_SELF']);
$currentUser = function_exists('kidstore_current_user') ? kidstore_current_user() : null;

if (defined('KIDSTORE_FRONT_URL_PREFIX')) {
    $prefix = KIDSTORE_FRONT_URL_PREFIX;
} else {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $prefix = '';
    if (($pos = strpos($scriptName, '/frontend/')) !== false) {
        $relative = substr($scriptName, $pos + strlen('/frontend/'));
        $depth = substr_count(trim($relative, '/'), '/');
        if ($depth > 0) {
            $prefix = str_repeat('../', $depth);
        }
    }
}
?>


<header class="header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="<?php echo $prefix; ?>index.php">
                    <i class="fas fa-star"></i>
                    <span>Little Stars</span>
                </a>
            </div>
            
            <nav class="nav">
                <ul class="nav-links">
                    <li><a href="<?php echo $prefix; ?>index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="<?php echo $prefix; ?>pages/shop.php" class="<?php echo ($current_page == 'shop.php') ? 'active' : ''; ?>">Shop</a></li>
                    <li><a href="<?php echo $prefix; ?>pages/about.php" class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">About</a></li>
                    <li><a href="<?php echo $prefix; ?>pages/contact.php" class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>">Contact</a></li>
                </ul>
            </nav>
            
            <div class="header-actions">
                <div class="search-toggle" onclick="toggleSearch()">
                    <i class="fas fa-search"></i>
                </div>
                <?php if ($currentUser): ?>
                    <?php $userInitial = strtoupper(substr($currentUser['name'], 0, 1)); ?>
                    <div class="user-pill">
                        <span class="avatar-circle"><?php echo htmlspecialchars($userInitial); ?></span>
                        <div class="user-meta">
                            <span class="user-name">Hi, <?php echo htmlspecialchars(explode(' ', $currentUser['name'])[0]); ?></span>
                            <?php if (kidstore_is_admin()): ?>
                                <a class="user-link" href="<?php echo $prefix; ?>../backend/index.php">Admin Dashboard</a>
                            <?php else: ?>
                                <span class="user-link">Member</span>
                            <?php endif; ?>
                        </div>
                        <a class="button ghost" href="<?php echo $prefix; ?>pages/auth/logout.php">Logout</a>
                    </div>
                <?php else: ?>
                    <div class="auth-buttons">
                        <a class="button ghost" href="<?php echo $prefix; ?>pages/auth/login.php">Log in</a>
                        <a class="button solid" href="<?php echo $prefix; ?>pages/auth/register.php">Sign up</a>
                    </div>
                <?php endif; ?>
                <a href="<?php echo $prefix; ?>pages/cart.php" class="cart" title="View Cart">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cart-count" style="<?php echo $cartItemCount > 0 ? 'display:flex;' : 'display:none;'; ?>"><?php echo $cartItemCount; ?></span>
                </a>
                <div class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
        </div>
        
        <div class="search-bar" id="search-bar">
            <form action="<?php echo $prefix; ?>pages/shop.php" method="GET" class="search-form">
                <div class="search-input-wrapper">
                    <input type="text" name="search" placeholder="Search for products..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                    <button type="button" class="search-close" onclick="toggleSearch()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </form>
        </div>
        
        <div class="mobile-nav" id="mobile-nav">
            <ul class="mobile-nav-links">
                <li><a href="<?php echo $prefix; ?>index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Home
                </a></li>
                <li><a href="<?php echo $prefix; ?>pages/shop.php" class="<?php echo ($current_page == 'shop.php') ? 'active' : ''; ?>">
                    <i class="fas fa-store"></i> Shop
                </a></li>
                <li><a href="<?php echo $prefix; ?>pages/about.php" class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">
                    <i class="fas fa-info-circle"></i> About
                </a></li>
                <li><a href="<?php echo $prefix; ?>pages/contact.php" class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> Contact
                </a></li>
                <li><a href="<?php echo $prefix; ?>pages/cart.php" class="<?php echo ($current_page == 'cart.php') ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> Cart (<?php echo $cartItemCount; ?>)
                </a></li>
                <?php if ($currentUser): ?>
                    <?php if (kidstore_is_admin()): ?>
                        <li><a href="<?php echo $prefix; ?>../backend/index.php"><i class="fas fa-chart-line"></i> Admin</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo $prefix; ?>pages/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo $prefix; ?>pages/auth/login.php"><i class="fas fa-sign-in-alt"></i> Log in</a></li>
                    <li><a href="<?php echo $prefix; ?>pages/auth/register.php"><i class="fas fa-user-plus"></i> Sign up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</header>

<style>
.header {
    background-color: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
}
.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
}
.logo a {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: #ff6b6b;
    font-size: 1.5rem;
    font-weight: bold;
}
.logo i {
    margin-right: 0.5rem;
    font-size: 1.8rem;
}
.nav-links {
    display: flex;
    list-style: none;
    gap: 2rem;
}
.nav-links a {
    text-decoration: none;
    color: #333;
    font-weight: 500;
}
.nav-links a:hover,
.nav-links a.active {
    color: #ff6b6b;
}
.header-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}
.search-toggle,
.mobile-menu-toggle {
    cursor: pointer;
    padding: 0.5rem;
}
.cart {
    position: relative;
    text-decoration: none;
    color: #333;
}
.cart-count {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #ff6b6b;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}
.search-bar {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}
.search-bar.active {
    max-height: 80px;
    padding: 1rem 0;
}
.search-input-wrapper {
    display: flex;
    align-items: center;
    background-color: white;
    border: 1px solid #dee2e6;
    border-radius: 25px;
}
.search-input-wrapper input {
    flex: 1;
    padding: 0.75rem 1rem;
    border: none;
    outline: none;
}
.search-btn,
.search-close {
    background: none;
    border: none;
    padding: 0.75rem;
    cursor: pointer;
}
.mobile-nav {
    display: none;
}
.mobile-nav.active {
    display: block;
}
.mobile-nav-links {
    list-style: none;
    padding: 0;
}
.mobile-nav-links a {
    display: flex;
    align-items: center;
    padding: 0.75rem 0;
    text-decoration: none;
    color: #333;
}

.auth-buttons {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    padding: 0.55rem 1.1rem;
    border-radius: 999px;
    font-size: 0.95rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
}
.button.solid {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
}
.button.solid:hover {
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.25);
    transform: translateY(-1px);
}
.button.ghost {
    background: rgba(102, 126, 234, 0.12);
    color: #4c51bf;
}
.button.ghost:hover {
    background: rgba(102, 126, 234, 0.18);
}
.user-pill {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.4rem 0.6rem;
    border-radius: 999px;
    background: rgba(248, 250, 252, 0.75);
    backdrop-filter: blur(10px);
}
.avatar-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #ff6b6b, #ffa726);
    color: #fff;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.95rem;
}
.user-meta {
    display: flex;
    flex-direction: column;
    line-height: 1.1;
}
.user-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1f2937;
}
.user-link {
    font-size: 0.8rem;
    color: #6366f1;
    text-decoration: none;
}
.user-link:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .nav {
        display: none;
    }
    .mobile-menu-toggle {
        display: block;
    }
}
@media (min-width: 769px) {
    .mobile-nav {
        display: none !important;
    }
}
</style>

<script>
window.KIDSTORE_FRONT_PREFIX = '<?php echo $prefix; ?>';
function toggleSearch() {
    const searchBar = document.getElementById('search-bar');
    searchBar.classList.toggle('active');
}
function toggleMobileMenu() {
    const mobileNav = document.getElementById('mobile-nav');
    mobileNav.classList.toggle('active');
}
function updateCartCount(count) {
    const cartCountElement = document.getElementById('cart-count');
    cartCountElement.textContent = count;
    cartCountElement.style.display = count === 0 ? 'none' : 'flex';
}
</script>