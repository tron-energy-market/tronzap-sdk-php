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

O SDK lança uma exceção `TronZapException` em caso de erros da API. Códigos comuns de erros incluem:

- 1: Erro de autenticação: Verifique seu token API e assinatura
- 2: Serviço ou parâmetros inválidos: Verifique o nome do serviço e parâmetros
- 5: Carteira interna não encontrada: contate o suporte
- 6: Saldo insuficiente: Adicione fundos ou reduza a quantidade solicitada de energia
- 10: Endereço TRON inválido: Verifique o formato do endereço (34 caracteres)
- 11: Quantidade de energia inválida
- 12: Duração inválida. Valores possíveis 1 ou 24 horas.
- 20: Transação não encontrada: Verifique o ID da transação ou ID externo
- 24: Endereço não ativado: Ative primeiro o endereço
- 25: Endereço já ativado
- 30: Verificação AML não encontrada: Repita a verificação ou confirme o ID
- 35: Serviço não disponível: O serviço está temporariamente indisponível
- 500: Internal Server Error

## Testes

```bash
composer test
```

## Licença

Licença MIT. Para mais detalhes, veja o [arquivo de licença](LICENSE).

## Suporte

Para suporte técnico, entre em contato com [support@tronzap.com](mailto:support@tronzap.com).
