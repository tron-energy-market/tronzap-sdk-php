# Покупка энергии Tron черезе API
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

- PHP 7.4 или выше

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

    // Расчёт количества энергии для перевода USDT
    $estimate = $client->estimateEnergy('FROM_TRX_ADDRESS', 'TO_TRX_ADDRESS', 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');
    print_r($estimate);

    // Создание транзакции на энергию
    $transaction = $client->createEnergyTransaction(
        'TRX_ADDRESS',       // адрес кошелька TRON
        $estimate['energy'], // количество энергии
        1,                   // длительность делегирования (часы), 1 или 24
        'my-tx-id',          // внешний ID (опционально)
        true                 // активация адреса (опционально)
    );
    print_r($transaction);

    // Проверка статуса транзакции
    $status = $client->checkTransaction($transaction['id']);
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

- 1: Ошибка аутентификации: Проверьте API-токен и правильность подписи.
- 2: Некорректный сервис или параметры: Проверьте название сервиса и параметры.
- 5: Внутренний кошелек не найден: обратитесь в поддержку.
- 6: Недостаточно средств: Пополните баланс или уменьшите запрашиваемое количество энергии.
- 10: Некорректный адрес TRON: Проверьте формат адреса (34 символа).
- 11: Некорректное количество энергии.
- 12: Некорректная длительность. Возможные значения 1 или 24 часа.
- 20: Транзакция не найдена: Проверьте ID транзакции или внешний ID.
- 24: Адрес не активирован: Сначала активируйте адрес.
- 25: Адрес уже активирован.
- 500: Internal Server Error.

## Тестирование

```bash
composer test
```

## Лицензия

MIT. Подробности в [файле лицензии](LICENSE).

## Поддержка

По вопросам поддержки обращайтесь на [support@tronzap.com](mailto:support@tronzap.com).
