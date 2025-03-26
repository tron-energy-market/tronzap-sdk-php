# SDK PHP para TronZap

SDK oficial em PHP para a API do TronZap.
Este SDK permite integrar facilmente os serviços TronZap para aluguel de energia TRON e gerenciamento de carteiras.

TronZap.com permite comprar energia TRX, reduzindo significativamente as taxas nas transferências de USDT (TRC20).

## Instalação

Você pode instalar o pacote via composer:

```bash
composer require tron-energy-market/tronzap-sdk-php
```

## Requisitos

- PHP 7.4 ou superior
- Cliente HTTP Guzzle
- Extensão JSON

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

    // Criar transação de energia
    $transaction = $client->createEnergyTransaction(
        'TRX_ADDRESS', // endereço da carteira TRON
        32000,         // quantidade de energia
        1,             // duração (horas)
        'my-tx-id',    // ID externo (opcional)
        true          // ativar endereço (opcional)
    );
    print_r($transaction);

    // Verificar status da transação
    $status = $client->checkTransaction($transaction['transaction_id']);
    print_r($status);
} catch (TronZapException $e) {
    echo "Erro: " . $e->getMessage() . " (Código: " . $e->getCode() . ")\n";
}
```

## Métodos disponíveis

- `getServices()` - Obter lista de serviços disponíveis e preços
- `getBalance()` - Obter saldo atual da conta
- `createEnergyTransaction(address, energyAmount, duration, externalId, activateAddress)` - Criar transação para compra de energia
- `createAddressActivationTransaction(address, externalId)` - Criar transação para ativação de endereço
- `checkTransaction(transactionId)` - Verificar status da transação
- `getDirectRechargeInfo()` - Obter informações sobre recargas diretas

## Tratamento de erros

O SDK lança uma exceção `TronZapException` em caso de erros da API. Códigos comuns de erros incluem:

- 1: Erro de autenticação: Verifique seu token API e assinatura
- 2: Serviço ou parâmetros inválidos: Verifique o nome do serviço e parâmetros
- 5: Carteira não encontrada: Verifique o endereço ou contate o suporte
- 6: Saldo insuficiente: Adicione fundos ou reduza a quantidade solicitada de energia
- 10: Endereço TRON inválido: Verifique o formato do endereço (34 caracteres)
- 11: Quantidade de energia inválida
- 12: Duração inválida
- 20: Transação não encontrada: Verifique o ID da transação ou ID externo
- 24: Endereço não ativado: Ative primeiro o endereço
- 25: Endereço já ativado

## Testes

```bash
composer test
```

## Licença

Licença MIT. Para mais detalhes, veja o [arquivo de licença](LICENSE).

## Suporte

Para suporte técnico, entre em contato com [support@tronzap.com](mailto:support@tronzap.com).