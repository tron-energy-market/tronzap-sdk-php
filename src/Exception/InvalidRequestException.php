<?php

namespace TronZap\Exception;

/**
 * Thrown before a request is sent when an argument is missing or cannot be sent.
 */
class InvalidRequestException extends TronZapException
{
    public function __construct(string $message)
    {
        parent::__construct($message, 0);
    }
}
