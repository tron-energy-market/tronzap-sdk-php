<?php

namespace TronZap\Exception;

class ApiException extends TronZapException
{
    private ?string $errorKey;

    private ?string $requestId;

    private int $statusCode;

    public function __construct(
        string $message,
        int $code,
        ?string $errorKey = null,
        ?string $requestId = null,
        int $statusCode = 0
    ) {
        parent::__construct($message, $code);
        $this->errorKey = $errorKey;
        $this->requestId = $requestId;
        $this->statusCode = $statusCode;
    }

    /**
     * Returns the error key alias (e.g. "invalid_tron_address" or "invalid_tron_address.from_address").
     */
    public function getErrorKey(): ?string
    {
        return $this->errorKey;
    }

    /**
     * Returns the request ID assigned by the API, to quote when contacting support.
     */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Returns the HTTP status of the response that carried the error.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
