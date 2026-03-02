<?php

namespace TronZap\Exception;

class ApiException extends TronZapException
{
    private ?string $errorKey;

    public function __construct(string $message, int $code, ?string $errorKey = null)
    {
        parent::__construct($message, $code);
        $this->errorKey = $errorKey;
    }

    /**
     * Returns the error key alias (e.g. "invalid_tron_address" or "invalid_tron_address.from_address").
     */
    public function getErrorKey(): ?string
    {
        return $this->errorKey;
    }
}
