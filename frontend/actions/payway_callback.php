<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$payload = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

$statusRaw = strtolower(trim((string) ($payload['status'] ?? $payload['payment_status'] ?? $payload['result'] ?? '')));
$tranId = trim((string) ($payload['tran_id'] ?? $payload['transaction_id'] ?? ''));
$returnParamsRaw = $payload['return_params'] ?? $payload['returnParams'] ?? null;

$orderId = 0;
if (is_string($returnParamsRaw) && $returnParamsRaw !== '') {
    $decoded = json_decode($returnParamsRaw, true);
    if (is_array($decoded)) {
        $orderId = (int) ($decoded['order_id'] ?? $decoded['orderId'] ?? 0);
        $tranId = $tranId !== '' ? $tranId : (string) ($decoded['tran_id'] ?? $decoded['tranId'] ?? '');
    }
}

$paywaySession = $_SESSION['payway_checkout'] ?? null;
if ($orderId <= 0 && is_array($paywaySession)) {
    $orderId = (int) ($paywaySession['order_id'] ?? 0);
}

if ($tranId === '' && is_array($paywaySession)) {
    $tranId = (string) ($paywaySession['tran_id'] ?? '');
}

if ($orderId <= 0) {
    $_SESSION['checkout_error'] = 'We could not verify the PayWay payment. Please try again.';
    unset($_SESSION['payway_checkout'], $_SESSION['payway_pending_order_id']);
    header('Location: ../pages/checkout.php');
    exit;
}

$expectedTran = is_array($paywaySession) ? (string) ($paywaySession['tran_id'] ?? '') : '';
$tranMismatch = $expectedTran !== '' && $tranId !== '' && !hash_equals($expectedTran, $tranId);

$successStatuses = ['0', 'success', 'completed', 'approved', 'true'];
$isSuccessful = !$tranMismatch && ($statusRaw === '' ? false : in_array($statusRaw, $successStatuses, true));

if ($statusRaw === '' && !$isSuccessful && !$tranMismatch) {
    $isSuccessful = isset($payload['success']) && (bool) $payload['success'];
}

if ($isSuccessful) {
    try {
        kidstore_update_payment_outcome($orderId, 'completed');
    } catch (Throwable $e) {
        error_log('Failed to mark PayWay order as completed: ' . $e->getMessage());
        $_SESSION['checkout_error'] = 'We completed your payment, but updating the order failed. Contact support with order #' . $orderId . '.';
        header('Location: ../pages/checkout.php');
        exit;
    }

    $order = kidstore_fetch_order_summary($orderId);
    if ($order) {
        $_SESSION['last_order_total'] = (float) ($order['total_price'] ?? 0);
    }
    clearCart();
    unset($_SESSION['payway_checkout'], $_SESSION['payway_pending_order_id']);
    unset($_SESSION['checkout_form_data']);

    header('Location: ../pages/order_confirmation.php?order_id=' . $orderId);
    exit;
}

try {
    kidstore_update_payment_outcome($orderId, 'failed');
} catch (Throwable $e) {
    error_log('Failed to mark PayWay order as failed: ' . $e->getMessage());
}

$_SESSION['checkout_error'] = 'Your PayWay payment was cancelled or failed. Please try again.';
unset($_SESSION['payway_checkout'], $_SESSION['payway_pending_order_id']);
header('Location: ../pages/checkout.php');
exit;
