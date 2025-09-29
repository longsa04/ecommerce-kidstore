<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';

kidstore_admin_require_login();

$pageTitle = 'Categories';
$currentSection = 'categories';
$prefix = KIDSTORE_ADMIN_URL_PREFIX;

$status = $_GET['status'] ?? 'all';
$status = in_array($status, ['all', 'active', 'inactive'], true) ? $status : 'all';

$categories = kidstore_admin_fetch_all_categories(true);
if ($status === 'active') {
    $categories = array_values(array_filter($categories, static function (array $category): bool {
        return !empty($category['is_active']);
    }));
} elseif ($status === 'inactive') {
    $categories = array_values(array_filter($categories, static function (array $category): bool {
        return empty($category['is_active']);
    }));
}

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);
$error = $_SESSION['admin_error'] ?? null;
unset($_SESSION['admin_error']);

$csrfToken = kidstore_csrf_token();

include __DIR__ . '/../includes/header.php';
?>

<?php if ($flash): ?>
    <div class="admin-card" style="background:#dcfce7;color:#166534;">
        <?= htmlspecialchars($flash) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="admin-card" style="background:#fee2e2;color:#b91c1c;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-header" style="margin-bottom:16px;">
        <h3>All Categories</h3>
        <div style="display:flex;gap:12px;align-items:center;">
            <form method="get" style="display:flex;gap:10px;align-items:center;">
                <select name="status" style="padding:8px 12px;border-radius:10px;border:1px solid #d1d5db;">
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
                <button class="button secondary" type="submit">Filter</button>
            </form>
            <a href="<?php echo $prefix; ?>pages/category_form.php" class="button primary">
                <i class="fas fa-plus"></i> Add Category
            </a>
        </div>
    </div>

    <?php if ($categories): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?= htmlspecialchars($category['category_name']) ?></td>
                        <td>
                            <?php
                            $description = trim((string)($category['description'] ?? ''));
                            if ($description === '') {
                                echo '<span style="color:#6b7280;">No description</span>';
                            } else {
                                $preview = $description;
                                if (function_exists('mb_strlen') ? mb_strlen($description) > 80 : strlen($description) > 80) {
                                    $preview = (function_exists('mb_substr') ? mb_substr($description, 0, 80) : substr($description, 0, 80)) . '…';
                                }
                                echo htmlspecialchars($preview);
                            }
                            ?>
                        </td>
                        <td>
                            <?php $isActive = !empty($category['is_active']); ?>
                            <span class="tag" style="background: <?= $isActive ? 'rgba(34,197,94,0.15);color:#16a34a;' : 'rgba(248,113,113,0.15);color:#ef4444;' ?>">
                                <?= $isActive ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($category['updated_at'])): ?>
                                <?= htmlspecialchars(date('M j, Y', strtotime($category['updated_at']))) ?>
                            <?php elseif (!empty($category['created_at'])): ?>
                                <?= htmlspecialchars(date('M j, Y', strtotime($category['created_at']))) ?>
                            <?php else: ?>
                                <span style="color:#6b7280;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="display:flex;gap:10px;">
                            <a href="category_form.php?id=<?= (int) $category['category_id'] ?>" class="button secondary" style="padding:6px 12px;">Edit</a>
                            <?php if ($isActive): ?>
                                <form method="post" action="<?php echo $prefix; ?>pages/category_status.php" data-confirm="Deactivate this category?" style="margin:0;">
                                    <input type="hidden" name="action" value="deactivate" />
                                    <input type="hidden" name="category_id" value="<?= (int) $category['category_id'] ?>" />
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
                                    <button type="submit" class="button danger" style="padding:6px 12px;">Deactivate</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?php echo $prefix; ?>pages/category_status.php" style="margin:0;">
                                    <input type="hidden" name="action" value="activate" />
                                    <input type="hidden" name="category_id" value="<?= (int) $category['category_id'] ?>" />
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
                                    <button type="submit" class="button primary" style="padding:6px 12px;">Activate</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No categories found.</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>