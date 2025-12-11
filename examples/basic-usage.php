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

    // Estimate energy cost
    $estimate = $client->estimateEnergy('TRX_FROM_ADDRESS', 'TRX_TO_ADDRESS');
    echo "\nEnergy estimate:\n";
    print_r($estimate);

    // Calculate energy cost
    $calculate = $client->calculate('TRX_ADDRESS', 65150, 1);
    echo "\nEnergy cost:\n";
    print_r($calculate);

    // Create an energy transaction
    $energyTransaction = $client->createEnergyTransaction(
        'TRX_ADDRESS', // Replace with actual TRON address
        65150, // Energy amount, from 32000
        1,     // Duration (hours), possible values: 1 or 24 hours
        'my-external-id-' . time(), // External ID
        false  // Don't activate address
    );
    echo "\nEnergy transaction created:\n";
    print_r($energyTransaction);

    // Create a bandwidth transaction
    $bandwidthTransaction = $client->createBandwidthTransaction(
        'TRX_ADDRESS',
        1000,
        'bandwidth-' . time()
    );
    echo "\nBandwidth transaction created:\n";
    print_r($bandwidthTransaction);

    // Check transaction status
    $transactionId = $energyTransaction['id'] ?? '';
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

    // Create AML check
    $amlCheck = $client->createAmlCheck(
        'address',
        'TRX',
        'TRX_ADDRESS'
    );
    echo "\nAML check created:\n";
    print_r($amlCheck);

    // Check AML status
    if (isset($amlCheck['id'])) {
        $amlStatus = $client->checkAmlStatus($amlCheck['id']);
        echo "\nAML check status:\n";
        print_r($amlStatus);
    }

} catch (TronZapException $e) {
    echo "Error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")\n";
} catch (\Exception $e) {
    echo "General error: " . $e->getMessage() . "\n";
}
