<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';

kidstore_admin_require_login();

$pageTitle = 'Products';
$currentSection = 'products';

$status = $_GET['status'] ?? '';
$search = trim((string)($_GET['q'] ?? ''));
$availability = $_GET['availability'] ?? '';
$sort = $_GET['sort'] ?? 'name';
$validSorts = ['name', 'newest', 'price_asc', 'price_desc'];

$filters = [
    'sort' => in_array($sort, $validSorts, true) ? $sort : 'name',
    'activeOnly' => false,
];

if ($status !== '') {
    $filters['status'] = $status;
}
if ($search !== '') {
    $filters['search'] = $search;
}
if (in_array($availability, ['in_stock', 'out_of_stock'], true)) {
    $filters['availability'] = $availability;
}

$products = kidstore_admin_fetch_products($filters);
$productOverview = kidstore_admin_product_overview();
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
    <div class="card-header">
        <div>
            <h3>Inventory overview</h3>
            <p class="card-subtitle">Snapshot of your product catalog</p>
        </div>
    </div>
    <ul class="insight-list">
        <li><span>Total products</span><strong><?= number_format($productOverview['total']) ?></strong></li>
        <li><span>Active</span><strong><?= number_format($productOverview['active']) ?></strong></li>
        <li><span>Inactive</span><strong><?= number_format($productOverview['inactive']) ?></strong></li>
        <li><span>Low stock</span><strong><?= number_format($productOverview['low_stock']) ?></strong></li>
        <li><span>Out of stock</span><strong><?= number_format($productOverview['out_of_stock']) ?></strong></li>
    </ul>
</div>

<div class="admin-card">
    <div class="card-header">
        <div>
            <h3>All products</h3>
            <p class="card-subtitle">Search, filter, and manage listings</p>
        </div>
        <a href="<?php echo $prefix; ?>pages/product_form.php" class="button primary"><i class="fas fa-plus"></i> Add product</a>
    </div>

    <form method="get" class="form-inline" style="margin-bottom:16px;">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or category" class="input-control" />
        <select name="status" class="input-control">
            <option value="">All statuses</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
        <select name="availability" class="input-control">
            <option value="">Any availability</option>
            <option value="in_stock" <?= $availability === 'in_stock' ? 'selected' : '' ?>>In stock</option>
            <option value="out_of_stock" <?= $availability === 'out_of_stock' ? 'selected' : '' ?>>Out of stock</option>
        </select>
        <select name="sort" class="input-control">
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Alphabetical</option>
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to high</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to low</option>
        </select>
        <button class="button secondary" type="submit">Apply</button>
        <?php if ($search !== '' || $status !== '' || $availability !== '' || $sort !== 'name'): ?>
            <a href="<?php echo $prefix; ?>pages/products.php" class="button secondary">Reset</a>
        <?php endif; ?>
    </form>

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
                            <span class="tag status-<?= htmlspecialchars($product['status']) ?>">
                                <?= htmlspecialchars(ucfirst($product['status'])) ?>
                            </span>
                        </td>
                        <td style="display:flex;gap:10px;">
                            <a href="product_form.php?id=<?= (int) $product['product_id'] ?>" class="button secondary button-compact">Edit</a>
                            <form method="post" action="<?php echo $prefix; ?>pages/product_delete.php" data-confirm="Archive this product?" style="margin:0;">
                                <input type="hidden" name="product_id" value="<?= (int) $product['product_id'] ?>" />
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
                                <button type="submit" class="button danger button-compact">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-box"></i>
            <p>No products match the selected filters.</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
