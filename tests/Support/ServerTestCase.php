<?php

declare(strict_types=1);

namespace TronZap\Tests\Support;

use PHPUnit\Framework\TestCase;
use TronZap\Client;

abstract class ServerTestCase extends TestCase
{
    protected const API_TOKEN = 'test-token';
    protected const API_SECRET = 'test-secret';

    protected static ApiServer $server;

    protected Client $client;

    public static function setUpBeforeClass(): void
    {
        self::$server = ApiServer::http();
    }

    public static function tearDownAfterClass(): void
    {
        self::$server->stop();
    }

    protected function setUp(): void
    {
        self::$server->reset();
        $this->client = new Client(self::API_TOKEN, self::API_SECRET, self::$server->url);
    }
}
