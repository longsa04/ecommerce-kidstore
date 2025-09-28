<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/auth_layout.php';

if (kidstore_current_user()) {
    if (kidstore_is_admin()) {
        header('Location: ../../backend/index.php');
    } else {
        header('Location: ../../index.php');
    }
    exit;
}

$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } elseif (!kidstore_login($email, $password)) {
        $error = 'We could not sign you in. Double-check your details and try again.';
    } else {
        if (kidstore_is_admin()) {
            header('Location: ../../backend/index.php');
        } else {
            header('Location: ../../index.php');
        }
        exit;
    }
}
kidstore_auth_page_open(
    'Log in - Little Stars',
    'Welcome back',
    'Enter your details to access your Little Stars account.'
);

if ($error) {
    kidstore_auth_error($error);
}
?>
<form method="post" class="auth-form" novalidate>
    <div class="auth-field">
        <label class="auth-label" for="email">Email address</label>
        <input
            type="email"
            class="auth-input"
            id="email"
            name="email"
            value="<?php echo htmlspecialchars($email); ?>"
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
    <button type="submit" class="auth-submit">Log in</button>
</form>
<?php
$meta = kidstore_auth_meta('New to Little Stars?', 'Create an account', 'register.php');
kidstore_auth_page_close($meta);
