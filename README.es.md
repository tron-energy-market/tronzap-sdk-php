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

- PHP 7.4 o superior con las extensiones `curl` y `json`
- Certificados CA para cURL: el SDK verifica el certificado TLS de la API. Si PHP no tiene un paquete de CA configurado (habitual en Windows), define `curl.cainfo` en `php.ini` o pasa la ruta de un archivo CA como quinto argumento del constructor.

## Uso

```php
use TronZap\Client as TronZapClient;
use TronZap\Exception\TronZapException;

// Inicializar cliente
$apiToken = 'tu_api_token';
$apiSecret = 'tu_api_secret';
$client = new TronZapClient($apiToken, $apiSecret);
// Opcional: URL base, tiempo de espera en segundos (30 por defecto) y un archivo de certificados CA
// $client = new TronZapClient($apiToken, $apiSecret, 'https://api.tronzap.com', 30.0, '/ruta/a/cacert.pem');

try {
    // Obtener saldo de la cuenta
    $balance = $client->getBalance();
    print_r($balance);

    // Obtener información de la dirección (recursos y saldos)
    $addressInfo = $client->getAddressInfo('TRX_ADDRESS');
    print_r($addressInfo);

    // Servicios disponibles
    $services = $client->getServices();
    print_r($services);

    // Estimar cantidad de energía para transferencia USDT
    $estimate = $client->estimateEnergy('FROM_TRX_ADDRESS', 'TO_TRX_ADDRESS');
    print_r($estimate);

    // Crear transacción de energía
    $transaction = $client->createEnergyTransaction(
        'TRX_ADDRESS',       // dirección de billetera TRON
        $estimate['amount'], // cantidad de energía
        1,                   // duración (horas): una de las que devuelve getServices()
        'my-tx-id',          // ID externo (opcional)
        true                 // activar dirección (opcional)
    );
    print_r($transaction);

    // Comprar ancho de banda
    $bandwidth = $client->createBandwidthTransaction(
        'TRX_ADDRESS',   // dirección TRON
        345,             // cantidad de ancho de banda
        'bandwidth-1'    // ID externo (opcional)
    );
    print_r($bandwidth);

    // Comprar un paquete de recursos (energía + ancho de banda en una sola transacción)
    $bundle = $client->createResourceBundleTransaction(
        'TRX_ADDRESS',   // dirección TRON
        65000,           // cantidad de energía
        345,             // cantidad de ancho de banda
        1,               // duración (horas)
        'bundle-1',      // ID externo (opcional)
        true             // activar dirección (opcional)
    );
    print_r($bundle);

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
- `getAddressInfo(address)` - Obtiene recursos de la dirección (energy, bandwidth) y saldos (TRX, USDT)
- `createEnergyTransaction(address, energyAmount, duration, externalId, activateAddress)` - Crea una transacción para compra de energía
- `createBandwidthTransaction(address, amount, externalId)` - Crea una transacción para compra de ancho de banda
- `createResourceBundleTransaction(address, energyAmount, bandwidthAmount, duration, externalId, activateAddress)` - Crea una transacción que compra energía y ancho de banda en un solo paquete
- `createAddressActivationTransaction(address, externalId)` - Crea una transacción para activación de dirección
- `checkTransaction(transactionId)` - Consulta el estado de una transacción
- `getDirectRechargeInfo()` - Obtiene información sobre recargas directas
- `getAmlServices()` - Obtiene servicios AML y sus precios
- `createAmlCheck(type, network, address, hash, direction)` - Crea una nueva verificación AML
- `checkAmlStatus(id)` - Consulta el estado de una verificación AML
- `getAmlHistory(page, perPage, status)` - Obtiene historial de verificaciones AML
- `getSubscriptions()` - Obtiene planes de suscripción y precios, con el ID de suscripción como clave
- `startSubscription(subscriptionId, address, durationDays, transactionsLimit, externalId, activateAddress)` - Suscribe una dirección a un plan
- `checkSubscription(id, externalId)` - Consulta el estado de una suscripción, por id o id externo
- `stopSubscription(id, externalId)` - Detiene una suscripción
- `getSubscriptionHistory(page, perPage, status)` - Obtiene historial de suscripciones
- `bulkCalculate(items, duration)` - Valida y calcula hasta 10 000 filas de un pedido masivo de energía
- `bulkCreate(id, externalId, force, maxPriceIncrease, webhookUrl)` - Inicia un pedido masivo a partir de un cálculo
- `bulkCheck(id, externalId)` - Obtiene el estado y el progreso de un pedido masivo
- `bulkHistory(page, perPage)` - Obtiene los pedidos masivos, los más recientes primero
- `bulkReport(id, externalId, status)` - Descarga el informe por dirección de un pedido masivo en CSV
- `verifyWebhookSignature(body, signature)` - Verifica la cabecera `X-Signature` del webhook de un pedido masivo

En una verificación por hash (`createAmlCheck('hash', ...)`), `address` es la dirección del destinatario de la transacción, donde se recibieron los fondos, y `direction` indica en qué lado estás: `deposit` si los fondos llegaron a tu dirección (`address` es tu dirección), `withdrawal` si los enviaste tú (`address` es la dirección del destinatario externo). El riesgo se calcula para la contraparte: el remitente en un deposit, el destinatario en un withdrawal. Si omites `direction` en una verificación por hash, el SDK envía `deposit`.

## Suscripciones

Una suscripción mantiene una dirección abastecida de energía para cada transacción hasta que se detiene o se agotan sus días o transacciones. `getSubscriptions()` devuelve los planes con su ID de suscripción como clave, en el orden en que los lista la API. Pase esa clave, como `'unlimited_energy'`, a `startSubscription`, no el `id` numérico del plan. Iniciar una suscripción cobra el precio inicial del plan.

```php
foreach ($client->getSubscriptions() as $subscriptionId => $plan) {
    echo "$subscriptionId: initial {$plan['initial_price']}, {$plan['price']} per transaction\n";
}

$subscription = $client->startSubscription(
    'unlimited_energy', // Clave del plan de getSubscriptions()
    'TRX_ADDRESS',      // Dirección TRON
    30,                 // Duración en días, 0 para no limitar el tiempo
    0,                  // Límite de transacciones, 0 para no limitar
    'subscription-42',  // ID externo (opcional)
    false               // Activar dirección (opcional)
);

$subscription = $client->checkSubscription(null, 'subscription-42');

$subscription = $client->stopSubscription($subscription['id']);

$history = $client->getSubscriptionHistory(1, 10, 'active');
```

Iniciar, consultar y detener devuelven la suscripción con sus `params`; los elementos del historial traen en su lugar los contadores de uso `transactions_used`, `energy_used` y `total_price`. Una suscripción con límite de transacciones no se puede detener (`CANNOT_STOP_SUBSCRIPTION`).

## Pedidos masivos

Compra energía para hasta 10 000 direcciones en un solo pedido. `bulkCalculate` valida y calcula las filas y devuelve un cálculo válido durante 10 minutos; no se cobra nada. Las filas inválidas no hacen fallar la solicitud: se devuelven en `rejected` con su número de fila. `bulkCreate` inicia el pedido; cada dirección se cobra al procesarse, y las direcciones no activadas se omiten sin cobro.

Si el precio cambió desde el cálculo, `bulkCreate` no inicia el pedido y lanza `ApiException` con el código `BULK_PRICE_CHANGED`. El nuevo cálculo está en `getResult()`: confirma el nuevo total e inícialo con su `id`.

```php
use TronZap\Exception\ApiException;
use TronZap\Exception\TronZapException;

$items = [
    ['address' => 'TRX_ADDRESS_1', 'amount' => 65000],
    ['address' => 'TRX_ADDRESS_2', 'amount' => 131000],
];

try {
    $calculation = $client->bulkCalculate($items, 1);
} catch (ApiException $e) {
    if ($e->getCode() === TronZapException::BULK_NO_VALID_ITEMS) {
        print_r($e->getResult()['rejected'] ?? []); // Todas las filas fueron rechazadas
    }
    throw $e;
}

echo "Total: {$calculation['total_amount']} TRX, rechazadas: {$calculation['rejected_count']}\n";

try {
    $order = $client->bulkCreate($calculation['id'], 'order-42', false, null, 'https://example.com/tronzap/bulk');
} catch (ApiException $e) {
    if ($e->getCode() !== TronZapException::BULK_PRICE_CHANGED) {
        throw $e;
    }
    $calculation = $e->getResult(); // Nuevo cálculo: confirma el nuevo total e inícialo
    $order = $client->bulkCreate($calculation['id'], 'order-42');
}

$order = $client->bulkCheck(null, 'order-42'); // Consulta hasta completed, partial o failed

$csv = $client->bulkReport($order['id']); // row,address,amount,status,reason,charged,transaction_id
```

Con `webhookUrl`, la API envía un único `POST` cuando el pedido pasa a un estado final. Verifica su cabecera `X-Signature` con el cuerpo sin procesar antes de confiar en él:

```php
$body = file_get_contents('php://input');
if (!$client->verifyWebhookSignature($body, $_SERVER['HTTP_X_SIGNATURE'] ?? '')) {
    http_response_code(403);
    exit;
}
```

## Gestión de errores

El SDK utiliza una jerarquía de excepciones para un manejo preciso de errores:

```
TronZapException
├── ApiException             — errores a nivel de API (code != 0 en la respuesta), incluido HTTP 429 de la API
├── InvalidRequestException  — argumentos inválidos, rechazados antes de enviar
├── NetworkException         — errores de red/conectividad
│   ├── ConnectionException  — no se pudo conectar al servidor
│   ├── TimeoutException     — tiempo de espera agotado
│   └── SslException         — errores SSL/TLS
└── HttpException            — respuestas HTTP no 2xx
    ├── RateLimitException   — HTTP 429 sin respuesta de la API, p. ej. de un proxy
    ├── UnauthorizedException — HTTP 401/403
    └── ServerException      — errores HTTP 5xx
```

### Ejemplo

```php
use TronZap\Client as TronZapClient;
use TronZap\Exception\ApiException;
use TronZap\Exception\ConnectionException;
use TronZap\Exception\HttpException;
use TronZap\Exception\InvalidRequestException;
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

    // Indícalo al contactar con soporte
    echo "ID de la solicitud: {$e->getRequestId()}\n";

    // Datos que la API envió con el error, p. ej. el nuevo cálculo para BULK_PRICE_CHANGED
    $result = $e->getResult();

    if ($e->getCode() === TronZapException::RATE_LIMITED) {
        // Segundos de espera, si la API los envió
        sleep($e->getRetryAfter() ?? 60);
    }

    if ($e->getCode() === TronZapException::INVALID_TRON_ADDRESS) {
        echo "Revisa el formato de la dirección TRON.\n";
    }
} catch (InvalidRequestException $e) {
    echo "Argumentos inválidos: {$e->getMessage()}\n";
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

| Código | Constante                   | Descripción                                                              |
|--------|-----------------------------|--------------------------------------------------------------------------|
| 1      | `AUTH_ERROR`                | Error de autenticación — token API o firma inválidos                     |
| 2      | `INVALID_SERVICE_OR_PARAMS` | Servicio o parámetros inválidos                                          |
| 5      | `WALLET_NOT_FOUND`          | Billetera interna no encontrada. Contacta a soporte.                     |
| 6      | `INSUFFICIENT_FUNDS`        | Fondos insuficientes                                                     |
| 10     | `INVALID_TRON_ADDRESS`      | Dirección TRON inválida, o la dirección ya tiene una suscripción activa  |
| 11     | `INVALID_ENERGY_AMOUNT`     | Cantidad de energía inválida                                             |
| 12     | `INVALID_DURATION`          | Duración inválida                                                        |
| 20     | `TRANSACTION_NOT_FOUND`     | Transacción/suscripción no encontrada (alias: `TRANSACTION_NOT_FOUND`)   |
| 21     | `CANNOT_STOP_SUBSCRIPTION`  | No se puede detener la suscripción, p. ej. tiene límite de transacciones |
| 24     | `ADDRESS_NOT_ACTIVATED`     | Dirección no activada                                                    |
| 25     | `ADDRESS_ALREADY_ACTIVATED` | Dirección ya activada                                                    |
| 30     | `AML_CHECK_NOT_FOUND`       | Verificación AML no encontrada                                           |
| 35     | `SERVICE_NOT_AVAILABLE`     | Servicio no disponible                                                   |
| 50     | `INVALID_BANDWIDTH_AMOUNT`  | Cantidad de ancho de banda inválida                                      |
| 90   | `BULK_NOT_FOUND` | Pedido masivo no encontrado |
| 91   | `BULK_EXPIRED` | El cálculo expiró, calcula de nuevo |
| 92   | `BULK_PRICE_CHANGED` | El precio cambió; el nuevo cálculo está en `getResult()` |
| 93   | `BULK_TOO_MANY_ITEMS` | Más de 10 000 filas |
| 94   | `BULK_NO_VALID_ITEMS` | No hay filas válidas; están en `getResult()['rejected']` |
| 95   | `BULK_RESOURCE_NOT_SUPPORTED` | Solo se puede pedir energía de forma masiva |
| 96   | `PAYLOAD_TOO_LARGE` | Cuerpo de la solicitud demasiado grande (HTTP 413) |
| 429  | `RATE_LIMITED` | Demasiadas solicitudes; espera `getRetryAfter()` segundos si se indica |
| 500    | `INTERNAL_SERVER_ERROR`     | Error interno del servidor — contacta a soporte                          |

## Desarrollo

```bash
composer install
composer test
composer lint
```

Las pruebas se ejecutan contra un servidor HTTP/TLS local y nunca llaman a la API real. `examples/basic-usage.php` es una prueba de humo contra un entorno real; las instrucciones están al principio del archivo.

## Licencia

Licencia MIT. Más información en el [archivo de licencia](LICENSE).

## Soporte

Para soporte técnico contacta con [support@tronzap.com](mailto:support@tronzap.com).
