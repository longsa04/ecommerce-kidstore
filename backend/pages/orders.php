<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';

kidstore_admin_require_login();

$pageTitle = 'Orders';
$currentSection = 'orders';
$status = $_GET['status'] ?? '';
$search = trim((string)($_GET['q'] ?? ''));

$filters = [];
if ($status !== '') {
    $filters['status'] = $status;
}
if ($search !== '') {
    $filters['search'] = $search;
}

$orders = kidstore_admin_fetch_orders($filters);
$orderBreakdown = kidstore_admin_order_status_breakdown();
$totalOrders = array_sum($orderBreakdown);

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
    <div class="card-header">
        <div>
            <h3>Order health</h3>
            <p class="card-subtitle"><?= number_format($totalOrders) ?> total orders across all statuses</p>
        </div>
    </div>
    <div class="status-grid">
        <?php foreach ($orderBreakdown as $label => $count): ?>
            <div class="status-card">
                <span class="status-dot status-<?= htmlspecialchars($label) ?>"></span>
                <div>
                    <strong><?= htmlspecialchars(ucfirst($label)) ?></strong>
                    <span><?= number_format($count) ?> orders</span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="admin-card">
    <div class="card-header">
        <div>
            <h3>Order list</h3>
            <p class="card-subtitle">Search by customer or order number</p>
        </div>
    </div>
    <form method="get" class="form-inline" style="margin-bottom:16px;">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search customer or order #" class="input-control" />
        <select name="status" class="input-control">
            <option value="">All statuses</option>
            <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $option): ?>
                <option value="<?= $option ?>" <?= $status === $option ? 'selected' : '' ?>><?= ucfirst($option) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="button secondary" type="submit">Apply</button>
        <?php if ($status !== '' || $search !== ''): ?>
            <a href="<?php echo $prefix; ?>pages/orders.php" class="button secondary">Reset</a>
        <?php endif; ?>
    </form>

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
                        <td><span class="tag status-<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars(ucfirst($order['status'])) ?></span></td>
                        <td><?= date('M j, Y', strtotime((string) $order['created_at'])) ?></td>
                        <td><a href="<?php echo $prefix; ?>pages/order_view.php?id=<?= (int) $order['order_id'] ?>" class="button secondary button-compact">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-search"></i>
            <p>No orders match the selected filters.</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
