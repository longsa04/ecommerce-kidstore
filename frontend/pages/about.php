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
    <title>About Us - Little Stars</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo $prefix; ?>assets/styles.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>
    
    <main>
        <section class="about-hero">
            <div class="container">
                <h1>About Little Stars</h1>
                <p>Creating magical moments through quality children's clothing</p>
            </div>
        </section>

        <section class="about-content">
            <div class="container">
                <div class="about-grid">
                    <div class="about-text">
                        <h2>Our Story</h2>
                        <p>Founded in 2015, Little Stars has been dedicated to providing high-quality, comfortable, and stylish clothing for children of all ages. We understand that kids need clothes that can keep up with their active lifestyles while looking great.</p>
                        
                        <h3>Our Mission</h3>
                        <p>To create beautiful, durable, and affordable clothing that lets children express their unique personalities while giving parents peace of mind about quality and comfort.</p>
                        
                        <h3>Why Choose Little Stars?</h3>
                        <ul>
                            <li><strong>Uncompromised Quality:</strong> We use only the finest fabrics and craftsmanship.</li>
                            <li><strong>Comfort & Durability:</strong> Designed for play, built to last.</li>
                            <li><strong>Trendy & Unique Designs:</strong> Keep your little ones in style.</li>
                            <li><strong>Eco-Friendly Practices:</strong> Committed to sustainable fashion.</li>
                        </ul>
                    </div>
                    <div class="about-image">
                        <img src="https://images.pexels.com/photos/1648377/pexels-photo-1648377.jpeg?auto=compress&cs=tinysrgb&w=600" alt="About Little Stars">
                    </div>
                </div>
            </div>
        </section>

        <section class="our-values">
            <div class="container">
                <h2>Our Values</h2>
                <div class="values-grid">
                    <div class="value-item">
                        <i class="fas fa-heart"></i>
                        <h3>Quality First</h3>
                        <p>Every piece is carefully crafted with attention to detail and quality materials.</p>
                    </div>
                    <div class="value-item">
                        <i class="fas fa-leaf"></i>
                        <h3>Sustainability</h3>
                        <p>We're committed to eco-friendly practices and sustainable fashion.</p>
                    </div>
                    <div class="value-item">
                        <i class="fas fa-smile"></i>
                        <h3>Comfort</h3>
                        <p>Designed for active kids who need clothes that move with them.</p>
                    </div>
                    <div class="value-item">
                        <i class="fas fa-palette"></i>
                        <h3>Style</h3>
                        <p>Trendy designs that help kids express their unique personalities.</p>
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