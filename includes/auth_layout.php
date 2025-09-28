<?php
/**
 * Shared layout helpers for authentication screens.
 */
declare(strict_types=1);

if (!function_exists('kidstore_auth_styles')) {
    function kidstore_auth_styles(): void
    {
        ?>
        <style>
            :root {
                color-scheme: light;
            }
            body {
                margin: 0;
                font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
                background: #f3f4f6;
                color: #111827;
            }
            .auth-wrapper {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2.5rem 1.5rem;
                background: radial-gradient(circle at top left, rgba(129, 140, 248, 0.18), transparent 55%),
                    radial-gradient(circle at bottom right, rgba(249, 168, 212, 0.18), transparent 45%),
                    #f9fafb;
            }
            .auth-card {
                width: min(420px, 100%);
                background: rgba(255, 255, 255, 0.92);
                border-radius: 22px;
                padding: 2.5rem;
                box-shadow: 0 25px 60px rgba(15, 23, 42, 0.12);
                backdrop-filter: blur(18px);
            }
            .auth-card--wide {
                width: min(520px, 100%);
            }
            .auth-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.3rem 0.75rem;
                border-radius: 999px;
                font-size: 0.75rem;
                font-weight: 600;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                background: rgba(99, 102, 241, 0.12);
                color: #4338ca;
                margin-bottom: 1.25rem;
            }
            .auth-header {
                margin-bottom: 2rem;
            }
            .auth-header h1 {
                margin: 0 0 0.75rem;
                font-size: 1.85rem;
                font-weight: 700;
            }
            .auth-subtitle {
                margin: 0;
                color: #6b7280;
                line-height: 1.5;
            }
            .auth-form {
                display: grid;
                gap: 1.15rem;
            }
            .auth-field {
                display: grid;
                gap: 0.45rem;
            }
            .auth-label {
                font-weight: 600;
                font-size: 0.95rem;
                color: #1f2937;
            }
            .auth-input {
                width: 100%;
                padding: 0.85rem 1rem;
                border-radius: 14px;
                border: 1px solid rgba(99, 102, 241, 0.18);
                font-size: 1rem;
                transition: border-color 0.2s ease, box-shadow 0.2s ease;
                background: rgba(255, 255, 255, 0.9);
            }
            .auth-input:focus {
                outline: none;
                border-color: #6366f1;
                box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
            }
            .auth-submit {
                width: 100%;
                background: linear-gradient(135deg, #6366f1, #8b5cf6);
                color: #fff;
                border: none;
                border-radius: 16px;
                padding: 0.95rem 1rem;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: transform 0.15s ease, box-shadow 0.15s ease;
            }
            .auth-submit:hover {
                transform: translateY(-1px);
                box-shadow: 0 16px 32px rgba(99, 102, 241, 0.25);
            }
            .auth-submit:focus-visible {
                outline: none;
                box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.25);
            }
            .auth-meta {
                margin-top: 2rem;
                text-align: center;
                font-size: 0.95rem;
                color: #4b5563;
            }
            .auth-meta a {
                color: #4f46e5;
                font-weight: 600;
                text-decoration: none;
            }
            .auth-meta a:hover {
                text-decoration: underline;
            }
            .auth-error,
            .auth-error-list {
                border-radius: 14px;
                padding: 0.95rem 1.1rem;
                background: rgba(248, 113, 113, 0.14);
                border: 1px solid rgba(239, 68, 68, 0.25);
                color: #b91c1c;
                font-size: 0.95rem;
            }
            .auth-error-list ul {
                margin: 0;
                padding-left: 1.2rem;
            }
            @media (max-width: 520px) {
                .auth-card,
                .auth-card--wide {
                    padding: 2rem 1.75rem;
                }
            }
        </style>
        <?php
    }

    function kidstore_auth_page_open(string $documentTitle, string $heading, string $description = '', array $options = []): void
    {
        $cardClass = 'auth-card';
        if (!empty($options['wide'])) {
            $cardClass .= ' auth-card--wide';
        }

        $badge = $options['badge'] ?? null;
        $subtitle = $description !== '' ? '<p class="auth-subtitle">' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '</p>' : '';

        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <title><?= htmlspecialchars($documentTitle, ENT_QUOTES, 'UTF-8') ?></title>
            <link rel="preconnect" href="https://fonts.googleapis.com" />
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
            <?php kidstore_auth_styles(); ?>
        </head>
        <body>
            <div class="auth-wrapper">
                <div class="<?= $cardClass ?>">
                    <?php if ($badge): ?>
                        <div class="auth-badge"><?= htmlspecialchars((string) $badge, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <div class="auth-header">
                        <h1><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></h1>
                        <?= $subtitle ?>
                    </div>
        <?php
    }

    function kidstore_auth_page_close(?string $metaHtml = null): void
    {
        if ($metaHtml !== null && $metaHtml !== '') {
            echo '<div class="auth-meta">' . $metaHtml . '</div>';
        }
        ?>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

    function kidstore_auth_error(string $message): void
    {
        echo '<div class="auth-error">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
    }

    function kidstore_auth_error_list(array $messages): void
    {
        if (!$messages) {
            return;
        }

        echo '<div class="auth-error-list"><ul>';
        foreach ($messages as $message) {
            echo '<li>' . htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') . '</li>';
        }
        echo '</ul></div>';
    }

    function kidstore_auth_meta(string $text, string $linkText, string $linkHref): string
    {
        $escapedText = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $escapedLinkText = htmlspecialchars($linkText, ENT_QUOTES, 'UTF-8');
        $escapedLinkHref = htmlspecialchars($linkHref, ENT_QUOTES, 'UTF-8');

        return $escapedText . ' <a href="' . $escapedLinkHref . '">' . $escapedLinkText . '</a>';
    }
}
