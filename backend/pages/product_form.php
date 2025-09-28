<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';

kidstore_admin_require_login();

$prefix = KIDSTORE_ADMIN_URL_PREFIX;
$csrfToken = kidstore_csrf_token();
$uploadDir = dirname(__DIR__, 2) . '/frontend/assets/img';

$pageTitle = 'Add Product';
$currentSection = 'products';

$productId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $productId !== null;
$errors = [];

$product = [
    'product_name' => '',
    'description' => '',
    'price' => 0,
    'stock_quantity' => 0,
    'category_id' => null,
    'image_url' => '',
    'status' => 'active',
    'is_active' => 1,
];

if ($isEdit) {
    $pageTitle = 'Edit Product';
    $existing = kidstore_fetch_product($productId);
    if (!$existing) {
        $_SESSION['admin_flash'] = 'Product not found.';
        header('Location: ' . $prefix . 'pages/products.php');
        exit;
    }
    $product = array_merge($product, $existing);
}

$priceFieldValue = number_format((float) $product['price'], 2, '.', '');
$stockFieldValue = (string) (int) $product['stock_quantity'];

if (isset($_SESSION['admin_form_errors'])) {
    $errors = (array) $_SESSION['admin_form_errors'];
    unset($_SESSION['admin_form_errors']);
}

if (isset($_SESSION['admin_form_values'])) {
    $formValues = $_SESSION['admin_form_values'];
    unset($_SESSION['admin_form_values']);

    if (!empty($formValues['product']) && is_array($formValues['product'])) {
        $product = array_merge($product, $formValues['product']);
    }

    if (isset($formValues['price_field'])) {
        $priceFieldValue = (string) $formValues['price_field'];
    }
    if (isset($formValues['stock_field'])) {
        $stockFieldValue = (string) $formValues['stock_field'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $priceInput = $_POST['price'] ?? '';
    $stockInput = $_POST['stock_quantity'] ?? '';

    $priceFieldValue = trim((string) $priceInput);
    $stockFieldValue = trim((string) $stockInput);

    $product['product_name'] = trim((string) ($_POST['product_name'] ?? ''));
    $product['description'] = trim((string) ($_POST['description'] ?? ''));
    $product['category_id'] = $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null;
    $product['status'] = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
    $product['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if (!kidstore_csrf_validate($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security validation failed. Please try again.';
    } else {
        $validationErrors = kidstore_admin_collect_product_validation_errors([
            'price' => $priceFieldValue,
            'stock_quantity' => $stockFieldValue,
        ]);

        if (!$validationErrors) {
            $product['price'] = (float) $priceFieldValue;
            $product['stock_quantity'] = (int) $stockFieldValue;
        }

        $errors = array_merge($errors, $validationErrors);

        $imagePath = $product['image_url'];
        $file = $_FILES['image_file'] ?? null;
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Unable to upload image. Please try again.';
            } else {
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                ];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                if (!isset($allowed[$mime])) {
                    $errors[] = 'Please upload a JPG, PNG, or WEBP image.';
                } elseif ($file['size'] > 4 * 1024 * 1024) {
                    $errors[] = 'Please upload an image smaller than 4MB.';
                } else {
                    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
                        $errors[] = 'Unable to prepare image storage directory.';
                    } else {
                        $filename = uniqid('prod_', true) . '.' . $allowed[$mime];
                        $destination = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
                        if (!move_uploaded_file($file['tmp_name'], $destination)) {
                            $errors[] = 'Failed to move uploaded image.';
                        } else {
                            if ($isEdit && !empty($product['image_url'])) {
                                $previous = dirname(__DIR__, 2) . '/frontend/' . ltrim($product['image_url'], '/');
                                if (strpos($product['image_url'], 'assets/img/') === 0 && is_file($previous)) {
                                    @unlink($previous);
                                }
                            }
                            $imagePath = 'assets/img/' . $filename;
                        }
                    }
                }
            }
        }

        if (!$isEdit && $imagePath === '') {
            $errors[] = 'Please upload a product image.';
        }

        $product['image_url'] = $imagePath;
        $product['image_path'] = $imagePath;

        if (!$errors) {
            try {
                if ($isEdit) {
                    kidstore_admin_update_product($productId, $product);
                    $_SESSION['admin_flash'] = 'Product updated successfully.';
                } else {
                    $newId = kidstore_admin_create_product($product);
                    $_SESSION['admin_flash'] = 'Product created successfully.';
                    $productId = $newId;
                }
                header('Location: ' . $prefix . 'pages/products.php');
                exit;
            } catch (InvalidArgumentException $exception) {
                $errors = array_merge(
                    $errors,
                    array_filter(array_map('trim', explode("\n", $exception->getMessage())))
                );
            } catch (Throwable $exception) {
                $errors[] = 'An unexpected error occurred while saving the product. Please try again.';
            }
        }
    }

    if ($errors) {
        $_SESSION['admin_form_errors'] = $errors;
        $_SESSION['admin_form_values'] = [
            'product' => [
                'product_name' => $product['product_name'],
                'description' => $product['description'],
                'price' => $priceFieldValue,
                'stock_quantity' => $stockFieldValue,
                'category_id' => $product['category_id'],
                'image_url' => $product['image_url'],
                'status' => $product['status'],
                'is_active' => $product['is_active'],
            ],
            'price_field' => $priceFieldValue,
            'stock_field' => $stockFieldValue,
        ];

        $target = $prefix . 'pages/product_form.php';
        if ($isEdit) {
            $target .= '?id=' . $productId;
        }

        header('Location: ' . $target);
        exit;
    }
}


$categories = kidstore_fetch_categories(false);

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

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>" />
        <div class="form-grid">
            <div class="form-group">
                <label for="product_name">Name</label>
                <input type="text" id="product_name" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required />
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" id="price" name="price" value="<?= htmlspecialchars($priceFieldValue) ?>" step="0.01" min="0" required />
            </div>
            <div class="form-group">
                <label for="stock_quantity">Stock Quantity</label>
                <input type="number" id="stock_quantity" name="stock_quantity" value="<?= htmlspecialchars($stockFieldValue) ?>" min="0" required />
            </div>
            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <option value="">Unassigned</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['category_id'] ?>" <?= ((int) $product['category_id'] === (int) $category['category_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="form-group">
                <label for="image_file">Product image</label>
                <input type="file" id="image_file" name="image_file" accept="image/*" <?= $isEdit ? '' : 'required' ?> />
                <?php if (!empty($product['image_url'])): ?>
                    <div class="current-image">
                        <img src="<?= htmlspecialchars(kidstore_product_image($product['image_url'], $prefix)) ?>" alt="Current product image" />
                        <small>Current image</small>
                    </div>
                <?php endif; ?>
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?= htmlspecialchars($product['description']) ?></textarea>
            </div>
            <div class="form-group" style="align-self:end;">
                <label><input type="checkbox" name="is_active" value="1" <?= !empty($product['is_active']) ? 'checked' : '' ?> /> Visible on storefront</label>
            </div>
        </div>
        <div class="form-actions">
            <button class="button primary" type="submit">Save Product</button>
            <a href="<?php echo $prefix; ?>pages/products.php" class="button secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
