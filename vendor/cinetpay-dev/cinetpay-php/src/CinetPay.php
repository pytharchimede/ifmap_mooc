<?php

declare(strict_types=1);

namespace CinetPay;

use CinetPay\Auth\Authenticator;
use CinetPay\Auth\MemoryTokenStore;
use CinetPay\Auth\TokenStore;
use CinetPay\Http\AuthenticatedClient;
use CinetPay\Http\HttpTransport;
use CinetPay\Service\BalanceService;
use CinetPay\Service\PaymentService;
use CinetPay\Service\TransferService;
use CinetPay\Service\WebhookService;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;

final class CinetPay
{
    private readonly Authenticator $authenticator;

    private readonly PaymentService $payments;

    private readonly TransferService $transfers;

    private readonly BalanceService $balances;

    private readonly WebhookService $webhooks;

    public function __construct(
        Config $config,
        ?ClientInterface $httpClient = null,
        ?TokenStore $tokenStore = null,
    ) {
        $transport = new HttpTransport($config, $httpClient ?? new GuzzleClient());
        $this->authenticator = new Authenticator($config, $transport, $tokenStore ?? new MemoryTokenStore());
        $authenticatedClient = new AuthenticatedClient($transport, $this->authenticator);
        $this->payments = new PaymentService($authenticatedClient, $config->country);
        $this->transfers = new TransferService($authenticatedClient, $config->country);
        $this->balances = new BalanceService($authenticatedClient);
        $this->webhooks = new WebhookService($this->payments, $this->transfers);
    }

    public static function sandbox(
        string $apiKey,
        string $apiPassword,
        Country $country,
        ?ClientInterface $httpClient = null,
        ?TokenStore $tokenStore = null,
    ): self {
        return new self(
            new Config($apiKey, $apiPassword, $country, Environment::Sandbox),
            $httpClient,
            $tokenStore,
        );
    }

    public static function production(
        string $apiKey,
        string $apiPassword,
        Country $country,
        ?ClientInterface $httpClient = null,
        ?TokenStore $tokenStore = null,
    ): self {
        return new self(
            new Config($apiKey, $apiPassword, $country, Environment::Production),
            $httpClient,
            $tokenStore,
        );
    }

    public function payments(): PaymentService
    {
        return $this->payments;
    }

    public function transfers(): TransferService
    {
        return $this->transfers;
    }

    public function balances(): BalanceService
    {
        return $this->balances;
    }

    public function webhooks(): WebhookService
    {
        return $this->webhooks;
    }

    public function forgetAccessToken(): void
    {
        $this->authenticator->forget();
    }
}
