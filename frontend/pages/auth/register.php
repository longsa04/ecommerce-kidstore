<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/auth_layout.php';

if (kidstore_current_user()) {
    header('Location: ../../index.php');
    exit;
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = kidstore_register($_POST);
    if ($result['success']) {
        header('Location: ../../index.php');
        exit;
    }
    $errors = $result['errors'];
}
kidstore_auth_page_open(
    'Create an account - Little Stars',
    'Join Little Stars',
    'Create an account to save favourites, track orders, and unlock member-only perks.',
    ['wide' => true]
);

if ($errors) {
    kidstore_auth_error_list($errors);
}
?>
<form method="post" class="auth-form" novalidate>
    <div class="auth-field">
        <label class="auth-label" for="name">Full name</label>
        <input
            type="text"
            class="auth-input"
            id="name"
            name="name"
            value="<?php echo htmlspecialchars($name); ?>"
            required
            autocomplete="name"
        />
    </div>
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
            autocomplete="new-password"
        />
    </div>
    <div class="auth-field">
        <label class="auth-label" for="confirm_password">Confirm password</label>
        <input
            type="password"
            class="auth-input"
            id="confirm_password"
            name="confirm_password"
            required
            autocomplete="new-password"
        />
    </div>
    <button type="submit" class="auth-submit">Create account</button>
</form>
<?php
$meta = kidstore_auth_meta('Already have an account?', 'Log in', 'login.php');
kidstore_auth_page_close($meta);
