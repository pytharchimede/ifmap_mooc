<?php

declare(strict_types=1);

namespace CinetPay\Service;

use CinetPay\Http\AuthenticatedClient;
use CinetPay\Response\Balance;

final readonly class BalanceService
{
    public function __construct(private AuthenticatedClient $client)
    {
    }

    public function get(): Balance
    {
        return Balance::fromArray($this->client->request('GET', '/v1/balances'));
    }
}
