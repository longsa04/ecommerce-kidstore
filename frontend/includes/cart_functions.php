<?php
/**
 * Cart helper functions backed by session storage and database validation.
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/functions.php';

function kidstore_ensure_cart_init(): void
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function kidstore_cart_refresh_from_database(): void
{
    kidstore_ensure_cart_init();

    foreach ($_SESSION['cart'] as $productId => $item) {
        $product = kidstore_fetch_product((int) $productId);
        if (!$product || ($product['status'] ?? 'inactive') !== 'active' || (int) ($product['is_active'] ?? 0) !== 1) {
            unset($_SESSION['cart'][$productId]);
            continue;
        }

        $available = (int) $product['stock_quantity'];
        if ($available <= 0) {
            unset($_SESSION['cart'][$productId]);
            continue;
        }

        $quantity = (int) ($item['quantity'] ?? 1);
        if ($quantity > $available) {
            $quantity = $available;
        }

        $_SESSION['cart'][$productId] = [
            'name' => $product['product_name'],
            'price' => (float) $product['price'],
            'image' => $product['image_url'] ?? '',
            'quantity' => $quantity,
            'stock' => $available,
            'category_id' => $product['category_id'],
        ];
    }
}

function getCartItems(): array
{
    kidstore_cart_refresh_from_database();
    return $_SESSION['cart'];
}

function getCartItemCount(): int
{
    kidstore_cart_refresh_from_database();

    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += (int) ($item['quantity'] ?? 0);
    }
    return $count;
}

function getCartTotal(): float
{
    kidstore_cart_refresh_from_database();

    $total = 0.0;
    foreach ($_SESSION['cart'] as $item) {
        $quantity = (int) ($item['quantity'] ?? 0);
        $price = (float) ($item['price'] ?? 0);
        $total += $price * $quantity;
    }
    return $total;
}

function addToCart($productId, $name = null, $price = null, $image = null, $quantity = 1): bool
{
    kidstore_ensure_cart_init();

    $product = kidstore_fetch_product((int) $productId);
    if (!$product || ($product['status'] ?? 'inactive') !== 'active' || (int) ($product['is_active'] ?? 0) !== 1) {
        return false;
    }

    $available = (int) $product['stock_quantity'];
    if ($available <= 0) {
        return false;
    }

    $currentQuantity = isset($_SESSION['cart'][$productId]) ? (int) $_SESSION['cart'][$productId]['quantity'] : 0;
    $quantity = max(1, (int) $quantity);
    $newQuantity = min($currentQuantity + $quantity, $available);

    if ($newQuantity <= $currentQuantity) {
        return false;
    }

    $_SESSION['cart'][$productId] = [
        'name' => $product['product_name'],
        'price' => (float) $product['price'],
        'image' => $product['image_url'] ?? '',
        'quantity' => $newQuantity,
        'stock' => $available,
        'category_id' => $product['category_id'],
    ];

    return true;
}

function removeFromCart($productId): bool
{
    kidstore_ensure_cart_init();

    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        return true;
    }
    return false;
}

function updateCartQuantity($productId, $quantity): bool
{
    kidstore_ensure_cart_init();

    $quantity = (int) $quantity;
    if ($quantity <= 0) {
        return removeFromCart($productId);
    }

    $product = kidstore_fetch_product((int) $productId);
    if (!$product || ($product['status'] ?? 'inactive') !== 'active' || (int) ($product['is_active'] ?? 0) !== 1) {
        return false;
    }

    $available = (int) $product['stock_quantity'];
    if ($available <= 0) {
        return removeFromCart($productId);
    }

    $quantity = min($quantity, $available);

    $_SESSION['cart'][$productId] = [
        'name' => $product['product_name'],
        'price' => (float) $product['price'],
        'image' => $product['image_url'] ?? '',
        'quantity' => $quantity,
        'stock' => $available,
        'category_id' => $product['category_id'],
    ];

    return true;
}

function clearCart(): bool
{
    kidstore_ensure_cart_init();
    $_SESSION['cart'] = [];
    return true;
}

