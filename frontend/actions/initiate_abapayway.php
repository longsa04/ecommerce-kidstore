<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../../abapayway/PayWayApiCheckout.php';

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
    'payment_method' => 'ABA PayWay',
];

if (!kidstore_frontend_csrf_validate($_POST[KIDSTORE_FRONTEND_CSRF_FIELD] ?? null)) {
    $_SESSION['checkout_error'] = 'Your session has expired. Please refresh and try again.';
    $_SESSION['checkout_form_data'] = array_merge($_SESSION['checkout_form_data'] ?? [], $fields);
    header('Location: ../pages/checkout.php');
    exit;
}

if ($fields['name'] === '' || $fields['email'] === '' || !filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
    $_SESSION['checkout_error'] = 'Please provide a valid name and email address.';
    $_SESSION['checkout_form_data'] = array_merge($_SESSION['checkout_form_data'] ?? [], $fields);
    header('Location: ../pages/checkout.php');
    exit;
}

if ($fields['phone'] === '' || $fields['address'] === '' || $fields['city'] === '' || $fields['postal_code'] === '' || $fields['country'] === '') {
    $_SESSION['checkout_error'] = 'Please fill in all required shipping details.';
    $_SESSION['checkout_form_data'] = array_merge($_SESSION['checkout_form_data'] ?? [], $fields);
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

    $_SESSION['customer_last_shipping'] = [
        'address_id' => $shippingAddressId,
        'recipient_name' => $fields['name'],
        'phone' => $fields['phone'],
        'address_line' => $fields['address'],
        'city' => $fields['city'],
        'postal_code' => $fields['postal_code'],
        'country' => $fields['country'],
    ];

    $order = kidstore_create_order_with_items([
        'user_id' => $customer['user_id'],
        'items' => $cartItems,
        'payment_method' => 'ABA PayWay',
        'shipping_address_id' => $shippingAddressId,
    ]);

    kidstore_update_payment_outcome((int) $order['order_id'], 'pending');

    $nameParts = preg_split('/\s+/u', $fields['name'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $firstName = array_shift($nameParts) ?? 'Guest';
    $lastName = $nameParts ? implode(' ', $nameParts) : 'Customer';

    $formattedAmount = number_format((float) $order['total'], 2, '.', '');
    $transactionId = sprintf('order-%d-%s', (int) $order['order_id'], bin2hex(random_bytes(4)));
    $reqTime = (string) time();
    $merchantId = PayWayApiCheckout::getMerchantId();
    $apiUrl = PayWayApiCheckout::getApiUrl();

    $returnParams = json_encode([
        'order_id' => (int) $order['order_id'],
        'tran_id' => $transactionId,
        'merchant_id' => $merchantId,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($returnParams === false) {
        $returnParams = sprintf(
            '{"order_id":%d,"tran_id":"%s","merchant_id":"%s"}',
            (int) $order['order_id'],
            addslashes($transactionId),
            addslashes($merchantId)
        );
    }

    $hashInput = $reqTime . $merchantId . $transactionId . $formattedAmount . $firstName . $lastName . $fields['email'] . $fields['phone'] . $returnParams;
    $hash = PayWayApiCheckout::getHash($hashInput);

    $_SESSION['payway_checkout'] = [
        'order_id' => (int) $order['order_id'],
        'tran_id' => $transactionId,
        'amount' => $formattedAmount,
        'req_time' => $reqTime,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $fields['email'],
        'phone' => $fields['phone'],
        'return_params' => $returnParams,
        'hash' => $hash,
        'merchant_id' => $merchantId,
        'api_url' => $apiUrl,
    ];

    $_SESSION['payway_pending_order_id'] = (int) $order['order_id'];
    $_SESSION['checkout_form_data'] = array_merge($_SESSION['checkout_form_data'] ?? [], $fields);

    header('Location: ' . kidstore_frontend_url('pages/payway_checkout.php'));
    exit;
} catch (RuntimeException $e) {
    error_log('PayWay initiation error: ' . $e->getMessage());
    $_SESSION['checkout_error'] = $e->getMessage();
    $_SESSION['checkout_form_data'] = array_merge($_SESSION['checkout_form_data'] ?? [], $fields);
    header('Location: ../pages/checkout.php');
    exit;
} catch (Throwable $e) {
    error_log('PayWay initiation error: ' . $e->getMessage());
    $_SESSION['checkout_error'] = 'We could not start the PayWay payment. Please try again.';
    $_SESSION['checkout_form_data'] = array_merge($_SESSION['checkout_form_data'] ?? [], $fields);
    header('Location: ../pages/checkout.php');
    exit;
}
