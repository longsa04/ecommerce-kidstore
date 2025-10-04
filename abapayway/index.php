<?php
declare(strict_types=1);

require_once __DIR__ . '/../frontend/includes/bootstrap.php';
require_once __DIR__ . '/PayWayApiCheckout.php';

$cartItems = getCartItems();
$cartTotal = getCartTotal();

if (empty($cartItems) || $cartTotal <= 0) {
    header('Location: ' . kidstore_frontend_url('pages/checkout.php'));
    exit;
}

$customerContact = [
    'name' => '',
    'email' => '',
    'phone' => '',
];

$shippingSession = $_SESSION['customer_last_shipping'] ?? null;
if (is_array($shippingSession)) {
    $customerContact['name'] = trim((string) ($shippingSession['recipient_name'] ?? $customerContact['name']));
    $customerContact['phone'] = trim((string) ($shippingSession['phone'] ?? $customerContact['phone']));
}

$checkoutForm = $_SESSION['checkout_form_data'] ?? null;
if (is_array($checkoutForm)) {
    if ($customerContact['name'] === '') {
        $customerContact['name'] = trim((string) ($checkoutForm['name'] ?? ''));
    }
    if ($customerContact['email'] === '') {
        $customerContact['email'] = trim((string) ($checkoutForm['email'] ?? ''));
    }
    if ($customerContact['phone'] === '') {
        $customerContact['phone'] = trim((string) ($checkoutForm['phone'] ?? ''));
    }
}

$currentUser = kidstore_current_user();
if (is_array($currentUser)) {
    if ($customerContact['name'] === '') {
        $customerContact['name'] = trim((string) ($currentUser['name'] ?? ''));
    }
    if ($customerContact['email'] === '') {
        $customerContact['email'] = trim((string) ($currentUser['email'] ?? ''));
    }
}

if ($customerContact['name'] === '') {
    $customerContact['name'] = 'Guest Customer';
}

if ($customerContact['email'] === '') {
    $customerContact['email'] = 'guest@example.com';
}

if ($customerContact['phone'] === '') {
    $customerContact['phone'] = '0000000000';
}

$nameParts = preg_split('/\s+/u', $customerContact['name'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
$firstName = array_shift($nameParts) ?? 'Guest';
$lastName = $nameParts ? implode(' ', $nameParts) : 'Customer';

$formattedAmount = number_format($cartTotal, 2, '.', '');

$paywaySession = $_SESSION['payway_checkout'] ?? [];
$transactionId = is_string($paywaySession['tran_id'] ?? null) && $paywaySession['tran_id'] !== ''
    ? $paywaySession['tran_id']
    : sprintf('cart-%s-%s', $currentUser['user_id'] ?? 'guest', bin2hex(random_bytes(4)));

$itemized = [];
foreach ($cartItems as $productId => $item) {
    $itemized[] = [
        'id' => (string) $productId,
        'name' => (string) ($item['name'] ?? ''),
        'quantity' => (int) ($item['quantity'] ?? 0),
        'price' => number_format((float) ($item['price'] ?? 0), 2, '.', ''),
    ];
}

$itemsJson = json_encode($itemized, JSON_UNESCAPED_UNICODE);
if ($itemsJson === false) {
    $itemsJson = '[]';
}

$itemsPayload = base64_encode($itemsJson);
$reqTime = (string) time();

$returnParams = json_encode(['reference' => $transactionId], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($returnParams === false) {
    $safeReference = addslashes((string) $transactionId);
    $returnParams = '{"reference":"' . $safeReference . '"}';
}

$_SESSION['payway_checkout'] = [
    'tran_id' => $transactionId,
    'amount' => $formattedAmount,
    'items' => $itemized,
];
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
        <meta name="author" content="PayWay" />
        <title>PayWay Checkout</title>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
    </head>
    <body>
        <div id="aba_main_modal" class="aba-modal">
            <div class="aba-modal-content">
                <form method="POST" target="aba_webservice" action="<?= htmlspecialchars(PayWayApiCheckout::getApiUrl(), ENT_QUOTES, 'UTF-8'); ?>" id="aba_merchant_request">
                    <input type="hidden" name="hash" value="<?= htmlspecialchars(PayWayApiCheckout::getHash($reqTime . ABA_PAYWAY_MERCHANT_ID . $transactionId . $formattedAmount . $firstName . $lastName . $customerContact['email'] . $customerContact['phone'] . $returnParams), ENT_QUOTES, 'UTF-8'); ?>" id="hash" />
                    <input type="hidden" name="tran_id" value="<?= htmlspecialchars($transactionId, ENT_QUOTES, 'UTF-8'); ?>" id="tran_id" />
                    <input type="hidden" name="amount" value="<?= htmlspecialchars($formattedAmount, ENT_QUOTES, 'UTF-8'); ?>" id="amount" />
                    <input type="hidden" name="firstname" value="<?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?>" />
                    <input type="hidden" name="lastname" value="<?= htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8'); ?>" />
                    <input type="hidden" name="phone" value="<?= htmlspecialchars($customerContact['phone'], ENT_QUOTES, 'UTF-8'); ?>" />
                    <input type="hidden" name="email" value="<?= htmlspecialchars($customerContact['email'], ENT_QUOTES, 'UTF-8'); ?>" />
                    <input type="hidden" name="items" value="<?= htmlspecialchars($itemsPayload, ENT_QUOTES, 'UTF-8'); ?>" id="items" />
                    <input type="hidden" name="return_params" value='<?= htmlspecialchars($returnParams, ENT_QUOTES, 'UTF-8'); ?>' />
                    <input type="hidden" name="merchant_id" value="<?= htmlspecialchars(ABA_PAYWAY_MERCHANT_ID, ENT_QUOTES, 'UTF-8'); ?>" />
                    <input type="hidden" name="req_time" value="<?= htmlspecialchars($reqTime, ENT_QUOTES, 'UTF-8'); ?>" />
                </form>
            </div>
        </div>

        <div class="container" style="margin-top: 75px;margin: 0 auto;">
            <div style="width: 200px;margin: 0 auto;">
                <h2>TOTAL: $<?= htmlspecialchars($formattedAmount, ENT_QUOTES, 'UTF-8'); ?></h2>
                <input type="button" id="checkout_button" value="Checkout Now" />
            </div>
        </div>

        <script src="https://checkout.payway.com.kh/plugins/checkout2-0.js"></script>
        <script>
            $(document).ready(function () {
                $('#checkout_button').click(function () {
                    AbaPayway.checkout();
                });
            });
        </script>
    </body>
</html>
