<?php

/**
 * TronZap SDK Client
 *
 * This module provides a PHP client for interacting with the TronZap API
 * to purchase TRX energy for low-cost USDT transfers.
 */

namespace TronZap;

use TronZap\Exception\TronZapException;

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
     * @throws TronZapException
     */
    public function request(string $method, string $endpoint, array $params): array
    {
        $requestBody = json_encode($params);
        $signature = hash('sha256', $requestBody . $this->apiSecret);

        try {
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

            if (curl_errno($ch)) {
                throw new \Exception(curl_error($ch));
            }

            curl_close($ch);

            $responseData = json_decode($response, true);

            if (!isset($responseData['code']) || $responseData['code'] !== 0) {
                throw new TronZapException(
                    $responseData['error'] ?? 'Unknown API error',
                    $responseData['code'] ?? 1
                );
            }

            return $responseData['result'];
        } catch (\Exception $e) {
            if ($e instanceof TronZapException) {
                throw $e;
            } else {
                throw new TronZapException('API request failed: ' . $e->getMessage());
            }
        }
    }
}