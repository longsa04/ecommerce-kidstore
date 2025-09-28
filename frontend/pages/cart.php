<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$prefix = KIDSTORE_FRONT_URL_PREFIX;
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
        .product-info img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 12px;
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
        .quantity-control input {
            width: 50px;
            border: none;
            text-align: center;
            font-size: 1rem;
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
            }
            .product-info {
                flex-direction: column;
                align-items: flex-start;
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
                                <tr data-product-id="<?= (int) $productId ?>">
                                    <td>
                                        <div class="product-info">
                                            <img src="<?= htmlspecialchars($item['image'] ?: 'https://images.pexels.com/photos/45982/pexels-photo-45982.jpeg?auto=compress&cs=tinysrgb&w=300') ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                            <div>
                                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                                                <?php if (!empty($item['stock'])): ?>
                                                    <div style="color:#64748b;font-size:0.85rem;">In stock: <?= (int) $item['stock'] ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>$<?= kidstore_format_price((float) $item['price']) ?></td>
                                    <td>
                                        <div class="quantity-control">
                                            <button class="decrease" type="button">?</button>
                                            <input type="number" class="quantity-input" value="<?= (int) $item['quantity'] ?>" min="1">
                                            <button class="increase" type="button">+</button>
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

        document.querySelectorAll('.quantity-control').forEach(control => {
            const row = control.closest('tr');
            const productId = parseInt(row.dataset.productId, 10);
            const input = control.querySelector('.quantity-input');
            const decrease = control.querySelector('.decrease');
            const increase = control.querySelector('.increase');

            const updateQuantity = (quantity) => {
                if (quantity < 1) {
                    quantity = 1;
                }
                fetch('<?php echo $prefix; ?>actions/update_cart.php', {
                    method: 'POST',
                    headers: buildJsonHeaders(),
                    body: JSON.stringify({ action: 'update', productId, quantity })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            notify(data.message || 'Unable to update cart.', true);
                            return;
                        }
                        input.value = data.itemQuantity;
                        row.querySelector('.item-total').textContent = `$${data.itemTotal.toFixed(2)}`;
                        syncTotals(data);
                        if (data.stock !== undefined && data.itemQuantity >= data.stock) {
                            notify('Reached available stock limit for this item.', true);
                        }
                    })
                    .catch(() => notify('Unable to update cart right now.', true));
            };

            decrease.addEventListener('click', () => updateQuantity(parseInt(input.value, 10) - 1));
            increase.addEventListener('click', () => updateQuantity(parseInt(input.value, 10) + 1));
            input.addEventListener('change', () => updateQuantity(parseInt(input.value, 10)));
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
