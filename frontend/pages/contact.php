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
    <title>Contact Us - Little Stars</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo $prefix; ?>assets/styles.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>
    
    <main>
        <section class="contact-hero">
            <div class="container">
                <h1>Contact Us</h1>
                <p>We'd love to hear from you! Get in touch with our team.</p>
            </div>
        </section>

        <section class="contact-content">
            <div class="container">
                <div class="contact-grid">
                    <div class="contact-info">
                        <h2>Get in Touch</h2>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <h3>Address</h3>
                                <p>No. 86A, Street 110, Russian Federation Blvd (110), Phnom Penh</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <h3>Phone & Telegram</h3>
                                <p>+855-98-360-347</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <h3>Email</h3>
                                <p>info@littlestars.com</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h3>Hours</h3>
                                <p>Mon - Sun: 7:00 AM - 7:00 PM EST</p>
                            </div>
                        </div>
                    </div>
                    <div class="contact-form">
                        <h2>Send Us a Message</h2>
                        <?php if (isset($_GET['message']) && $_GET['message'] == 'sent'): ?>
                            <p class="success-message">Your message has been sent successfully!</p>
                        <?php endif; ?>
                        <form action="<?php echo $prefix; ?>actions/contact-handler.php" method="POST">
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" id="subject" name="subject" required>
                            </div>
                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea id="message" name="message" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="submit-btn">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <?php include __DIR__ . '/../partials/footer.php'; ?>
    <button class="scroll-to-top" style="display: none;"><i class="fas fa-chevron-up"></i></button>
    
    <script src="<?php echo $prefix; ?>assets/script.js"></script>
</body>
</html>