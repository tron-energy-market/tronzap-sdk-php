<?php

namespace TronZap\Exception;

class ApiException extends TronZapException
{
    private ?string $errorKey;

    private ?string $requestId;

    private int $statusCode;

    /**
     * @var array<mixed>|null
     */
    private ?array $result;

    private ?int $retryAfter;

    /**
     * @param array<mixed>|null $result
     */
    public function __construct(
        string $message,
        int $code,
        ?string $errorKey = null,
        ?string $requestId = null,
        int $statusCode = 0,
        ?array $result = null,
        ?int $retryAfter = null
    ) {
        parent::__construct($message, $code);
        $this->errorKey = $errorKey;
        $this->requestId = $requestId;
        $this->statusCode = $statusCode;
        $this->result = $result;
        $this->retryAfter = $retryAfter;
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

    /**
     * Returns the result the API sent along with the error, or null when there is none.
     *
     * Some errors carry data: BULK_PRICE_CHANGED (92) the new calculation, BULK_NO_VALID_ITEMS (94) the rejected rows.
     *
     * @return array<mixed>|null
     */
    public function getResult(): ?array
    {
        return $this->result;
    }

    /**
     * Returns the seconds to wait before retrying, when the API sent them (RATE_LIMITED errors), otherwise null.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
