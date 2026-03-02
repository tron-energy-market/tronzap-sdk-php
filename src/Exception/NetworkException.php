<?php

namespace TronZap\Exception;

class NetworkException extends TronZapException
{
    private int $curlErrorCode;

    public function __construct(string $message, int $curlErrorCode = 0)
    {
        parent::__construct($message, 0);
        $this->curlErrorCode = $curlErrorCode;
    }

    public function getCurlErrorCode(): int
    {
        return $this->curlErrorCode;
    }
}
