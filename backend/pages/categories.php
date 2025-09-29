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
$search = trim((string)($_GET['q'] ?? ''));

$categories = kidstore_admin_fetch_all_categories(true);
$activeCount = count(array_filter($categories, static fn (array $category): bool => !empty($category['is_active'])));
$overview = [
    'total' => count($categories),
    'active' => $activeCount,
    'inactive' => count($categories) - $activeCount,
];

if ($status === 'active') {
    $categories = array_values(array_filter($categories, static fn (array $category): bool => !empty($category['is_active'])));
} elseif ($status === 'inactive') {
    $categories = array_values(array_filter($categories, static fn (array $category): bool => empty($category['is_active'])));
}

if ($search !== '') {
    $categories = array_values(array_filter($categories, static function (array $category) use ($search): bool {
        $haystack = strtolower(($category['category_name'] ?? '') . ' ' . ($category['description'] ?? ''));
        return strpos($haystack, strtolower($search)) !== false;
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
    <div class="card-header">
        <div>
            <h3>Category overview</h3>
            <p class="card-subtitle">Manage <?= number_format($overview['total']) ?> collection<?= $overview['total'] === 1 ? '' : 's' ?></p>
        </div>
    </div>
    <ul class="insight-list">
        <li><span>Total categories</span><strong><?= number_format($overview['total']) ?></strong></li>
        <li><span>Active</span><strong><?= number_format($overview['active']) ?></strong></li>
        <li><span>Inactive</span><strong><?= number_format($overview['inactive']) ?></strong></li>
    </ul>
</div>

<div class="admin-card">
    <div class="card-header">
        <div>
            <h3>All categories</h3>
            <p class="card-subtitle">Search and update merchandising groups</p>
        </div>
        <a href="<?php echo $prefix; ?>pages/category_form.php" class="button primary">
            <i class="fas fa-plus"></i> Add category
        </a>
    </div>

    <form method="get" class="form-inline" style="margin-bottom:16px;">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name or description" class="input-control" />
        <select name="status" class="input-control">
            <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
        <button class="button secondary" type="submit">Apply</button>
        <?php if ($search !== '' || $status !== 'all'): ?>
            <a href="<?php echo $prefix; ?>pages/categories.php" class="button secondary">Reset</a>
        <?php endif; ?>
    </form>

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
                            <span class="tag status-<?= $isActive ? 'active' : 'inactive' ?>">
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
                            <a href="category_form.php?id=<?= (int) $category['category_id'] ?>" class="button secondary button-compact">Edit</a>
                            <?php if ($isActive): ?>
                                <form method="post" action="<?php echo $prefix; ?>pages/category_status.php" data-confirm="Deactivate this category?" style="margin:0;">
                                    <input type="hidden" name="action" value="deactivate" />
                                    <input type="hidden" name="category_id" value="<?= (int) $category['category_id'] ?>" />
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
                                    <button type="submit" class="button danger button-compact">Deactivate</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?php echo $prefix; ?>pages/category_status.php" style="margin:0;">
                                    <input type="hidden" name="action" value="activate" />
                                    <input type="hidden" name="category_id" value="<?= (int) $category['category_id'] ?>" />
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
                                    <button type="submit" class="button primary button-compact">Activate</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-folder-open"></i>
            <p>No categories match the selected filters.</p>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>