<?php

declare(strict_types=1);

namespace TronZap\Tests\Support;

use RuntimeException;

final class ReceivedRequest
{
    public string $method;

    public string $path;

    /** @var array<string, string> */
    public array $headers;

    public string $body;

    /** @param array<string, string> $headers */
    private function __construct(string $method, string $path, array $headers, string $body)
    {
        $this->method = $method;
        $this->path = $path;
        $this->headers = $headers;
        $this->body = $body;
    }

    public static function fromRecord(string $line): self
    {
        $record = json_decode($line, true);
        if (
            !is_array($record)
            || !is_string($record['method'] ?? null)
            || !is_string($record['path'] ?? null)
            || !is_array($record['headers'] ?? null)
            || !is_string($record['body'] ?? null)
        ) {
            throw new RuntimeException("Malformed request record: $line");
        }
        $headers = [];
        foreach ($record['headers'] as $name => $value) {
            $headers[(string) $name] = is_scalar($value) ? (string) $value : '';
        }
        return new self($record['method'], $record['path'], $headers, (string) base64_decode($record['body'], true));
    }

    /** @return mixed */
    public function json()
    {
        return json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);
    }
}
