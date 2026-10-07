<?php

declare(strict_types=1);

namespace TronZap\Tests;

use ReflectionClass;
use ReflectionMethod;
use TronZap\Client;
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
use TronZap\Tests\Support\ApiServer;
use TronZap\Tests\Support\ServerTestCase;

final class ErrorTest extends ServerTestCase
{
    private const INSUFFICIENT_FUNDS = [
        'code' => 6,
        'error' => 'Insufficient funds',
        'key' => 'insufficient_funds',
        'request_id' => 'req-42',
    ];

    /** @return array<string, array{int}> */
    public static function statuses(): array
    {
        $statuses = [];
        foreach ([200, 400, 401, 403, 429, 500, 503] as $status) {
            $statuses["HTTP $status"] = [$status];
        }
        return $statuses;
    }

    /** @dataProvider statuses */
    public function testApiErrorWinsOverHttpStatus(int $status): void
    {
        self::$server->respond($status, self::INSUFFICIENT_FUNDS);

        $error = $this->catch(fn () => $this->client->getBalance());

        self::assertSame(ApiException::class, get_class($error));
        self::assertInstanceOf(ApiException::class, $error);
        self::assertSame(TronZapException::INSUFFICIENT_FUNDS, $error->getCode());
        self::assertSame('Insufficient funds', $error->getMessage());
        self::assertSame('insufficient_funds', $error->getErrorKey());
        self::assertSame('req-42', $error->getRequestId());
        self::assertSame($status, $error->getStatusCode());
    }

    public function testApiErrorWithoutMessageKeyOrRequestId(): void
    {
        self::$server->respond(200, ['code' => 10]);

        $error = $this->catch(fn () => $this->client->getBalance());

        self::assertInstanceOf(ApiException::class, $error);
        self::assertSame(TronZapException::INVALID_TRON_ADDRESS, $error->getCode());
        self::assertSame('Unknown API error', $error->getMessage());
        self::assertNull($error->getErrorKey());
        self::assertNull($error->getRequestId());
    }

    public function testMissingCodeIsAnApiError(): void
    {
        self::$server->respond(200, ['result' => ['balance' => 1]]);

        $error = $this->catch(fn () => $this->client->getBalance());

        self::assertInstanceOf(ApiException::class, $error);
        self::assertSame(1, $error->getCode());
    }

    /** @return array<string, array{string}> */
    public static function nonObjectBodies(): array
    {
        return [
            'array' => ['[]'],
            'list' => ['[1,2]'],
            'string' => ['"text"'],
            'number' => ['42'],
            'null' => ['null'],
            'true' => ['true'],
        ];
    }

    /** @dataProvider nonObjectBodies */
    public function testJsonThatIsNotAnObjectIsAnApiError(string $body): void
    {
        self::$server->respond(200, $body);

        $error = $this->catch(fn () => $this->client->getBalance());

        self::assertSame(ApiException::class, get_class($error));
        self::assertSame(1, $error->getCode());
    }

    /** @return array<string, array{int, class-string<HttpException>}> */
    public static function httpErrors(): array
    {
        return [
            'HTTP 401' => [401, UnauthorizedException::class],
            'HTTP 403' => [403, UnauthorizedException::class],
            'HTTP 429' => [429, RateLimitException::class],
            'HTTP 500' => [500, ServerException::class],
            'HTTP 502' => [502, ServerException::class],
            'HTTP 404' => [404, HttpException::class],
        ];
    }

    /**
     * @dataProvider httpErrors
     * @param class-string<HttpException> $exception
     */
    public function testHttpErrorsWithoutApiPayload(int $status, string $exception): void
    {
        self::$server->respond($status, '<html>gateway</html>');

        $error = $this->catch(fn () => $this->client->getBalance());

        self::assertSame($exception, get_class($error));
        self::assertInstanceOf(HttpException::class, $error);
        self::assertSame($status, $error->getStatusCode());
        self::assertSame($status, $error->getCode());
        self::assertSame('<html>gateway</html>', $error->getResponseBody());
    }

    public function testHttpErrorWithSuccessfulApiCode(): void
    {
        self::$server->respond(500, ['code' => 0, 'result' => []]);

        $error = $this->catch(fn () => $this->client->getBalance());

        self::assertInstanceOf(ServerException::class, $error);
        self::assertSame(500, $error->getStatusCode());
    }

    /** @return array<string, array{string}> */
    public static function invalidSuccessBodies(): array
    {
        return [
            'text' => ['not json'],
            'empty' => [''],
            'truncated' => ['{"code": 0, "result": '],
            'no result' => ['{"code": 0}'],
            'null result' => ['{"code": 0, "result": null}'],
            'string result' => ['{"code": 0, "result": "ok"}'],
            'number result' => ['{"code": 0, "result": 1}'],
        ];
    }

    /** @dataProvider invalidSuccessBodies */
    public function testInvalidSuccessResponse(string $body): void
    {
        self::$server->respond(200, $body);

        $error = $this->catch(fn () => $this->client->getBalance());

        self::assertInstanceOf(ServerException::class, $error);
        self::assertSame(200, $error->getStatusCode());
        self::assertSame($body, $error->getResponseBody());
    }

    public function testConnectionRefused(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        self::assertIsResource($socket);
        $address = (string) stream_socket_get_name($socket, false);
        fclose($socket);

        $client = new Client(self::API_TOKEN, self::API_SECRET, "http://$address");

        $error = $this->catch(fn () => $client->getBalance());

        self::assertSame(ConnectionException::class, get_class($error));
        self::assertInstanceOf(NetworkException::class, $error);
        self::assertSame(0, $error->getCode());
        self::assertSame(7, $error->getCurlErrorCode());
    }

    public function testUnknownHost(): void
    {
        $client = new Client(self::API_TOKEN, self::API_SECRET, 'http://tronzap-sdk-test.invalid');

        self::assertSame(ConnectionException::class, get_class($this->catch(fn () => $client->getBalance())));
    }

    public function testOtherRequestFailuresAreNetworkErrors(): void
    {
        $client = new Client(self::API_TOKEN, self::API_SECRET, 'unsupported://api.tronzap.com');

        self::assertSame(NetworkException::class, get_class($this->catch(fn () => $client->getBalance())));
    }

    public function testTimeout(): void
    {
        $server = ApiServer::http();
        try {
            $server->respond(200, ['code' => 0, 'result' => []], 2.0);
            $client = new Client(self::API_TOKEN, self::API_SECRET, $server->url, 0.3);

            $started = microtime(true);
            $error = $this->catch(fn () => $client->getBalance());

            self::assertSame(TimeoutException::class, get_class($error));
            self::assertLessThan(1.5, microtime(true) - $started);
        } finally {
            $server->stop();
        }
    }

    public function testDefaultTimeoutIs30Seconds(): void
    {
        $parameters = (new ReflectionMethod(Client::class, '__construct'))->getParameters();

        self::assertSame('timeout', $parameters[3]->getName());
        self::assertSame(30.0, $parameters[3]->getDefaultValue());
    }

    public function testBaseUrlIsRequired(): void
    {
        $this->expectException(InvalidRequestException::class);

        new Client(self::API_TOKEN, self::API_SECRET, '/');
    }

    public function testMethodIsRequired(): void
    {
        $this->expectException(InvalidRequestException::class);

        $this->client->request('', '/v1/balance', []);
    }

    public function testTimeoutMustBePositive(): void
    {
        $this->expectException(InvalidRequestException::class);

        new Client(self::API_TOKEN, self::API_SECRET, self::$server->url, 0.0);
    }

    /** @return array<string, array{class-string, class-string}> */
    public static function hierarchy(): array
    {
        return [
            'ApiException' => [ApiException::class, TronZapException::class],
            'NetworkException' => [NetworkException::class, TronZapException::class],
            'ConnectionException' => [ConnectionException::class, NetworkException::class],
            'TimeoutException' => [TimeoutException::class, NetworkException::class],
            'SslException' => [SslException::class, NetworkException::class],
            'HttpException' => [HttpException::class, TronZapException::class],
            'ServerException' => [ServerException::class, HttpException::class],
            'RateLimitException' => [RateLimitException::class, HttpException::class],
            'UnauthorizedException' => [UnauthorizedException::class, HttpException::class],
            'InvalidRequestException' => [InvalidRequestException::class, TronZapException::class],
        ];
    }

    /**
     * @dataProvider hierarchy
     * @param class-string $exception
     * @param class-string $parent
     */
    public function testExceptionHierarchy(string $exception, string $parent): void
    {
        self::assertTrue(is_subclass_of($exception, $parent));
    }

    public function testErrorCodes(): void
    {
        self::assertSame([
            'INTERNAL_SERVER_ERROR' => 500,
            'AUTH_ERROR' => 1,
            'INVALID_SERVICE_OR_PARAMS' => 2,
            'WALLET_NOT_FOUND' => 5,
            'INSUFFICIENT_FUNDS' => 6,
            'INVALID_TRON_ADDRESS' => 10,
            'INVALID_ENERGY_AMOUNT' => 11,
            'INVALID_DURATION' => 12,
            'TRANSACTION_NOT_FOUND' => 20,
            'CANNOT_STOP_SUBSCRIPTION' => 21,
            'ADDRESS_NOT_ACTIVATED' => 24,
            'ADDRESS_ALREADY_ACTIVATED' => 25,
            'AML_CHECK_NOT_FOUND' => 30,
            'SERVICE_NOT_AVAILABLE' => 35,
            'INVALID_BANDWIDTH_AMOUNT' => 50,
        ], (new ReflectionClass(TronZapException::class))->getConstants());
    }

    /** @param callable(): mixed $call */
    private function catch(callable $call): TronZapException
    {
        try {
            $call();
        } catch (TronZapException $e) {
            return $e;
        }
        self::fail('No TronZapException was thrown');
    }
}
