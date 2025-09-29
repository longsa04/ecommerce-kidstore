<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';

kidstore_admin_require_login();

$prefix = KIDSTORE_ADMIN_URL_PREFIX;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $prefix . 'pages/categories.php');
    exit;
}

if (!kidstore_csrf_validate($_POST['csrf_token'] ?? '')) {
    $_SESSION['admin_error'] = 'Security validation failed. Please try again.';
    header('Location: ' . $prefix . 'pages/categories.php');
    exit;
}

$categoryId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
if ($categoryId <= 0) {
    $_SESSION['admin_error'] = 'Invalid category selected.';
    header('Location: ' . $prefix . 'pages/categories.php');
    exit;
}

$category = kidstore_admin_fetch_category($categoryId);
if (!$category) {
    $_SESSION['admin_error'] = 'Category not found.';
    header('Location: ' . $prefix . 'pages/categories.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'deactivate') {
        kidstore_admin_delete_category($categoryId);
        $_SESSION['admin_flash'] = 'Category deactivated successfully.';
    } elseif ($action === 'activate') {
        kidstore_admin_activate_category($categoryId);
        $_SESSION['admin_flash'] = 'Category activated successfully.';
    } else {
        $_SESSION['admin_error'] = 'Unknown action requested.';
    }
} catch (Throwable $exception) {
    $_SESSION['admin_error'] = 'Unable to update category. Please try again.';
}

header('Location: ' . $prefix . 'pages/categories.php');
exit;