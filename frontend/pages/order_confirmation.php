<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
if ($orderId <= 0) {
    header('Location: shop.php');
    exit;
}

$prefix = KIDSTORE_FRONT_URL_PREFIX;
$order = kidstore_fetch_order_summary($orderId);
if (!$order) {
    header('Location: shop.php');
    exit;
}


$sessionShipping = $_SESSION['customer_last_shipping'] ?? null;
$total = $_SESSION['last_order_total'] ?? $order['total_price'] ?? 0;
unset($_SESSION['customer_last_shipping'], $_SESSION['last_order_total']);

$shipping = $order['shipping'] ?? $sessionShipping;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order Confirmation - Little Stars</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="<?php echo $prefix; ?>assets/styles.css" rel="stylesheet" />
    <style>
        .confirmation-page {
            padding: 80px 0;
        }
        .confirmation-card {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
            padding: 40px;
            text-align: center;
        }
        .confirmation-card h1 {
            font-size: 2.4rem;
            margin-bottom: 10px;
        }
        .order-id {
            font-size: 1.1rem;
            color: #6366f1;
            margin-bottom: 25px;
        }
        .summary {
            margin-top: 30px;
            text-align: left;
        }
        .summary h3 {
            font-size: 1.2rem;
            margin-bottom: 12px;
        }
        .summary ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .summary li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .summary li:last-child {
            border-bottom: none;
        }
        .actions {
            margin-top: 35px;
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .actions a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 40px;
            border: none;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }
        .actions a.secondary {
            background: #f1f5f9;
            color: #475569;
        }
        .address-box {
            margin-top: 18px;
            background: #f8fafc;
            padding: 18px;
            border-radius: 16px;
            font-size: 0.95rem;
            color: #475569;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../partials/header.php'; ?>
<main>
    <section class="confirmation-page">
        <div class="container">
            <div class="confirmation-card">
                <i class="fas fa-check-circle" style="font-size:3rem;color:#22c55e;margin-bottom:18px;"></i>
                <h1>Thank you for your order!</h1>
                <p>Your little star's goodies are on the way.</p>
                <div class="order-id">Order ID: #<?= str_pad((string) $orderId, 5, '0', STR_PAD_LEFT) ?></div>

                <div class="summary">
                    <h3>Order Summary</h3>
                    <ul>
                        <?php foreach ($order['items'] as $item): ?>
                            <li>
                                <span><?= htmlspecialchars($item['product_name']) ?> ? <?= (int) $item['quantity'] ?></span>
                                <span>$<?= kidstore_format_price((float) $item['price'] * (int) $item['quantity']) ?></span>
                            </li>
                        <?php endforeach; ?>
                        <li style="font-weight:700;">
                            <span>Total Paid</span>
                            <span>$<?= kidstore_format_price((float) $total) ?></span>
                        </li>
                    </ul>
                </div>

                <?php if ($shipping): ?>
                    <div class="address-box">
                        <strong>Shipping to:</strong><br />
                        <?= htmlspecialchars($shipping['recipient_name']) ?><br />
                        <?= htmlspecialchars($shipping['address_line']) ?><br />
                        <?= htmlspecialchars($shipping['city']) ?>, <?= htmlspecialchars($shipping['postal_code']) ?><br />
                        <?= htmlspecialchars($shipping['country']) ?><br />
                        Phone: <?= htmlspecialchars($shipping['phone']) ?>
                    </div>
                <?php endif; ?>

                <div class="actions">
                    <a href="<?php echo $prefix; ?>pages/shop.php" class="secondary">
                        <i class="fas fa-store"></i>
                        Continue Shopping
                    </a>
                    <a href="<?php echo $prefix; ?>index.php">Go to Homepage</a>
                </div>
            </div>
        </div>
    </section>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
<script src="<?php echo $prefix; ?>assets/script.js"></script>
</body>
</html>
