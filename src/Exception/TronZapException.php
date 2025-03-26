<?php

namespace TronZap\Exception;

class TronZapException extends \Exception
{
    // Internal server error - Contact support if this error persists.
    public const INTERNAL_SERVER_ERROR = 500;

    // Authentication error - Check your API token and ensure your signature is calculated correctly.
    public const AUTH_ERROR = 1;

    // Invalid service or parameters - Check that the service name and parameters are correct.
    public const INVALID_SERVICE_OR_PARAMS = 2;

    // Wallet not found - Verify the wallet address or contact support if you believe this is an error.
    public const WALLET_NOT_FOUND = 5;

    // Insufficient funds - Add funds to your account or reduce the amount of energy you're requesting.
    public const INSUFFICIENT_FUNDS = 6;

    // Invalid TRON address - Check the TRON address format. It should be a valid 34-character TRON address.
    public const INVALID_TRON_ADDRESS = 10;

    // Invalid energy amount - Ensure the requested energy amount is valid.
    public const INVALID_ENERGY_AMOUNT = 11;

    // Invalid duration - Check that the duration parameter is valid.
    public const INVALID_DURATION = 12;

    // Transaction not found - Verify the transaction ID or external ID is correct.
    public const TRANSACTION_NOT_FOUND = 20;

    // Address not activated - Activate the address first by making an address activation transaction.
    public const ADDRESS_NOT_ACTIVATED = 24;

    // Address already activated - The address is already activated. No action needed.
    public const ADDRESS_ALREADY_ACTIVATED = 25;
}
