<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Little Stars - Children's Clothing</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo KIDSTORE_FRONT_URL_PREFIX; ?>assets/styles.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/partials/header.php'; ?>
    
    <main>
        <?php include __DIR__ . '/pages/home.php'; ?>
    </main>
    
    <?php include __DIR__ . '/partials/footer.php'; ?>
    
    <script src="<?php echo KIDSTORE_FRONT_URL_PREFIX; ?>assets/script.js"></script>
</body>
</html>