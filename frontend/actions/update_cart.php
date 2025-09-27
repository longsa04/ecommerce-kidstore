<?php
declare(strict_types=1);

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

$action = $payload['action'] ?? null;
$productId = isset($payload['productId']) ? (int) $payload['productId'] : null;

if ($action === null || $productId === null) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Missing action or product identifier']);
    exit;
}

$response = ['success' => false];

if ($action === 'update') {
    $quantity = isset($payload['quantity']) ? max(1, (int) $payload['quantity']) : 1;
    $updated = updateCartQuantity($productId, $quantity);

    if (!$updated) {
        echo json_encode([
            'success' => false,
            'message' => 'Unable to update cart. The product might be unavailable.',
            'cartCount' => getCartItemCount(),
            'cartTotal' => getCartTotal(),
        ]);
        exit;
    }

    $items = getCartItems();
    if (!isset($items[$productId])) {
        echo json_encode([
            'success' => false,
            'message' => 'Product removed because it is unavailable.',
            'cartCount' => getCartItemCount(),
            'cartTotal' => getCartTotal(),
        ]);
        exit;
    }

    $item = $items[$productId];
    $itemQuantity = (int) $item['quantity'];
    $itemTotal = (float) $item['price'] * $itemQuantity;

    $response = [
        'success' => true,
        'cartCount' => getCartItemCount(),
        'cartTotal' => getCartTotal(),
        'itemTotal' => $itemTotal,
        'itemQuantity' => $itemQuantity,
        'stock' => (int) ($item['stock'] ?? 0),
    ];
} elseif ($action === 'remove') {
    $removed = removeFromCart($productId);

    $response = [
        'success' => $removed,
        'cartCount' => getCartItemCount(),
        'cartTotal' => getCartTotal(),
    ];
} elseif ($action === 'clear') {
    clearCart();
    $response = [
        'success' => true,
        'cartCount' => 0,
        'cartTotal' => 0,
    ];
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unsupported action.']);
    exit;
}

echo json_encode($response);
