# TronZap PHP SDK

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

## Requirements

- PHP 7.4 or higher
- php-json extension

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

    // Check transaction status
    $status = $client->checkTransaction($transaction['id']);
    print_r($status);
} catch (TronZapException $e) {
    echo "Error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")\n";
}
```

## Available Methods

- `getServices()` - Get list of available services and prices
- `getBalance()` - Get current account balance
- `createEnergyTransaction(address, energyAmount, duration, externalId, activateAddress)` - Create a transaction for energy purchase
- `createAddressActivationTransaction(address, externalId)` - Create a transaction for address activation
- `checkTransaction(transactionId)` - Check status of a transaction
- `getDirectRechargeInfo()` - Get direct recharge service information

## Error Handling

The SDK will throw a `TronZapException` if an API error occurs. Common error codes include:

- 1: Authentication error: Check your API token and ensure your signature is calculated correctly.
- 2: Invalid service or parameters: Check that the service name and parameters are correct.
- 5: Internal wallet not found: contact support.
- 6: Insufficient funds: Add funds to your account or reduce the amount of energy you're requesting.
- 10: Invalid TRON address: Check the TRON address format. It should be a valid 34-character TRON address.
- 11: Invalid energy amount: Ensure the requested energy amount is valid.
- 12: Invalid duration: Check that the duration parameter is valid. Can be 1 or 24 hours.
- 20: Transaction not found: Verify the transaction ID or external ID is correct.
- 24: Address not activated: Activate the address first by making an address activation transaction.
- 25: Address already activated: The address is already activated. No action needed.
- 500: Internal Server Error.

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Support

For support, please contact [support@tronzap.com](mailto:support@tronzap.com).