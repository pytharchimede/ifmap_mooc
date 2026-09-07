# CinetPay PHP SDK

SDK PHP officiel pour l'API REST CinetPay : paiements web, transferts et consultation du solde.

## Prérequis

- PHP 8.2 ou supérieur
- Extensions JSON et Mbstring
- Un couple `api_key` / `api_password` CinetPay dédié à chaque pays exploité

## Installation

```bash
composer require cinetpay-dev/cinetpay-php
```

## Démarrage rapide

```php
<?php

use CinetPay\CinetPay;
use CinetPay\Country;
use CinetPay\Currency;
use CinetPay\Language;
use CinetPay\Request\CreatePaymentRequest;

$cinetpay = CinetPay::sandbox(
    apiKey: $_ENV['CINETPAY_API_KEY'],
    apiPassword: $_ENV['CINETPAY_API_PASSWORD'],
    country: Country::IvoryCoast,
);

$payment = $cinetpay->payments()->create(new CreatePaymentRequest(
    currency: Currency::XOF,
    merchantTransactionId: 'ORDER-'.bin2hex(random_bytes(8)),
    amount: 500,
    successUrl: 'https://shop.example.com/payment/success',
    failedUrl: 'https://shop.example.com/payment/failed',
    notifyUrl: 'https://shop.example.com/webhooks/cinetpay',
    language: Language::French,
    designation: 'Commande #42',
    clientFirstName: 'Jane',
    clientLastName: 'Doe',
    clientEmail: 'jane@example.com',
));

header('Location: '.$payment->paymentUrl);
```

Le SDK obtient et réutilise automatiquement le Bearer token. Il le renouvelle une seule fois lorsque l'API retourne `EXPIRED_TOKEN` ou `INVALID_TOKEN`.

Pour la production, utilisez `CinetPay::production(...)`. Les URLs employées sont :

- Sandbox : `https://api.cinetpay.net`
- Production : `https://api.cinetpay.co`

## Comptes et pays

Les URL d'API sont communes à tous les pays. Le pays est déterminé par le compte marchand utilisé pour générer le Bearer token : **un compte = un pays**. Pour exploiter plusieurs pays, obtenez un couple `api_key` / `api_password` distinct pour chacun d'eux et créez une instance du SDK par compte.

```php
use CinetPay\CinetPay;
use CinetPay\Country;

$ivoryCoast = CinetPay::production(
    apiKey: $_ENV['CINETPAY_CI_API_KEY'],
    apiPassword: $_ENV['CINETPAY_CI_API_PASSWORD'],
    country: Country::IvoryCoast,
);

$senegal = CinetPay::production(
    apiKey: $_ENV['CINETPAY_SN_API_KEY'],
    apiPassword: $_ENV['CINETPAY_SN_API_PASSWORD'],
    country: Country::Senegal,
);
```

Le SDK valide localement les invariants du compte avant tout appel réseau :

- la devise doit correspondre au pays du compte ;
- une méthode explicite doit porter le suffixe pays attendu, par exemple `OM_CI`, `OM_SN` ou `MTN_CM` ;
- les numéros doivent être au format E.164 et utiliser l'indicatif du pays du compte.

| Pays | Valeur `Country` | Devise | Indicatif |
| --- | --- | --- | --- |
| Côte d'Ivoire | `Country::IvoryCoast` | XOF | +225 |
| Burkina Faso | `Country::BurkinaFaso` | XOF | +226 |
| Mali | `Country::Mali` | XOF | +223 |
| Sénégal | `Country::Senegal` | XOF | +221 |
| Togo | `Country::Togo` | XOF | +228 |
| Guinée | `Country::Guinea` | GNF | +224 |
| Cameroun | `Country::Cameroon` | XAF | +237 |
| Bénin | `Country::Benin` | XOF | +229 |
| RD Congo | `Country::CongoKinshasa` | CDF | +243 |
| Niger | `Country::Niger` | XOF | +227 |
| Tchad | `Country::Chad` | XAF | +235 |
| Congo | `Country::CongoBrazzaville` | XAF | +242 |
| République centrafricaine | `Country::CentralAfricanRepublic` | XAF | +236 |
| Gabon | `Country::Gabon` | XAF | +241 |
| Guinée équatoriale | `Country::EquatorialGuinea` | XAF | +240 |

La présence d'un pays dans l'énumération ne garantit pas qu'un opérateur soit déjà activé sur votre compte. La disponibilité des moyens de paiement et de transfert reste déterminée par la configuration CinetPay côté serveur.

## Vérifier un paiement

Le statut peut être recherché avec le `merchant_transaction_id` ou le `transaction_id` renvoyé par CinetPay.

```php
$payment = $cinetpay->payments()->find('ORDER-42');

if ($payment->isSuccessful()) {
    // Le paiement est confirmé par l'API CinetPay.
}
```

## Effectuer et vérifier un transfert

```php
use CinetPay\Request\CreateTransferRequest;

$transfer = $cinetpay->transfers()->create(new CreateTransferRequest(
    currency: Currency::XOF,
    merchantTransactionId: 'PAYOUT-'.bin2hex(random_bytes(8)),
    phoneNumber: '+2250707000001',
    amount: 500,
    paymentMethod: 'OM_CI',
    reason: 'Remboursement commande #42',
    notifyUrl: 'https://shop.example.com/webhooks/cinetpay-transfer',
));

$currentTransfer = $cinetpay->transfers()->find($transfer->transactionId);
```

## Consulter le solde

```php
$balance = $cinetpay->balances()->get();

echo $balance->availableBalance.' '.$balance->currency;
```

Les montants de réponse sont exposés sous forme de chaînes afin de ne pas perdre de précision lors des calculs monétaires.

## Webhooks : règle de sécurité

Un webhook est seulement un signal invitant votre backend à vérifier une transaction. Ne débloquez jamais une commande à partir du champ `status` reçu dans le webhook.

```php
$confirmed = $cinetpay->webhooks()->handlePayment(
    payload: $requestBody, // Tableau associatif ou corps JSON brut
    expectedNotifyToken: $notifyTokenStoredAtInitialization,
);

if ($confirmed->isSuccessful()) {
    // Le statut SUCCESS vient de l'API CinetPay, jamais du webhook.
}
```

`handlePayment()` effectue automatiquement les opérations suivantes :

1. Parse et valide `notify_token`, `merchant_transaction_id` et `transaction_id`.
2. Compare le `notify_token` avec `hash_equals()`.
3. Ignore tout statut fourni dans le webhook.
4. Interroge `GET /v1/payment/{merchant_transaction_id}`.
5. Vérifie que l'identifiant de la réponse canonique correspond à la notification.

Pour un transfert, le fonctionnement est identique mais la confirmation utilise le `transaction_id` CinetPay :

```php
$confirmed = $cinetpay->webhooks()->handleTransfer(
    payload: $requestBody,
    expectedNotifyToken: $notifyTokenStoredAtInitialization,
);

if ($confirmed->isFinal()) {
    // SUCCESS, FAILED ou autre statut final documenté.
}
```

La notification expose une clé d'idempotence fondée sur le `transaction_id` :

```php
$key = $confirmed->notification->deduplicationKey();
```

Répondez avec HTTP 200 en moins de 10 secondes. Dans une application web, exécutez `handlePayment()` ou `handleTransfer()` dans un job asynchrone, puis dédupliquez le traitement avec cette clé. Les propriétés `raw` et `reportedUser` représentent des données entrantes non fiables et ne doivent jamais servir à valider une transaction.

## Erreurs

```php
use CinetPay\Exception\ApiException;
use CinetPay\Exception\AuthenticationException;
use CinetPay\Exception\TransportException;
use CinetPay\Exception\ValidationException;

try {
    $balance = $cinetpay->balances()->get();
} catch (AuthenticationException $exception) {
    // Identifiants invalides ou jeton refusé après renouvellement.
} catch (ValidationException $exception) {
    // HTTP 422 : $exception->apiCode, apiStatus et response sont disponibles.
} catch (TransportException $exception) {
    // Timeout, DNS ou erreur réseau.
} catch (ApiException $exception) {
    // Autre erreur HTTP/API.
}
```

Les statuts métier retournés avec HTTP 200 (`SUCCESS`, `PENDING`, `FAILED`, `INSUFFICIENT_BALANCE`, etc.) ne lèvent pas d'exception. Ils restent accessibles sur l'objet de réponse.

## Cache de jeton personnalisé

Par défaut, le jeton est conservé en mémoire pendant la vie de l'instance. Une application persistante peut fournir sa propre implémentation de `CinetPay\Auth\TokenStore` au constructeur ou aux factories `sandbox` / `production`.

Chaque opération du `TokenStore` reçoit une clé de cache opaque calculée par le SDK. Elle isole automatiquement le jeton par clé API, pays, environnement et URL. Une implémentation persistante doit impérativement utiliser cette clé :

```php
interface TokenStore
{
    public function get(string $accountKey): ?AccessToken;
    public function put(string $accountKey, AccessToken $token): void;
    public function forget(string $accountKey): void;
}
```

## Tests

```bash
composer install
composer test
composer test:coverage # nécessite PCOV ou Xdebug, seuil obligatoire : 100 %
composer analyse       # PHPStan niveau maximal
```
