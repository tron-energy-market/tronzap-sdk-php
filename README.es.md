# Alquiler de Energía Tron vía API
## SDK PHP por TronZap.com

[English](README.md) | **[Español](README.es.md)** | [Português](README.pt-br.md) | [Русский](README.ru.md)

SDK oficial en PHP para la API de TronZap.
Este SDK permite integrar fácilmente los servicios de TronZap para alquilar energía TRON.

TronZap.com permite [comprar energía TRON](https://tronzap.com/), reduciendo significativamente las comisiones en transferencias de USDT (TRC20).

👉 [Regístrate para obtener una clave API](https://tronzap.com) para comenzar a usar la API de TronZap e integrarla a través del SDK.

## Instalación

Puedes instalar el paquete mediante composer:

```bash
composer require tron-energy-market/tronzap-sdk-php
```

## Requisitos

- PHP 7.4 o superior

## Uso

```php
use TronZap\Client as TronZapClient;
use TronZap\Exception\TronZapException;

// Inicializar cliente
$apiToken = 'tu_api_token';
$apiSecret = 'tu_api_secret';
$client = new TronZapClient($apiToken, $apiSecret);

try {
    // Obtener saldo de la cuenta
    $balance = $client->getBalance();
    print_r($balance);

    // Servicios disponibles
    $services = $client->getServices();
    print_r($services);

    // Estimar cantidad de energía para transferencia USDT
    $estimate = $client->estimateEnergy('FROM_TRX_ADDRESS', 'TO_TRX_ADDRESS', 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');
    print_r($estimate);

    // Crear transacción de energía
    $transaction = $client->createEnergyTransaction(
        'TRX_ADDRESS',       // dirección de billetera TRON
        $estimate['energy'], // cantidad de energía
        1,                   // duración (horas), 1 o 24
        'my-tx-id',          // ID externo (opcional)
        true                 // activar dirección (opcional)
    );
    print_r($transaction);

    // Comprar ancho de banda
    $bandwidth = $client->createBandwidthTransaction(
        'TRX_ADDRESS',   // dirección TRON
        1000,            // cantidad de ancho de banda
        'bandwidth-1'    // ID externo (opcional)
    );
    print_r($bandwidth);

    // Consultar estado de transacción
    $status = $client->checkTransaction($transaction['id']);
    print_r($status);

    // Crear verificación AML para una dirección
    $amlCheck = $client->createAmlCheck(
        'address',
        'TRX',
        'TRX_ADDRESS'
    );
    print_r($amlCheck);

    // Consultar estado AML
    $amlStatus = $client->checkAmlStatus($amlCheck['id']);
    print_r($amlStatus);
} catch (TronZapException $e) {
    echo "Error: " . $e->getMessage() . " (Código: " . $e->getCode() . ")\n";
}
```

## Métodos disponibles

- `getServices()` - Obtiene lista de servicios disponibles y precios
- `getBalance()` - Obtiene saldo actual de la cuenta
- `createEnergyTransaction(address, energyAmount, duration, externalId, activateAddress)` - Crea una transacción para compra de energía
- `createBandwidthTransaction(address, amount, externalId)` - Crea una transacción para compra de ancho de banda
- `createAddressActivationTransaction(address, externalId)` - Crea una transacción para activación de dirección
- `checkTransaction(transactionId)` - Consulta el estado de una transacción
- `getDirectRechargeInfo()` - Obtiene información sobre recargas directas
- `getAmlServices()` - Obtiene servicios AML y sus precios
- `createAmlCheck(type, network, address, hash, direction)` - Crea una nueva verificación AML
- `checkAmlStatus(id)` - Consulta el estado de una verificación AML
- `getAmlHistory(page, perPage, status)` - Obtiene historial de verificaciones AML

## Gestión de errores

El SDK utiliza una jerarquía de excepciones para un manejo preciso de errores:

```
TronZapException
├── ApiException             — errores a nivel de API (code != 0 en la respuesta)
├── NetworkException         — errores de red/conectividad
│   ├── ConnectionException  — no se pudo conectar al servidor
│   ├── TimeoutException     — tiempo de espera agotado
│   └── SslException         — errores SSL/TLS
└── HttpException            — respuestas HTTP no 2xx
    ├── RateLimitException   — HTTP 429 Too Many Requests
    ├── UnauthorizedException — HTTP 401/403
    └── ServerException      — errores HTTP 5xx
```

### Ejemplo

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

$client = new TronZapClient('tu_api_token', 'tu_api_secret');

try {
    $transaction = $client->createEnergyTransaction('TRX_ADDRESS', 65000, 1);
} catch (ApiException $e) {
    // Error a nivel de API (parámetros inválidos, fondos insuficientes, etc.)
    echo "Error API [{$e->getCode()}]: {$e->getMessage()}\n";

    // Clave alias del error, p.ej. "invalid_tron_address" o "invalid_tron_address.from_address"
    if ($e->getErrorKey()) {
        echo "Clave de error: {$e->getErrorKey()}\n";
    }

    if ($e->getCode() === TronZapException::INVALID_TRON_ADDRESS) {
        echo "Revisa el formato de la dirección TRON.\n";
    }
} catch (RateLimitException $e) {
    echo "Demasiadas solicitudes. Reduce la frecuencia.\n";
} catch (UnauthorizedException $e) {
    echo "Token API o firma inválidos.\n";
} catch (ServerException $e) {
    echo "Error del servidor TronZap [{$e->getStatusCode()}].\n";
} catch (HttpException $e) {
    echo "Error HTTP [{$e->getStatusCode()}]: {$e->getMessage()}\n";
} catch (TimeoutException $e) {
    echo "Tiempo de espera agotado.\n";
} catch (SslException $e) {
    echo "Error SSL: {$e->getMessage()}\n";
} catch (ConnectionException $e) {
    echo "Error de conexión: {$e->getMessage()}\n";
} catch (NetworkException $e) {
    echo "Error de red: {$e->getMessage()}\n";
} catch (TronZapException $e) {
    echo "Error [{$e->getCode()}]: {$e->getMessage()}\n";
}
```

### Códigos de error de la API

| Código | Constante                       | Descripción |
|--------|---------------------------------|-------------|
| 1      | `AUTH_ERROR`                    | Error de autenticación — token API o firma inválidos |
| 2      | `INVALID_SERVICE_OR_PARAMS`    | Servicio o parámetros inválidos |
| 5      | `WALLET_NOT_FOUND`             | Billetera interna no encontrada. Contacta a soporte. |
| 6      | `INSUFFICIENT_FUNDS`           | Fondos insuficientes |
| 10     | `INVALID_TRON_ADDRESS`         | Dirección TRON inválida |
| 11     | `INVALID_ENERGY_AMOUNT`        | Cantidad de energía inválida |
| 12     | `INVALID_DURATION`             | Duración inválida |
| 20     | `TRANSACTION_NOT_FOUND`        | Transacción no encontrada |
| 24     | `ADDRESS_NOT_ACTIVATED`        | Dirección no activada |
| 25     | `ADDRESS_ALREADY_ACTIVATED`    | Dirección ya activada |
| 30     | `AML_CHECK_NOT_FOUND`          | Verificación AML no encontrada |
| 35     | `SERVICE_NOT_AVAILABLE`        | Servicio no disponible |
| 500    | `INTERNAL_SERVER_ERROR`        | Error interno del servidor — contacta a soporte |

## Pruebas

```bash
composer test
```

## Licencia

Licencia MIT. Más información en el [archivo de licencia](LICENSE).

## Soporte

Para soporte técnico contacta con [support@tronzap.com](mailto:support@tronzap.com).
