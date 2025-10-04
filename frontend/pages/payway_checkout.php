<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../../abapayway/PayWayApiCheckout.php';

$paywaySession = $_SESSION['payway_checkout'] ?? null;
if (!is_array($paywaySession) || empty($paywaySession)) {
    $_SESSION['checkout_error'] = 'We could not find a pending PayWay transaction. Please try again.';
    header('Location: checkout.php');
    exit;
}

$orderId = (int) ($paywaySession['order_id'] ?? 0);
if ($orderId <= 0) {
    $_SESSION['checkout_error'] = 'Your PayWay session has expired. Please restart the checkout.';
    unset($_SESSION['payway_checkout'], $_SESSION['payway_pending_order_id']);
    header('Location: checkout.php');
    exit;
}

$order = kidstore_fetch_order_summary($orderId);
if (!$order) {
    $_SESSION['checkout_error'] = 'We could not locate your order. Please try again.';
    unset($_SESSION['payway_checkout'], $_SESSION['payway_pending_order_id']);
    header('Location: checkout.php');
    exit;
}

$prefix = KIDSTORE_FRONT_URL_PREFIX;
$callbackUrl = kidstore_frontend_url('actions/payway_callback.php');
$tranId = (string) $paywaySession['tran_id'];
$amount = (string) $paywaySession['amount'];
$reqTime = (string) $paywaySession['req_time'];
$returnParams = (string) $paywaySession['return_params'];
$firstName = (string) $paywaySession['first_name'];
$lastName = (string) $paywaySession['last_name'];
$email = (string) $paywaySession['email'];
$phone = (string) $paywaySession['phone'];
$hashInput = $reqTime . ABA_PAYWAY_MERCHANT_ID . $tranId . $amount . $firstName . $lastName . $email . $phone . $returnParams;
$hash = PayWayApiCheckout::getHash($hashInput);
$_SESSION['payway_checkout']['hash'] = $hash;

$items = $order['items'] ?? [];
$total = $order['total_price'] ?? (float) $amount;
$itemPayload = [];
foreach ($items as $item) {
    $itemPayload[] = [
        'id' => (string) ($item['product_id'] ?? ''),
        'name' => (string) ($item['product_name'] ?? ''),
        'quantity' => (int) ($item['quantity'] ?? 0),
        'price' => number_format((float) ($item['price'] ?? 0), 2, '.', ''),
    ];
}
$itemsJson = json_encode($itemPayload, JSON_UNESCAPED_UNICODE);
if ($itemsJson === false) {
    $itemsJson = '[]';
}
$itemsEncoded = base64_encode($itemsJson);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ABA PayWay Checkout - Little Stars</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="<?php echo $prefix; ?>assets/styles.css" rel="stylesheet" />
    <style>
        body {
            background: #f5f5f9;
            font-family: 'Poppins', sans-serif;
        }
        .payway-wrapper {
            max-width: 760px;
            margin: 60px auto;
            background: #fff;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12);
        }
        .payway-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .payway-header h1 {
            font-size: 1.8rem;
            margin: 0;
        }
        .badge {
            padding: 6px 12px;
            background: linear-gradient(135deg, #0f172a, #1d4ed8);
            color: #fff;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .summary-list {
            list-style: none;
            padding: 0;
            margin: 30px 0;
        }
        .summary-list li {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.95rem;
        }
        .summary-list li:last-child {
            border-bottom: none;
        }
        .total-line {
            font-weight: 700;
            font-size: 1.05rem;
        }
        .payway-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        .payway-actions button,
        .payway-actions a {
            border: none;
            cursor: pointer;
            padding: 12px 22px;
            border-radius: 14px;
            font-weight: 600;
            transition: transform 0.15s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .payway-actions button.primary,
        .payway-actions a.primary {
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            color: #fff;
        }
        .payway-actions button.secondary,
        .payway-actions a.secondary {
            background: #e2e8f0;
            color: #0f172a;
        }
        .payway-actions button:hover,
        .payway-actions a:hover {
            transform: translateY(-2px);
        }
        .note {
            margin-top: 20px;
            font-size: 0.9rem;
            color: #475569;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../partials/header.php'; ?>
<main>
    <section>
        <div class="payway-wrapper">
            <div class="payway-header">
                <h1>Complete your payment</h1>
                <span class="badge"><i class="fas fa-lock"></i> ABA PayWay Secure</span>
            </div>
            <p>The ABA PayWay popup should appear shortly. If it does not, click “Launch PayWay” below.</p>
            <ul class="summary-list">
                <?php foreach ($items as $item): ?>
                    <li>
                        <span><?= htmlspecialchars($item['product_name']) ?> × <?= (int) $item['quantity'] ?></span>
                        <span>$<?= kidstore_format_price((float) $item['price'] * (int) $item['quantity']) ?></span>
                    </li>
                <?php endforeach; ?>
                <li class="total-line">
                    <span>Total</span>
                    <span>$<?= kidstore_format_price((float) $total) ?></span>
                </li>
            </ul>
            <div class="payway-actions">
                <button type="button" class="primary" id="launch-payway">
                    <i class="fas fa-credit-card"></i> Launch PayWay
                </button>
                <a class="secondary" href="<?= htmlspecialchars(kidstore_frontend_url('pages/checkout.php'), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="fas fa-arrow-left"></i> Cancel &amp; return to checkout
                </a>
                <a class="secondary" href="<?= htmlspecialchars($callbackUrl . '?status=failed&tran_id=' . rawurlencode($tranId), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="fas fa-ban"></i> Report failure
                </a>
                <a class="primary" href="<?= htmlspecialchars($callbackUrl . '?status=success&tran_id=' . rawurlencode($tranId), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="fas fa-check"></i> Payment completed
                </a>
            </div>
            <p class="note">The buttons above are available for sandbox/testing scenarios. Real payments will redirect you automatically.</p>
        </div>
    </section>
</main>
<form method="POST" target="aba_webservice" action="<?= htmlspecialchars(PayWayApiCheckout::getApiUrl(), ENT_QUOTES, 'UTF-8'); ?>" id="aba_merchant_request">
    <input type="hidden" name="hash" value="<?= htmlspecialchars($hash, ENT_QUOTES, 'UTF-8'); ?>" />
    <input type="hidden" name="tran_id" value="<?= htmlspecialchars($tranId, ENT_QUOTES, 'UTF-8'); ?>" />
    <input type="hidden" name="amount" value="<?= htmlspecialchars($amount, ENT_QUOTES, 'UTF-8'); ?>" />
    <input type="hidden" name="firstname" value="<?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?>" />
    <input type="hidden" name="lastname" value="<?= htmlspecialchars($lastName, ENT_QUOTES, 'UTF-8'); ?>" />
    <input type="hidden" name="phone" value="<?= htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>" />
    <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" />
    <input type="hidden" name="items" value="<?= htmlspecialchars($itemsEncoded, ENT_QUOTES, 'UTF-8'); ?>" />
    <input type="hidden" name="return_params" value='<?= htmlspecialchars($returnParams, ENT_QUOTES, 'UTF-8'); ?>' />
    <input type="hidden" name="merchant_id" value="<?= htmlspecialchars(ABA_PAYWAY_MERCHANT_ID, ENT_QUOTES, 'UTF-8'); ?>" />
    <input type="hidden" name="req_time" value="<?= htmlspecialchars($reqTime, ENT_QUOTES, 'UTF-8'); ?>" />
    <input type="hidden" name="return_url" value="<?= htmlspecialchars($callbackUrl, ENT_QUOTES, 'UTF-8'); ?>" />
</form>
<iframe name="aba_webservice" style="display:none;"></iframe>
<script src="https://checkout.payway.com.kh/plugins/checkout2-0.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkoutButton = document.getElementById('launch-payway');
        const form = document.getElementById('aba_merchant_request');
        function openPayWay() {
            if (typeof AbaPayway !== 'undefined' && form) {
                AbaPayway.checkout();
            } else if (form) {
                form.submit();
            }
        }
        if (checkoutButton) {
            checkoutButton.addEventListener('click', openPayWay);
        }
        openPayWay();
    });
</script>
<?php include __DIR__ . '/../partials/footer.php'; ?>
<script src="<?php echo $prefix; ?>assets/script.js"></script>
</body>
</html>
