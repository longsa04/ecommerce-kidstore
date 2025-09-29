<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$prefix = KIDSTORE_FRONT_URL_PREFIX;
$currentUser = kidstore_current_user();
$isGuest = $currentUser === null;
$items = getCartItems();
$cartTotal = getCartTotal();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="kidstore-csrf-token" content="<?= htmlspecialchars(kidstore_frontend_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
    <title>Your Cart - Little Stars</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="<?php echo $prefix; ?>assets/styles.css" rel="stylesheet" />
    <style>
        .cart-page {
            padding: 60px 0;
        }
        .cart-container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .cart-auth-alert {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            margin: 0 30px 20px;
            padding: 18px 24px;
            border-radius: 14px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
        }
        .cart-auth-alert i {
            font-size: 1.5rem;
            margin-top: 4px;
        }
        .cart-auth-alert strong {
            display: block;
            font-size: 1.05rem;
            margin-bottom: 4px;
        }
        .cart-auth-alert p {
            margin: 0 0 10px;
            color: #b45309;
        }
        .cart-auth-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .cart-auth-actions a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 999px;
            font-weight: 600;
            text-decoration: none;
        }
        .cart-auth-actions a:first-child {
            background: linear-gradient(135deg, #f97316, #fb923c);
            color: #fff;
        }
        .cart-auth-actions a:last-child {
            background: #fff;
            color: #b45309;
            border: 1px solid rgba(234, 88, 12, 0.2);
        }
        .cart-header {
            padding: 25px 30px;
            border-bottom: 1px solid #f0f0f0;
        }
        .cart-header h1 {
            font-size: 2rem;
            margin-bottom: 6px;
        }
        .cart-header p {
            color: #666;
        }
        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }
        .cart-table th,
        .cart-table td {
            padding: 18px 30px;
            text-align: left;
            border-bottom: 1px solid #f5f5f5;
        }
        .cart-table th {
            background: #fafafa;
            font-weight: 600;
            color: #444;
        }
        .product-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .product-info__details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .product-info__details strong {
            font-size: 1rem;
            color: #0f172a;
        }
        .product-info__stock {
            color: #64748b;
            font-size: 0.85rem;
        }
        .product-info__stock.is-low {
            color: #b45309;
        }
        .product-info__stock.is-out {
            color: #dc2626;
        }
        .product-info__image {
            flex-shrink: 0;
            width: 64px;
            height: 64px;
            border-radius: 12px;
            overflow: hidden;
            background: #f8fafc;
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.2);
            display: grid;
            place-items: center;
        }
        .product-info__image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .quantity-control {
            display: inline-flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 12px;
            overflow: hidden;
        }
        .quantity-control button {
            background: #f5f5f5;
            border: none;
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            cursor: pointer;
        }
        .quantity-control button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .quantity-control input {
            width: 50px;
            border: none;
            text-align: center;
            font-size: 1rem;
        }
         .quantity-control input:focus {
            outline: none;
        }
        .remove-item {
            color: #f44336;
            cursor: pointer;
        }
        .cart-summary {
            padding: 25px 30px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }
        .cart-summary .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        .empty-cart {
            text-align: center;
            padding: 60px 30px;
        }
        .empty-cart h2 {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        .empty-cart p {
            color: #666;
            margin-bottom: 25px;
        }
        @media (max-width: 768px) {
            .cart-table th,
            .cart-table td {
                padding: 14px 18px;
             gap: 10px;
            }
            .product-info__image {
                width: 56px;
                height: 56px;
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../partials/header.php'; ?>
<main>
    <section class="cart-page">
        <div class="container">
            <div class="cart-container">
                <?php if ($isGuest): ?>
                    <div class="cart-auth-alert" role="alert">
                        <i class="fas fa-user-circle" aria-hidden="true"></i>
                        <div>
                            <strong>Save your picks by signing in</strong>
                            <p>Log in to sync your cart with your account and keep items reserved across devices.</p>
                            <div class="cart-auth-actions">
                                <a href="<?= htmlspecialchars(kidstore_frontend_url('pages/auth/login.php')) ?>">
                                    <i class="fas fa-sign-in-alt"></i>
                                    Log In
                                </a>
                                <a href="<?= htmlspecialchars(kidstore_frontend_url('pages/auth/register.php')) ?>">
                                    <i class="fas fa-user-plus"></i>
                                    Create Account
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($items)): ?>
                    <div class="cart-header">
                        <h1>Your Cart</h1>
                        <p>You have <?= getCartItemCount() ?> item<?= getCartItemCount() === 1 ? '' : 's' ?> ready to checkout.</p>
                    </div>
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $productId => $item): ?>
                                 <tr data-product-id="<?= (int) $productId ?>" data-stock="<?= isset($item['stock']) ? (int) $item['stock'] : 0 ?>">
                                    <td>
                                        <div class="product-info__image">
                                                <img src="<?= htmlspecialchars($item['image'] ?? '') ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy">
                                            </div>
                                            <div class="product-info__details">
                                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                                                <?php
                                                    $availableStock = isset($item['stock']) ? (int) $item['stock'] : 0;
                                                    $stockClass = 'product-info__stock';
                                                    if ($availableStock <= 0) {
                                                        $stockClass .= ' is-out';
                                                        $stockLabel = 'Currently unavailable';
                                                    } elseif ($availableStock <= 5) {
                                                        $stockClass .= ' is-low';
                                                        $stockLabel = 'Only ' . $availableStock . ' left';
                                                    } else {
                                                        $stockLabel = 'In stock: ' . $availableStock;
                                                    }
                                                ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>$<?= kidstore_format_price((float) $item['price']) ?></td>
                                    <td>
                                        <div class="quantity-control">
                                            <button class="decrease" type="button" aria-label="Reduce quantity">
                                                &minus;
                                            </button>
                                            <input type="number" class="quantity-input" value="<?= (int) $item['quantity'] ?>" min="1" inputmode="numeric" aria-label="Quantity for <?= htmlspecialchars($item['name']) ?>">
                                            <button class="increase" type="button" aria-label="Increase quantity">
                                                +
                                            </button>
                                        </div>
                                    </td>
                                    <td class="item-total">$<?= kidstore_format_price((float) $item['price'] * (int) $item['quantity']) ?></td>
                                    <td>
                                        <button class="remove-item" type="button" title="Remove item">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="cart-summary">
                        <div class="totals">
                            <strong>Subtotal:</strong> $<span id="cart-total"><?= kidstore_format_price($cartTotal) ?></span>
                        </div>
                        <div class="actions">
                            <button class="btn btn-secondary" id="clear-cart" type="button">
                                <i class="fas fa-times"></i>
                                Clear Cart
                            </button>
                            <a href="<?php echo $prefix; ?>pages/shop.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i>
                                Continue Shopping
                            </a>
                            <a href="<?php echo $prefix; ?>pages/checkout.php" class="btn btn-primary">
                                <i class="fas fa-credit-card"></i>
                                Proceed to Checkout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-cart">
                        <h2>Your cart is empty</h2>
                        <p>Looks like you haven't added anything to your cart yet.</p>
                        <a href="<?php echo $prefix; ?>pages/shop.php" class="btn btn-primary">
                            <i class="fas fa-shopping-bag"></i>
                            Start Shopping
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
<div id="notification"></div>
<script src="<?php echo $prefix; ?>assets/script.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const notify = typeof window.kidstoreShowNotification === 'function'
            ? window.kidstoreShowNotification
            : () => {};
        const updateBadge = typeof window.kidstoreUpdateCartBadge === 'function'
            ? window.kidstoreUpdateCartBadge
            : () => {};
        const buildJsonHeaders = typeof window.kidstoreBuildJsonHeaders === 'function'
            ? window.kidstoreBuildJsonHeaders
            : function () {
                const headers = { 'Content-Type': 'application/json' };
                const token = window.KIDSTORE_CSRF_TOKEN;
                if (token) {
                    const headerName = window.KIDSTORE_CSRF_HEADER || 'X-Kidstore-CSRF';
                    headers[headerName] = token;
                }
                return headers;
            };

        function syncTotals(data) {
            if (typeof data.cartTotal === 'number') {
                document.getElementById('cart-total').textContent = data.cartTotal.toFixed(2);
            }
            if (typeof data.cartCount === 'number') {
                updateBadge(data.cartCount);
            }
        }

        const clampQuantity = (value) => {
            const numeric = Number.parseInt(value, 10);
            if (!Number.isFinite(numeric) || numeric < 1) {
                return 1;
            }
            return numeric;
        };

        const parseStock = (value) => {
            const numeric = Number.parseInt(value, 10);
            if (!Number.isFinite(numeric) || numeric < 0) {
                return 0;
            }
            return numeric;
        };

        const updateControlState = (control, quantity, stock) => {
            const decrease = control.querySelector('.decrease');
            const increase = control.querySelector('.increase');
            if (decrease) {
                decrease.disabled = quantity <= 1;
            }
            if (increase) {
                if (stock === 0) {
                    increase.disabled = true;
                } else if (stock > 0) {
                    increase.disabled = quantity >= stock;
                } else {
                    increase.disabled = false;
                }
            }
        };

        const updateStockLabel = (row, stock) => {
            const label = row.querySelector('.product-info__stock');
            if (!label) {
                return;
            }

            label.classList.remove('is-low', 'is-out');

            if (stock <= 0) {
                label.textContent = 'Currently unavailable';
                label.classList.add('is-out');
            } else if (stock <= 5) {
                label.textContent = `Only ${stock} left`;
                label.classList.add('is-low');
            } else {
                label.textContent = `In stock: ${stock}`;
            }
        };

        document.querySelectorAll('.quantity-control').forEach(control => {
            const row = control.closest('tr');

             if (!row) {
                return;
            }

            const productId = Number.parseInt(row.dataset.productId, 10);
            if (!Number.isFinite(productId)) {
                return;
            }

            const input = control.querySelector('.quantity-input');

            if (!input) {
                return;
            }

            const updateQuantity = (nextQuantity) => {
                const desiredQuantity = clampQuantity(nextQuantity);


          
                fetch('<?php echo $prefix; ?>actions/update_cart.php', {
                    method: 'POST',
                    headers: buildJsonHeaders(),
                    body: JSON.stringify({ action: 'update', productId, quantity: desiredQuantity })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            notify(data.message || 'Unable to update cart.', true);
                            return;
                        }
                        // input.value = data.itemQuantity;
                        // row.querySelector('.item-total').textContent = `$${data.itemTotal.toFixed(2)}`;
                        const itemQuantity = clampQuantity(data.itemQuantity);
                        const itemTotal = Number.parseFloat(data.itemTotal);
                        const stock = typeof data.stock === 'number' ? Math.max(0, data.stock) : parseStock(row.dataset.stock);

                        input.value = itemQuantity;
                        row.dataset.stock = String(stock);

                        const itemTotalCell = row.querySelector('.item-total');
                        if (itemTotalCell && Number.isFinite(itemTotal)) {
                            itemTotalCell.textContent = `$${itemTotal.toFixed(2)}`;
                        }

                        updateStockLabel(row, stock);
                        updateControlState(control, itemQuantity, stock);
                        syncTotals(data);
                        if (stock === 0) {
                            notify('This item is now out of stock.', true);
                        } else if (itemQuantity >= stock) {
                            notify('Reached available stock limit for this item.', true);
                        }
                    })
                    .catch(() => notify('Unable to update cart right now.', true));
            };

            const initialStock = parseStock(row.dataset.stock);
            updateStockLabel(row, initialStock);
            updateControlState(control, clampQuantity(input.value), initialStock);

            const decrease = control.querySelector('.decrease');
            const increase = control.querySelector('.increase');

            if (decrease) {
                decrease.addEventListener('click', () => {
                    const current = clampQuantity(input.value);
                    updateQuantity(current - 1);
                });
            }

            if (increase) {
                increase.addEventListener('click', () => {
                    const current = clampQuantity(input.value);
                    updateQuantity(current + 1);
                });
            }

            input.addEventListener('change', () => {
                updateQuantity(input.value);
            });

            input.addEventListener('input', () => {
                const sanitized = clampQuantity(input.value);
                if (String(sanitized) !== input.value) {
                    input.value = sanitized;
                }
                updateControlState(control, sanitized, parseStock(row.dataset.stock));
            });
        });

        document.querySelectorAll('.remove-item').forEach(button => {
            const row = button.closest('tr');
            const productId = parseInt(row.dataset.productId, 10);
            button.addEventListener('click', () => {
                fetch('<?php echo $prefix; ?>actions/update_cart.php', {
                    method: 'POST',
                    headers: buildJsonHeaders(),
                    body: JSON.stringify({ action: 'remove', productId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            notify('Unable to remove item right now.', true);
                            return;
                        }
                        row.remove();
                        syncTotals(data);
                        notify('Item removed from cart.');
                        if (data.cartCount === 0) {
                            window.location.reload();
                        }
                    })
                    .catch(() => notify('Unable to remove item right now.', true));
            });
        });

        const clearCartBtn = document.getElementById('clear-cart');
        if (clearCartBtn) {
            clearCartBtn.addEventListener('click', () => {
                fetch('<?php echo $prefix; ?>actions/update_cart.php', {
                    method: 'POST',
                    headers: buildJsonHeaders(),
                    body: JSON.stringify({ action: 'clear' })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            notify('Unable to clear cart right now.', true);
                            return;
                        }
                        updateBadge(0);
                        notify('Cart cleared');
                        window.location.reload();
                    })
                    .catch(() => notify('Unable to clear cart right now.', true));
            });
        }
    });
</script>
</body>
</html>
