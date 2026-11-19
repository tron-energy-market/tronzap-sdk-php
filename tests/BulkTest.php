<?php

declare(strict_types=1);

namespace TronZap\Tests;

use TronZap\Exception\ApiException;
use TronZap\Exception\InvalidRequestException;
use TronZap\Exception\ServerException;
use TronZap\Exception\TronZapException;
use TronZap\Tests\Support\ServerTestCase;

final class BulkTest extends ServerTestCase
{
    private const CSV = "row,address,amount,status,reason,charged,transaction_id\n"
        . "1,TPY1Kb8cKAZQfm95gXQQs2Mh8Uygtos21D,65000,delivered,,3.25,01kb2m9c4de5fg6hj7km8np9qr\n"
        . "2,TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE,131000,skipped,address_not_activated,,\n";

    private const CALCULATION = [
        'id' => '01kb2m7a1bc2de3fg4hj5km6np',
        'status' => 'draft',
        'resource' => 'energy',
        'duration' => 1,
        'total_amount' => 1240.1,
        'accepted' => 1,
        'rejected_count' => 0,
        'rejected' => [],
        'expires_at' => '2026-11-16T12:15:00+00:00',
    ];

    public function testCalculateSendsEnergyAndTheRowsAsAList(): void
    {
        self::$server->ok(self::CALCULATION);

        $result = $this->client->bulkCalculate([
            5 => ['address' => 'TPY1Kb8cKAZQfm95gXQQs2Mh8Uygtos21D', 'amount' => 65000],
            9 => ['address' => 'TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE', 'amount' => 131000],
        ], 24);

        self::assertSame(self::CALCULATION, $result);
        self::assertSame('/v1/bulk/calculate', self::$server->last()->path);
        self::assertSame([
            'resource' => 'energy',
            'duration' => 24,
            'items' => [
                ['address' => 'TPY1Kb8cKAZQfm95gXQQs2Mh8Uygtos21D', 'amount' => 65000],
                ['address' => 'TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE', 'amount' => 131000],
            ],
        ], self::$server->last()->json());
    }

    public function testCalculateDefaultsTheDurationToOneHour(): void
    {
        $this->client->bulkCalculate([['address' => 'TAddress', 'amount' => 65000]], 0);

        $body = self::$server->last()->json();
        self::assertIsArray($body);
        self::assertSame(1, $body['duration']);
    }

    public function testCalculateWithoutItemsIsNotSent(): void
    {
        $this->expectException(InvalidRequestException::class);

        try {
            $this->client->bulkCalculate([]);
        } finally {
            self::assertSame([], self::$server->requests());
        }
    }

    public function testCalculateWithNoValidRowsReturnsThemInTheError(): void
    {
        $rejected = [['row' => 1, 'address' => 'abc', 'reason' => 'invalid_address']];
        self::$server->respond(200, [
            'code' => 94,
            'key' => 'bulk_no_valid_items',
            'error' => 'No valid items',
            'result' => ['rejected' => $rejected],
        ]);

        try {
            $this->client->bulkCalculate([['address' => 'abc', 'amount' => 65000]]);
            self::fail('No ApiException was thrown');
        } catch (ApiException $e) {
            self::assertSame(TronZapException::BULK_NO_VALID_ITEMS, $e->getCode());
            self::assertSame(['rejected' => $rejected], $e->getResult());
        }
    }

    public function testCreateSendsOnlyTheGivenOptions(): void
    {
        $this->client->bulkCreate('calc-1');

        self::assertSame('/v1/bulk/create', self::$server->last()->path);
        self::assertSame(['id' => 'calc-1'], self::$server->last()->json());
    }

    public function testCreateWithAllOptions(): void
    {
        $this->client->bulkCreate('calc-1', 'order-42', true, 0, 'https://example.com/hook');

        self::assertSame([
            'id' => 'calc-1',
            'force' => true,
            'max_price_increase' => 0,
            'webhook_url' => 'https://example.com/hook',
            'external_id' => 'order-42',
        ], self::$server->last()->json());
    }

    public function testCreateWithoutIdIsNotSent(): void
    {
        $this->expectException(InvalidRequestException::class);

        $this->client->bulkCreate('');
    }

    public function testCreateReturnsTheNewCalculationWhenThePriceChanged(): void
    {
        self::$server->respond(200, [
            'code' => 92,
            'key' => 'bulk_price_changed',
            'error' => 'Price changed, confirmation required',
            'request_id' => 'req-92',
            'result' => self::CALCULATION,
        ]);

        try {
            $this->client->bulkCreate('calc-old');
            self::fail('No ApiException was thrown');
        } catch (ApiException $e) {
            self::assertSame(TronZapException::BULK_PRICE_CHANGED, $e->getCode());
            self::assertSame('bulk_price_changed', $e->getErrorKey());
            self::assertSame(self::CALCULATION, $e->getResult());
        }
    }

    public function testCheckByIdOrExternalId(): void
    {
        self::$server->ok(['id' => 'order-1', 'status' => 'processing']);

        self::assertSame(['id' => 'order-1', 'status' => 'processing'], $this->client->bulkCheck('order-1'));
        self::assertSame('/v1/bulk/check', self::$server->last()->path);
        self::assertSame(['id' => 'order-1'], self::$server->last()->json());

        $this->client->bulkCheck(null, 'my-order');
        self::assertSame(['external_id' => 'my-order'], self::$server->last()->json());
    }

    public function testCheckNeedsAnId(): void
    {
        $this->expectException(InvalidRequestException::class);

        $this->client->bulkCheck();
    }

    public function testHistoryPaging(): void
    {
        $this->client->bulkHistory(3, 50);
        self::assertSame('/v1/bulk/history', self::$server->last()->path);
        self::assertSame(['page' => 3, 'per_page' => 50], self::$server->last()->json());

        $this->client->bulkHistory(0, 0);
        self::assertSame(['page' => 1, 'per_page' => 10], self::$server->last()->json());
    }

    public function testReportReturnsTheCsv(): void
    {
        self::$server->respond(200, self::CSV, 0.0, ['Content-Type' => 'text/csv; charset=UTF-8']);

        self::assertSame(self::CSV, $this->client->bulkReport('order-1', null, 'skipped'));
        self::assertSame('/v1/bulk/report', self::$server->last()->path);
        self::assertSame(['id' => 'order-1', 'status' => 'skipped'], self::$server->last()->json());
    }

    public function testReportErrorsAreJson(): void
    {
        self::$server->respond(200, ['code' => 90, 'key' => 'bulk_not_found', 'error' => 'Bulk order not found']);

        try {
            $this->client->bulkReport(null, 'missing');
            self::fail('No ApiException was thrown');
        } catch (ApiException $e) {
            self::assertSame(TronZapException::BULK_NOT_FOUND, $e->getCode());
        }
    }

    public function testReportThatIsNotCsvIsAnInvalidResponse(): void
    {
        self::$server->ok(['rows' => []]);

        $this->expectException(ServerException::class);
        $this->client->bulkReport('order-1');
    }

    public function testWebhookSignature(): void
    {
        $body = '{"id":"01kb2m5x9qj4v8n3c7d6e5f4g3","external_id":"my-order-42","status":"partial"}';
        $signature = hash('sha256', $body . self::API_SECRET);

        self::assertTrue($this->client->verifyWebhookSignature($body, $signature));
        self::assertTrue($this->client->verifyWebhookSignature($body, ' ' . strtoupper($signature) . "\n"));
        self::assertFalse($this->client->verifyWebhookSignature($body . ' ', $signature));
        self::assertFalse($this->client->verifyWebhookSignature($body, ''));
        self::assertFalse($this->client->verifyWebhookSignature($body, hash('sha256', $body . 'other-secret')));
    }
}
