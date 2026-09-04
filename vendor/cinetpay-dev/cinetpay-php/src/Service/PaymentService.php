<?php

declare(strict_types=1);

namespace CinetPay\Service;

use CinetPay\Country;
use CinetPay\Http\AuthenticatedClient;
use CinetPay\Request\CreatePaymentRequest;
use CinetPay\Response\PaymentInitialization;
use CinetPay\Response\PaymentStatus;
use InvalidArgumentException;

final readonly class PaymentService
{
    public function __construct(
        private AuthenticatedClient $client,
        private Country $country,
    ) {
    }

    public function create(CreatePaymentRequest $request): PaymentInitialization
    {
        $this->country->assertCurrency($request->currency);
        $this->country->assertPaymentMethod($request->paymentMethod);
        $this->country->assertPhoneNumber($request->clientPhoneNumber);

        return PaymentInitialization::fromArray(
            $this->client->request('POST', '/v1/payment', $request->toArray()),
        );
    }

    public function find(string $transactionId): PaymentStatus
    {
        if (trim($transactionId) === '') {
            throw new InvalidArgumentException('The payment transaction ID cannot be empty.');
        }

        return PaymentStatus::fromArray(
            $this->client->request('GET', '/v1/payment/'.rawurlencode($transactionId)),
        );
    }
}
