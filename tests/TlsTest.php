<?php

declare(strict_types=1);

namespace TronZap\Tests;

use PHPUnit\Framework\TestCase;
use TronZap\Client;
use TronZap\Exception\SslException;
use TronZap\Tests\Support\ApiServer;
use TronZap\Tests\Support\TestCertificate;

final class TlsTest extends TestCase
{
    private ApiServer $server;

    protected function tearDown(): void
    {
        $this->server->stop();
    }

    public function testUntrustedCertificateIsRejected(): void
    {
        $this->server = ApiServer::https(TestCertificate::get());

        $this->expectException(SslException::class);
        try {
            (new Client('token', 'secret', $this->server->url))->getBalance();
        } finally {
            self::assertSame([], $this->server->requests());
        }
    }

    public function testEmptyCaBundleMeansTheSystemOne(): void
    {
        $this->server = ApiServer::https(TestCertificate::get());

        $this->expectException(SslException::class);
        (new Client('token', 'secret', $this->server->url, 30.0, ''))->getBalance();
    }

    public function testCertificateForAnotherHostIsRejected(): void
    {
        $this->server = ApiServer::https(TestCertificate::get(), '127.0.0.1');

        $this->expectException(SslException::class);
        try {
            (new Client('token', 'secret', $this->server->url, 30.0, TestCertificate::get()->caFile))->getBalance();
        } finally {
            self::assertSame([], $this->server->requests());
        }
    }

    public function testMissingCaBundleIsAnSslError(): void
    {
        $this->server = ApiServer::https(TestCertificate::get());

        $this->expectException(SslException::class);
        (new Client('token', 'secret', $this->server->url, 30.0, __DIR__ . '/missing-ca.pem'))->getBalance();
    }

    public function testTrustedCertificateIsAccepted(): void
    {
        $this->server = ApiServer::https(TestCertificate::get());
        $this->server->ok(['balance' => '1']);

        $client = new Client('token', 'secret', $this->server->url, 30.0, TestCertificate::get()->caFile);

        self::assertSame(['balance' => '1'], $client->getBalance());
    }
}
