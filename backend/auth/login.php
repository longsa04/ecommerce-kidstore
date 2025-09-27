<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../includes/admin_auth.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login - Kid Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="../assets/admin.css" />
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
        }
        .login-card {
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(15, 23, 42, 0.2);
            width: min(420px, 92vw);
        }
        .login-card h1 {
            margin: 0 0 20px;
            font-size: 1.8rem;
            text-align: center;
        }
        .login-card form {
            display: grid;
            gap: 16px;
        }
        .login-card label {
            font-weight: 600;
        }
        .login-card input {
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid #d1d5db;
            font-size: 1rem;
        }
        .login-card button {
            margin-top: 10px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
        }
        .login-error {
            background: #fee2e2;
            color: #b91c1c;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.95rem;
        }
    </style>
</head>
<body class="login-wrapper">
    <div class="login-card">
        <h1><i class="fas fa-star"></i> Kid Store Admin</h1>
        <?php if ($error): ?>
            <div class="login-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post">
            <div>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required />
            </div>
            <div>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required />
            </div>
            <button type="submit">Sign In</button>
        </form>
    </div>
</body>
</html>
