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
    <title>Size Guide - Little Stars</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo $prefix; ?>assets/styles.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <main>
        <section class="page-hero">
            <div class="container">
                <h1>Find the Perfect Fit</h1>
                <p>Comfortable, play-ready outfits sized with growing kids in mind.</p>
            </div>
        </section>

        <section class="info-section">
            <div class="container">
                <div class="info-grid">
                    <div class="info-card">
                        <h2>Baby (0-24 months)</h2>
                        <ul>
                            <li><strong>Newborn:</strong> Up to 7 lbs &bull; 18-21 in</li>
                            <li><strong>0-3 months:</strong> 7-12 lbs &bull; 21-24 in</li>
                            <li><strong>3-6 months:</strong> 12-17 lbs &bull; 24-27 in</li>
                            <li><strong>6-12 months:</strong> 17-22 lbs &bull; 27-29 in</li>
                            <li><strong>12-24 months:</strong> 22-28 lbs &bull; 29-33 in</li>
                        </ul>
                    </div>
                    <div class="info-card">
                        <h2>Toddler (2T-5T)</h2>
                        <ul>
                            <li><strong>2T:</strong> 26-30 lbs &bull; 33-36 in</li>
                            <li><strong>3T:</strong> 30-34 lbs &bull; 36-39 in</li>
                            <li><strong>4T:</strong> 34-39 lbs &bull; 39-42 in</li>
                            <li><strong>5T:</strong> 39-45 lbs &bull; 42-45 in</li>
                        </ul>
                    </div>
                    <div class="info-card">
                        <h2>Kids (4-12 years)</h2>
                        <ul>
                            <li><strong>XS (4-5):</strong> 40-46 lbs &bull; 42-46 in</li>
                            <li><strong>S (6-7):</strong> 46-60 lbs &bull; 46-50 in</li>
                            <li><strong>M (8-9):</strong> 60-74 lbs &bull; 50-54 in</li>
                            <li><strong>L (10-11):</strong> 74-90 lbs &bull; 54-58 in</li>
                            <li><strong>XL (12):</strong> 90-105 lbs &bull; 58-62 in</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section class="info-section accent">
            <div class="container">
                <h2>Fit Tips</h2>
                <div class="tips-grid">
                    <div class="tip-item">
                        <i class="fas fa-ruler-combined"></i>
                        <h3>Measure Twice</h3>
                        <p>Use a soft measuring tape around the chest, waist, and hips for the best accuracy.</p>
                    </div>
                    <div class="tip-item">
                        <i class="fas fa-arrows-alt-v"></i>
                        <h3>Allow for Growth</h3>
                        <p>Kids grow quickly! If you're between sizes, size up for extra months of wear.</p>
                    </div>
                    <div class="tip-item">
                        <i class="fas fa-tshirt"></i>
                        <h3>Check the Fabric</h3>
                        <p>Stretch fabrics offer more flexibility. For structured pieces, opt for the larger size.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="cta-section">
            <div class="container">
                <h2>Ready to Find Their Next Favorite Outfit?</h2>
                <p>Head back to the shop to explore new arrivals, seasonal picks, and everyday essentials.</p>
                <a class="button solid" href="<?php echo $prefix; ?>pages/shop.php">Return to Shop</a>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
    <button class="scroll-to-top" style="display: none;"><i class="fas fa-chevron-up"></i></button>

    <script src="<?php echo $prefix; ?>assets/script.js"></script>
</body>
</html>
