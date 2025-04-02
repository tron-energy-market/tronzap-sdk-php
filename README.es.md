# SDK PHP para TronZap

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
- php-json extensión

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

    // Crear transacción de energía
    $transaction = $client->createEnergyTransaction(
        'TRX_ADDRESS', // dirección de billetera TRON
        32000,         // cantidad de energía
        1,             // duración (horas)
        'my-tx-id',    // ID externo (opcional)
        true          // activar dirección (opcional)
    );
    print_r($transaction);

    // Consultar estado de transacción
    $status = $client->checkTransaction($transaction['transaction_id']);
    print_r($status);
} catch (TronZapException $e) {
    echo "Error: " . $e->getMessage() . " (Código: " . $e->getCode() . ")\n";
}
```

## Métodos disponibles

- `getServices()` - Obtiene lista de servicios disponibles y precios
- `getBalance()` - Obtiene saldo actual de la cuenta
- `createEnergyTransaction(address, energyAmount, duration, externalId, activateAddress)` - Crea una transacción para compra de energía
- `createAddressActivationTransaction(address, externalId)` - Crea una transacción para activación de dirección
- `checkTransaction(transactionId)` - Consulta el estado de una transacción
- `getDirectRechargeInfo()` - Obtiene información sobre recargas directas

## Gestión de errores

El SDK lanzará una excepción `TronZapException` en caso de errores de la API. Códigos comunes de error:

- 1: Error de autenticación: Revisa tu token API y firma
- 2: Servicio o parámetros inválidos: Revisa el nombre del servicio y parámetros
- 5: Billetera interna no encontrada: contacta soporte
- 6: Fondos insuficientes: Añade fondos o reduce la cantidad solicitada de energía
- 10: Dirección TRON inválida: Revisa el formato de dirección (34 caracteres)
- 11: Cantidad de energía inválida
- 12: Duración inválida
- 20: Transacción no encontrada: Verifica el ID de transacción o externo
- 24: Dirección no activada: Activa primero la dirección
- 25: Dirección ya activada
- 500: Internal Server Error

## Pruebas

```bash
composer test
```

## Licencia

Licencia MIT. Más información en el [archivo de licencia](LICENSE).

## Soporte

Para soporte técnico contacta con [support@tronzap.com](mailto:support@tronzap.com).
