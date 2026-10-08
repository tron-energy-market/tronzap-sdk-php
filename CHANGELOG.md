# Changelog

All notable changes to this project are documented in this file. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- The README and `examples/basic-usage.php` read the API's `amount`, `min_amount` and `max_amount` fields instead of
  the deprecated `energy`, `min_energy` and `max_energy`.

### Fixed

- `examples/basic-usage.php` labels the energy `price` of `getServices()` as per 1000 units, not per unit. Energy and
  bandwidth are both priced per 1000 units: the cost is `price × amount / 1000`.
- The PHPDoc of `calculate` and `createEnergyTransaction` gave the duration as 1 or 24 hours; only 1 is supported.

## [1.5.0] - 2026-10-07

### Security

- The SDK verifies the API's TLS certificate and host name. Earlier versions turned verification off
  (`CURLOPT_SSL_VERIFYPEER` and `CURLOPT_SSL_VERIFYHOST`), so anyone able to intercept the connection could read the
  API token and the request signature. If PHP has no CA bundle configured, which is common on Windows, requests now
  fail with `SslException`: set `curl.cainfo` in `php.ini`, or pass the path of a CA file as the new `caBundle`
  argument.

### Added

- `timeout` argument of `Client`, 30 seconds by default. Requests used to wait for the API indefinitely.
- `caBundle` argument of `Client`: a PEM file with the certificate authorities to trust instead of the system ones.
- `InvalidRequestException`, thrown before any request is sent when a required argument is empty, such as an
  address, both `id` and `externalId` of `checkTransaction`, or the type, network or address of an AML check, and
  when the parameters cannot be encoded as JSON. It extends `TronZapException` and has code 0.
- `getRequestId()` and `getStatusCode()` on `ApiException`.
- `Client::VERSION`.

### Changed

- `calculate` sends the energy amount as the API's `amount` field instead of the deprecated `energy` field. The PHP
  argument is still called `$energy`.
- `estimateEnergy` leaves `contract_address` out of the request when it is empty or `null`, so the API estimates a
  USDT (TRC20) transfer, instead of sending `null`.
- A `duration`, `page` or `perPage` below 1 is sent as 1, 1 and 10.
- Requests without parameters send `{}` instead of `[]`.
- A trailing `/` of the base URL is ignored.
- `ext-curl` is declared in `composer.json`; the SDK has always needed it.

### Fixed

- An `externalId` of `"0"`, and an `id` of `"0"` in `checkTransaction`, were dropped from the request.
- A successful response whose `result` is not an object or a list, such as `"ok"` or `1`, caused a `TypeError`. It
  now throws `ServerException`.
- An `externalId` that is not valid UTF-8 made the SDK send an empty body. It now throws `InvalidRequestException`.

## [1.4.0] - 2026-05-05

### Added

- `createResourceBundleTransaction` buys energy and bandwidth in one transaction.
- Error codes `CANNOT_STOP_SUBSCRIPTION` (21), `SERVICE_NOT_AVAILABLE` (35) and `INVALID_BANDWIDTH_AMOUNT` (50).

### Changed

- `createEnergyTransaction` and `createBandwidthTransaction` send the amount as `params.amounts.energy` and
  `params.amounts.bandwidth` instead of `params.energy_amount` and `params.amount`.

### Fixed

- Network errors are classified by numeric libcurl error codes, so PHP builds that do not define every `CURLE_*`
  constant no longer fail with an undefined constant error.

## [1.3.0] - 2026-03-31

### Added

- `getAddressInfo` returns the resources and balances of an address.

## [1.2.1] - 2026-03-04

### Fixed

- API errors in responses with a non-2xx HTTP status are thrown as `ApiException` instead of an HTTP exception.

## [1.2.0] - 2026-03-02

### Added

- `ApiException::getErrorKey()` with the machine-readable error alias.
- A typed exception hierarchy for network, TLS, timeout, rate limit, authorization and server errors.

## [1.1.0] - 2025-12-11

### Added

- AML checks: `getAmlServices`, `createAmlCheck`, `checkAmlStatus` and `getAmlHistory`.
- `createBandwidthTransaction`.

