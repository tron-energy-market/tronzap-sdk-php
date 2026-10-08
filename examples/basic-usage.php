<?php

/*
 * Walks through the TronZap API operations. By default it only reads and spends nothing.
 *
 *     export TRONZAP_API_TOKEN=your_api_token
 *     export TRONZAP_API_SECRET=your_api_secret
 *     export TRONZAP_BASE_URL=https://api.tronzap.com  # optional, e.g. a dev host
 *     export TRONZAP_ADDRESS=TRON_ADDRESS              # optional
 *     export TRONZAP_FROM_ADDRESS=TRON_ADDRESS         # optional, with TO_ADDRESS
 *     export TRONZAP_TO_ADDRESS=TRON_ADDRESS           # optional, with FROM_ADDRESS
 *     export TRONZAP_TRANSACTION_ID=id                 # optional
 *     export TRONZAP_AML_CHECK_ID=id                   # optional
 *     composer install
 *     php examples/basic-usage.php
 *
 * Setting TRONZAP_ALLOW_PURCHASES=1 additionally exercises the endpoints that create transactions and AML checks.
 * Those DEBIT THE ACCOUNT BALANCE. It is meant for verifying an integration against a development environment, and
 * it also needs TRONZAP_ADDRESS.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use TronZap\Client;
use TronZap\Exception\ApiException;
use TronZap\Exception\TronZapException;

const ENERGY = 65000;
const BANDWIDTH = 345;

function env(string $name): ?string
{
    $value = getenv($name);
    $value = $value === false ? '' : trim($value);

    return $value === '' ? null : $value;
}

/**
 * @param array<mixed> $data
 */
function field(array $data, string $key): string
{
    $value = $data[$key] ?? null;
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    return is_scalar($value) ? (string) $value : '-';
}

/**
 * @param array<mixed> $data
 * @return array<array<mixed>>
 */
function rows(array $data, string $key): array
{
    $value = $data[$key] ?? null;

    return is_array($value) ? array_filter($value, 'is_array') : [];
}

/**
 * @param array<mixed> $transaction
 */
function printTransaction(array $transaction): void
{
    printf(
        "  %s %s %s, charged %s, created %s\n",
        field($transaction, 'id'),
        field($transaction, 'service'),
        field($transaction, 'status'),
        field($transaction, 'amount'),
        field($transaction, 'created_at')
    );
}

function main(): int
{
    $token = env('TRONZAP_API_TOKEN');
    $secret = env('TRONZAP_API_SECRET');
    if ($token === null || $secret === null) {
        fwrite(STDERR, "set TRONZAP_API_TOKEN and TRONZAP_API_SECRET\n");
        return 1;
    }

    $baseUrl = env('TRONZAP_BASE_URL') ?? 'https://api.tronzap.com';
    $client = new Client($token, $secret, $baseUrl, 20.0);
    echo "Calling $baseUrl\n";

    $failed = [];

    $step = static function (string $name, callable $call) use (&$failed): void {
        echo "\n$name\n";
        try {
            $call();
        } catch (TronZapException $e) {
            printf("  FAILED: %s: %s (code %d)\n", get_class($e), $e->getMessage(), $e->getCode());
            $failed[] = $name;
        }
    };

    $optionalStep = static function (string $name, ?string $subject, callable $call) use ($step): void {
        if ($subject === null) {
            echo "\n$name\n  skipped: its environment variable is not set\n";
            return;
        }
        $step($name, static fn () => $call($subject));
    };

    $step('getBalance', static function () use ($client): void {
        $balance = $client->getBalance();
        printf("  balance %s, deposit address %s\n", field($balance, 'balance'), field($balance, 'address'));
    });

    $step('getServices', static function () use ($client): void {
        $services = $client->getServices();
        foreach (rows($services, 'energy') as $rate) {
            printf(
                "  energy %sh %s..%s at %s per 1000 units (65k = %s)\n",
                field($rate, 'duration'),
                field($rate, 'min_amount'),
                field($rate, 'max_amount'),
                field($rate, 'price'),
                field($rate, 'price_65k')
            );
        }
        foreach (rows($services, 'bandwidth') as $rate) {
            printf(
                "  bandwidth %sh %s..%s at %s per 1000 units\n",
                field($rate, 'duration'),
                field($rate, 'min_amount'),
                field($rate, 'max_amount'),
                field($rate, 'price')
            );
        }
        $activation = $services['activate_address'] ?? null;
        if (is_array($activation)) {
            printf("  activation %s\n", field($activation, 'price'));
        }
    });

    $step('getDirectRechargeInfo', static function () use ($client): void {
        $info = $client->getDirectRechargeInfo();
        printf("  pay to %s, %d rate(s)\n", field($info, 'address'), count(rows($info, 'rates')));
    });

    $step('getAmlServices', static function () use ($client): void {
        foreach (array_filter($client->getAmlServices(), 'is_array') as $service) {
            printf("  %s %s at %s\n", field($service, 'id'), field($service, 'type'), field($service, 'price'));
        }
    });

    $step('getAmlHistory', static function () use ($client): void {
        $history = $client->getAmlHistory();
        printf(
            "  page %s, %d of %s check(s)\n",
            field($history, 'page'),
            count(rows($history, 'items')),
            field($history, 'total')
        );
    });

    $address = env('TRONZAP_ADDRESS');

    $optionalStep('getAddressInfo', $address, static function (string $value) use ($client): void {
        $info = $client->getAddressInfo($value);
        $resources = is_array($info['resources'] ?? null) ? $info['resources'] : [];
        $balances = [];
        foreach (is_array($info['balances'] ?? null) ? $info['balances'] : [] as $symbol => $amount) {
            $balances[] = $symbol . ' ' . (is_scalar($amount) ? (string) $amount : '-');
        }
        printf(
            "  energy %s, bandwidth %s, balances %s\n",
            field($resources, 'energy'),
            field($resources, 'bandwidth'),
            implode(', ', $balances)
        );
    });

    $optionalStep('calculate', $address, static function (string $value) use ($client): void {
        $calculation = $client->calculate($value, ENERGY);
        printf(
            "  %s energy for %sh costs %s\n",
            field($calculation, 'amount'),
            field($calculation, 'duration'),
            field($calculation, 'total')
        );
    });

    $fromAddress = env('TRONZAP_FROM_ADDRESS');
    $toAddress = env('TRONZAP_TO_ADDRESS') ?? '';
    $optionalStep(
        'estimateEnergy',
        $toAddress === '' ? null : $fromAddress,
        static function (string $value) use ($client, $toAddress): void {
            $estimate = $client->estimateEnergy($value, $toAddress);
            printf("  %s energy, total %s\n", field($estimate, 'amount'), field($estimate, 'total'));
        }
    );

    $transactionId = env('TRONZAP_TRANSACTION_ID');
    $optionalStep('checkTransaction', $transactionId, static function (string $value) use ($client): void {
        printTransaction($client->checkTransaction($value));
    });

    $amlCheckId = env('TRONZAP_AML_CHECK_ID');
    $optionalStep('checkAmlStatus', $amlCheckId, static function (string $value) use ($client): void {
        $check = $client->checkAmlStatus($value);
        $risk = field($check, 'risk_score');
        printf("  %s, risk %s\n", field($check, 'status'), $risk === '-' ? 'not scored yet' : $risk);
    });

    if (env('TRONZAP_ALLOW_PURCHASES') !== '1') {
        echo "\nSkipping purchases: set TRONZAP_ALLOW_PURCHASES=1 to create transactions (debits the balance)\n";
    } elseif ($address === null) {
        echo "\nSkipping purchases: TRONZAP_ADDRESS is not set\n";
    } else {
        $runId = 'php-example-' . (int) (microtime(true) * 1000);

        $step('createAddressActivationTransaction', static function () use ($client, $address, $runId): void {
            try {
                printTransaction($client->createAddressActivationTransaction($address, "$runId-activate"));
            } catch (ApiException $e) {
                if ($e->getCode() !== TronZapException::ADDRESS_ALREADY_ACTIVATED) {
                    throw $e;
                }
                echo "  already activated\n";
            }
        });

        $step('createEnergyTransaction', static function () use ($client, $address, $runId): void {
            printTransaction($client->createEnergyTransaction($address, ENERGY, 1, "$runId-energy"));
            printTransaction($client->checkTransaction(null, "$runId-energy"));
        });

        $step('createBandwidthTransaction', static function () use ($client, $address, $runId): void {
            printTransaction($client->createBandwidthTransaction($address, BANDWIDTH, "$runId-bandwidth"));
        });

        $step('createResourceBundleTransaction', static function () use ($client, $address, $runId): void {
            printTransaction(
                $client->createResourceBundleTransaction($address, ENERGY, BANDWIDTH, 1, "$runId-bundle")
            );
        });

        $step('createAmlCheck', static function () use ($client, $address): void {
            $check = $client->createAmlCheck('address', 'TRX', $address);
            printf("  AML check %s is %s\n", field($check, 'id'), field($check, 'status'));
        });
    }

    if ($failed !== []) {
        fwrite(STDERR, "\nFailed: " . implode(', ', $failed) . "\n");
        return 1;
    }
    echo "\nAll calls succeeded\n";

    return 0;
}

exit(main());
