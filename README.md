# Tron Energy Rental via API
## PHP SDK by TronZap.com

**[English](README.md)** | [Español](README.es.md) | [Português](README.pt-br.md) | [Русский](README.ru.md)

Official PHP SDK for the TronZap API.
This SDK allows you to easily integrate with TronZap services for TRON energy rental.

TronZap.com allows you to [buy TRON energy](https://tronzap.com/), making USDT (TRC20) transfers cheaper by significantly reducing transaction fees.

👉 [Register for an API key](https://tronzap.com) to start using TronZap API and integrate it via the SDK.

## Installation

You can install the package via composer:

```bash
composer require tron-energy-market/tronzap-sdk-php
```

Check out at Packagist: https://packagist.org/packages/tron-energy-market/tronzap-sdk-php

## Requirements

- PHP 7.4 or higher

## Usage

```php
use TronZap\Client as TronZapClient;
use TronZap\Exception\TronZapException;

// Initialize the client
$apiToken = 'your_api_token';
$apiSecret = 'your_api_secret';
$client = new TronZapClient($apiToken, $apiSecret);

try {
    // Get account balance
    $balance = $client->getBalance();
    print_r($balance);

    // Get available services
    $services = $client->getServices();
    print_r($services);

    // Estimate energy amount for USDT transfer
    $estimate = $client->estimateEnergy('FROM_TRX_ADDRESS', 'TO_TRX_ADDRESS', 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');
    print_r($estimate);

    // Create an energy transaction
    $transaction = $client->createEnergyTransaction(
        'TRX_ADDRESS',       // TRON wallet address
        $estimate['energy'], // Energy amount
        1,                   // Duration (hours), can be 1 or 24
        'my-tx-id',          // External ID (optional)
        true                 // Activate address (optional)
    );
    print_r($transaction);

    // Buy bandwidth
    $bandwidth = $client->createBandwidthTransaction(
        'TRX_ADDRESS',   // TRON wallet address
        1000,            // Bandwidth amount
        'bandwidth-1'    // External ID (optional)
    );
    print_r($bandwidth);

    // Check transaction status
    $status = $client->checkTransaction($transaction['id']);
    print_r($status);

    // Create AML check for an address
    $amlCheck = $client->createAmlCheck(
        'address',
        'TRX',
        'TRX_ADDRESS'
    );
    print_r($amlCheck);

    // Check AML status
    $amlStatus = $client->checkAmlStatus($amlCheck['id']);
    print_r($amlStatus);
} catch (TronZapException $e) {
    echo "Error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")\n";
}
```

## Available Methods

- `getServices()` - Get list of available services and prices
- `getBalance()` - Get current account balance
- `createEnergyTransaction(address, energyAmount, duration, externalId, activateAddress)` - Create a transaction for energy purchase
- `createBandwidthTransaction(address, amount, externalId)` - Create a transaction for bandwidth purchase
- `createAddressActivationTransaction(address, externalId)` - Create a transaction for address activation
- `checkTransaction(transactionId, externalId)` - Check status of a transaction
- `getDirectRechargeInfo()` - Get direct recharge service information
- `getAmlServices()` - Get AML services and pricing
- `createAmlCheck(type, network, address, hash, direction)` - Create a new AML check
- `checkAmlStatus(id)` - Get status for an AML check
- `getAmlHistory(page, perPage, status)` - Get AML checks history

## Error Handling

The SDK uses a hierarchy of exceptions for precise error handling:

```
TronZapException
├── ApiException             — API-level errors (response code != 0)
├── NetworkException         — Network/connectivity errors
│   ├── ConnectionException  — Could not connect to server
│   ├── TimeoutException     — Request timed out
│   └── SslException         — SSL/TLS errors
└── HttpException            — HTTP non-2xx responses
    ├── RateLimitException   — HTTP 429 Too Many Requests
    ├── UnauthorizedException — HTTP 401/403
    └── ServerException      — HTTP 5xx errors
```

### Example

```php
use TronZap\Client as TronZapClient;
use TronZap\Exception\ApiException;
use TronZap\Exception\ConnectionException;
use TronZap\Exception\HttpException;
use TronZap\Exception\NetworkException;
use TronZap\Exception\RateLimitException;
use TronZap\Exception\ServerException;
use TronZap\Exception\SslException;
use TronZap\Exception\TimeoutException;
use TronZap\Exception\TronZapException;
use TronZap\Exception\UnauthorizedException;

$client = new TronZapClient('your_api_token', 'your_api_secret');

try {
    $transaction = $client->createEnergyTransaction('TRX_ADDRESS', 65000, 1);
} catch (ApiException $e) {
    // API-level error (invalid params, insufficient funds, etc.)
    echo "API error [{$e->getCode()}]: {$e->getMessage()}\n";

    // Error key alias, e.g. "invalid_tron_address" or "invalid_tron_address.from_address"
    if ($e->getErrorKey()) {
        echo "Error key: {$e->getErrorKey()}\n";
    }

    if ($e->getCode() === TronZapException::INVALID_TRON_ADDRESS) {
        echo "Check the TRON address format.\n";
    }
} catch (RateLimitException $e) {
    echo "Too many requests, please slow down.\n";
} catch (UnauthorizedException $e) {
    echo "Invalid API token or signature.\n";
} catch (ServerException $e) {
    echo "TronZap server error [{$e->getStatusCode()}].\n";
} catch (HttpException $e) {
    echo "HTTP error [{$e->getStatusCode()}]: {$e->getMessage()}\n";
} catch (TimeoutException $e) {
    echo "Request timed out.\n";
} catch (SslException $e) {
    echo "SSL error: {$e->getMessage()}\n";
} catch (ConnectionException $e) {
    echo "Connection failed: {$e->getMessage()}\n";
} catch (NetworkException $e) {
    echo "Network error: {$e->getMessage()}\n";
} catch (TronZapException $e) {
    echo "Error [{$e->getCode()}]: {$e->getMessage()}\n";
}
```

### API Error Codes

| Code | Constant                        | Description |
|------|----------------------------------|-------------|
| 1    | `AUTH_ERROR`                    | Authentication error – Invalid API token or signature |
| 2    | `INVALID_SERVICE_OR_PARAMS`    | Invalid service or parameters |
| 5    | `WALLET_NOT_FOUND`             | Internal wallet not found. Contact support. |
| 6    | `INSUFFICIENT_FUNDS`           | Insufficient funds |
| 10   | `INVALID_TRON_ADDRESS`         | Invalid TRON address |
| 11   | `INVALID_ENERGY_AMOUNT`        | Invalid energy amount |
| 12   | `INVALID_DURATION`             | Invalid duration |
| 20   | `TRANSACTION_NOT_FOUND`        | Transaction not found |
| 24   | `ADDRESS_NOT_ACTIVATED`        | Address not activated |
| 25   | `ADDRESS_ALREADY_ACTIVATED`    | Address already activated |
| 30   | `AML_CHECK_NOT_FOUND`          | AML check not found |
| 35   | `SERVICE_NOT_AVAILABLE`        | Service not available |
| 500  | `INTERNAL_SERVER_ERROR`        | Internal server error – Contact support |

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Support

For support, please contact [support@tronzap.com](mailto:support@tronzap.com).
