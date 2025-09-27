<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';

kidstore_admin_require_login();

$pageTitle = 'Customers';
$currentSection = 'customers';

$pdo = kidstore_get_pdo();
$customers = $pdo->query('SELECT u.user_id, u.name, u.email, u.phone, u.created_at, COUNT(o.order_id) AS orders_count, COALESCE(SUM(o.total_price), 0) AS total_spent FROM tbl_users u LEFT JOIN tbl_orders o ON o.user_id = u.user_id WHERE u.is_admin = 0 GROUP BY u.user_id ORDER BY u.created_at DESC')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-card">
    <div class="admin-header" style="margin-bottom:16px;">
        <h3>Customers</h3>
    </div>
    <?php if ($customers): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Orders</th>
                    <th>Total Spent</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?= htmlspecialchars($customer['name']) ?></td>
                        <td><?= htmlspecialchars($customer['email']) ?></td>
                        <td><?= htmlspecialchars($customer['phone'] ?? '?') ?></td>
                        <td><?= (int) $customer['orders_count'] ?></td>
                        <td>$<?= number_format((float) $customer['total_spent'], 2) ?></td>
                        <td><?= date('M j, Y', strtotime((string) $customer['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No customers yet.</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
