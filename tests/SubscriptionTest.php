<?php

declare(strict_types=1);

namespace TronZap\Tests;

use TronZap\Exception\ServerException;
use TronZap\Exception\TronZapException;
use TronZap\Tests\Support\ServerTestCase;

final class SubscriptionTest extends ServerTestCase
{
    private const SUBSCRIPTION = '{"id":"01m4e1z3q0r7x225zc6p63m5ey","subscription_id":"unlimited_energy",'
        . '"created_at":"2026-10-08T15:26:32+00:00","expire_at":"2026-11-07T15:26:32+00:00","address":"TAddress",'
        . '"status":"active","external_id":"sub-1","params":{"address":"TAddress","duration":30,'
        . '"transactions_limit":0,"activate_address":false}}';

    public function testPlansKeepTheApiOrderAndKeys(): void
    {
        $this->respondResult(
            '{"unlimited_energy":{"id":8,"name":"Unlimited Energy","activation_fee":0,"initial_price":8,'
            . '"price":2.8,"transactions_limit":0,"duration_days":0},'
            . '"energy_pack_100":{"id":2,"name":"Energy Pack 100","activation_fee":"2.0","initial_price":"10.50",'
            . '"price":"3.1","transactions_limit":10,"duration_days":5}}'
        );

        $plans = $this->client->getSubscriptions();

        self::assertSame(['unlimited_energy', 'energy_pack_100'], array_keys($plans));
        self::assertSame([
            'id' => 8,
            'name' => 'Unlimited Energy',
            'activation_fee' => 0,
            'initial_price' => 8,
            'price' => 2.8,
            'transactions_limit' => 0,
            'duration_days' => 0,
        ], $plans['unlimited_energy']);
        self::assertIsArray($plans['energy_pack_100']);
        self::assertSame('2.0', $plans['energy_pack_100']['activation_fee']);
        self::assertSame(10, $plans['energy_pack_100']['transactions_limit']);
    }

    /** @return array<string, array{string}> */
    public static function emptyPlans(): array
    {
        return ['object' => ['{}'], 'list' => ['[]']];
    }

    /** @dataProvider emptyPlans */
    public function testNoPlans(string $result): void
    {
        $this->respondResult($result);

        self::assertSame([], $this->client->getSubscriptions());
    }

    public function testPlansThatAreNotAnObjectAreAnInvalidResponse(): void
    {
        $this->respondResult('"unlimited_energy"');

        $this->expectException(ServerException::class);
        $this->client->getSubscriptions();
    }

    public function testStartReturnsTheSubscriptionWithParams(): void
    {
        $this->respondResult(self::SUBSCRIPTION);

        $subscription = $this->client->startSubscription('unlimited_energy', 'TAddress', 30, 0, 'sub-1');

        self::assertSame('01m4e1z3q0r7x225zc6p63m5ey', $subscription['id']);
        self::assertSame('active', $subscription['status']);
        self::assertSame('2026-11-07T15:26:32+00:00', $subscription['expire_at']);
        self::assertSame(
            ['address' => 'TAddress', 'duration' => 30, 'transactions_limit' => 0, 'activate_address' => false],
            $subscription['params']
        );
    }

    public function testCheckReturnsTheSubscription(): void
    {
        $this->respondResult(self::SUBSCRIPTION);

        $subscription = $this->client->checkSubscription(null, 'sub-1');

        self::assertSame('sub-1', $subscription['external_id']);
        self::assertSame('unlimited_energy', $subscription['subscription_id']);
    }

    public function testStopReturnsTheSubscriptionWithoutAddress(): void
    {
        $this->respondResult(
            '{"id":"01m4e1z3q0r7x225zc6p63m5ey","subscription_id":"unlimited_energy",'
            . '"created_at":"2026-10-08T15:26:32+00:00","stopped_at":"2026-10-08T15:28:44+00:00","status":"stopped",'
            . '"external_id":"sub-1","params":{"address":"TAddress","duration":30,"transactions_limit":0,'
            . '"activate_address":false}}'
        );

        $subscription = $this->client->stopSubscription('01m4e1z3q0r7x225zc6p63m5ey');

        self::assertSame('stopped', $subscription['status']);
        self::assertSame('2026-10-08T15:28:44+00:00', $subscription['stopped_at']);
        self::assertArrayNotHasKey('address', $subscription);
        self::assertArrayNotHasKey('expire_at', $subscription);
    }

    public function testHistoryCarriesUsageCounters(): void
    {
        $this->respondResult(
            '{"page":1,"per_page":10,"total":2,"items":['
            . '{"id":"01m4e1z3q0r7x225zc6p63m5ey","status":"active","subscription_id":"unlimited_energy",'
            . '"address":"TAddress","transactions_limit":0,"transactions_used":4,"energy_used":262000,'
            . '"total_price":13.6,"started_at":"2026-10-08T15:26:33+00:00",'
            . '"renewed_at":"2026-10-08T15:27:35+00:00","stopped_at":null,"expire_at":"2026-11-07T15:26:32+00:00",'
            . '"created_at":"2026-10-08T15:26:32+00:00"},'
            . '{"id":"01m4e1z3q0r7x225zc6p63m5ez","status":"stopped","subscription_id":"unlimited_energy",'
            . '"address":"TAddress","transactions_limit":10,"transactions_used":1,"energy_used":65000,'
            . '"total_price":"8.00","started_at":null,"renewed_at":null,"stopped_at":null,"expire_at":null,'
            . '"created_at":"2026-10-07T10:00:00+00:00"}]}'
        );

        $history = $this->client->getSubscriptionHistory();

        self::assertSame(1, $history['page']);
        self::assertSame(10, $history['per_page']);
        self::assertSame(2, $history['total']);
        self::assertIsArray($history['items']);
        self::assertCount(2, $history['items']);
        [$active, $stopped] = $history['items'];
        self::assertIsArray($active);
        self::assertIsArray($stopped);
        self::assertSame(4, $active['transactions_used']);
        self::assertSame(262000, $active['energy_used']);
        self::assertSame(13.6, $active['total_price']);
        self::assertNull($active['stopped_at']);
        self::assertArrayNotHasKey('params', $active);
        self::assertSame('8.00', $stopped['total_price']);
    }

    public function testCannotStopSubscription(): void
    {
        self::$server->respond(200, [
            'code' => TronZapException::CANNOT_STOP_SUBSCRIPTION,
            'error' => 'Cannot stop subscription',
            'key' => 'cannot_stop_subscription',
        ]);

        try {
            $this->client->stopSubscription(null, 'sub-1');
            self::fail('No TronZapException was thrown');
        } catch (TronZapException $e) {
            self::assertSame(TronZapException::CANNOT_STOP_SUBSCRIPTION, $e->getCode());
        }
    }

    private function respondResult(string $resultJson): void
    {
        self::$server->respond(200, '{"code":0,"result":' . $resultJson . '}');
    }
}
