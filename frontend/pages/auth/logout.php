<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

kidstore_logout();
header('Location: ../../index.php');
exit;
