<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

function kidstore_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function kidstore_admin_require_login(): void
{
    if (!kidstore_admin_logged_in()) {
        header('Location: ../login.php');
        exit;
    }
}

function kidstore_admin_logout(): void
{
    unset($_SESSION['admin_id'], $_SESSION['admin_name']);
}
