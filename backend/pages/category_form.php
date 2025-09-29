<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';

kidstore_admin_require_login();

$prefix = KIDSTORE_ADMIN_URL_PREFIX;
$pageTitle = 'Add Category';
$currentSection = 'categories';

$categoryId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $categoryId !== null;
$errors = [];

$category = [
    'category_name' => '',
    'description' => '',
    'is_active' => 1,
];

if ($isEdit) {
    $pageTitle = 'Edit Category';
    $existing = kidstore_admin_fetch_category($categoryId);
    if (!$existing) {
        $_SESSION['admin_flash'] = 'Category not found.';
        header('Location: ' . $prefix . 'pages/categories.php');
        exit;
    }
    $category = array_merge($category, $existing);
}

if (isset($_SESSION['admin_form_errors'])) {
    $errors = (array) $_SESSION['admin_form_errors'];
    unset($_SESSION['admin_form_errors']);
}

if (isset($_SESSION['admin_form_values'])) {
    $values = (array) $_SESSION['admin_form_values'];
    unset($_SESSION['admin_form_values']);

    $category = array_merge($category, array_intersect_key($values, $category));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category['category_name'] = trim((string) ($_POST['category_name'] ?? ''));
    $category['description'] = trim((string) ($_POST['description'] ?? ''));
    $category['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if (!kidstore_csrf_validate($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security validation failed. Please try again.';
    } else {
        try {
            if ($isEdit) {
                kidstore_admin_update_category($categoryId, $category);
                $_SESSION['admin_flash'] = 'Category updated successfully.';
            } else {
                kidstore_admin_create_category($category);
                $_SESSION['admin_flash'] = 'Category created successfully.';
            }

            header('Location: ' . $prefix . 'pages/categories.php');
            exit;
        } catch (InvalidArgumentException $exception) {
            $errors = array_merge(
                $errors,
                array_filter(array_map('trim', explode("\n", $exception->getMessage())))
            );
        } catch (Throwable $exception) {
            $errors[] = 'An unexpected error occurred while saving the category. Please try again.';
        }
    }

    if ($errors) {
        $_SESSION['admin_form_errors'] = $errors;
        $_SESSION['admin_form_values'] = $category;

        $target = $prefix . 'pages/category_form.php';
        if ($isEdit) {
            $target .= '?id=' . $categoryId;
        }

        header('Location: ' . $target);
        exit;
    }
}

$csrfToken = kidstore_csrf_token();

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-card">
    <h3><?= htmlspecialchars($pageTitle) ?></h3>

    <?php if ($errors): ?>
        <div style="background:#fee2e2;color:#b91c1c;padding:12px 16px;border-radius:12px;margin:16px 0;">
            <ul style="margin:0;padding-left:18px;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />

        <div class="form-group">
            <label for="category_name">Name</label>
            <input type="text" id="category_name" name="category_name" value="<?= htmlspecialchars($category['category_name']) ?>" maxlength="<?= (int) KIDSTORE_ADMIN_CATEGORY_NAME_MAX_LENGTH ?>" required />
        </div>

        <div class="form-group">
            <label for="description">Description <span style="color:#9ca3af;">(optional)</span></label>
            <textarea id="description" name="description" rows="4" maxlength="<?= (int) KIDSTORE_ADMIN_CATEGORY_DESCRIPTION_MAX_LENGTH ?>"><?= htmlspecialchars($category['description']) ?></textarea>
        </div>

        <div class="form-group" style="margin:16px 0;">
            <label><input type="checkbox" name="is_active" value="1" <?= !empty($category['is_active']) ? 'checked' : '' ?> /> Visible in storefront</label>
        </div>

        <div class="form-actions">
            <a href="<?php echo $prefix; ?>pages/categories.php" class="button secondary">Cancel</a>
            <button type="submit" class="button primary">Save Category</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>