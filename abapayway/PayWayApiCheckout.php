<?php
/**
 * PayWay checkout helper utilities.
 */
declare(strict_types=1);

define('ABA_PAYWAY_API_URL', 'https://checkout-sandbox.payway.com.kh/api/payment-gateway/v1/payments/purchase');
define('ABA_PAYWAY_API_KEY', 'YOUR_ABA_PAYWAY_API_KEY');
define('ABA_PAYWAY_MERCHANT_ID', 'YOUR_ABA_PAYWAY_MERCHANT_ID');
define('ABA_PAYWAY_PUBLIC_KEY', '');
define('ABA_PAYWAY_RSA_PUBLIC_KEY', '');
define('ABA_PAYWAY_RSA_PRIVATE_KEY', '');

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

        $merchantId = trim((string) ($payload['merchant_id'] ?? ABA_PAYWAY_MERCHANT_ID));

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
        $apiKey = trim((string) ABA_PAYWAY_API_KEY);
        $merchantId = trim((string) ABA_PAYWAY_MERCHANT_ID);

        return $apiKey !== ''
            && $merchantId !== ''
            && stripos($apiKey, 'YOUR_ABA_PAYWAY_API_KEY') === false
            && stripos($merchantId, 'YOUR_ABA_PAYWAY_MERCHANT_ID') === false;
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
        $base = rtrim(dirname(ABA_PAYWAY_API_URL), '/');

        return $base . '/check-transaction';
    }

    /**
     * Execute a transaction status check with PayWay.
     *
     * @return array{http_code:int,body:array<string,mixed>}|null
     */
    public static function checkTransactionStatus(string $tranId): ?array
    {
        if ($tranId === '' || !self::isConfigured() || !function_exists('curl_init')) {
            return null;
        }

        $reqTime = (string) time();
        $payload = [
            'req_time' => $reqTime,
            'merchant_id' => ABA_PAYWAY_MERCHANT_ID,
            'tran_id' => $tranId,
        ];

        $payload['hash'] = self::getHash($reqTime . ABA_PAYWAY_MERCHANT_ID . $tranId);

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