<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';

kidstore_admin_require_login();

$prefix = KIDSTORE_ADMIN_URL_PREFIX;
$redirectUrl = $prefix . 'pages/products.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['admin_flash'] = 'Invalid request method.';
    header('Location: ' . $redirectUrl);
    exit;
}

if (!kidstore_csrf_validate($_POST['csrf_token'] ?? '')) {
    $_SESSION['admin_flash'] = 'Unable to process request. Please try again.';
    header('Location: ' . $redirectUrl);
    exit;
}

$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
if ($productId > 0) {
    kidstore_admin_delete_product($productId);
    $_SESSION['admin_flash'] = 'Product deleted.';
} else {
    $_SESSION['admin_flash'] = 'Invalid product specified.';
}

header('Location: ' . $redirectUrl);
exit;
