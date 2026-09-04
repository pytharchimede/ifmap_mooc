<?php

declare(strict_types=1);

namespace CinetPay\Service;

use CinetPay\Exception\WebhookVerificationException;
use CinetPay\Webhook\ConfirmedPaymentNotification;
use CinetPay\Webhook\ConfirmedTransferNotification;
use CinetPay\Webhook\WebhookNotification;
use CinetPay\WebhookVerifier;

final readonly class WebhookService
{
    public function __construct(
        private PaymentService $payments,
        private TransferService $transfers,
    ) {
    }

    /** @param array<string, mixed>|string $payload */
    public function handlePayment(array|string $payload, string $expectedNotifyToken): ConfirmedPaymentNotification
    {
        $notification = $this->parse($payload);
        WebhookVerifier::assertNotifyToken($notification->notifyToken, $expectedNotifyToken);

        $payment = $this->payments->find($notification->merchantTransactionId);

        if (
            ! hash_equals($notification->merchantTransactionId, $payment->merchantTransactionId)
            || ! hash_equals($notification->transactionId, $payment->transactionId)
        ) {
            throw new WebhookVerificationException(
                'The canonical payment does not match the webhook transaction identifiers.',
            );
        }

        return new ConfirmedPaymentNotification($notification, $payment);
    }

    /** @param array<string, mixed>|string $payload */
    public function handleTransfer(array|string $payload, string $expectedNotifyToken): ConfirmedTransferNotification
    {
        $notification = $this->parse($payload);
        WebhookVerifier::assertNotifyToken($notification->notifyToken, $expectedNotifyToken);

        $transfer = $this->transfers->find($notification->transactionId);

        if (
            ! hash_equals($notification->merchantTransactionId, $transfer->merchantTransactionId)
            || ! hash_equals($notification->transactionId, $transfer->transactionId)
        ) {
            throw new WebhookVerificationException(
                'The canonical transfer does not match the webhook transaction identifiers.',
            );
        }

        return new ConfirmedTransferNotification($notification, $transfer);
    }

    /** @param array<string, mixed>|string $payload */
    public function parse(array|string $payload): WebhookNotification
    {
        return is_string($payload)
            ? WebhookNotification::fromJson($payload)
            : WebhookNotification::fromArray($payload);
    }
}
