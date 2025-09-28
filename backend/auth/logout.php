<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';

kidstore_admin_logout();
header('Location: ' . kidstore_backend_url('auth/login.php'));
exit;
