<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$prefix = KIDSTORE_FRONT_URL_PREFIX;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Information - Little Stars</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo $prefix; ?>assets/styles.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <main>
        <section class="page-hero">
            <div class="container">
                <h1>Shipping Information</h1>
                <p>Quick, reliable delivery to bring smiles straight to your doorstep.</p>
            </div>
        </section>

        <section class="info-section">
            <div class="container">
                <div class="info-grid">
                    <div class="info-card">
                        <h2>Delivery Options</h2>
                        <ul>
                            <li><strong>Standard Shipping:</strong> 4-6 business days &bull; Complimentary on orders over $75</li>
                            <li><strong>Expedited Shipping:</strong> 2-3 business days &bull; Flat $12.95</li>
                            <li><strong>Overnight Shipping:</strong> Next business day &bull; Calculated at checkout</li>
                        </ul>
                    </div>
                    <div class="info-card">
                        <h2>Processing Times</h2>
                        <p>Orders placed before 1 PM ET ship the same day. Weekend orders ship Monday. Personalized pieces may add 1-2 extra days.</p>
                    </div>
                    <div class="info-card">
                        <h2>International</h2>
                        <p>We currently ship to Canada, the UK, and Australia. Duties are calculated upfront so there are no surprise fees at delivery.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="info-section accent">
            <div class="container">
                <h2>Tracking & Updates</h2>
                <div class="tips-grid">
                    <div class="tip-item">
                        <i class="fas fa-envelope-open-text"></i>
                        <h3>Shipping Confirmation</h3>
                        <p>You'll receive an email with your tracking link as soon as your order leaves our studio.</p>
                    </div>
                    <div class="tip-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <h3>Live Tracking</h3>
                        <p>Follow every step of the journey and sign up for carrier text updates during checkout.</p>
                    </div>
                    <div class="tip-item">
                        <i class="fas fa-headset"></i>
                        <h3>Need Help?</h3>
                        <p>Our care team is available 7 days a week at <a href="mailto:hello@littlestars.com">hello@littlestars.com</a>.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="cta-section">
            <div class="container">
                <h2>Shop New Arrivals with Confidence</h2>
                <p>From checkout to delivery, we keep you updated every step of the way.</p>
                <a class="button solid" href="<?php echo $prefix; ?>pages/shop.php">Return to Shop</a>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
    <button class="scroll-to-top" style="display: none;"><i class="fas fa-chevron-up"></i></button>

    <script src="<?php echo $prefix; ?>assets/script.js"></script>
</body>
</html>
