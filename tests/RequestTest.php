<?php

declare(strict_types=1);

namespace TronZap\Tests;

use Closure;
use TronZap\Client;
use TronZap\Exception\InvalidRequestException;
use TronZap\Tests\Support\ServerTestCase;

final class RequestTest extends ServerTestCase
{
    private const ADDRESS = 'TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE';
    private const FROM_ADDRESS = 'TJRabPrwbZy45sbavfcjinPJC18kjpRTv8';
    private const TO_ADDRESS = 'TXLAQ63Xg1NAzckPwKHvzw7CSEmLMEqcdj';
    private const USDT_CONTRACT = 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t';

    /** @return array<string, array{Closure(Client): mixed, string, array<string, mixed>}> */
    public static function requests(): array
    {
        $address = self::ADDRESS;

        return [
            'getServices' => [fn (Client $c) => $c->getServices(), '/v1/services', []],
            'getBalance' => [fn (Client $c) => $c->getBalance(), '/v1/balance', []],
            'getAddressInfo' => [
                fn (Client $c) => $c->getAddressInfo($address),
                '/v1/address-info',
                ['address' => $address],
            ],
            'estimateEnergy' => [
                fn (Client $c) => $c->estimateEnergy(self::FROM_ADDRESS, self::TO_ADDRESS, self::USDT_CONTRACT),
                '/v1/estimate-energy',
                [
                    'from_address' => self::FROM_ADDRESS,
                    'to_address' => self::TO_ADDRESS,
                    'contract_address' => self::USDT_CONTRACT,
                ],
            ],
            'estimateEnergy without contract' => [
                fn (Client $c) => $c->estimateEnergy(self::FROM_ADDRESS, self::TO_ADDRESS),
                '/v1/estimate-energy',
                ['from_address' => self::FROM_ADDRESS, 'to_address' => self::TO_ADDRESS],
            ],
            'estimateEnergy with empty contract' => [
                fn (Client $c) => $c->estimateEnergy(self::FROM_ADDRESS, self::TO_ADDRESS, ''),
                '/v1/estimate-energy',
                ['from_address' => self::FROM_ADDRESS, 'to_address' => self::TO_ADDRESS],
            ],
            'calculate' => [
                fn (Client $c) => $c->calculate($address, 65000, 24),
                '/v1/calculate',
                ['address' => $address, 'amount' => 65000, 'duration' => 24],
            ],
            'calculate default duration' => [
                fn (Client $c) => $c->calculate($address, 65000),
                '/v1/calculate',
                ['address' => $address, 'amount' => 65000, 'duration' => 1],
            ],
            'createEnergyTransaction' => [
                fn (Client $c) => $c->createEnergyTransaction($address, 65000),
                '/v1/transaction/new',
                [
                    'service' => 'energy',
                    'params' => ['address' => $address, 'amounts' => ['energy' => 65000], 'duration' => 1],
                ],
            ],
            'createEnergyTransaction full' => [
                fn (Client $c) => $c->createEnergyTransaction($address, 65000, 24, 'order-1', true),
                '/v1/transaction/new',
                [
                    'service' => 'energy',
                    'external_id' => 'order-1',
                    'params' => [
                        'address' => $address,
                        'amounts' => ['energy' => 65000],
                        'duration' => 24,
                        'activate_address' => true,
                    ],
                ],
            ],
            'createBandwidthTransaction' => [
                fn (Client $c) => $c->createBandwidthTransaction($address, 345, 'order-2'),
                '/v1/transaction/new',
                [
                    'service' => 'bandwidth',
                    'external_id' => 'order-2',
                    'params' => ['address' => $address, 'amounts' => ['bandwidth' => 345], 'duration' => 1],
                ],
            ],
            'createBandwidthTransaction minimal' => [
                fn (Client $c) => $c->createBandwidthTransaction($address, 345),
                '/v1/transaction/new',
                [
                    'service' => 'bandwidth',
                    'params' => ['address' => $address, 'amounts' => ['bandwidth' => 345], 'duration' => 1],
                ],
            ],
            'createResourceBundleTransaction' => [
                fn (Client $c) => $c->createResourceBundleTransaction($address, 65000, 345, 1, 'order-3', true),
                '/v1/transaction/new',
                [
                    'service' => 'resource_bundle',
                    'external_id' => 'order-3',
                    'params' => [
                        'address' => $address,
                        'amounts' => ['energy' => 65000, 'bandwidth' => 345],
                        'duration' => 1,
                        'activate_address' => true,
                    ],
                ],
            ],
            'createResourceBundleTransaction minimal' => [
                fn (Client $c) => $c->createResourceBundleTransaction($address, 65000, 345),
                '/v1/transaction/new',
                [
                    'service' => 'resource_bundle',
                    'params' => [
                        'address' => $address,
                        'amounts' => ['energy' => 65000, 'bandwidth' => 345],
                        'duration' => 1,
                    ],
                ],
            ],
            'createAddressActivationTransaction minimal' => [
                fn (Client $c) => $c->createAddressActivationTransaction($address),
                '/v1/transaction/new',
                ['service' => 'activate_address', 'params' => ['address' => $address]],
            ],
            'createAddressActivationTransaction' => [
                fn (Client $c) => $c->createAddressActivationTransaction($address, 'order-4'),
                '/v1/transaction/new',
                ['service' => 'activate_address', 'external_id' => 'order-4', 'params' => ['address' => $address]],
            ],
            'external id "0" is sent' => [
                fn (Client $c) => $c->createAddressActivationTransaction($address, '0'),
                '/v1/transaction/new',
                ['service' => 'activate_address', 'external_id' => '0', 'params' => ['address' => $address]],
            ],
            'checkTransaction by id' => [
                fn (Client $c) => $c->checkTransaction('tx-1'),
                '/v1/transaction/check',
                ['id' => 'tx-1'],
            ],
            'checkTransaction by external id' => [
                fn (Client $c) => $c->checkTransaction(null, 'order-1'),
                '/v1/transaction/check',
                ['external_id' => 'order-1'],
            ],
            'checkTransaction by external id "0"' => [
                fn (Client $c) => $c->checkTransaction(null, '0'),
                '/v1/transaction/check',
                ['external_id' => '0'],
            ],
            'getDirectRechargeInfo' => [fn (Client $c) => $c->getDirectRechargeInfo(), '/v1/direct-recharge-info', []],
            'getAmlServices' => [fn (Client $c) => $c->getAmlServices(), '/v1/aml-checks', []],
            'createAmlCheck address' => [
                fn (Client $c) => $c->createAmlCheck('address', 'TRX', $address),
                '/v1/aml-checks/new',
                ['type' => 'address', 'network' => 'TRX', 'address' => $address],
            ],
            'createAmlCheck hash' => [
                fn (Client $c) => $c->createAmlCheck('hash', 'TRX', $address, 'abc123', 'deposit'),
                '/v1/aml-checks/new',
                [
                    'type' => 'hash',
                    'network' => 'TRX',
                    'address' => $address,
                    'hash' => 'abc123',
                    'direction' => 'deposit',
                ],
            ],
            'checkAmlStatus' => [
                fn (Client $c) => $c->checkAmlStatus('aml-1'),
                '/v1/aml-checks/check',
                ['id' => 'aml-1'],
            ],
            'getAmlHistory' => [
                fn (Client $c) => $c->getAmlHistory(),
                '/v1/aml-checks/history',
                ['page' => 1, 'per_page' => 10],
            ],
            'getAmlHistory filtered' => [
                fn (Client $c) => $c->getAmlHistory(3, 50, 'completed'),
                '/v1/aml-checks/history',
                ['page' => 3, 'per_page' => 50, 'status' => 'completed'],
            ],
            'calculate duration 0' => [
                fn (Client $c) => $c->calculate($address, 65000, 0),
                '/v1/calculate',
                ['address' => $address, 'amount' => 65000, 'duration' => 1],
            ],
            'createEnergyTransaction duration 0' => [
                fn (Client $c) => $c->createEnergyTransaction($address, 65000, 0),
                '/v1/transaction/new',
                [
                    'service' => 'energy',
                    'params' => ['address' => $address, 'amounts' => ['energy' => 65000], 'duration' => 1],
                ],
            ],
            'getAmlHistory paging 0' => [
                fn (Client $c) => $c->getAmlHistory(0, 0),
                '/v1/aml-checks/history',
                ['page' => 1, 'per_page' => 10],
            ],
            'getSubscriptions' => [fn (Client $c) => $c->getSubscriptions(), '/v1/subscriptions', []],
            'startSubscription' => [
                fn (Client $c) => $c->startSubscription('unlimited_energy', $address),
                '/v1/subscription/start',
                [
                    'subscription_id' => 'unlimited_energy',
                    'params' => ['address' => $address, 'duration' => 0, 'transactions_limit' => 0],
                ],
            ],
            'startSubscription full' => [
                fn (Client $c) => $c->startSubscription('unlimited_energy', $address, 30, 100, 'sub-1', true),
                '/v1/subscription/start',
                [
                    'subscription_id' => 'unlimited_energy',
                    'external_id' => 'sub-1',
                    'params' => [
                        'address' => $address,
                        'duration' => 30,
                        'transactions_limit' => 100,
                        'activate_address' => true,
                    ],
                ],
            ],
            'startSubscription external id "0"' => [
                fn (Client $c) => $c->startSubscription('unlimited_energy', $address, 30, 0, '0'),
                '/v1/subscription/start',
                [
                    'subscription_id' => 'unlimited_energy',
                    'external_id' => '0',
                    'params' => ['address' => $address, 'duration' => 30, 'transactions_limit' => 0],
                ],
            ],
            'startSubscription empty external id' => [
                fn (Client $c) => $c->startSubscription('unlimited_energy', $address, 30, 0, ''),
                '/v1/subscription/start',
                [
                    'subscription_id' => 'unlimited_energy',
                    'params' => ['address' => $address, 'duration' => 30, 'transactions_limit' => 0],
                ],
            ],
            'checkSubscription by id' => [
                fn (Client $c) => $c->checkSubscription('sub-id-1'),
                '/v1/subscription/check',
                ['id' => 'sub-id-1'],
            ],
            'checkSubscription by external id' => [
                fn (Client $c) => $c->checkSubscription(null, 'sub-1'),
                '/v1/subscription/check',
                ['external_id' => 'sub-1'],
            ],
            'checkSubscription by external id "0"' => [
                fn (Client $c) => $c->checkSubscription(null, '0'),
                '/v1/subscription/check',
                ['external_id' => '0'],
            ],
            'stopSubscription with both ids' => [
                fn (Client $c) => $c->stopSubscription('sub-id-1', 'sub-1'),
                '/v1/subscription/stop',
                ['id' => 'sub-id-1', 'external_id' => 'sub-1'],
            ],
            'stopSubscription by external id' => [
                fn (Client $c) => $c->stopSubscription('', 'sub-1'),
                '/v1/subscription/stop',
                ['external_id' => 'sub-1'],
            ],
            'getSubscriptionHistory' => [
                fn (Client $c) => $c->getSubscriptionHistory(),
                '/v1/subscriptions/history',
                ['page' => 1, 'per_page' => 10],
            ],
            'getSubscriptionHistory filtered' => [
                fn (Client $c) => $c->getSubscriptionHistory(2, 50, 'active'),
                '/v1/subscriptions/history',
                ['page' => 2, 'per_page' => 50, 'status' => 'active'],
            ],
            'getSubscriptionHistory paging 0 and empty status' => [
                fn (Client $c) => $c->getSubscriptionHistory(0, -1, ''),
                '/v1/subscriptions/history',
                ['page' => 1, 'per_page' => 10],
            ],
        ];
    }

    /**
     * @dataProvider requests
     * @param Closure(Client): mixed $call
     * @param array<string, mixed> $expected
     */
    public function testRequestBody(Closure $call, string $path, array $expected): void
    {
        $call($this->client);

        $requests = self::$server->requests();
        self::assertCount(1, $requests);
        self::assertSame('POST', $requests[0]->method);
        self::assertSame($path, $requests[0]->path);
        self::assertSame(self::sorted($expected), self::sorted($requests[0]->json()));
    }

    public function testEmptyParamsAreSentAsAnObject(): void
    {
        $this->client->getBalance();

        self::assertSame('{}', self::$server->last()->body);
    }

    /**
     * @dataProvider requests
     * @param Closure(Client): mixed $call
     * @param array<string, mixed> $expected
     */
    public function testSignatureIsSha256OfTheBodyActuallySent(Closure $call, string $path, array $expected): void
    {
        $call($this->client);

        $received = self::$server->last();
        self::assertSame(hash('sha256', $received->body . self::API_SECRET), $received->headers['x-signature']);
    }

    public function testSignatureCoversNonAsciiBody(): void
    {
        $this->client->checkTransaction(null, 'pedido-año-订单-😀');

        $received = self::$server->last();
        self::assertSame(['external_id' => 'pedido-año-订单-😀'], $received->json());
        self::assertSame(hash('sha256', $received->body . self::API_SECRET), $received->headers['x-signature']);
    }

    public function testHeaders(): void
    {
        $this->client->getBalance();

        $headers = self::$server->last()->headers;
        self::assertSame('Bearer ' . self::API_TOKEN, $headers['authorization']);
        self::assertSame('application/json', $headers['content-type']);
    }

    public function testReturnsTheResultField(): void
    {
        self::$server->ok(['balance' => '12.5', 'address' => self::ADDRESS]);

        self::assertSame(['balance' => '12.5', 'address' => self::ADDRESS], $this->client->getBalance());
    }

    public function testReturnsListResults(): void
    {
        self::$server->ok([['id' => 'address', 'price' => 1]]);

        self::assertSame([['id' => 'address', 'price' => 1]], $this->client->getAmlServices());
    }

    public function testBaseUrlTrailingSlashIsIgnored(): void
    {
        (new Client(self::API_TOKEN, self::API_SECRET, self::$server->url . '/'))->getBalance();

        self::assertSame('/v1/balance', self::$server->last()->path);
    }

    /** @return array<string, array{Closure(Client): mixed}> */
    public static function invalidCalls(): array
    {
        $address = self::ADDRESS;

        return [
            'getAddressInfo without address' => [fn (Client $c) => $c->getAddressInfo('')],
            'estimateEnergy without from' => [fn (Client $c) => $c->estimateEnergy('', self::TO_ADDRESS)],
            'estimateEnergy without to' => [fn (Client $c) => $c->estimateEnergy(self::FROM_ADDRESS, '')],
            'calculate without address' => [fn (Client $c) => $c->calculate('', 65000)],
            'energy without address' => [fn (Client $c) => $c->createEnergyTransaction('', 65000)],
            'bandwidth without address' => [fn (Client $c) => $c->createBandwidthTransaction('', 345)],
            'bundle without address' => [fn (Client $c) => $c->createResourceBundleTransaction('', 65000, 345)],
            'activation without address' => [fn (Client $c) => $c->createAddressActivationTransaction('')],
            'checkTransaction without ids' => [fn (Client $c) => $c->checkTransaction()],
            'checkTransaction with empty ids' => [fn (Client $c) => $c->checkTransaction('', '')],
            'aml check without type' => [fn (Client $c) => $c->createAmlCheck('', 'TRX', $address)],
            'aml check without network' => [fn (Client $c) => $c->createAmlCheck('address', '', $address)],
            'aml check without address' => [fn (Client $c) => $c->createAmlCheck('address', 'TRX', '')],
            'aml status without id' => [fn (Client $c) => $c->checkAmlStatus('')],
            'subscription without plan' => [fn (Client $c) => $c->startSubscription('', $address)],
            'subscription without address' => [fn (Client $c) => $c->startSubscription('unlimited_energy', '')],
            'subscription with negative days' => [
                fn (Client $c) => $c->startSubscription('unlimited_energy', $address, -1),
            ],
            'subscription with negative limit' => [
                fn (Client $c) => $c->startSubscription('unlimited_energy', $address, 30, -1),
            ],
            'checkSubscription without ids' => [fn (Client $c) => $c->checkSubscription()],
            'checkSubscription with empty ids' => [fn (Client $c) => $c->checkSubscription('', '')],
            'stopSubscription without ids' => [fn (Client $c) => $c->stopSubscription()],
            'stopSubscription with empty ids' => [fn (Client $c) => $c->stopSubscription(null, '')],
            'external id that is not UTF-8' => [
                fn (Client $c) => $c->createAddressActivationTransaction($address, "\xB1\x31"),
            ],
        ];
    }

    /**
     * @dataProvider invalidCalls
     * @param Closure(Client): mixed $call
     */
    public function testInvalidArgumentsAreRejectedBeforeSending(Closure $call): void
    {
        try {
            $call($this->client);
            self::fail('InvalidRequestException was not thrown');
        } catch (InvalidRequestException $e) {
            self::assertSame(0, $e->getCode());
        }

        self::assertSame([], self::$server->requests());
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function sorted($value)
    {
        if (!is_array($value)) {
            return $value;
        }
        ksort($value);
        return array_map([self::class, 'sorted'], $value);
    }
}
