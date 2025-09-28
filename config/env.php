<?php
/**
 * Lightweight environment loader for Kid Store.
 */
declare(strict_types=1);

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        $needleLength = strlen($needle);

        return $needleLength <= strlen($haystack)
            && substr($haystack, -$needleLength) === $needle;
    }
}

if (!function_exists('kidstore_env')) {
    /**
     * Load environment variables from the project .env file if present and
     * return the value for the requested key.
     *
     * @throws RuntimeException when the variable is missing and no default is provided.
     */
    function kidstore_env(string $key, ?string $default = null): string
    {
        static $initialised = false;

        if ($initialised === false) {
            $initialised = true;
            $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

            if (is_readable($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

                foreach ($lines as $line) {
                    $line = trim($line);

                    if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
                        continue;
                    }

                    $separatorPosition = strpos($line, '=');
                    if ($separatorPosition === false) {
                        continue;
                    }

                    $name = rtrim(substr($line, 0, $separatorPosition));
                    $value = ltrim(substr($line, $separatorPosition + 1));

                    if ($value !== '' && ($value[0] === "'" || $value[0] === '"')) {
                        $quote = $value[0];
                        if (str_ends_with($value, $quote)) {
                            $value = substr($value, 1, -1);
                        }
                    }

                    if ($name === '') {
                        continue;
                    }

                    if (!array_key_exists($name, $_ENV)) {
                        $_ENV[$name] = $value;
                    }

                    if (!array_key_exists($name, $_SERVER)) {
                        $_SERVER[$name] = $value;
                    }

                    if (function_exists('putenv')) {
                        putenv(sprintf('%s=%s', $name, $value));
                    }
                }
            }
        }

        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            if ($default === null) {
                throw new RuntimeException(sprintf('Environment variable "%s" is not set.', $key));
            }

            return $default;
        }

        return (string) $value;
    }
}