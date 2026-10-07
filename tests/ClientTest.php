<?php

declare(strict_types=1);

namespace TronZap\Tests;

use PHPUnit\Framework\TestCase;
use TronZap\Client;

final class ClientTest extends TestCase
{
    public function testVersionMatchesSemver(): void
    {
        self::assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', Client::VERSION);
    }

    public function testDefaultBaseUrl(): void
    {
        $parameters = (new \ReflectionMethod(Client::class, '__construct'))->getParameters();

        self::assertSame('https://api.tronzap.com', $parameters[2]->getDefaultValue());
    }
}
