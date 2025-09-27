<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';

kidstore_admin_require_login();

$productId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($productId > 0) {
    kidstore_admin_delete_product($productId);
    $_SESSION['admin_flash'] = 'Product deleted.';
}

header('Location: products.php');
exit;
