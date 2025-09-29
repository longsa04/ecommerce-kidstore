<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';

kidstore_admin_require_login();

$pageTitle = 'Products';
$currentSection = 'products';

$status = $_GET['status'] ?? '';
$filters = [
    'sort' => 'name',
    'activeOnly' => false,
];
if ($status !== '') {
    $filters['status'] = $status;
}
$products = kidstore_admin_fetch_products($filters);
$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);
$csrfToken = kidstore_csrf_token();

include __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?>
    <div class="admin-card" style="background:#dcfce7;color:#166534;">
        <?= htmlspecialchars($flash) ?>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-header" style="margin-bottom:16px;">
        <h3>All Products</h3>
        <div style="display:flex;gap:12px;align-items:center;">
            <form method="get" style="display:flex;gap:10px;align-items:center;">
                <select name="status" style="padding:8px 12px;border-radius:10px;border:1px solid #d1d5db;">
                    <option value="">All Statuses</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
                <button class="button secondary" type="submit">Filter</button>
            </form>
            <a href="<?php echo $prefix; ?>pages/product_form.php" class="button primary"><i class="fas fa-plus"></i> Add Product</a>
        </div>
    </div>
    <?php if ($products): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['product_name']) ?></td>
                        <td><?= htmlspecialchars($product['category_name'] ?? 'Unassigned') ?></td>
                        <td>$<?= number_format((float) $product['price'], 2) ?></td>
                        <td><?= (int) $product['stock_quantity'] ?></td>
                        <td>
                            <span class="tag" style="background: <?= $product['status'] === 'active' ? 'rgba(34,197,94,0.15);color:#16a34a;' : 'rgba(248,113,113,0.15);color:#ef4444;' ?>">
                                <?= htmlspecialchars(ucfirst($product['status'])) ?>
                            </span>
                        </td>
                        <td style="display:flex;gap:10px;">
                            <a href="product_form.php?id=<?= (int) $product['product_id'] ?>" class="button secondary" style="padding:6px 12px;">Edit</a>
                            <form method="post" action="<?php echo $prefix; ?>pages/product_delete.php" data-confirm="Archive this product?" style="margin:0;">
                                <input type="hidden" name="product_id" value="<?= (int) $product['product_id'] ?>" />
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
                                <button type="submit" class="button danger" style="padding:6px 12px;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No products found.</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>



