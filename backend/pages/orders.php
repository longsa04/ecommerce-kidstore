<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';

kidstore_admin_require_login();

$pageTitle = 'Orders';
$currentSection = 'orders';
$status = $_GET['status'] ?? '';
$filters = [];
if ($status !== '') {
    $filters['status'] = $status;
}
$orders = kidstore_admin_fetch_orders($filters);

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

include __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?>
    <div class="admin-card" style="background:#dcfce7;color:#166534;">
        <?= htmlspecialchars($flash) ?>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-header" style="margin-bottom:16px;">
        <h3>Order List</h3>
        <form method="get" style="display:flex;gap:10px;align-items:center;">
            <select name="status" style="padding:8px 12px;border-radius:10px;border:1px solid #d1d5db;">
                <option value="">All Statuses</option>
                <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $option): ?>
                    <option value="<?= $option ?>" <?= $status === $option ? 'selected' : '' ?>><?= ucfirst($option) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button secondary" type="submit">Filter</button>
        </form>
    </div>
    <?php if ($orders): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Placed</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= str_pad((string) $order['order_id'], 5, '0', STR_PAD_LEFT) ?></td>
                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                        <td>$<?= number_format((float) $order['total_price'], 2) ?></td>
                        <td><span class="tag"><?= htmlspecialchars(ucfirst($order['status'])) ?></span></td>
                        <td><?= date('M j, Y', strtotime((string) $order['created_at'])) ?></td>
                        <td><a href="<?php echo $prefix; ?>pages/order_view.php?id=<?= (int) $order['order_id'] ?>" class="button secondary" style="padding:6px 12px;">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No orders match the selected filters.</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
