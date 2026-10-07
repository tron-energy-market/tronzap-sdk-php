<?php

declare(strict_types=1);

namespace TronZap\Tests\Support;

use RuntimeException;

final class ApiServer
{
    public string $url;

    private string $stateDir;

    /** @var resource */
    private $process;

    private function __construct(string $scheme, string $host, ?TestCertificate $certificate)
    {
        $stateDir = tempnam(sys_get_temp_dir(), 'tronzap-');
        if ($stateDir === false) {
            throw new RuntimeException('Cannot create a temporary directory');
        }
        unlink($stateDir);
        mkdir($stateDir);
        $this->stateDir = $stateDir;
        $this->respond(200, ['code' => 0, 'result' => []]);

        $command = [PHP_BINARY, __DIR__ . '/server.php', $stateDir];
        if ($certificate !== null) {
            $command[] = $certificate->certFile;
            $command[] = $certificate->keyFile;
        }
        $process = proc_open($command, [], $pipes);
        if ($process === false) {
            throw new RuntimeException('Cannot start the test server');
        }
        $this->process = $process;

        $deadline = microtime(true) + 10;
        while (!is_file("$stateDir/port")) {
            if (microtime(true) > $deadline || !(proc_get_status($process)['running'] ?? false)) {
                $this->stop();
                throw new RuntimeException('The test server did not start');
            }
            usleep(10000);
        }
        $this->url = sprintf('%s://%s:%s', $scheme, $host, file_get_contents("$stateDir/port"));
    }

    public static function http(): self
    {
        return new self('http', '127.0.0.1', null);
    }

    public static function https(TestCertificate $certificate, string $host = 'localhost'): self
    {
        return new self('https', $host, $certificate);
    }

    /** @param mixed $body */
    public function respond(int $status, $body, float $delay = 0.0): void
    {
        $payload = is_string($body) ? $body : json_encode($body);
        file_put_contents(
            "$this->stateDir/response",
            json_encode(['status' => $status, 'body' => $payload, 'delay' => $delay])
        );
    }

    /** @param mixed $result */
    public function ok($result): void
    {
        $this->respond(200, ['code' => 0, 'result' => $result]);
    }

    /** @return list<ReceivedRequest> */
    public function requests(): array
    {
        $file = "$this->stateDir/requests";
        if (!is_file($file)) {
            return [];
        }
        $requests = [];
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $requests[] = ReceivedRequest::fromRecord($line);
        }
        return $requests;
    }

    public function last(): ReceivedRequest
    {
        $requests = $this->requests();
        if ($requests === []) {
            throw new RuntimeException('The server received no request');
        }
        return $requests[count($requests) - 1];
    }

    public function reset(): void
    {
        @unlink("$this->stateDir/requests");
        $this->respond(200, ['code' => 0, 'result' => []]);
    }

    public function stop(): void
    {
        proc_terminate($this->process);
        proc_close($this->process);
        foreach (glob("$this->stateDir/*") ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->stateDir);
    }
}
