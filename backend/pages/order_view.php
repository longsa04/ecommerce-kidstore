<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';

kidstore_admin_require_login();

$orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($orderId <= 0) {
    header('Location: orders.php');
    exit;
}

$order = kidstore_fetch_order_summary($orderId);
if (!$order) {
    $_SESSION['admin_flash'] = 'Order not found.';
    header('Location: orders.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kidstore_csrf_validate($_POST['csrf_token'] ?? null)) {
        $_SESSION['admin_flash'] = 'Invalid request. Please try again.';
        header('Location: order_view.php?id=' . $orderId);
        exit;
    }

    $newStatus = $_POST['status'] ?? $order['status'];
    if (in_array($newStatus, ['pending','processing','shipped','delivered','cancelled'], true)) {
        kidstore_admin_update_order_status($orderId, $newStatus);
        $order['status'] = $newStatus;
        $_SESSION['admin_flash'] = 'Order status updated.';
        header('Location: order_view.php?id=' . $orderId);
        exit;
    }
}

if (isset($_SESSION['admin_flash'])) {
    $flashMessage = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);
} else {
    $flashMessage = null;
}

$pdo = kidstore_get_pdo();
$addressStmt = $pdo->prepare('SELECT * FROM tbl_shipping_addresses WHERE user_id = :user_id ORDER BY address_id DESC LIMIT 1');
$addressStmt->execute(['user_id' => $order['user_id']]);
$shipping = $addressStmt->fetch();

$pageTitle = 'Order #' . str_pad((string) $orderId, 5, '0', STR_PAD_LEFT);
$currentSection = 'orders';
$csrfToken = kidstore_csrf_token();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-toolbar">
    <a class="button secondary" href="<?php echo $prefix; ?>pages/orders.php" onclick="if (window.history.length > 1) { window.history.back(); return false; } return true;">
        <i class="fas fa-arrow-left"></i> Back to Orders
    </a>
    <div class="order-meta">
        <span class="order-status status-<?= htmlspecialchars($order['status']) ?>">Status: <?= htmlspecialchars(ucfirst($order['status'])) ?></span>
        <span class="order-id">Order #<?= str_pad((string) $orderId, 5, '0', STR_PAD_LEFT) ?></span>
    </div>
</div>

<?php if (!empty($flashMessage)): ?>
    <div class="admin-card" style="background:#dcfce7;color:#166534;">
        <?= htmlspecialchars($flashMessage) ?>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="card-header">
        <div>
            <h3>Order Details</h3>
            <p>Placed on <?= date('F j, Y g:i A', strtotime((string) $order['created_at'])) ?></p>
        </div>
        <div class="card-actions">
            <a class="button secondary" href="mailto:<?= htmlspecialchars($order['customer_email']) ?>">
                <i class="fas fa-envelope"></i> Email customer
            </a>
            <button class="button secondary" type="button" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <div class="order-columns">
        <div class="order-panel">
            <h4>Customer</h4>
            <p><?= htmlspecialchars($order['customer_name']) ?><br />
               <?= htmlspecialchars($order['customer_email']) ?><br />
               <?= htmlspecialchars($order['customer_phone'] ?? 'N/A') ?></p>
        </div>
        <div class="order-panel">
            <h4>Payment</h4>
            <?php if (!empty($order['payment'])): ?>
                <p>Method: <?= htmlspecialchars($order['payment']['payment_method']) ?><br />
                Status: <?= htmlspecialchars(ucfirst($order['payment']['payment_status'])) ?><br />
                Amount: $<?= number_format((float) $order['payment']['amount'], 2) ?></p>
            <?php else: ?>
                <p>No payment record.</p>
            <?php endif; ?>
        </div>
        <div class="order-panel">
            <h4>Shipping</h4>
            <?php if ($shipping): ?>
                <p><?= htmlspecialchars($shipping['recipient_name']) ?><br />
                   <?= htmlspecialchars($shipping['address_line']) ?><br />
                   <?= htmlspecialchars($shipping['city']) ?> <?= htmlspecialchars($shipping['postal_code']) ?><br />
                   <?= htmlspecialchars($shipping['country']) ?><br />
                   Phone: <?= htmlspecialchars($shipping['phone']) ?></p>
            <?php else: ?>
                <p>No shipping address on file.</p>
            <?php endif; ?>
        </div>
    </div>

    <form method="post" class="form-inline">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
        <label for="status">Update status:</label>
        <select id="status" name="status" class="input-control">
            <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $option): ?>
                <option value="<?= $option ?>" <?= $order['status'] === $option ? 'selected' : '' ?>><?= ucfirst($option) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button primary" type="submit">Save</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order['items'] as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td>$<?= number_format((float) $item['price'], 2) ?></td>
                    <td><?= (int) $item['quantity'] ?></td>
                    <td>$<?= number_format((float) $item['price'] * (int) $item['quantity'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
