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
    <title>Frequently Asked Questions - Little Stars</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo $prefix; ?>assets/styles.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <main>
        <section class="page-hero">
            <div class="container">
                <h1>Frequently Asked Questions</h1>
                <p>Answers to the questions parents ask us most.</p>
            </div>
        </section>

        <section class="info-section">
            <div class="container">
                <div class="faq-grid">
                    <div class="faq-item">
                        <h2>Do you restock sold-out items?</h2>
                        <p>Yes! Our design team refreshes best sellers regularly. Sign up for restock alerts on the product page to be the first to know.</p>
                    </div>
                    <div class="faq-item">
                        <h2>What materials do you use?</h2>
                        <p>We prioritize soft, breathable fabrics like organic cotton and bamboo blends that are gentle on sensitive skin.</p>
                    </div>
                    <div class="faq-item">
                        <h2>Can I modify my order after placing it?</h2>
                        <p>Orders move quickly, but contact us within 1 hour and we'll do our best to update addresses, sizes, or styles.</p>
                    </div>
                    <div class="faq-item">
                        <h2>Do you offer gift wrapping?</h2>
                        <p>Absolutely! Choose "Gift Wrap" at checkout and we'll include a handwritten note and keepsake box.</p>
                    </div>
                    <div class="faq-item">
                        <h2>How do I earn rewards?</h2>
                        <p>Create an account to earn Stars on every purchase. Redeem them for exclusive discounts and early access launches.</p>
                    </div>
                    <div class="faq-item">
                        <h2>Are your clothes true to size?</h2>
                        <p>Our styles are crafted to match the size guide above. When in doubt, check the fit notes on each product page.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="info-section accent">
            <div class="container">
                <h2>Still Curious?</h2>
                <p>Our Little Stars stylists love chatting about upcoming collections, fit tips, and gift ideas.</p>
                <div class="cta-actions">
                    <a class="button ghost" href="<?php echo $prefix; ?>pages/contact.php">Contact Us</a>
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
