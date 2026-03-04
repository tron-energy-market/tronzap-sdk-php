<?php

/**
 * TronZap SDK Client
 *
 * This module provides a PHP client for interacting with the TronZap API
 * to purchase TRX energy for low-cost USDT transfers.
 */

namespace TronZap;

use TronZap\Exception\ApiException;
use TronZap\Exception\ConnectionException;
use TronZap\Exception\HttpException;
use TronZap\Exception\NetworkException;
use TronZap\Exception\RateLimitException;
use TronZap\Exception\ServerException;
use TronZap\Exception\SslException;
use TronZap\Exception\TimeoutException;
use TronZap\Exception\TronZapException;
use TronZap\Exception\UnauthorizedException;

class Client
{
    /**
     * @var string Base API URL, default is https://api.tronzap.com
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
     * Client constructor
     *
     * @param string $apiToken Your API token
     * @param string $apiSecret Your API secret for signature generation
     * @param string $baseUrl Base API URL
     */
    public function __construct(string $apiToken, string $apiSecret, string $baseUrl = 'https://api.tronzap.com')
    {
        $this->apiToken = $apiToken;
        $this->apiSecret = $apiSecret;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Get available services
     *
     * @return array Services data
     * @throws TronZapException
     */
    public function getServices(): array
    {
        return $this->request('POST', '/v1/services', []);
    }

    /**
     * Get AML services
     *
     * @return array AML services data
     * @throws TronZapException
     */
    public function getAmlServices(): array
    {
        return $this->request('POST', '/v1/aml-checks', []);
    }

    /**
     * Get account balance
     *
     * @return array Balance data
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
     * @param string $contractAddress TRON contract address, optional. Default is TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t
     * @return array Estimate result
     * @throws TronZapException
     */
    public function estimateEnergy(string $fromAddress, string $toAddress, string $contractAddress = null): array
    {
        return $this->request('POST', '/v1/estimate-energy', [
            'from_address' => $fromAddress,
            'to_address' => $toAddress,
            'contract_address' => $contractAddress
        ]);
    }

    /**
     * Calculate cost for energy purchase
     *
     * @param string $address TRON wallet address
     * @param int $energy Amount of energy to purchase
     * @param int $duration Duration in hours (1 or 24)
     * @return array Calculation result
     * @throws TronZapException
     */
    public function calculate(string $address, int $energy, int $duration = 1): array
    {
        return $this->request('POST', '/v1/calculate', [
            'address' => $address,
            'energy' => $energy,
            'duration' => $duration
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
     * @return array Transaction data
     * @throws TronZapException
     */
    public function createEnergyTransaction(
        string $address,
        int $energyAmount,
        int $duration = 1,
        ?string $externalId = null,
        bool $activateAddress = false
    ): array {
        $params = [
            'service' => 'energy',
            'params' => [
                'address' => $address,
                'energy_amount' => $energyAmount,
                'amount' => $energyAmount,
                'duration' => $duration
            ]
        ];

        if ($activateAddress) {
            $params['params']['activate_address'] = true;
        }

        if ($externalId) {
            $params['external_id'] = $externalId;
        }

        return $this->request('POST', '/v1/transaction/new', $params);
    }

    /**
     * Create a new transaction for bandwidth purchase
     *
     * @param string $address TRON wallet address
     * @param int $amount Amount of bandwidth to purchase
     * @param string|null $externalId Optional external transaction ID
     * @return array Transaction data
     * @throws TronZapException
     */
    public function createBandwidthTransaction(
        string $address,
        int $amount,
        ?string $externalId = null
    ): array {
        $params = [
            'service' => 'bandwidth',
            'params' => [
                'address' => $address,
                'amount' => $amount,
                'duration' => 1
            ]
        ];

        if ($externalId) {
            $params['external_id'] = $externalId;
        }

        return $this->request('POST', '/v1/transaction/new', $params);
    }

    /**
     * Create a new transaction for address activation
     *
     * @param string $address TRON wallet address
     * @param string|null $externalId Optional external transaction ID
     * @return array Transaction data
     * @throws TronZapException
     */
    public function createAddressActivationTransaction(string $address, ?string $externalId = null): array
    {
        $params = [
            'service' => 'activate_address',
            'params' => [
                'address' => $address
            ]
        ];

        if ($externalId) {
            $params['external_id'] = $externalId;
        }

        return $this->request('POST', '/v1/transaction/new', $params);
    }

    /**
     * Create a new AML check
     *
     * @param string $type AML service type: address or hash
     * @param string $network Network code (e.g. TRX, BTC, ETH)
     * @param string $address Wallet address
     * @param string|null $hash Transaction hash when type=hash
     * @param string|null $direction Transaction direction (deposit or withdrawal) when type=hash
     * @return array AML check data
     * @throws TronZapException
     */
    public function createAmlCheck(
        string $type,
        string $network,
        string $address,
        ?string $hash = null,
        ?string $direction = null
    ): array {
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
     * @return array AML check status
     * @throws TronZapException
     */
    public function checkAmlStatus(string $id): array
    {
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
     * @return array AML history data
     * @throws TronZapException
     */
    public function getAmlHistory(int $page = 1, int $perPage = 10, ?string $status = null): array
    {
        $params = [
            'page' => $page,
            'per_page' => $perPage
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
     * @return array Transaction status data
     * @throws TronZapException
     */
    public function checkTransaction(?string $id = null, ?string $externalId = null): array
    {
        $params = [];
        if ($id) {
            $params['id'] = $id;
        }
        if ($externalId) {
            $params['external_id'] = $externalId;
        }

        return $this->request('POST', '/v1/transaction/check', $params);
    }

    /**
     * Get direct recharge information
     *
     * @return array Direct recharge information
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
     * @param array $params Request parameters
     * @return array API response
     * @throws NetworkException on cURL / connectivity errors
     * @throws HttpException on non-2xx HTTP responses
     * @throws ApiException on API-level errors (code != 0)
     * @throws TronZapException on any other error
     */
    public function request(string $method, string $endpoint, array $params): array
    {
        $requestBody = json_encode($params);
        $signature = hash('sha256', $requestBody . $this->apiSecret);

        $ch = curl_init($this->baseUrl . $endpoint);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiToken,
            'X-Signature: ' . $signature,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrno = curl_errno($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        // 1. Network-level errors
        if ($curlErrno !== 0) {
            throw self::buildNetworkException($curlErrno, $curlError);
        }

        // 2. JSON parsing
        $responseData = json_decode($response, true);

        // 3. API-level errors (valid JSON + code !== 0, regardless of HTTP status)
        if (json_last_error() === JSON_ERROR_NONE && (!isset($responseData['code']) || $responseData['code'] !== 0)) {
            throw new ApiException(
                $responseData['error'] ?? 'Unknown API error',
                $responseData['code'] ?? 1,
                $responseData['key'] ?? null
            );
        }

        // 4. HTTP-level errors (non-2xx: invalid JSON or valid JSON with code=0)
        if ($httpCode < 200 || $httpCode >= 300) {
            throw self::buildHttpException($httpCode, (string) $response);
        }

        // 5. HTTP 2xx but invalid JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ServerException(
                'Invalid JSON response: ' . json_last_error_msg(),
                $httpCode,
                (string) $response
            );
        }

        // 6. Missing result key in a successful response
        if (!isset($responseData['result'])) {
            throw new ServerException('Missing result in response', $httpCode, (string) $response);
        }

        return $responseData['result'];
    }

    private static function buildNetworkException(int $errno, string $error): NetworkException
    {
        $sslErrors = [
            CURLE_SSL_CONNECT_ERROR,
            CURLE_PEER_FAILED_VERIFICATION,
            CURLE_SSL_CERTPROBLEM,
            CURLE_SSL_CACERT,
        ];
        $timeoutErrors = [
            CURLE_OPERATION_TIMEDOUT,
        ];
        $connectionErrors = [
            CURLE_COULDNT_RESOLVE_HOST,
            CURLE_COULDNT_CONNECT,
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
