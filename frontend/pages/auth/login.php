<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Log in - Little Stars</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        body {
            margin: 0;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            color: #1f2937;
        }
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .auth-card {
            width: min(420px, 100%);
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(18px);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 30px 60px rgba(79, 114, 205, 0.2);
        }
        .auth-card h1 {
            margin: 0 0 0.75rem;
            font-size: 2rem;
            font-weight: 700;
        }
        .auth-card p {
            margin: 0 0 1.8rem;
            color: #6b7280;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 600;
            font-size: 0.95rem;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.85rem 1rem;
            border-radius: 14px;
            border: 1px solid rgba(99, 102, 241, 0.18);
            font-size: 1rem;
            transition: box-shadow 0.2s ease, border 0.2s ease;
        }
        input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.18);
        }
        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            border: none;
            border-radius: 16px;
            padding: 0.95rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 30px rgba(102, 126, 234, 0.25);
        }
        .auth-meta {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.95rem;
        }
        .auth-meta a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
        }
        .auth-meta a:hover {
            text-decoration: underline;
        }
        .error-box {
            background: rgba(248, 113, 113, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #b91c1c;
            padding: 0.9rem 1rem;
            border-radius: 14px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1>Welcome back</h1>
            <p>Enter your credentials to access your Little Stars account.</p>
            <?php if ($error): ?>
                <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" novalidate>
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required />
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required />
                </div>
                <button type="submit" class="submit-btn">Log in</button>
            </form>
            <div class="auth-meta">
                New to Little Stars? <a href="register.php">Create an account</a>
            </div>
        </div>
    </div>
</body>
</html>
