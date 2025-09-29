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
    <title>Returns & Exchanges - Little Stars</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo $prefix; ?>assets/styles.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <main>
        <section class="page-hero">
            <div class="container">
                <h1>Returns & Exchanges</h1>
                <p>Happiness guaranteed. If something's not quite right, we're here to help.</p>
            </div>
        </section>

        <section class="info-section">
            <div class="container">
                <div class="info-grid">
                    <div class="info-card">
                        <h2>Return Window</h2>
                        <p>You have 30 days from delivery to return unworn, unwashed items with original tags attached.</p>
                    </div>
                    <div class="info-card">
                        <h2>Easy Exchanges</h2>
                        <p>Need a different size or color? Start an exchange to secure your preferred item before it sells out.</p>
                    </div>
                    <div class="info-card">
                        <h2>Gift Returns</h2>
                        <p>Returning a gift? Choose store credit and we'll keep the gift giver's secret safe.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="info-section accent">
            <div class="container">
                <h2>How to Start a Return</h2>
                <ol class="steps-list">
                    <li>Visit your order history and select the items you'd like to return.</li>
                    <li>Choose a return or exchange and print your prepaid label.</li>
                    <li>Drop the package at any carrier location and keep the receipt for your records.</li>
                </ol>
                <p class="note">Please allow 5-7 business days for returns to be processed once they reach our studio.</p>
            </div>
        </section>

        <section class="info-section">
            <div class="container">
                <h2>Items Not Eligible for Return</h2>
                <ul class="bullet-list">
                    <li>Final sale items marked "Last Chance"</li>
                    <li>Personalized pieces with custom embroidery</li>
                    <li>Worn or washed garments</li>
                    <li>Socks, tights, and hair accessories once opened</li>
                </ul>
            </div>
        </section>

        <section class="cta-section">
            <div class="container">
                <h2>Need a Hand?</h2>
                <p>Our team loves helping you find the right fit. Reach out or head back to the shop to keep exploring.</p>
                <div class="cta-actions">
                    <a class="button ghost" href="<?php echo $prefix; ?>pages/contact.php">Contact Support</a>
                    <a class="button solid" href="<?php echo $prefix; ?>pages/shop.php">Return to Shop</a>
                </div>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
    <button class="scroll-to-top" style="display: none;"><i class="fas fa-chevron-up"></i></button>

    <script src="<?php echo $prefix; ?>assets/script.js"></script>
</body>
</html>
