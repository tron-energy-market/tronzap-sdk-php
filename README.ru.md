# Покупка энергии Tron через API
## PHP SDK от TronZap.com

[English](README.md) | [Español](README.es.md) | [Português](README.pt-br.md) | **[Русский](README.ru.md)**

Официальный PHP SDK для API TronZap.
Данный SDK позволяет легко интегрировать сервисы TronZap для аренды энергии TRON.

TronZap.com позволяет [покупать энергию TRON](https://tronzap.com/), существенно снижая комиссии при переводах USDT (TRC20).

👉 [Зарегистрируйтесь для получения API ключа](https://tronzap.com), чтобы начать использовать TronZap API.

## Установка

Установите пакет через composer:

```bash
composer require tron-energy-market/tronzap-sdk-php
```

## Требования

- PHP 7.4 или выше с расширениями `curl` и `json`
- Корневые сертификаты CA для cURL: SDK проверяет TLS-сертификат API. Если в PHP не настроен CA bundle (часто на Windows), задайте `curl.cainfo` в `php.ini` или передайте путь к файлу CA пятым аргументом конструктора.

## Использование

```php
use TronZap\Client as TronZapClient;
use TronZap\Exception\TronZapException;

// Инициализация клиента
$apiToken = 'ваш_api_token';
$apiSecret = 'ваш_api_secret';
$client = new TronZapClient($apiToken, $apiSecret);
// Опционально: базовый URL, таймаут в секундах (по умолчанию 30) и файл с корневыми сертификатами CA
// $client = new TronZapClient($apiToken, $apiSecret, 'https://api.tronzap.com', 30.0, '/путь/к/cacert.pem');

try {
    // Получение баланса аккаунта
    $balance = $client->getBalance();
    print_r($balance);

    // Получение информации об адресе (ресурсы и балансы)
    $addressInfo = $client->getAddressInfo('TRX_ADDRESS');
    print_r($addressInfo);

    // Доступные сервисы
    $services = $client->getServices();
    print_r($services);

    // Расчёт количества энергии для перевода USDT
    $estimate = $client->estimateEnergy('FROM_TRX_ADDRESS', 'TO_TRX_ADDRESS');
    print_r($estimate);

    // Создание транзакции на энергию
    $transaction = $client->createEnergyTransaction(
        'TRX_ADDRESS',       // адрес кошелька TRON
        $estimate['amount'], // количество энергии
        1,                   // длительность (часы): одна из тех, что возвращает getServices()
        'my-tx-id',          // внешний ID (опционально)
        true                 // активация адреса (опционально)
    );
    print_r($transaction);

    // Покупка пропускной способности
    $bandwidth = $client->createBandwidthTransaction(
        'TRX_ADDRESS',   // адрес TRON
        345,             // объем bandwidth
        'bandwidth-1'    // внешний ID (опционально)
    );
    print_r($bandwidth);

    // Покупка пакета ресурсов (energy + bandwidth одной транзакцией)
    $bundle = $client->createResourceBundleTransaction(
        'TRX_ADDRESS',   // адрес TRON
        65000,           // количество energy
        345,             // количество bandwidth
        1,               // длительность (часы)
        'bundle-1',      // внешний ID (опционально)
        true             // активировать адрес (опционально)
    );
    print_r($bundle);

    // Проверка статуса транзакции
    $status = $client->checkTransaction($transaction['id']);
    print_r($status);

    // Создание AML-проверки адреса
    $amlCheck = $client->createAmlCheck(
        'address',
        'TRX',
        'TRX_ADDRESS'
    );
    print_r($amlCheck);

    // Проверка статуса AML
    $amlStatus = $client->checkAmlStatus($amlCheck['id']);
    print_r($amlStatus);
} catch (TronZapException $e) {
    echo "Ошибка: " . $e->getMessage() . " (Код: " . $e->getCode() . ")\n";
}
```

## Доступные методы

- `getServices()` - Получение списка доступных сервисов и цен
- `getBalance()` - Получение текущего баланса аккаунта
- `getAddressInfo(address)` - Получение ресурсов адреса (energy, bandwidth) и балансов (TRX, USDT)
- `createEnergyTransaction(address, energyAmount, duration, externalId, activateAddress)` - Создание транзакции на покупку энергии
- `createBandwidthTransaction(address, amount, externalId)` - Создание транзакции на покупку bandwidth
- `createResourceBundleTransaction(address, energyAmount, bandwidthAmount, duration, externalId, activateAddress)` - Создание транзакции, покупающей energy и bandwidth одним пакетом
- `createAddressActivationTransaction(address, externalId)` - Создание транзакции для активации адреса
- `checkTransaction(transactionId)` - Проверка статуса транзакции
- `getDirectRechargeInfo()` - Получение информации о прямом пополнении
- `getAmlServices()` - Получение доступных AML-сервисов и цен
- `createAmlCheck(type, network, address, hash, direction)` - Создание AML-проверки
- `checkAmlStatus(id)` - Получение статуса AML-проверки
- `getAmlHistory(page, perPage, status)` - История AML-проверок
- `getSubscriptions()` - Планы подписок и цены, ключ — ID подписки
- `startSubscription(subscriptionId, address, durationDays, transactionsLimit, externalId, activateAddress)` - Подписать адрес на план
- `checkSubscription(id, externalId)` - Статус подписки по id или внешнему id
- `stopSubscription(id, externalId)` - Остановить подписку
- `getSubscriptionHistory(page, perPage, status)` - История подписок

## Подписки

Подписка обеспечивает адрес энергией для каждой транзакции, пока её не остановят или не закончатся её дни или транзакции. `getSubscriptions()` возвращает планы с ID подписки в качестве ключа, в том порядке, в котором их перечисляет API. Передайте этот ключ, например `'unlimited_energy'`, в `startSubscription`, а не числовой `id` плана. При запуске подписки списывается начальная цена плана.

```php
foreach ($client->getSubscriptions() as $subscriptionId => $plan) {
    echo "$subscriptionId: initial {$plan['initial_price']}, {$plan['price']} per transaction\n";
}

$subscription = $client->startSubscription(
    'unlimited_energy', // Ключ плана из getSubscriptions()
    'TRX_ADDRESS',      // Адрес TRON
    30,                 // Длительность в днях, 0 — без ограничения по времени
    0,                  // Лимит транзакций, 0 — без ограничения
    'subscription-42',  // Внешний ID (необязательно)
    false               // Активировать адрес (необязательно)
);

$subscription = $client->checkSubscription(null, 'subscription-42');

$subscription = $client->stopSubscription($subscription['id']);

$history = $client->getSubscriptionHistory(1, 10, 'active');
```

Запуск, проверка и остановка возвращают подписку с её `params`, а элементы истории вместо них содержат счётчики использования `transactions_used`, `energy_used` и `total_price`. Подписку с лимитом транзакций остановить нельзя (`CANNOT_STOP_SUBSCRIPTION`).

## Обработка ошибок

SDK использует иерархию исключений для точной обработки ошибок:

```
TronZapException
├── ApiException             — ошибки API (code != 0 в ответе)
├── InvalidRequestException  — неверные аргументы, отклонены до отправки
├── NetworkException         — сетевые ошибки
│   ├── ConnectionException  — невозможно подключиться к серверу
│   ├── TimeoutException     — превышено время ожидания
│   └── SslException         — ошибки SSL/TLS
└── HttpException            — HTTP-ответы с кодом не 2xx
    ├── RateLimitException   — HTTP 429 Too Many Requests
    ├── UnauthorizedException — HTTP 401/403
    └── ServerException      — HTTP 5xx
```

### Пример

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

$client = new TronZapClient('ваш_api_token', 'ваш_api_secret');

try {
    $transaction = $client->createEnergyTransaction('TRX_ADDRESS', 65000, 1);
} catch (ApiException $e) {
    // Ошибка API (неверные параметры, недостаточно средств и т.д.)
    echo "Ошибка API [{$e->getCode()}]: {$e->getMessage()}\n";

    // Ключ-алиас ошибки, например "invalid_tron_address" или "invalid_tron_address.from_address"
    if ($e->getErrorKey()) {
        echo "Ключ ошибки: {$e->getErrorKey()}\n";
    }

    // Укажите его при обращении в поддержку
    echo "ID запроса: {$e->getRequestId()}\n";

    if ($e->getCode() === TronZapException::INVALID_TRON_ADDRESS) {
        echo "Проверьте формат адреса TRON.\n";
    }
} catch (InvalidRequestException $e) {
    echo "Неверные аргументы: {$e->getMessage()}\n";
} catch (RateLimitException $e) {
    echo "Слишком много запросов. Замедлите частоту обращений.\n";
} catch (UnauthorizedException $e) {
    echo "Неверный API-токен или подпись.\n";
} catch (ServerException $e) {
    echo "Ошибка сервера TronZap [{$e->getStatusCode()}].\n";
} catch (HttpException $e) {
    echo "HTTP-ошибка [{$e->getStatusCode()}]: {$e->getMessage()}\n";
} catch (TimeoutException $e) {
    echo "Превышено время ожидания запроса.\n";
} catch (SslException $e) {
    echo "Ошибка SSL: {$e->getMessage()}\n";
} catch (ConnectionException $e) {
    echo "Ошибка подключения: {$e->getMessage()}\n";
} catch (NetworkException $e) {
    echo "Сетевая ошибка: {$e->getMessage()}\n";
} catch (TronZapException $e) {
    echo "Ошибка [{$e->getCode()}]: {$e->getMessage()}\n";
}
```

### Коды ошибок API

| Код | Константа                   | Описание                                                              |
|-----|-----------------------------|-----------------------------------------------------------------------|
| 1   | `AUTH_ERROR`                | Ошибка аутентификации — неверный API-токен или подпись                |
| 2   | `INVALID_SERVICE_OR_PARAMS` | Некорректный сервис или параметры                                     |
| 5   | `WALLET_NOT_FOUND`          | Внутренний кошелёк не найден. Обратитесь в поддержку.                 |
| 6   | `INSUFFICIENT_FUNDS`        | Недостаточно средств                                                  |
| 10  | `INVALID_TRON_ADDRESS`      | Некорректный адрес TRON, или у адреса уже есть активная подписка      |
| 11  | `INVALID_ENERGY_AMOUNT`     | Некорректное количество энергии                                       |
| 12  | `INVALID_DURATION`          | Некорректная длительность                                             |
| 20  | `TRANSACTION_NOT_FOUND`     | Транзакция/подписка не найдена (алиас: `TRANSACTION_NOT_FOUND`)       |
| 21  | `CANNOT_STOP_SUBSCRIPTION`  | Невозможно остановить подписку, например, у неё есть лимит транзакций |
| 24  | `ADDRESS_NOT_ACTIVATED`     | Адрес не активирован                                                  |
| 25  | `ADDRESS_ALREADY_ACTIVATED` | Адрес уже активирован                                                 |
| 30  | `AML_CHECK_NOT_FOUND`       | AML-проверка не найдена                                               |
| 35  | `SERVICE_NOT_AVAILABLE`     | Сервис временно недоступен                                            |
| 50  | `INVALID_BANDWIDTH_AMOUNT`  | Некорректное количество bandwidth                                     |
| 500 | `INTERNAL_SERVER_ERROR`     | Внутренняя ошибка сервера — обратитесь в поддержку                    |

## Разработка

```bash
composer install
composer test
composer lint
```

Тесты работают с локальным HTTP/TLS-сервером и никогда не обращаются к реальному API. `examples/basic-usage.php` — smoke-тест против реального окружения; инструкция в начале файла.

## Лицензия

MIT. Подробности в [файле лицензии](LICENSE).

## Поддержка

По вопросам поддержки обращайтесь на [support@tronzap.com](mailto:support@tronzap.com).
