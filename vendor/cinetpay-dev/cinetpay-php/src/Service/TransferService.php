<?php

declare(strict_types=1);

namespace CinetPay\Service;

use CinetPay\Country;
use CinetPay\Http\AuthenticatedClient;
use CinetPay\Request\CreateTransferRequest;
use CinetPay\Response\Transfer;
use InvalidArgumentException;

final readonly class TransferService
{
    public function __construct(
        private AuthenticatedClient $client,
        private Country $country,
    ) {
    }

    public function create(CreateTransferRequest $request): Transfer
    {
        $this->country->assertCurrency($request->currency);
        $this->country->assertPaymentMethod($request->paymentMethod);
        $this->country->assertPhoneNumber($request->phoneNumber);

        return Transfer::fromArray(
            $this->client->request('POST', '/v1/transfer', $request->toArray()),
        );
    }

    public function find(string $transactionId): Transfer
    {
        if (trim($transactionId) === '') {
            throw new InvalidArgumentException('The transfer transaction ID cannot be empty.');
        }

        return Transfer::fromArray(
            $this->client->request('GET', '/v1/transfer/'.rawurlencode($transactionId)),
        );
    }
}
