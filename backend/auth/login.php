<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../../includes/auth_layout.php';

if (kidstore_admin_logged_in()) {
    header('Location: ../index.php');
    exit;
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $pdo = kidstore_get_pdo();
        $stmt = $pdo->prepare('SELECT user_id, name, password FROM tbl_users WHERE email = :email AND is_admin = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_id'] = (int) $user['user_id'];
            $_SESSION['admin_name'] = $user['name'];
            header('Location: ../index.php');
            exit;
        }

        $error = 'Invalid credentials or insufficient permissions.';
    }
}
kidstore_auth_page_open(
    'Admin sign in - Little Stars',
    'Sign in to the dashboard',
    'Use your administrator credentials to manage the Little Stars store.',
    ['badge' => 'Admin']
);

if ($error) {
    kidstore_auth_error($error);
}
?>
<form method="post" class="auth-form" novalidate>
    <div class="auth-field">
        <label class="auth-label" for="email">Email</label>
        <input
            type="email"
            class="auth-input"
            id="email"
            name="email"
            value="<?= htmlspecialchars($email) ?>"
            required
            autocomplete="email"
        />
    </div>
    <div class="auth-field">
        <label class="auth-label" for="password">Password</label>
        <input
            type="password"
            class="auth-input"
            id="password"
            name="password"
            required
            autocomplete="current-password"
        />
    </div>
    <button type="submit" class="auth-submit">Sign in</button>
</form>
<?php
$meta = kidstore_auth_meta('Need to head back?', 'Return to storefront', '../../frontend/index.php');
kidstore_auth_page_close($meta);
