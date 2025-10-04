<?php
/**
 * PayWay checkout helper utilities.
 */
declare(strict_types=1);

require_once __DIR__ . '/../config/env.php';

if (!defined('ABA_PAYWAY_API_URL')) {
    $apiUrl = trim(kidstore_env('ABA_PAYWAY_API_URL', 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase'));
    if ($apiUrl === '') {
        throw new RuntimeException('Environment variable "ABA_PAYWAY_API_URL" must not be empty.');
    }

    define('ABA_PAYWAY_API_URL', $apiUrl);
}

if (!defined('ABA_PAYWAY_API_KEY')) {
    $apiKey = trim(kidstore_env('ABA_PAYWAY_API_KEY'));
    if ($apiKey === '') {
        throw new RuntimeException('Environment variable "ABA_PAYWAY_API_KEY" must not be empty.');
    }

    define('ABA_PAYWAY_API_KEY', $apiKey);
}

if (!defined('ABA_PAYWAY_MERCHANT_ID')) {
    $merchantId = trim(kidstore_env('ABA_PAYWAY_MERCHANT_ID'));
    if ($merchantId === '') {
        throw new RuntimeException('Environment variable "ABA_PAYWAY_MERCHANT_ID" must not be empty.');
    }

    define('ABA_PAYWAY_MERCHANT_ID', $merchantId);
}

if (!defined('ABA_PAYWAY_PUBLIC_KEY')) {
    $publicKey = trim(kidstore_env('ABA_PAYWAY_PUBLIC_KEY', ''));
    if ($publicKey !== '') {
        define('ABA_PAYWAY_PUBLIC_KEY', $publicKey);
    }
}

if (!defined('ABA_PAYWAY_RSA_PUBLIC_KEY')) {
    $rsaPublicKey = trim(kidstore_env('ABA_PAYWAY_RSA_PUBLIC_KEY', ''));
    if ($rsaPublicKey !== '') {
        define('ABA_PAYWAY_RSA_PUBLIC_KEY', $rsaPublicKey);
    }
}

if (!defined('ABA_PAYWAY_RSA_PRIVATE_KEY')) {
    $rsaPrivateKey = trim(kidstore_env('ABA_PAYWAY_RSA_PRIVATE_KEY', ''));
    if ($rsaPrivateKey !== '') {
        define('ABA_PAYWAY_RSA_PRIVATE_KEY', $rsaPrivateKey);
    }
}

class PayWayApiCheckout
{
    /**
     * Generate the encrypted hash using the configured API key.
     */
    public static function getHash(string $payload): string
    {
        return base64_encode(hash_hmac('sha512', $payload, ABA_PAYWAY_API_KEY, true));
    }

    /**
     * Retrieve the configured API URL.
     */
    public static function getApiUrl(): string
    {
        return ABA_PAYWAY_API_URL;
    }

    /**
     * Retrieve the configured merchant ID.
     */
    public static function getMerchantId(): string
    {
        return ABA_PAYWAY_MERCHANT_ID;
    }

    /**
     * Retrieve the configured public key if available.
     */
    public static function getPublicKey(): ?string
    {
        return defined('ABA_PAYWAY_PUBLIC_KEY') ? ABA_PAYWAY_PUBLIC_KEY : null;
    }

    /**
     * Retrieve the configured RSA public key if available.
     */
    public static function getRsaPublicKey(): ?string
    {
        return defined('ABA_PAYWAY_RSA_PUBLIC_KEY') ? ABA_PAYWAY_RSA_PUBLIC_KEY : null;
    }

    /**
     * Retrieve the configured RSA private key if available.
     */
    public static function getRsaPrivateKey(): ?string
    {
        return defined('ABA_PAYWAY_RSA_PRIVATE_KEY') ? ABA_PAYWAY_RSA_PRIVATE_KEY : null;
    }
}
/*|--------------------------------------------------------------------------| ABA PayWay API URL|--------------------------------------------------------------------------| API URL that is provided by PayWay must be required in your post form|*/define('ABA_PAYWAY_API_URL', 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase');/*|--------------------------------------------------------------------------| ABA PayWay API KEY|--------------------------------------------------------------------------| API KEY that is generated and provided by PayWay must be required in your post form|*/define('ABA_PAYWAY_API_KEY', '308f1c5f450ff6d971bf8a805b4d18a6ef142464');/*|--------------------------------------------------------------------------| ABA PayWay Merchant ID|--------------------------------------------------------------------------| Merchant ID that is generated and provided by PayWay must be required in your post form|*/define('ABA_PAYWAY_MERCHANT_ID', 'ec000262');class PayWayApiCheckout {    /**     * Returns the getHash     * For PayWay security, you must follow the way of encryption for hash.     *     * @param string $transactionId     * @param string $amount     *     * @return string getHash     */    public static function getHash($str) {      //  echo 'before hash: '.$str.'<br><br>';        $hash = base64_encode(hash_hmac('sha512', $str, ABA_PAYWAY_API_KEY, true));        return $hash;    }    /**     * Returns the getApiUrl     *     * @return string getApiUrl     */    public static function getApiUrl() {        return ABA_PAYWAY_API_URL;    }}