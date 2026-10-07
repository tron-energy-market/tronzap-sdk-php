<?php

/**
 * TronZap SDK Client
 *
 * This module provides a PHP client for interacting with the TronZap API
 * to purchase TRX energy for low-cost USDT transfers.
 */

namespace TronZap;

use JsonException;
use stdClass;
use TronZap\Exception\ApiException;
use TronZap\Exception\ConnectionException;
use TronZap\Exception\HttpException;
use TronZap\Exception\InvalidRequestException;
use TronZap\Exception\NetworkException;
use TronZap\Exception\RateLimitException;
use TronZap\Exception\ServerException;
use TronZap\Exception\SslException;
use TronZap\Exception\TimeoutException;
use TronZap\Exception\TronZapException;
use TronZap\Exception\UnauthorizedException;

class Client
{
    public const VERSION = '1.5.0';

    /**
     * @var non-empty-string Base API URL, default is https://api.tronzap.com
     */
    private string $baseUrl;

    /**
     * @var string API token
     */
    private string $apiToken;

    /**
     * @var string API secret for signature generation
     */
    private string $apiSecret;

    /**
     * @var float Seconds to wait for the API to connect and to answer
     */
    private float $timeout;

    /**
     * @var non-empty-string|null Path to a PEM file with the certificate authorities to trust
     */
    private ?string $caBundle;

    /**
     * Client constructor
     *
     * @param string $apiToken Your API token
     * @param string $apiSecret Your API secret for signature generation
     * @param string $baseUrl Base API URL
     * @param float $timeout Seconds to wait for the API to connect and to answer
     * @param string|null $caBundle Path to a PEM file with the certificate authorities to trust instead of the
     *                              system ones
     * @throws InvalidRequestException if the base URL is empty or the timeout is not positive
     */
    public function __construct(
        string $apiToken,
        string $apiSecret,
        string $baseUrl = 'https://api.tronzap.com',
        float $timeout = 30.0,
        ?string $caBundle = null
    ) {
        $baseUrl = rtrim($baseUrl, '/');
        if ($baseUrl === '') {
            throw new InvalidRequestException('baseUrl is required');
        }
        if ($timeout <= 0) {
            throw new InvalidRequestException('timeout must be greater than 0');
        }

        $this->apiToken = $apiToken;
        $this->apiSecret = $apiSecret;
        $this->baseUrl = $baseUrl;
        $this->timeout = $timeout;
        $this->caBundle = $caBundle === '' ? null : $caBundle;
    }

    /**
     * Get available services
     *
     * @return array<mixed> Services data
     * @throws TronZapException
     */
    public function getServices(): array
    {
        return $this->request('POST', '/v1/services', []);
    }

    /**
     * Get AML services
     *
     * @return array<mixed> AML services data
     * @throws TronZapException
     */
    public function getAmlServices(): array
    {
        return $this->request('POST', '/v1/aml-checks', []);
    }

    /**
     * Get account balance
     *
     * @return array<mixed> Balance data
     * @throws TronZapException
     */
    public function getBalance(): array
    {
        return $this->request('POST', '/v1/balance', []);
    }

    /**
     * Estimate energy cost
     *
     * @param string $fromAddress TRON wallet address
     * @param string $toAddress TRON wallet address
     * @param string|null $contractAddress TRON contract address, optional.
     *                                     Default is TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t
     * @return array<mixed> Estimate result
     * @throws TronZapException
     */
    public function estimateEnergy(string $fromAddress, string $toAddress, ?string $contractAddress = null): array
    {
        self::requireValue($fromAddress, 'fromAddress');
        self::requireValue($toAddress, 'toAddress');

        $params = [
            'from_address' => $fromAddress,
            'to_address' => $toAddress,
        ];

        if ($contractAddress !== null && $contractAddress !== '') {
            $params['contract_address'] = $contractAddress;
        }

        return $this->request('POST', '/v1/estimate-energy', $params);
    }

    /**
     * Calculate cost for energy purchase
     *
     * @param string $address TRON wallet address
     * @param int $energy Amount of energy to purchase
     * @param int $duration Duration in hours (1 or 24)
     * @return array<mixed> Calculation result
     * @throws TronZapException
     */
    public function calculate(string $address, int $energy, int $duration = 1): array
    {
        self::requireValue($address, 'address');

        return $this->request('POST', '/v1/calculate', [
            'address' => $address,
            'amount' => $energy,
            'duration' => self::atLeastOne($duration, 1)
        ]);
    }

    /**
     * Create a new transaction for energy purchase
     *
     * @param string $address TRON wallet address
     * @param int $energyAmount Amount of energy to purchase
     * @param int $duration Duration in hours (1 or 24)
     * @param string|null $externalId Optional external transaction ID
     * @param bool $activateAddress Whether to activate the address
     * @return array<mixed> Transaction data
     * @throws TronZapException
     */
    public function createEnergyTransaction(
        string $address,
        int $energyAmount,
        int $duration = 1,
        ?string $externalId = null,
        bool $activateAddress = false
    ): array {
        self::requireValue($address, 'address');

        $params = [
            'service' => 'energy',
            'params' => [
                'address' => $address,
                'amounts' => [
                    'energy' => $energyAmount
                ],
                'duration' => self::atLeastOne($duration, 1)
            ]
        ];

        if ($activateAddress) {
            $params['params']['activate_address'] = true;
        }

        return $this->request('POST', '/v1/transaction/new', self::withExternalId($params, $externalId));
    }

    /**
     * Create a new transaction for bandwidth purchase
     *
     * @param string $address TRON wallet address
     * @param int $amount Amount of bandwidth to purchase
     * @param string|null $externalId Optional external transaction ID
     * @return array<mixed> Transaction data
     * @throws TronZapException
     */
    public function createBandwidthTransaction(
        string $address,
        int $amount,
        ?string $externalId = null
    ): array {
        self::requireValue($address, 'address');

        $params = [
            'service' => 'bandwidth',
            'params' => [
                'address' => $address,
                'amounts' => [
                    'bandwidth' => $amount
                ],
                'duration' => 1
            ]
        ];

        return $this->request('POST', '/v1/transaction/new', self::withExternalId($params, $externalId));
    }

    /**
     * Create a new transaction for a resource bundle (energy + bandwidth in one purchase).
     *
     * @param string $address TRON wallet address
     * @param int $energyAmount Amount of energy to purchase
     * @param int $bandwidthAmount Amount of bandwidth to purchase
     * @param int $duration Duration in hours (currently only 1 is supported)
     * @param string|null $externalId Optional external transaction ID
     * @param bool $activateAddress Whether to activate the address
     * @return array<mixed> Transaction data
     * @throws TronZapException
     */
    public function createResourceBundleTransaction(
        string $address,
        int $energyAmount,
        int $bandwidthAmount,
        int $duration = 1,
        ?string $externalId = null,
        bool $activateAddress = false
    ): array {
        self::requireValue($address, 'address');

        $params = [
            'service' => 'resource_bundle',
            'params' => [
                'address' => $address,
                'amounts' => [
                    'energy' => $energyAmount,
                    'bandwidth' => $bandwidthAmount
                ],
                'duration' => self::atLeastOne($duration, 1)
            ]
        ];

        if ($activateAddress) {
            $params['params']['activate_address'] = true;
        }

        return $this->request('POST', '/v1/transaction/new', self::withExternalId($params, $externalId));
    }

    /**
     * Create a new transaction for address activation
     *
     * @param string $address TRON wallet address
     * @param string|null $externalId Optional external transaction ID
     * @return array<mixed> Transaction data
     * @throws TronZapException
     */
    public function createAddressActivationTransaction(string $address, ?string $externalId = null): array
    {
        self::requireValue($address, 'address');

        $params = [
            'service' => 'activate_address',
            'params' => [
                'address' => $address
            ]
        ];

        return $this->request('POST', '/v1/transaction/new', self::withExternalId($params, $externalId));
    }

    /**
     * Create a new AML check
     *
     * @param string $type AML service type: address or hash
     * @param string $network Network code (e.g. TRX, BTC, ETH)
     * @param string $address Wallet address
     * @param string|null $hash Transaction hash when type=hash
     * @param string|null $direction Transaction direction (deposit or withdrawal) when type=hash
     * @return array<mixed> AML check data
     * @throws TronZapException
     */
    public function createAmlCheck(
        string $type,
        string $network,
        string $address,
        ?string $hash = null,
        ?string $direction = null
    ): array {
        self::requireValue($type, 'type');
        self::requireValue($network, 'network');
        self::requireValue($address, 'address');

        $params = [
            'type' => $type,
            'network' => $network,
            'address' => $address
        ];

        if ($hash !== null) {
            $params['hash'] = $hash;
        }

        if ($direction !== null) {
            $params['direction'] = $direction;
        }

        return $this->request('POST', '/v1/aml-checks/new', $params);
    }

    /**
     * Check AML status
     *
     * @param string $id AML check ID
     * @return array<mixed> AML check status
     * @throws TronZapException
     */
    public function checkAmlStatus(string $id): array
    {
        self::requireValue($id, 'id');

        return $this->request('POST', '/v1/aml-checks/check', [
            'id' => $id
        ]);
    }

    /**
     * Get AML history
     *
     * @param int $page Page number
     * @param int $perPage Items per page
     * @param string|null $status Filter by status (pending, processing, completed, failed)
     * @return array<mixed> AML history data
     * @throws TronZapException
     */
    public function getAmlHistory(int $page = 1, int $perPage = 10, ?string $status = null): array
    {
        $params = [
            'page' => self::atLeastOne($page, 1),
            'per_page' => self::atLeastOne($perPage, 10)
        ];

        if ($status !== null) {
            $params['status'] = $status;
        }

        return $this->request('POST', '/v1/aml-checks/history', $params);
    }

    /**
     * Check transaction status
     *
     * @param string|null $id Internal transaction ID
     * @param string|null $externalId External transaction ID
     * @return array<mixed> Transaction status data
     * @throws TronZapException
     */
    public function checkTransaction(?string $id = null, ?string $externalId = null): array
    {
        $params = [];
        if ($id !== null && $id !== '') {
            $params['id'] = $id;
        }
        if ($externalId !== null && $externalId !== '') {
            $params['external_id'] = $externalId;
        }
        if ($params === []) {
            throw new InvalidRequestException('either id or externalId is required');
        }

        return $this->request('POST', '/v1/transaction/check', $params);
    }

    /**
     * Get address info (resources and balances)
     *
     * @param string $address TRON address to query
     * @return array<mixed> Address resources (energy, bandwidth) and balances (TRX, USDT)
     * @throws TronZapException
     */
    public function getAddressInfo(string $address): array
    {
        self::requireValue($address, 'address');

        return $this->request('POST', '/v1/address-info', [
            'address' => $address
        ]);
    }

    /**
     * Get direct recharge information
     *
     * @return array<mixed> Direct recharge information
     * @throws TronZapException
     */
    public function getDirectRechargeInfo(): array
    {
        return $this->request('POST', '/v1/direct-recharge-info', []);
    }

    /**
     * Make an API request
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array<mixed> $params Request parameters
     * @return array<mixed> API response
     * @throws InvalidRequestException if the method is empty or the parameters cannot be encoded as JSON
     * @throws NetworkException on cURL / connectivity errors
     * @throws HttpException on non-2xx HTTP responses
     * @throws ApiException on API-level errors (code != 0)
     * @throws TronZapException on any other error
     */
    public function request(string $method, string $endpoint, array $params): array
    {
        if ($method === '') {
            throw new InvalidRequestException('method is required');
        }
        try {
            $requestBody = json_encode($params === [] ? new stdClass() : $params, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidRequestException('Cannot encode the request as JSON: ' . $e->getMessage());
        }
        $signature = hash('sha256', $requestBody . $this->apiSecret);

        $ch = curl_init();
        $timeoutMs = (int) ceil($this->timeout * 1000);
        $options = [
            CURLOPT_URL => $this->baseUrl . $endpoint,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $requestBody,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT_MS => $timeoutMs,
            CURLOPT_TIMEOUT_MS => $timeoutMs,
            // Without it, libcurl's synchronous resolver ignores timeouts below one second.
            CURLOPT_NOSIGNAL => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiToken,
                'X-Signature: ' . $signature,
                'Content-Type: application/json'
            ],
        ];
        if ($this->caBundle !== null) {
            $options[CURLOPT_CAINFO] = $this->caBundle;
        }
        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);

        // 1. Network-level errors
        if ($curlErrno !== 0 || !is_string($response)) {
            throw self::buildNetworkException($curlErrno, $curlError);
        }

        // 2. JSON parsing
        $responseData = json_decode($response, true);
        $isJson = json_last_error() === JSON_ERROR_NONE;

        // 3. API-level errors (valid JSON + code !== 0, regardless of HTTP status)
        if ($isJson && (!is_array($responseData) || ($responseData['code'] ?? null) !== 0)) {
            throw self::buildApiException(is_array($responseData) ? $responseData : [], $httpCode);
        }

        // 4. HTTP-level errors (non-2xx: invalid JSON or valid JSON with code=0)
        if ($httpCode < 200 || $httpCode >= 300) {
            throw self::buildHttpException($httpCode, $response);
        }

        // 5. HTTP 2xx but invalid JSON
        if (!is_array($responseData)) {
            throw new ServerException('Invalid JSON response: ' . json_last_error_msg(), $httpCode, $response);
        }

        // 6. Missing or malformed result in a successful response
        if (!is_array($responseData['result'] ?? null)) {
            throw new ServerException('Missing result in response', $httpCode, $response);
        }

        return $responseData['result'];
    }

    /**
     * @throws InvalidRequestException
     */
    private static function requireValue(string $value, string $name): void
    {
        if ($value === '') {
            throw new InvalidRequestException($name . ' is required');
        }
    }

    private static function atLeastOne(int $value, int $default): int
    {
        return $value >= 1 ? $value : $default;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private static function withExternalId(array $params, ?string $externalId): array
    {
        if ($externalId !== null && $externalId !== '') {
            $params['external_id'] = $externalId;
        }

        return $params;
    }

    /**
     * @param array<mixed> $data
     */
    private static function buildApiException(array $data, int $httpCode): ApiException
    {
        $code = $data['code'] ?? null;
        $error = $data['error'] ?? null;
        $key = $data['key'] ?? null;
        $requestId = $data['request_id'] ?? null;

        return new ApiException(
            is_string($error) && $error !== '' ? $error : 'Unknown API error',
            is_int($code) ? $code : 1,
            is_string($key) ? $key : null,
            is_string($requestId) ? $requestId : null,
            $httpCode
        );
    }

    private static function buildNetworkException(int $errno, string $error): NetworkException
    {
        // Use numeric libcurl error codes — symbolic CURLE_* constants are
        // not always defined in PHP depending on the libcurl/PHP build.
        // Reference: https://curl.se/libcurl/c/libcurl-errors.html
        $sslErrors = [
            35, // CURLE_SSL_CONNECT_ERROR
            51, // CURLE_PEER_FAILED_VERIFICATION (before libcurl 7.62)
            53, // CURLE_SSL_ENGINE_NOTFOUND
            54, // CURLE_SSL_ENGINE_SETFAILED
            58, // CURLE_SSL_CERTPROBLEM
            59, // CURLE_SSL_CIPHER
            60, // CURLE_PEER_FAILED_VERIFICATION
            64, // CURLE_USE_SSL_FAILED
            66, // CURLE_SSL_ENGINE_INITFAILED
            77, // CURLE_SSL_CACERT_BADFILE
            80, // CURLE_SSL_SHUTDOWN_FAILED
            82, // CURLE_SSL_CRL_BADFILE
            83, // CURLE_SSL_ISSUER_ERROR
            90, // CURLE_SSL_PINNEDPUBKEYNOTMATCH
            91, // CURLE_SSL_INVALIDCERTSTATUS
            98, // CURLE_SSL_CLIENTCERT
        ];
        $timeoutErrors = [
            28, // CURLE_OPERATION_TIMEDOUT
        ];
        $connectionErrors = [
            5, // CURLE_COULDNT_RESOLVE_PROXY
            6, // CURLE_COULDNT_RESOLVE_HOST
            7, // CURLE_COULDNT_CONNECT
        ];

        if (in_array($errno, $sslErrors, true)) {
            return new SslException($error, $errno);
        }
        if (in_array($errno, $timeoutErrors, true)) {
            return new TimeoutException($error, $errno);
        }
        if (in_array($errno, $connectionErrors, true)) {
            return new ConnectionException($error, $errno);
        }

        return new NetworkException($error, $errno);
    }

    private static function buildHttpException(int $httpCode, string $body): HttpException
    {
        if ($httpCode === 429) {
            return new RateLimitException('Too many requests', $httpCode, $body);
        }
        if ($httpCode === 401 || $httpCode === 403) {
            return new UnauthorizedException('Unauthorized', $httpCode, $body);
        }
        if ($httpCode >= 500) {
            return new ServerException('Server error', $httpCode, $body);
        }

        return new HttpException('HTTP error ' . $httpCode, $httpCode, $body);
    }
}
