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

$asiaCountries = [
    ['code' => 'SG', 'name' => 'Singapore', 'dial_code' => '+65', 'placeholder' => '+65 6123 4567', 'example' => '+65 6123 4567', 'pattern' => '^\\+65\\s?[0-9]{4}\\s?[0-9]{4}$'],
    ['code' => 'PH', 'name' => 'Philippines', 'dial_code' => '+63', 'placeholder' => '+63 912 345 6789', 'example' => '+63 912 345 6789', 'pattern' => '^\\+63\\s?[0-9]{3}\\s?[0-9]{3}\\s?[0-9]{4}$'],
    ['code' => 'MY', 'name' => 'Malaysia', 'dial_code' => '+60', 'placeholder' => '+60 12 345 6789', 'example' => '+60 12 345 6789', 'pattern' => '^\\+60\\s?[0-9]{1,2}[\\s-]?[0-9]{3}[\\s-]?[0-9]{4}$'],
    ['code' => 'ID', 'name' => 'Indonesia', 'dial_code' => '+62', 'placeholder' => '+62 812 3456 7890', 'example' => '+62 812 3456 7890', 'pattern' => '^\\+62\\s?[0-9]{2,3}[\\s-]?[0-9]{3,4}[\\s-]?[0-9]{3,4}$'],
    ['code' => 'TH', 'name' => 'Thailand', 'dial_code' => '+66', 'placeholder' => '+66 89 123 4567', 'example' => '+66 89 123 4567', 'pattern' => '^\\+66\\s?[0-9]{2}\\s?[0-9]{3}\\s?[0-9]{4}$'],
    ['code' => 'VN', 'name' => 'Vietnam', 'dial_code' => '+84', 'placeholder' => '+84 91 234 5678', 'example' => '+84 91 234 5678', 'pattern' => '^\\+84\\s?[0-9]{2}\\s?[0-9]{3}\\s?[0-9]{4}$'],
    ['code' => 'IN', 'name' => 'India', 'dial_code' => '+91', 'placeholder' => '+91 98765 43210', 'example' => '+91 98765 43210', 'pattern' => '^\\+91\\s?[0-9]{5}\\s?[0-9]{5}$'],
    ['code' => 'JP', 'name' => 'Japan', 'dial_code' => '+81', 'placeholder' => '+81 90 1234 5678', 'example' => '+81 90 1234 5678', 'pattern' => '^\\+81\\s?[0-9]{2}[\\s-]?[0-9]{4}[\\s-]?[0-9]{4}$'],
    ['code' => 'KR', 'name' => 'South Korea', 'dial_code' => '+82', 'placeholder' => '+82 10 1234 5678', 'example' => '+82 10 1234 5678', 'pattern' => '^\\+82\\s?[0-9]{2}[\\s-]?[0-9]{4}[\\s-]?[0-9]{4}$'],
    ['code' => 'CN', 'name' => 'China', 'dial_code' => '+86', 'placeholder' => '+86 131 2345 6789', 'example' => '+86 131 2345 6789', 'pattern' => '^\\+86\\s?[0-9]{3}\\s?[0-9]{4}\\s?[0-9]{4}$'],
    ['code' => 'HK', 'name' => 'Hong Kong', 'dial_code' => '+852', 'placeholder' => '+852 5123 4567', 'example' => '+852 5123 4567', 'pattern' => '^\\+852\\s?[0-9]{4}\\s?[0-9]{4}$'],
    ['code' => 'TW', 'name' => 'Taiwan', 'dial_code' => '+886', 'placeholder' => '+886 912 345 678', 'example' => '+886 912 345 678', 'pattern' => '^\\+886\\s?[0-9]{3}\\s?[0-9]{3}\\s?[0-9]{3}$'],
    ['code' => 'AE', 'name' => 'United Arab Emirates', 'dial_code' => '+971', 'placeholder' => '+971 50 123 4567', 'example' => '+971 50 123 4567', 'pattern' => '^\\+971\\s?[0-9]{2}\\s?[0-9]{3}\\s?[0-9]{4}$'],
    ['code' => 'SA', 'name' => 'Saudi Arabia', 'dial_code' => '+966', 'placeholder' => '+966 50 123 4567', 'example' => '+966 50 123 4567', 'pattern' => '^\\+966\\s?[0-9]{2}\\s?[0-9]{3}\\s?[0-9]{4}$'],
    ['code' => 'QA', 'name' => 'Qatar', 'dial_code' => '+974', 'placeholder' => '+974 3312 3456', 'example' => '+974 3312 3456', 'pattern' => '^\\+974\\s?[0-9]{4}\\s?[0-9]{4}$'],
    ['code' => 'KH', 'name' => 'Cambodia', 'dial_code' => '+855', 'placeholder' => '+855 12 345 678', 'example' => '+855 12 345 678', 'pattern' => '^\\+855\\s?[0-9]{2,3}[\\s-]?[0-9]{3}[\\s-]?[0-9]{3}$'],
];

$selectedCountry = trim((string) ($formData['country'] ?? ''));
$validCountryNames = array_column($asiaCountries, 'name');
if (!in_array($selectedCountry, $validCountryNames, true)) {
    $selectedCountry = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="kidstore-csrf-token" content="<?= htmlspecialchars(kidstore_frontend_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
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
                        <input type="hidden" name="<?= KIDSTORE_FRONTEND_CSRF_FIELD ?>" value="<?= htmlspecialchars(kidstore_frontend_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" />
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
                                <input type="tel"
                                       id="phone"
                                       name="phone"
                                       placeholder="+65 6123 4567"
                                       inputmode="tel"
                                       pattern="^\+?[0-9\s-]{7,}$"
                                       value="<?= htmlspecialchars($formData['phone'] ?? '') ?>"
                                       required />
                                <span class="form-hint" data-phone-hint></span>
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
                                <select id="country" name="country" required>
                                    <option value="" disabled <?= $selectedCountry === '' ? 'selected' : '' ?>>Select your country</option>
                                    <?php foreach ($asiaCountries as $country): ?>
                                        <option value="<?= htmlspecialchars($country['name']) ?>"
                                                data-dial-code="<?= htmlspecialchars($country['dial_code']) ?>"
                                                data-placeholder="<?= htmlspecialchars($country['placeholder']) ?>"
                                                data-example="<?= htmlspecialchars($country['example']) ?>"
                                                data-pattern="<?= htmlspecialchars($country['pattern']) ?>"
                                                <?= $selectedCountry === $country['name'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($country['name']) ?> (<?= htmlspecialchars($country['dial_code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
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
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const countrySelect = document.getElementById('country');
        const phoneInput = document.getElementById('phone');
        const hint = document.querySelector('[data-phone-hint]');
        if (!countrySelect || !phoneInput) {
            return;
        }

        let lastDialCode = '';

        function updatePhoneFormat(options) {
            const selectedOption = countrySelect.selectedOptions[0];
            if (!selectedOption) {
                phoneInput.placeholder = 'Select your country first';
                phoneInput.removeAttribute('pattern');
                if (hint) {
                    hint.textContent = '';
                }
                lastDialCode = '';
                return;
            }

            const dial = selectedOption.getAttribute('data-dial-code') || '';
            const placeholder = selectedOption.getAttribute('data-placeholder') || dial;
            const example = selectedOption.getAttribute('data-example') || '';
            const pattern = selectedOption.getAttribute('data-pattern');

            if (placeholder) {
                phoneInput.placeholder = placeholder;
            }

            if (pattern) {
                phoneInput.setAttribute('pattern', pattern);
            } else {
                phoneInput.removeAttribute('pattern');
            }

            if (hint) {
                hint.textContent = example ? `Format: ${example}` : dial ? `Dial code: ${dial}` : '';
            }

            const existingValue = phoneInput.value.trim();
            const forceUpdate = options && options.force === true;
            if (forceUpdate || existingValue === '' || existingValue === lastDialCode || existingValue === `${lastDialCode} `) {
                phoneInput.value = dial ? `${dial} ` : '';
            }

            lastDialCode = dial.trim();
        }

        countrySelect.addEventListener('change', function () {
            updatePhoneFormat({ force: true });
        });

        updatePhoneFormat();
    });
</script>
</body>
</html>


