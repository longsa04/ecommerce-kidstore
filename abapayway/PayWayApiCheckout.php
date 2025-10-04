<?php
/**
 * PayWay checkout helper utilities.
 */
declare(strict_types=1);

/**
 * @psalm-type PayWayConfig = array{
 *     api_url:string,
 *     sandbox_api_url:string,
 *     api_key:string,
 *     merchant_id:string,
 *     public_key:?string,
 *     rsa_public_key:?string,
 *     rsa_private_key:?string,
 *     mode:string
 * }
 */

class PayWayApiCheckout
{
    private const DEFAULT_SANDBOX_API_URL = 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase';

    /**
     * Map configuration keys to their corresponding environment variables.
     */
    private const ENVIRONMENT_KEY_MAP = [
        'api_url' => 'PAYWAY_API_URL',
        'sandbox_api_url' => 'PAYWAY_SANDBOX_API_URL',
        'api_key' => 'PAYWAY_API_KEY',
        'merchant_id' => 'PAYWAY_MERCHANT_ID',
        'public_key' => 'PAYWAY_PUBLIC_KEY',
        'rsa_public_key' => 'PAYWAY_RSA_PUBLIC_KEY',
        'rsa_private_key' => 'PAYWAY_RSA_PRIVATE_KEY',
        'mode' => 'PAYWAY_ENV',
    ];

    /**
     * Cached configuration data.
     *
     * @var PayWayConfig|null
     */
    private static ?array $configuration = null;

    /**
     * Retrieve the loaded configuration, populating it if necessary.
     *
     * @return PayWayConfig
     */
    private static function getConfiguration(): array
    {
        if (self::$configuration !== null) {
            return self::$configuration;
        }

        $config = [
            'api_url' => '',
            'sandbox_api_url' => self::DEFAULT_SANDBOX_API_URL,
            'api_key' => '',
            'merchant_id' => '',
            'public_key' => null,
            'rsa_public_key' => null,
            'rsa_private_key' => null,
            'mode' => 'sandbox',
        ];

        $fileConfig = self::loadConfigurationFromFile();
        foreach ($fileConfig as $key => $value) {
            if (array_key_exists($key, $config)) {
                $config[$key] = self::sanitizeConfigValue($value, $config[$key]);
            }
        }

        foreach (self::ENVIRONMENT_KEY_MAP as $key => $envName) {
            $value = self::getEnv($envName);
            if ($value !== null) {
                $config[$key] = self::sanitizeConfigValue($value, $config[$key]);
            }
        }

        $modeOverride = self::getEnv('PAYWAY_MODE');
        if ($modeOverride !== null) {
            $config['mode'] = self::sanitizeConfigValue($modeOverride, $config['mode']);
        }

        $mode = strtolower((string) ($config['mode'] ?? ''));
        if ($mode === '') {
            $mode = 'sandbox';
        }
        $config['mode'] = $mode;

        self::$configuration = $config;

        return self::$configuration;
    }

    /**
     * Load configuration values from a PHP config file if available.
     *
     * @return array<string,mixed>
     */
    private static function loadConfigurationFromFile(): array
    {
        $paths = [];

        $customPath = self::getEnv('PAYWAY_CONFIG_PATH');
        if ($customPath !== null) {
            $paths[] = $customPath;
        }

        $paths[] = dirname(__DIR__) . '/config/payway.php';
        $paths[] = __DIR__ . '/config/payway.php';
        $paths[] = __DIR__ . '/payway.php';

        foreach ($paths as $path) {
            if ($path === null || $path === '') {
                continue;
            }

            if (is_file($path)) {
                /** @psalm-suppress UnresolvableInclude */
                $data = include $path;
                if (is_array($data)) {
                    return $data;
                }
            }
        }

        return [];
    }

    /**
     * Retrieve an environment variable, returning null when it is unset.
     */
    private static function getEnv(string $key): ?string
    {
        $value = getenv($key);
        if ($value === false) {
            return null;
        }

        $value = is_string($value) ? $value : (string) $value;

        return $value !== '' ? $value : null;
    }

    /**
     * Normalize a configuration value so that downstream consumers receive
     * predictable data types.
     *
     * @param mixed $value
     * @param mixed $current
     * @return mixed
     */
    private static function sanitizeConfigValue($value, $current)
    {
        if (is_bool($current)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $current;
        }

        if ($current === null) {
            return $value === null ? null : trim((string) $value);
        }

        return trim((string) $value);
    }

    /**
     * Retrieve a required configuration value or throw an informative
     * RuntimeException if it has not been defined.
     */
    private static function requireConfig(string $key): string
    {
        $config = self::getConfiguration();
        $value = isset($config[$key]) ? trim((string) $config[$key]) : '';

        if ($value === '') {
            $envName = self::ENVIRONMENT_KEY_MAP[$key] ?? strtoupper($key);

            throw new RuntimeException(
                sprintf(
                    'Missing PayWay configuration: please define a non-empty value for %s via the %s environment variable or config file.',
                    $key,
                    $envName
                )
            );
        }

        return $value;
    }

    /**
     * Retrieve an optional configuration string.
     */
    private static function optionalConfig(string $key): ?string
    {
        $config = self::getConfiguration();
        $value = $config[$key] ?? null;

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * Generate the encrypted hash using the configured API key.
     */
    public static function getHash(string $payload): string
    {
        $apiKey = self::requireConfig('api_key');

        return base64_encode(hash_hmac('sha512', $payload, $apiKey, true));
    }

    /**
     * Generate the callback hash as documented by PayWay.
     *
     * The response hash is calculated from the transaction ID, status, amount,
     * currency, request timestamp, and merchant ID using the same HMAC-SHA512
     * signature mechanism that is used during purchase initiation.
     *
     * @param array<string, mixed> $payload Raw callback payload.
     */
    public static function buildCallbackHash(array $payload): string
    {
        $tranId = trim((string) ($payload['tran_id'] ?? $payload['transaction_id'] ?? $payload['transactionId'] ?? ''));
        $status = trim((string) ($payload['status'] ?? $payload['payment_status'] ?? $payload['result'] ?? ''));
        $amount = trim((string) ($payload['amount'] ?? $payload['total_amount'] ?? $payload['payment_amount'] ?? ''));
        $currency = trim((string) ($payload['currency'] ?? $payload['currency_code'] ?? ''));
        $requestTime = trim((string) (
            $payload['req_time']
            ?? $payload['request_time']
            ?? $payload['requestTime']
            ?? $payload['res_time']
            ?? $payload['response_time']
            ?? $payload['timestamp']
            ?? ''
        ));

        $merchantId = trim((string) ($payload['merchant_id'] ?? self::getMerchantId()));

        $signaturePayload = $tranId . $status . $amount . $currency . $requestTime . $merchantId;

        if ($signaturePayload === '') {
            return '';
        }

        return self::getHash($signaturePayload);
    }

    /**
     * Determine if the helper has been configured with real credentials.
     */
    public static function isConfigured(): bool
    {
        try {
            self::requireConfig('api_key');
            self::requireConfig('merchant_id');
            self::getApiUrl();
        } catch (RuntimeException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Normalize a monetary value to the format PayWay expects (two decimals).
     */
    public static function normalizeAmount(?string $amount): string
    {
        if ($amount === null || $amount === '') {
            return '0.00';
        }

        return number_format((float) $amount, 2, '.', '');
    }

    /**
     * Retrieve the status verification endpoint.
     */
    public static function getVerificationUrl(): string
    {
        $base = rtrim(dirname(self::getApiUrl()), '/');

        return $base . '/check-transaction';
    }

    /**
     * Execute a transaction status check with PayWay.
     *
     * @return array{http_code:int,body:array<string,mixed>}|null
     */
    public static function checkTransactionStatus(string $tranId): ?array
    {
        if ($tranId === '' || !function_exists('curl_init')) {
            return null;
        }

        $reqTime = (string) time();
        $payload = [
            'req_time' => $reqTime,
            'merchant_id' => self::getMerchantId(),
            'tran_id' => $tranId,
        ];

        $merchantId = $payload['merchant_id'];
        $payload['hash'] = self::getHash($reqTime . $merchantId . $tranId);

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonPayload === false) {
            return null;
        }

        $ch = curl_init(self::getVerificationUrl());
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);

        $body = curl_exec($ch);
        $errorNumber = curl_errno($ch);
        $errorMessage = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $errorNumber !== 0) {
            if ($errorMessage !== '') {
                error_log('PayWay status verification failed: ' . $errorMessage);
            }

            return null;
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            error_log('PayWay status verification returned an unexpected payload.');

            return null;
        }

        return [
            'http_code' => $statusCode,
            'body' => $decoded,
        ];
    }

    /**
     * Determine if the queried transaction is successful.
     */
    public static function confirmTransactionStatus(string $tranId, ?string $expectedAmount = null): ?bool
    {
        $response = self::checkTransactionStatus($tranId);
        if ($response === null) {
            return null;
        }

        $data = $response['body'];
        $statusRaw = (string) ($data['status'] ?? $data['payment_status'] ?? $data['result'] ?? '');
        $statusNormalized = strtolower(trim($statusRaw));

        $successStatuses = ['0', 'success', 'completed', 'approved', 'paid', 'true'];
        $isSuccessful = $statusNormalized !== '' && in_array($statusNormalized, $successStatuses, true);

        if (!$isSuccessful) {
            $code = isset($data['code']) ? (string) $data['code'] : '';
            if ($code === '0' || (isset($data['status']) && (string) $data['status'] === '0')) {
                $isSuccessful = true;
            }
        }

        if ($expectedAmount !== null && $expectedAmount !== '') {
            $remoteAmount = trim((string) ($data['amount'] ?? $data['total_amount'] ?? $data['total'] ?? ''));
            if ($remoteAmount !== '') {
                $expectedNormalized = self::normalizeAmount($expectedAmount);
                $remoteNormalized = self::normalizeAmount($remoteAmount);

                if ($expectedNormalized !== $remoteNormalized) {
                    return false;
                }
            }
        }

        return $isSuccessful;
    }

    /**
     * Retrieve the configured API URL.
     */
    public static function getApiUrl(): string
    {
        $config = self::getConfiguration();
        $mode = strtolower((string) ($config['mode'] ?? 'sandbox'));

        if (in_array($mode, ['production', 'prod', 'live'], true)) {
            return self::requireConfig('api_url');
        }

        $sandboxUrl = trim((string) ($config['sandbox_api_url'] ?? ''));
        if ($sandboxUrl === '') {
            $sandboxUrl = self::DEFAULT_SANDBOX_API_URL;
        }

        return $sandboxUrl;
    }

    /**
     * Retrieve the configured merchant ID.
     */
    public static function getMerchantId(): string
    {
        return self::requireConfig('merchant_id');
    }

    /**
     * Retrieve the configured public key if available.
     */
    public static function getPublicKey(): ?string
    {
        return self::optionalConfig('public_key');
    }

    /**
     * Retrieve the configured RSA public key if available.
     */
    public static function getRsaPublicKey(): ?string
    {
        return self::optionalConfig('rsa_public_key');
    }

    /**
     * Retrieve the configured RSA private key if available.
     */
    public static function getRsaPrivateKey(): ?string
    {
        return self::optionalConfig('rsa_private_key');
    }
}
/*|--------------------------------------------------------------------------| ABA PayWay API URL|--------------------------------------------------------------------------| API URL that is provided by PayWay must be required in your post form|*/define('ABA_PAYWAY_API_URL', 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase');/*|--------------------------------------------------------------------------| ABA PayWay API KEY|--------------------------------------------------------------------------| API KEY that is generated and provided by PayWay must be required in your post form|*/define('ABA_PAYWAY_API_KEY', '308f1c5f450ff6d971bf8a805b4d18a6ef142464');/*|--------------------------------------------------------------------------| ABA PayWay Merchant ID|--------------------------------------------------------------------------| Merchant ID that is generated and provided by PayWay must be required in your post form|*/define('ABA_PAYWAY_MERCHANT_ID', 'ec000262');class PayWayApiCheckout {    /**     * Returns the getHash     * For PayWay security, you must follow the way of encryption for hash.     *     * @param string $transactionId     * @param string $amount     *     * @return string getHash     */    public static function getHash($str) {      //  echo 'before hash: '.$str.'<br><br>';        $hash = base64_encode(hash_hmac('sha512', $str, ABA_PAYWAY_API_KEY, true));        return $hash;    }    /**     * Returns the getApiUrl     *     * @return string getApiUrl     */    public static function getApiUrl() {        return ABA_PAYWAY_API_URL;    }}
|--------------------------------------------------------------------------| ABA PayWay API URL|--------------------------------------------------------------------------| API URL that is provided by PayWay must be required in your post form|*/define('ABA_PAYWAY_API_URL', 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase');/*|--------------------------------------------------------------------------| ABA PayWay API KEY|--------------------------------------------------------------------------| API KEY that is generated and provided by PayWay must be required in your post form|*/define('ABA_PAYWAY_API_KEY', '308f1c5f450ff6d971bf8a805b4d18a6ef142464');/*|--------------------------------------------------------------------------| ABA PayWay Merchant ID|--------------------------------------------------------------------------| Merchant ID that is generated and provided by PayWay must be required in your post form|*/define('ABA_PAYWAY_MERCHANT_ID', 'ec000262');class PayWayApiCheckout {    /**     * Returns the getHash     * For PayWay security, you must follow the way of encryption for hash.     *     * @param string $transactionId     * @param string $amount     *     * @return string getHash     */    public static function getHash($str) {      //  echo 'before hash: '.$str.'<br><br>';        $hash = base64_encode(hash_hmac('sha512', $str, ABA_PAYWAY_API_KEY, true));        return $hash;    }    /**     * Returns the getApiUrl     *     * @return string getApiUrl     */    public static function getApiUrl() {        return ABA_PAYWAY_API_URL;    }}