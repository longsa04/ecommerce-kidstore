<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';

kidstore_admin_require_login();

$pageTitle = 'Dashboard';
$currentSection = 'dashboard';
$metrics = kidstore_admin_dashboard_metrics();
$orderBreakdown = kidstore_admin_order_status_breakdown();
$productOverview = kidstore_admin_product_overview();
$salesSeries = kidstore_admin_monthly_sales(6);
$salesChartData = [
    'labels' => array_map(static fn (array $row): string => $row['label'], $salesSeries),
    'totals' => array_map(static fn (array $row): float => round($row['total'], 2), $salesSeries),
];
$salesChartJson = htmlspecialchars(json_encode($salesChartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');

$recentOrders = kidstore_admin_fetch_orders([
    'limit' => 5,
]);
$recentProducts = kidstore_admin_fetch_products([
    'limit' => 5,
    'sort' => 'newest',
]);

include __DIR__ . '/../includes/header.php';
?>

<div class="metric-grid">
    <div class="metric-card">
        <span class="metric-label">Total Sales</span>
        <span class="metric-value">$<?= number_format($metrics['total_sales'], 2) ?></span>
        <span class="metric-sub">Today: $<?= number_format($metrics['today_sales'], 2) ?></span>
    </div>
    <div class="metric-card">
        <span class="metric-label">Orders</span>
        <span class="metric-value"><?= number_format($metrics['order_count']) ?></span>
        <span class="metric-sub">Pending: <?= number_format($metrics['pending_orders']) ?></span>
    </div>
    <div class="metric-card">
        <span class="metric-label">Customers</span>
        <span class="metric-value"><?= number_format($metrics['customer_count']) ?></span>
        <span class="metric-sub">New this month: <?= number_format($metrics['recent_customers']) ?></span>
    </div>
    <div class="metric-card">
        <span class="metric-label">Inventory</span>
        <span class="metric-value"><?= number_format($metrics['items_in_stock']) ?></span>
        <span class="metric-sub">Low stock: <?= number_format($metrics['low_stock']) ?></span>
    </div>
</div>

<div class="dashboard-grid">
    <section class="dashboard-column">
        <div class="admin-card">
            <div class="card-header">
                <div>
                    <h3>Sales trend</h3>
                    <p class="card-subtitle">Revenue for the past 6 months</p>
                </div>
            </div>
            <canvas id="salesChart" data-sales-chart="<?= $salesChartJson ?>"></canvas>
        </div>

        <div class="admin-card">
            <div class="card-header">
                <div>
                    <h3>Order pipeline</h3>
                    <p class="card-subtitle">Monitor fulfillment progress</p>
                </div>
                <a href="<?php echo $prefix; ?>pages/orders.php" class="button secondary">View orders</a>
            </div>
            <div class="status-grid">
                <?php foreach ($orderBreakdown as $status => $total): ?>
                    <div class="status-card">
                        <span class="status-dot status-<?= htmlspecialchars($status) ?>"></span>
                        <div>
                            <strong><?= htmlspecialchars(ucfirst($status)) ?></strong>
                            <span><?= number_format($total) ?> orders</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="dashboard-column">
        <div class="admin-card">
            <div class="card-header">
                <div>
                    <h3>Quick actions</h3>
                    <p class="card-subtitle">Jump back into store management</p>
                </div>
            </div>
            <div class="quick-actions">
                <a href="<?php echo $prefix; ?>pages/product_form.php" class="button primary"><i class="fas fa-plus"></i> Add product</a>
                <a href="<?php echo $prefix; ?>pages/categories.php" class="button secondary"><i class="fas fa-tags"></i> Manage categories</a>
                <a href="<?php echo $prefix; ?>pages/orders.php" class="button secondary"><i class="fas fa-receipt"></i> Review orders</a>
                <a href="<?php echo $prefix; ?>pages/customers.php" class="button secondary"><i class="fas fa-users"></i> View customers</a>
            </div>
        </div>

        <div class="admin-card">
            <div class="card-header">
                <div>
                    <h3>Inventory snapshot</h3>
                    <p class="card-subtitle">Keep an eye on product availability</p>
                </div>
            </div>
            <ul class="insight-list">
                <li><span>Total products</span><strong><?= number_format($productOverview['total']) ?></strong></li>
                <li><span>Active listings</span><strong><?= number_format($productOverview['active']) ?></strong></li>
                <li><span>Inactive listings</span><strong><?= number_format($productOverview['inactive']) ?></strong></li>
                <li><span>Low stock alerts</span><strong><?= number_format($productOverview['low_stock']) ?></strong></li>
                <li><span>Out of stock</span><strong><?= number_format($productOverview['out_of_stock']) ?></strong></li>
            </ul>
        </div>
    </section>
</div>

<div class="dashboard-columns">
    <div class="admin-card">
        <div class="card-header">
            <h3>Recent orders</h3>
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
                            <td><span class="tag status-<?= htmlspecialchars($order['status']) ?>"><?= htmlspecialchars(ucfirst($order['status'])) ?></span></td>
                            <td>$<?= number_format((float) $order['total_price'], 2) ?></td>
                            <td><?= date('M j, Y', strtotime((string) $order['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-receipt"></i>
                <p>No orders yet. Share your store to start selling!</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="admin-card">
        <div class="card-header">
            <h3>Recently added products</h3>
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
                            <td><span class="tag status-<?= htmlspecialchars($product['status']) ?>"><?= htmlspecialchars(ucfirst($product['status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-box"></i>
                <p>No products available. Create your first listing.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
