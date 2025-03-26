<?php

require_once __DIR__ . '/../vendor/autoload.php';

use TronZap\Client as TronZapClient;
use TronZap\Exception\TronZapException;

// Initialize the client
$apiToken = 'your_api_token';
$apiSecret = 'your_api_secret';
$client = new TronZapClient($apiToken, $apiSecret);

try {
    // Get available services
    $services = $client->getServices();
    echo "Available services:\n";
    print_r($services);

    // Get account balance
    $balance = $client->getBalance();
    echo "\nAccount balance:\n";
    print_r($balance);

    // Create an energy transaction
    $energyTransaction = $client->createEnergyTransaction(
        'TRX_ADDRESS', // Replace with actual TRON address
        32000, // Energy amount
        1,     // Duration (hours)
        'my-external-id-' . time(), // External ID
        false  // Don't activate address
    );
    echo "\nEnergy transaction created:\n";
    print_r($energyTransaction);

    // Check transaction status
    $transactionId = $energyTransaction['result']['transaction_id'] ?? '';
    if ($transactionId) {
        $transactionStatus = $client->checkTransaction($transactionId);
        echo "\nTransaction status:\n";
        print_r($transactionStatus);
    }

    // Create an address activation transaction
    $activationTransaction = $client->createAddressActivationTransaction(
        'TRX_ADDRESS', // Replace with actual TRON address
        'activation-' . time() // External ID
    );
    echo "\nAddress activation transaction created:\n";
    print_r($activationTransaction);

} catch (TronZapException $e) {
    echo "Error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")\n";
} catch (\Exception $e) {
    echo "General error: " . $e->getMessage() . "\n";
}