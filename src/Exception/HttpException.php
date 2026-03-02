<?php

namespace TronZap\Exception;

class HttpException extends TronZapException
{
    private string $responseBody;

    public function __construct(string $message, int $httpStatusCode, string $responseBody = '')
    {
        parent::__construct($message, $httpStatusCode);
        $this->responseBody = $responseBody;
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }
}
