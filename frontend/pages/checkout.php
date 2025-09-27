<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$prefix = KIDSTORE_FRONT_URL_PREFIX;
$items = getCartItems();
$cartTotal = getCartTotal();

if (empty($items)) {
    header('Location: cart.php');
    exit;
}

$error = $_SESSION['checkout_error'] ?? null;
$formData = $_SESSION['checkout_form_data'] ?? [];
unset($_SESSION['checkout_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Checkout - Little Stars</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="<?php echo $prefix; ?>assets/styles.css" rel="stylesheet" />
    <style>
        .checkout-page {
            padding: 60px 0;
        }
        .checkout-wrapper {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 30px;
        }
        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
            padding: 30px;
        }
        .card h2 {
            font-size: 1.8rem;
            margin-bottom: 20px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-group label {
            font-weight: 600;
            color: #374151;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid #d1d5db;
            font-size: 0.95rem;
            transition: border-color 0.2s ease;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #667eea;
            outline: none;
        }
        .order-summary ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .order-summary li {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .order-summary .total {
            border-top: 1px solid #e5e7eb;
            padding-top: 16px;
            margin-top: 16px;
            font-size: 1.1rem;
            font-weight: 700;
        }
        .btn-submit {
            margin-top: 25px;
            width: 100%;
            padding: 14px;
            border-radius: 50px;
            border: none;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
        }
        .secure-note {
            margin-top: 12px;
            font-size: 0.85rem;
            color: #64748b;
            text-align: center;
        }
        @media (max-width: 992px) {
            .checkout-wrapper {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../partials/header.php'; ?>
<main>
    <section class="checkout-page">
        <div class="container">
            <div class="checkout-wrapper">
                <div class="card">
                    <h2>Shipping & Contact</h2>
                    <?php if ($error): ?>
                        <div style="margin-bottom:16px;padding:12px 16px;border-radius:12px;background:#fee2e2;color:#b91c1c;">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    <form action="<?php echo $prefix; ?>actions/place_order.php" method="post" class="checkout-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" placeholder="e.g. Jamie Carter" value="<?= htmlspecialchars($formData['name'] ?? '') ?>" required />
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" placeholder="you@example.com" value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required />
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" placeholder="(+1) 555-1234" value="<?= htmlspecialchars($formData['phone'] ?? '') ?>" required />
                            </div>
                            <div class="form-group">
                                <label for="address">Street Address</label>
                                <input type="text" id="address" name="address" placeholder="123 Rainbow Lane" value="<?= htmlspecialchars($formData['address'] ?? '') ?>" required />
                            </div>
                            <div class="form-group">
                                <label for="city">City</label>
                                <input type="text" id="city" name="city" placeholder="Wonderland" value="<?= htmlspecialchars($formData['city'] ?? '') ?>" required />
                            </div>
                            <div class="form-group">
                                <label for="postal_code">Postal Code</label>
                                <input type="text" id="postal_code" name="postal_code" placeholder="90210" value="<?= htmlspecialchars($formData['postal_code'] ?? '') ?>" required />
                            </div>
                            <div class="form-group">
                                <label for="country">Country</label>
                                <input type="text" id="country" name="country" placeholder="United States" value="<?= htmlspecialchars($formData['country'] ?? '') ?>" required />
                            </div>
                            <div class="form-group">
                                <label for="payment_method">Payment Method</label>
                                <select id="payment_method" name="payment_method" required>
                                    <option value="Cash on Delivery" <?= ($formData['payment_method'] ?? '') === 'Cash on Delivery' ? 'selected' : '' ?>>Cash on Delivery</option>
                                    <option value="Credit Card" <?= ($formData['payment_method'] ?? '') === 'Credit Card' ? 'selected' : '' ?>>Credit Card (demo)</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-shipping-fast"></i>
                            Place Order
                        </button>
                        <div class="secure-note">
                            <i class="fas fa-lock"></i> Your information is secure and used only to fulfill your order.
                        </div>
                    </form>
                </div>
                <div class="card order-summary">
                    <h2>Order Summary</h2>
                    <ul>
                        <?php foreach ($items as $item): ?>
                            <li>
                                <span><?= htmlspecialchars($item['name']) ?> &times; <?= (int) $item['quantity'] ?></span>
                                <span>$<?= kidstore_format_price((float) $item['price'] * (int) $item['quantity']) ?></span>
                            </li>
                        <?php endforeach; ?>
                        <li class="total">
                            <span>Total</span>
                            <span>$<?= kidstore_format_price($cartTotal) ?></span>
                        </li>
                    </ul>
                    <p style="margin-top:18px;color:#64748b;font-size:0.9rem;">
                        Shipping is free for all orders over $50. Taxes are calculated at delivery.
                    </p>
                </div>
            </div>
        </div>
    </section>
</main>
<?php include __DIR__ . '/../partials/footer.php'; ?>
<script src="<?php echo $prefix; ?>assets/script.js"></script>
</body>
</html>


