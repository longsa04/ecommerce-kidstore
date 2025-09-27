<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';

kidstore_admin_require_login();

$pageTitle = 'Dashboard';
$currentSection = 'dashboard';
$metrics = kidstore_admin_dashboard_metrics();
$recentOrders = kidstore_admin_fetch_orders(['limit' => 5]);
$recentProducts = kidstore_admin_fetch_products([
    'limit' => 5,
    'sort' => 'newest',
]);

include __DIR__ . '/../includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Orders</h3>
        <strong><?= number_format($metrics['order_count']) ?></strong>
    </div>
    <div class="stat-card">
        <h3>Total Sales</h3>
        <strong>$<?= number_format($metrics['total_sales'], 2) ?></strong>
    </div>
    <div class="stat-card">
        <h3>Products</h3>
        <strong><?= number_format($metrics['product_count']) ?></strong>
    </div>
    <div class="stat-card">
        <h3>Items in Stock</h3>
        <strong><?= number_format($metrics['items_in_stock']) ?></strong>
    </div>
</div>

<div class="admin-card">
    <div class="admin-header" style="margin-bottom:16px;">
        <h3>Recent Orders</h3>
        <a href="<?php echo $prefix; ?>pages/orders.php" class="button secondary">View all</a>
    </div>
    <?php if ($recentOrders): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Placed</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td><a href="<?php echo $prefix; ?>pages/order_view.php?id=<?= (int) $order['order_id'] ?>">#<?= str_pad((string) $order['order_id'], 5, '0', STR_PAD_LEFT) ?></a></td>
                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                        <td><span class="tag"><?= htmlspecialchars(ucfirst($order['status'])) ?></span></td>
                        <td>$<?= number_format((float) $order['total_price'], 2) ?></td>
                        <td><?= date('M j, Y', strtotime((string) $order['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No orders yet.</p>
    <?php endif; ?>
</div>

<div class="admin-card">
    <div class="admin-header" style="margin-bottom:16px;">
        <h3>Recently Added Products</h3>
        <a href="<?php echo $prefix; ?>pages/products.php" class="button secondary">Manage</a>
    </div>
    <?php if ($recentProducts): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentProducts as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['product_name']) ?></td>
                        <td>$<?= number_format((float) $product['price'], 2) ?></td>
                        <td><?= (int) $product['stock_quantity'] ?></td>
                        <td><?= htmlspecialchars(ucfirst($product['status'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No products available.</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
