<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Create an account - Little Stars</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        body {
            margin: 0;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #fdfcfb, #e2d1c3);
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
            width: min(460px, 100%);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(16px);
            border-radius: 28px;
            padding: 2.75rem;
            box-shadow: 0 40px 70px rgba(170, 126, 84, 0.15);
        }
        .auth-card h1 {
            margin: 0 0 0.75rem;
            font-size: 2.1rem;
            font-weight: 700;
        }
        .auth-card p {
            margin: 0 0 2rem;
            color: #6b7280;
        }
        .form-grid {
            display: grid;
            gap: 1.2rem;
        }
        label {
            display: block;
            margin-bottom: 0.45rem;
            font-weight: 600;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.85rem 1rem;
            border-radius: 16px;
            border: 1px solid rgba(249, 115, 22, 0.2);
            background: rgba(255, 255, 255, 0.85);
            font-size: 1rem;
            transition: box-shadow 0.2s ease, border 0.2s ease;
        }
        input:focus {
            outline: none;
            border-color: #f97316;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.15);
        }
        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #f97316, #facc15);
            color: #fff;
            border: none;
            border-radius: 18px;
            padding: 1rem;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 20px 35px rgba(249, 115, 22, 0.25);
        }
        .auth-meta {
            margin-top: 1.8rem;
            text-align: center;
            font-size: 0.95rem;
        }
        .auth-meta a {
            color: #f97316;
            text-decoration: none;
            font-weight: 600;
        }
        .auth-meta a:hover {
            text-decoration: underline;
        }
        .error-list {
            background: rgba(248, 113, 113, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #b91c1c;
            padding: 1rem 1.2rem;
            border-radius: 16px;
            margin-bottom: 1.6rem;
        }
        .error-list ul {
            margin: 0;
            padding-left: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1>Join Little Stars</h1>
            <p>Create an account to save favourites, track orders, and get member-only perks.</p>
            <?php if ($errors): ?>
                <div class="error-list">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="post" class="form-grid" novalidate>
                <div>
                    <label for="name">Full name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required />
                </div>
                <div>
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required />
                </div>
                <div>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required />
                </div>
                <div>
                    <label for="confirm_password">Confirm password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required />
                </div>
                <button type="submit" class="submit-btn">Create account</button>
            </form>
            <div class="auth-meta">
                Already have an account? <a href="login.php">Log in</a>
            </div>
        </div>
    </div>
</body>
</html>
