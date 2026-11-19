<?php

namespace TronZap\Exception;

class RateLimitException extends HttpException
{
    private ?int $retryAfter;

    public function __construct(
        string $message,
        int $httpStatusCode,
        string $responseBody = '',
        ?int $retryAfter = null
    ) {
        parent::__construct($message, $httpStatusCode, $responseBody);
        $this->retryAfter = $retryAfter;
    }

    /**
     * Returns the seconds to wait before retrying from the Retry-After header, or null when it was not sent.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
