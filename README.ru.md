# PHP SDK для TronZap

Официальный PHP SDK для API TronZap.
Данный SDK позволяет легко интегрировать сервисы TronZap для аренды энергии TRON и управления кошельками.

TronZap.com позволяет [покупать энергию TRON](https://tronzap.com/), существенно снижая комиссии при переводах USDT (TRC20).

## Установка

Установите пакет через composer:

```bash
composer require tron-energy-market/tronzap-sdk-php
```

## Требования

- PHP 7.4 или выше
- JSON расширение

## Использование

```php
use TronZap\Client as TronZapClient;
use TronZap\Exception\TronZapException;

// Инициализация клиента
$apiToken = 'ваш_api_token';
$apiSecret = 'ваш_api_secret';
$client = new TronZapClient($apiToken, $apiSecret);

try {
    // Получение баланса аккаунта
    $balance = $client->getBalance();
    print_r($balance);

    // Доступные сервисы
    $services = $client->getServices();
    print_r($services);

    // Создание транзакции на энергию
    $transaction = $client->createEnergyTransaction(
        'TRX_ADDRESS', // адрес кошелька TRON
        32000,         // количество энергии
        1,             // длительность (часы)
        'my-tx-id',    // внешний ID (опционально)
        true          // активация адреса (опционально)
    );
    print_r($transaction);

    // Проверка статуса транзакции
    $status = $client->checkTransaction($transaction['transaction_id']);
    print_r($status);
} catch (TronZapException $e) {
    echo "Ошибка: " . $e->getMessage() . " (Код: " . $e->getCode() . ")\n";
}
```

## Доступные методы

- `getServices()` - Получение списка доступных сервисов и цен
- `getBalance()` - Получение текущего баланса аккаунта
- `createEnergyTransaction(address, energyAmount, duration, externalId, activateAddress)` - Создание транзакции на покупку энергии
- `createAddressActivationTransaction(address, externalId)` - Создание транзакции для активации адреса
- `checkTransaction(transactionId)` - Проверка статуса транзакции
- `getDirectRechargeInfo()` - Получение информации о прямом пополнении

## Обработка ошибок

SDK генерирует исключения `TronZapException` при ошибках API. Основные коды ошибок:

- 1: Ошибка аутентификации: Проверьте API-токен и правильность подписи
- 2: Некорректный сервис или параметры: Проверьте название сервиса и параметры
- 5: Кошелек не найден: Проверьте адрес кошелька или обратитесь в поддержку
- 6: Недостаточно средств: Пополните баланс или уменьшите запрашиваемое количество энергии
- 10: Некорректный адрес TRON: Проверьте формат адреса (34 символа)
- 11: Некорректное количество энергии
- 12: Некорректная длительность
- 20: Транзакция не найдена: Проверьте ID транзакции или внешний ID
- 24: Адрес не активирован: Сначала активируйте адрес
- 25: Адрес уже активирован

## Тестирование

```bash
composer test
```

## Лицензия

MIT. Подробности в [файле лицензии](LICENSE).

## Поддержка

По вопросам поддержки обращайтесь на [support@tronzap.com](mailto:support@tronzap.com).
