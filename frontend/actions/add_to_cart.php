<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/cart_functions.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

try {
    $payload = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request body']);
    exit;
}

$csrfToken = kidstore_frontend_extract_request_csrf_token();
if (!kidstore_frontend_csrf_validate($csrfToken)) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Security token mismatch. Please refresh and try again.']);
    exit;
}

if (!isset($payload['productId'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Missing product identifier']);
    exit;
}

$productId = (int) $payload['productId'];
$quantity = isset($payload['quantity']) ? (int) $payload['quantity'] : 1;
$quantity = max(1, $quantity);

$product = kidstore_fetch_product($productId);
if (!$product || ($product['status'] ?? 'inactive') !== 'active' || (int) ($product['is_active'] ?? 0) !== 1) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Product is unavailable']);
    exit;
}

if ((int) $product['stock_quantity'] <= 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Product is out of stock']);
    exit;
}

$currentQuantity = isset($_SESSION['cart'][$productId]) ? (int) $_SESSION['cart'][$productId]['quantity'] : 0;
$added = addToCart($productId, null, null, null, $quantity);

if (!$added) {
    echo json_encode([
        'success' => false,
        'message' => 'Requested quantity exceeds available stock',
        'cartCount' => getCartItemCount(),
        'itemQuantity' => $currentQuantity,
    ]);
    exit;
}

$updatedCart = getCartItems();
$itemQuantity = isset($updatedCart[$productId]) ? (int) $updatedCart[$productId]['quantity'] : $currentQuantity;

$response = [
    'success' => true,
    'message' => sprintf('%s added to cart', $product['product_name']),
    'cartCount' => getCartItemCount(),
    'cartTotal' => getCartTotal(),
    'itemQuantity' => $itemQuantity,
    'product' => [
        'id' => $product['product_id'],
        'name' => $product['product_name'],
        'price' => (float) $product['price'],
        'image' => $product['image_url'],
        'stock' => (int) $product['stock_quantity'],
    ],
];

echo json_encode($response);
