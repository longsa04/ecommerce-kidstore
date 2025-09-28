<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/checkout.php');
    exit;
}

$cartItems = getCartItems();
if (empty($cartItems)) {
    $_SESSION['checkout_error'] = 'Your cart is empty. Add items before checking out.';
    header('Location: ../pages/cart.php');
    exit;
}

$fields = [
    'name' => trim((string) ($_POST['name'] ?? '')),
    'email' => trim((string) ($_POST['email'] ?? '')),
    'phone' => trim((string) ($_POST['phone'] ?? '')),
    'address' => trim((string) ($_POST['address'] ?? '')),
    'city' => trim((string) ($_POST['city'] ?? '')),
    'postal_code' => trim((string) ($_POST['postal_code'] ?? '')),
    'country' => trim((string) ($_POST['country'] ?? '')),
    'payment_method' => trim((string) ($_POST['payment_method'] ?? 'Cash on Delivery')),
];

if (!kidstore_frontend_csrf_validate($_POST[KIDSTORE_FRONTEND_CSRF_FIELD] ?? null)) {
    $_SESSION['checkout_error'] = 'Your session has expired. Please refresh and try again.';
    $_SESSION['checkout_form_data'] = $fields;
    header('Location: ../pages/checkout.php');
    exit;
}

if ($fields['name'] === '' || $fields['email'] === '' || !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
    $_SESSION['checkout_error'] = 'Please provide a valid name and email address.';
    $_SESSION['checkout_form_data'] = $fields;
    header('Location: ../pages/checkout.php');
    exit;
}

if ($fields['phone'] === '' || $fields['address'] === '' || $fields['city'] === '' || $fields['postal_code'] === '' || $fields['country'] === '') {
    $_SESSION['checkout_error'] = 'Please fill in all required shipping details.';
    $_SESSION['checkout_form_data'] = $fields;
    header('Location: ../pages/checkout.php');
    exit;
}

try {
    $customer = kidstore_upsert_customer($fields);
    $shippingAddressId = kidstore_create_shipping_address((int) $customer['user_id'], [
        'recipient_name' => $fields['name'],
        'phone' => $fields['phone'],
        'address_line' => $fields['address'],
        'city' => $fields['city'],
        'postal_code' => $fields['postal_code'],
        'country' => $fields['country'],
    ]);
    // Store latest address info on user profile for quick reference
    $_SESSION['customer_last_shipping'] = [
        'address_id' => $shippingAddressId,
        'name' => $fields['name'],
        'phone' => $fields['phone'],
        'address' => $fields['address'],
        'city' => $fields['city'],
        'postal_code' => $fields['postal_code'],
        'country' => $fields['country'],
    ];

    $order = kidstore_create_order_with_items([
        'user_id' => $customer['user_id'],
        'items' => $cartItems,
        'payment_method' => $fields['payment_method'],
    ]);

    clearCart();
    $_SESSION['last_order_total'] = $order['total'];
    unset($_SESSION['checkout_form_data']);

    header('Location: ../pages/order_confirmation.php?order_id=' . $order['order_id']);
    exit;
} catch (Throwable $e) {
    error_log('Checkout error: ' . $e->getMessage());
    $_SESSION['checkout_error'] = 'We could not complete your order: ' . $e->getMessage();
    $_SESSION['checkout_form_data'] = $fields;
    header('Location: ../pages/checkout.php');
    exit;
}
