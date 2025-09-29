<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';

kidstore_admin_require_login();

$pageTitle = 'Customers';
$currentSection = 'customers';
$prefix = KIDSTORE_ADMIN_URL_PREFIX;

$search = trim((string)($_GET['q'] ?? ''));
$pdo = kidstore_get_pdo();
$customers = $pdo->query('SELECT u.user_id, u.name, u.email, u.phone, u.created_at, COUNT(o.order_id) AS orders_count, COALESCE(SUM(o.total_price), 0) AS total_spent FROM tbl_users u LEFT JOIN tbl_orders o ON o.user_id = u.user_id WHERE u.is_admin = 0 GROUP BY u.user_id ORDER BY u.created_at DESC')->fetchAll();

if ($search !== '') {
    $customers = array_values(array_filter($customers, static function (array $customer) use ($search): bool {
        $needle = strtolower($search);
        return strpos(strtolower((string)($customer['name'] ?? '')), $needle) !== false
            || strpos(strtolower((string)($customer['email'] ?? '')), $needle) !== false;
    }));
}

$glance = kidstore_admin_customer_glance();
$topCustomer = null;
if ($customers) {
    $topCustomer = $customers[0];
    foreach ($customers as $candidate) {
        if ((float) $candidate['total_spent'] > (float) $topCustomer['total_spent']) {
            $topCustomer = $candidate;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-card">
    <div class="card-header">
        <div>
            <h3>Customer insights</h3>
            <p class="card-subtitle">Understand your audience growth</p>
        </div>
    </div>
    <ul class="insight-list">
        <li><span>Total customers</span><strong><?= number_format($glance['total']) ?></strong></li>
        <li><span>Joined last 30 days</span><strong><?= number_format($glance['new_customers']) ?></strong></li>
        <li><span>Lifetime revenue</span><strong>$<?= number_format((float) $glance['gross_revenue'], 2) ?></strong></li>
        <?php if ($topCustomer): ?>
            <li><span>Top spender</span><strong><?= htmlspecialchars($topCustomer['name'] ?? 'Unknown') ?> ($<?= number_format((float) $topCustomer['total_spent'], 2) ?>)</strong></li>
        <?php endif; ?>
    </ul>
</div>

<div class="admin-card">
    <div class="card-header">
        <div>
            <h3>Customers</h3>
            <p class="card-subtitle">View purchase history and contact info</p>
        </div>
    </div>
    <form method="get" class="form-inline" style="margin-bottom:16px;">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name or email" class="input-control" />
        <button class="button secondary" type="submit">Search</button>
        <?php if ($search !== ''): ?>
            <a href="<?php echo $prefix; ?>pages/customers.php" class="button secondary">Reset</a>
        <?php endif; ?>
    </form>
    <?php if ($customers): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Orders</th>
                    <th>Total spent</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?= htmlspecialchars($customer['name']) ?></td>
                        <td><?= htmlspecialchars($customer['email']) ?></td>
                        <td><?= htmlspecialchars($customer['phone'] ?? '—') ?></td>
                        <td><?= (int) $customer['orders_count'] ?></td>
                        <td>$<?= number_format((float) $customer['total_spent'], 2) ?></td>
                        <td><?= date('M j, Y', strtotime((string) $customer['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-user"></i>
            <p>No customers match the search criteria.</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
