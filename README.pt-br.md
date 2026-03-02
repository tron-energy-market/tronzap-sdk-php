# Aluguel de Energia Tron via API
## SDK PHP por TronZap.com

[English](README.md) | [Español](README.es.md) | **[Português](README.pt-br.md)** | [Русский](README.ru.md)

SDK oficial em PHP para a API do TronZap.
Este SDK permite integrar facilmente os serviços TronZap para aluguel de energia TRON.

TronZap.com permite [comprar energia TRON](https://tronzap.com/), reduzindo significativamente as taxas nas transferências de USDT (TRC20).

👉 [Registre-se para obter uma chave API](https://tronzap.com) para começar a usar a API TronZap e integrá-la através do SDK.

## Instalação

Você pode instalar o pacote via composer:

```bash
composer require tron-energy-market/tronzap-sdk-php
```

## Requisitos

- PHP 7.4 ou superior

## Uso

```php
use TronZap\Client as TronZapClient;
use TronZap\Exception\TronZapException;

// Inicialização do cliente
$apiToken = 'seu_api_token';
$apiSecret = 'seu_api_secret';
$client = new TronZapClient($apiToken, $apiSecret);

try {
    // Obter saldo da conta
    $balance = $client->getBalance();
    print_r($balance);

    // Serviços disponíveis
    $services = $client->getServices();
    print_r($services);

    // Estimar quantidade de energia para transferência USDT
    $estimate = $client->estimateEnergy('FROM_TRX_ADDRESS', 'TO_TRX_ADDRESS', 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');
    print_r($estimate);

    // Criar transação de energia
    $transaction = $client->createEnergyTransaction(
        'TRX_ADDRESS',       // endereço da carteira TRON
        $estimate['energy'], // quantidade de energia
        1,                   // duração (horas), 1 ou 24
        'my-tx-id',          // ID externo (opcional)
        true                 // ativar endereço (opcional)
    );
    print_r($transaction);

    // Comprar banda larga (bandwidth)
    $bandwidth = $client->createBandwidthTransaction(
        'TRX_ADDRESS',   // endereço TRON
        1000,            // quantidade de bandwidth
        'bandwidth-1'    // ID externo (opcional)
    );
    print_r($bandwidth);

    // Verificar status da transação
    $status = $client->checkTransaction($transaction['id']);
    print_r($status);

    // Criar verificação AML para um endereço
    $amlCheck = $client->createAmlCheck(
        'address',
        'TRX',
        'TRX_ADDRESS'
    );
    print_r($amlCheck);

    // Consultar status AML
    $amlStatus = $client->checkAmlStatus($amlCheck['id']);
    print_r($amlStatus);
} catch (TronZapException $e) {
    echo "Erro: " . $e->getMessage() . " (Código: " . $e->getCode() . ")\n";
}
```

## Métodos disponíveis

- `getServices()` - Obter lista de serviços disponíveis e preços
- `getBalance()` - Obter saldo atual da conta
- `createEnergyTransaction(address, energyAmount, duration, externalId, activateAddress)` - Criar transação para compra de energia
- `createBandwidthTransaction(address, amount, externalId)` - Criar transação para compra de bandwidth
- `createAddressActivationTransaction(address, externalId)` - Criar transação para ativação de endereço
- `checkTransaction(transactionId)` - Verificar status da transação
- `getDirectRechargeInfo()` - Obter informações sobre recargas diretas
- `getAmlServices()` - Obter serviços AML e preços
- `createAmlCheck(type, network, address, hash, direction)` - Criar nova verificação AML
- `checkAmlStatus(id)` - Consultar status de verificação AML
- `getAmlHistory(page, perPage, status)` - Listar histórico de verificações AML

## Tratamento de erros

O SDK utiliza uma hierarquia de exceções para tratamento preciso de erros:

```
TronZapException
├── ApiException             — erros a nível de API (code != 0 na resposta)
├── NetworkException         — erros de rede/conectividade
│   ├── ConnectionException  — não foi possível conectar ao servidor
│   ├── TimeoutException     — tempo de espera esgotado
│   └── SslException         — erros SSL/TLS
└── HttpException            — respostas HTTP não 2xx
    ├── RateLimitException   — HTTP 429 Too Many Requests
    ├── UnauthorizedException — HTTP 401/403
    └── ServerException      — erros HTTP 5xx
```

### Exemplo

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

$client = new TronZapClient('seu_api_token', 'seu_api_secret');

try {
    $transaction = $client->createEnergyTransaction('TRX_ADDRESS', 65000, 1);
} catch (ApiException $e) {
    // Erro a nível de API (parâmetros inválidos, saldo insuficiente, etc.)
    echo "Erro API [{$e->getCode()}]: {$e->getMessage()}\n";

    // Chave alias do erro, ex. "invalid_tron_address" ou "invalid_tron_address.from_address"
    if ($e->getErrorKey()) {
        echo "Chave de erro: {$e->getErrorKey()}\n";
    }

    if ($e->getCode() === TronZapException::INVALID_TRON_ADDRESS) {
        echo "Verifique o formato do endereço TRON.\n";
    }
} catch (RateLimitException $e) {
    echo "Muitas requisições. Reduza a frequência.\n";
} catch (UnauthorizedException $e) {
    echo "Token API ou assinatura inválidos.\n";
} catch (ServerException $e) {
    echo "Erro do servidor TronZap [{$e->getStatusCode()}].\n";
} catch (HttpException $e) {
    echo "Erro HTTP [{$e->getStatusCode()}]: {$e->getMessage()}\n";
} catch (TimeoutException $e) {
    echo "Tempo de espera esgotado.\n";
} catch (SslException $e) {
    echo "Erro SSL: {$e->getMessage()}\n";
} catch (ConnectionException $e) {
    echo "Falha na conexão: {$e->getMessage()}\n";
} catch (NetworkException $e) {
    echo "Erro de rede: {$e->getMessage()}\n";
} catch (TronZapException $e) {
    echo "Erro [{$e->getCode()}]: {$e->getMessage()}\n";
}
```

### Códigos de erro da API

| Código | Constante                       | Descrição |
|--------|---------------------------------|-----------|
| 1      | `AUTH_ERROR`                    | Erro de autenticação — token API ou assinatura inválidos |
| 2      | `INVALID_SERVICE_OR_PARAMS`    | Serviço ou parâmetros inválidos |
| 5      | `WALLET_NOT_FOUND`             | Carteira interna não encontrada. Contate o suporte. |
| 6      | `INSUFFICIENT_FUNDS`           | Saldo insuficiente |
| 10     | `INVALID_TRON_ADDRESS`         | Endereço TRON inválido |
| 11     | `INVALID_ENERGY_AMOUNT`        | Quantidade de energia inválida |
| 12     | `INVALID_DURATION`             | Duração inválida |
| 20     | `TRANSACTION_NOT_FOUND`        | Transação não encontrada |
| 24     | `ADDRESS_NOT_ACTIVATED`        | Endereço não ativado |
| 25     | `ADDRESS_ALREADY_ACTIVATED`    | Endereço já ativado |
| 30     | `AML_CHECK_NOT_FOUND`          | Verificação AML não encontrada |
| 35     | `SERVICE_NOT_AVAILABLE`        | Serviço não disponível |
| 500    | `INTERNAL_SERVER_ERROR`        | Erro interno do servidor — contate o suporte |

## Testes

```bash
composer test
```

## Licença

Licença MIT. Para mais detalhes, veja o [arquivo de licença](LICENSE).

## Suporte

Para suporte técnico, entre em contato com [support@tronzap.com](mailto:support@tronzap.com).
